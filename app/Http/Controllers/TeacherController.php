<?php

namespace App\Http\Controllers;

use App\Repositories\Staff\StaffInterface;
use App\Repositories\Subscription\SubscriptionInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;
use TypeError;

class TeacherController extends Controller {
    private UserInterface $user;
    private StaffInterface $staff;
    private SubscriptionInterface $subscription;
    private CachingService $cache;

    public function __construct(StaffInterface $staff, UserInterface $user, SubscriptionInterface $subscription, CachingService $cache) {
        $this->user = $user;
        $this->staff = $staff;
        $this->subscription = $subscription;
        $this->cache = $cache;
    }

    public function index() {
        ResponseService::noPermissionThenRedirect('teacher-list');
        return view('teacher.index');
    }

    public function store(Request $request) {
        ResponseService::noAnyPermissionThenSendJson(['teacher-create', 'teacher-edit']);
        $request->validate([
            'first_name'        => 'required',
            'last_name'         => 'required',
            'gender'            => 'required',
            'email'             => 'required|email|unique:users,email',
            'mobile'            => 'required|numeric|digits_between:1,16',
            'dob'               => 'required|date|unique:users,email',
            'qualification'     => 'required',
            'current_address'   => 'required',
            'permanent_address' => 'required',
            'status'            => 'nullable|in:0,1',
        ]);
        try {
            DB::beginTransaction();

            // Check free trial package
            $today_date = Carbon::now()->format('Y-m-d');
            $subscription = $this->subscription->builder()->doesntHave('subscription_bill')->whereDate('start_date','<=',$today_date)->where('end_date','>=',$today_date)->whereHas('package',function($q){
                $q->where('is_trial',1);
            })->first();
            
            if ($subscription) {
                $systemSettings = $this->cache->getSystemSettings();
                $staff = $this->user->builder()->role('Teacher')->withTrashed()->orWhereHas('roles', function ($q) {
                    $q->where('custom_role', 1)->whereNotIn('name', ['Teacher','Guardian']);
                })->whereNotNull('school_id')->Owner()->count();
                if ($staff >= $systemSettings['staff_limit']) {
                    $message = "The free trial allows only ".$systemSettings['staff_limit']." staff.";
                    ResponseService::errorResponse($message);
                }
            }


            $teacher_plain_text_password = str_replace('-', '', date('d-m-Y', strtotime($request->dob)));

            $user_data = array(
                ...$request->all(),
                'password'          => Hash::make($teacher_plain_text_password),
                'image'             => $request->file('image'),
                'status'            => $request->status ?? 0,
                'deleted_at'        => $request->status == 1 ? null : '1970-01-01 01:00:00'
            );

            //Call store function of User Repository and get the User Data
            $user = $this->user->create($user_data);

            $user->assignRole('Teacher');

            $this->staff->create([
                'user_id'       => $user->id,
                'qualification' => $request->qualification,
                'salary'        => $request->salary
            ]);
            DB::commit();
            $school_name = Auth::user()->school->name;
            $data = [
                'subject'     => 'Welcome to ' . $school_name,
                'name'        => $request->first_name,
                'email'       => $request->email,
                'password'    => $teacher_plain_text_password,
                'school_name' => $school_name
            ];

            Mail::send('teacher.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), ['Failed', 'Mail', 'Mailer', 'MailManager'])) {
                ResponseService::warningResponse("Teacher Registered successfully. But Email not sent.");
            } else {
                DB::rollback();
                ResponseService::logErrorResponse($e, "Teacher Controller -> Store method");
                ResponseService::errorResponse();
            }
        }
    }

    public function show() {
        ResponseService::noPermissionThenRedirect('teacher-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $showDeleted = request('show_deactive');
        $sql = $this->user->builder()->role('Teacher')->with('staff')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('first_name', 'LIKE', "%$search%")
                    ->orwhere('last_name', 'LIKE', "%$search%")
                    ->orwhere('gender', 'LIKE', "%$search%")
                    ->orwhere('email', 'LIKE', "%$search%")
                    ->orwhere('dob', 'LIKE', "%" . date('Y-m-d', strtotime($search)) . "%")
                    ->orwhere('current_address', 'LIKE', "%$search%")
                    ->orwhere('permanent_address', 'LIKE', "%$search%")
                    ->whereHas('staff', function ($q) use ($search) {
                        $q->orwhere('staffs.qualification', 'LIKE', "%$search%");
                    });

                });
            })
            ->when(!empty($showDeleted), function ($query) {
                $query->where('status',0)->onlyTrashed();
            });
        $total = $sql->count();
        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            if ($showDeleted) {
                //Show Restore and Hard Delete Buttons
                // $operate = BootstrapTableService::button('fa fa-calendar', route('timetable.teacher.show', $row->id), ['btn-gradient-success'], ['title' => "View Timetable"]);
                $operate = BootstrapTableService::button('fa fa-check', route('teachers.change-status', $row->id), ['activate-teacher', 'btn-gradient-success'], ['title' => __('active')]);
                $operate .= BootstrapTableService::trashButton(route('teachers.trash', $row->id));
            } else {
                //Show Edit and Soft Delete Buttons
                $operate = BootstrapTableService::editButton(route('teachers.update', $row->id));
                $operate .= BootstrapTableService::button('fa fa-calendar', route('timetable.teacher.show', $row->id), ['btn-gradient-success'], ['title' => "View Timetable"]);
                $operate .= BootstrapTableService::button('fa fa-exclamation-triangle', route('teachers.change-status', $row->id), ['deactivate-teacher', 'btn-gradient-info'], ['title' => __('inactive')]);
                $operate .= BootstrapTableService::trashButton(route('teachers.trash', $row->id));
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            // $tempRow['dob'] = format_date($row->dob);
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }


    public function edit($id) {
        $teacher = $this->staff->findById($id);
        return response($teacher);
    }


    public function update(Request $request, $id) {
        // ResponseService::noFeatureThenSendJson('Teacher Management');
        ResponseService::noPermissionThenSendJson('teacher-edit');
        $validator = Validator::make($request->all(), [
            'first_name'        => 'required',
            'last_name'         => 'required',
            'gender'            => 'required',
            'email'             => 'required|email|unique:users,email,' . $id,
            'mobile'            => 'required|numeric|digits_between:1,16',
            'dob'               => 'required|date',
            'qualification'     => 'required',
            'current_address'   => 'required',
            'permanent_address' => 'required',
        ]);
        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }
        try {
            DB::beginTransaction();
            $user_data = array(
                ...$request->all(),
            );
            if ($request->file('image')) {
                $user_data['image'] = $request->file('image');
            }

            //Call store function of User Repository and get the User Data
            $user = $this->user->update($id, $user_data);

            //Call store function of User Repository and get the User Data
            $this->staff->update($user->staff->id, array('qualification' => $request->qualification, 'salary' => $request->salary));

            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            if ($e instanceof TypeError && Str::contains($e->getMessage(), ['Mail', 'Mailer', 'MailManager'])) {
                ResponseService::warningResponse("Teacher Registered successfully. But Email not sent.");
            } else {
                DB::rollBack();
                ResponseService::logErrorResponse($e, "Teacher Controller -> Update method");
                ResponseService::errorResponse();
            }
        }
    }


    public function trash($id) {
        ResponseService::noPermissionThenSendJson('teacher-delete');
        try {
            DB::beginTransaction();
            $this->user->findTrashedById($id)->forceDelete();
            DB::commit();
            ResponseService::successResponse("Data Deleted Permanently");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Teacher Controller ->trash Method", 'cannot_delete_because_data_is_associated_with_other_data');
            ResponseService::errorResponse();
        }
    }

    public function changeStatus($id) {
        // ResponseService::noFeatureThenSendJson('Teacher Management');
        ResponseService::noPermissionThenRedirect('teacher-delete');
        try {
            DB::beginTransaction();
            $teacher = $this->user->findTrashedById($id);
            $this->user->builder()->where('id',$id)->withTrashed()->update(['status' => $teacher->status == 0 ? 1 : 0,'deleted_at' => $teacher->status == 1 ? now() : null]);
            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, 'Status methods -> Teacher controller');
            ResponseService::errorResponse();
        }
    }
    public function changeStatusBulk(Request $request){
        // ResponseService::noFeatureThenSendJson('Teacher Management');
        ResponseService::noPermissionThenRedirect('teacher-delete');
        try {
            DB::beginTransaction();
            $userIds = json_decode($request->ids);
            foreach ($userIds as $userId) {
                $teacher = $this->user->findTrashedById($userId);
                $this->user->builder()->where('id',$userId)->withTrashed()->update(['status' => $teacher->status == 0 ? 1 : 0,'deleted_at' => $teacher->status == 1 ? now() : null]);
            }
            DB::commit();
            ResponseService::successResponse("Status Updated Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }
}
