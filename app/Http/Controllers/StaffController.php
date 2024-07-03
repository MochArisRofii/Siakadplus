<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Repositories\School\SchoolInterface;
use App\Repositories\Staff\StaffInterface;
use App\Repositories\StaffSupportSchool\StaffSupportSchoolInterface;
use App\Repositories\Subscription\SubscriptionInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\FeaturesService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

class StaffController extends Controller {

    private UserInterface $user;
    private StaffInterface $staff;
    private SchoolInterface $school;
    private StaffSupportSchoolInterface $staffSupportSchool;
    private FeaturesService $features;
    private SubscriptionInterface $subscription;
    private CachingService $cache;


    public function __construct(UserInterface $user, StaffInterface $staff, SchoolInterface $school, StaffSupportSchoolInterface $staffSupportSchool, FeaturesService $features, SubscriptionInterface $subscription, CachingService $cache) {
        $this->user = $user;
        $this->staff = $staff;
        $this->school = $school;
        $this->staffSupportSchool = $staffSupportSchool;
        $this->features = $features;
        $this->subscription = $subscription;
        $this->cache = $cache;
    }

    public function index() {
        ResponseService::noFeatureThenRedirect('Staff Management');
        ResponseService::noPermissionThenRedirect('staff-list');
        $roles = Role::where('custom_role', 1)->get();
        $schools = array();
        if (!Auth::user()->school_id) {
            $schools = $this->school->active()->pluck('name', 'id');
        }
        $features = $this->features->getFeatures();
        // $features = array();
        return response(view('staff.index', compact('roles', 'schools', 'features')));
    }

    public function store(Request $request) {
        ResponseService::noFeatureThenRedirect('Staff Management');
        ResponseService::noPermissionThenRedirect('staff-create');

        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required',
                'last_name'  => 'required',
                'mobile'     => 'required|digits_between:1,16',
                'email'      => 'required|unique:users,email',
                'role_id'    => 'required|numeric',
                'status'     => 'nullable|in:0,1',
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            DB::beginTransaction();

            // Check free trial package
            if (Auth::user()->school_id) {
                $today_date = Carbon::now()->format('Y-m-d');
                $subscription = $this->subscription->builder()->doesntHave('subscription_bill')->whereDate('start_date', '<=', $today_date)->where('end_date', '>=', $today_date)->whereHas('package', function ($q) {
                    $q->where('is_trial', 1);
                })->first();

                if ($subscription) {
                    $systemSettings = $this->cache->getSystemSettings();
                    $staff = $this->user->builder()->role('Teacher')->withTrashed()->orWhereHas('roles', function ($q) {
                        $q->where('custom_role', 1)->whereNot('name', 'Teacher');
                    })->whereNotNull('school_id')->Owner()->count();
                    if ($staff >= $systemSettings['staff_limit']) {
                        $message = "The free trial allows only " . $systemSettings['staff_limit'] . " staff.";
                        ResponseService::errorResponse($message);
                    }
                }
            }

            $role = Role::findOrFail($request->role_id);

            /*If Super admin creates the staff then make it active by default*/
            if (!empty(Auth::user()->school_id)) {
                $data = array(
                    ...$request->except('school_id'),
                    'password'   => Hash::make($request->mobile),
                    'image'      => $request->file('image'),
                    'status'     => $request->status ?? 0,
                    'deleted_at' => $request->status == 1 ? null : '1970-01-01 01:00:00'
                );
            } else {
                /*If School Admin creates the Staff then active/inactive staff based on status*/
                $data = array(
                    ...$request->except('school_id'),
                    'password' => Hash::make($request->mobile),
                    'image'    => $request->file('image'),
                    'status'   => 1,
                );
            }


            $user = $this->user->create($data);
            $user->assignRole($role);

            if ($user->school_id) {
                $leave_permission = [
                    'leave-list',
                    'leave-create',
                    'leave-edit',
                    'leave-delete',
                ];
                $user->givePermissionTo($leave_permission);
            }

            $this->staff->create([
                'user_id'       => $user->id,
                'qualification' => null,
                'salary'        => $request->salary ?? 0
            ]);


            if ($request->school_id) {
                $data = array();
                foreach ($request->school_id as $school) {
                    $data[] = [
                        'user_id'   => $user->id,
                        'school_id' => $school
                    ];
                }
                $this->staffSupportSchool->upsert($data, ['user_id', 'school_id'], ['user_id', 'school_id']);
            }


            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');

        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function show(Request $request) {
        ResponseService::noFeatureThenRedirect('Staff Management');
        ResponseService::noPermissionThenRedirect('staff-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');

        $sql = $this->user->builder()->whereHas('roles', function ($q) {
            $q->where('custom_role', 1)->whereNot('name', 'Teacher');
        })->with('staff', 'roles', 'support_school.school');

//        if (!empty(Auth::user()->school_id)) {
//            //Code For School Panel
//            $sql->whereHas('roles', function ($q) {
//                $q->where('custom_role', 1)->whereNot('name', 'Teacher');
//            });
//        } else {
//            //Code For Super Admin side Panel
//        }

        if (!empty($request->show_deactive)) {
            $sql = $sql->where('status', 0)->onlyTrashed();
        }
        if (!empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orwhere('first_name', 'LIKE', "%$search%")
                    ->orwhere('last_name', 'LIKE', "%$search%")
                    ->orwhere('email', 'LIKE', "%$search%")
                    ->orwhere('mobile', 'LIKE', "%$search%");
            })->Owner();
        }

        $total = $sql->count();

        $sql = $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            if ($request->show_deactive) {
                //Show Restore and Hard Delete Buttons
                $operate = BootstrapTableService::button('fa fa-check', route('staff.restore', $row->id), ['activate-staff', 'btn-gradient-success'], ['title' => __('active')]);
                $operate .= BootstrapTableService::trashButton(route('staff.trash', $row->id));
            } else {
                //Show Edit and Soft Delete Buttons
                $operate = BootstrapTableService::editButton(route('staff.update', $row->id));
                $operate .= BootstrapTableService::button('fa fa-exclamation-triangle', route('staff.destroy', $row->id), ['deactivate-staff', 'btn-gradient-info'], ['title' => __('inactive')]);
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['support_school_id'] = $row->support_school->pluck('school_id');
            $tempRow['operate'] = $operate;
            $tempRow['roles_name'] = $row->roles->pluck('name');
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function update(Request $request, $id) {
        ResponseService::noFeatureThenRedirect('Staff Management');
        ResponseService::noPermissionThenSendJson('staff-edit');
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required',
                'last_name'  => 'required',
                'mobile'     => 'required|digits_between:1,16',
                'email'      => 'required|unique:users,email,' . $id,
                'role_id'    => 'required|numeric'
            ]);
            if ($validator->fails()) {
                ResponseService::validationError($validator->errors()->first());
            }
            DB::beginTransaction();
            $data = $request->except('school_id');
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image');
            }

            $user = $this->user->update($id, $data);
            $oldRole = $user->roles;
            if ($oldRole[0]->id !== $request->role_id) {
                $newRole = Role::findById($request->role_id);
                $user->removeRole($oldRole[0]);
                $user->assignRole($newRole);
            }
            $this->staff->update($user->staff->id, [
                'salary' => $request->salary
            ]);

            if ($user->school_id) {
                $leave_permission = [
                    'leave-list',
                    'leave-create',
                    'leave-edit',
                    'leave-delete',
                ];
                $user->givePermissionTo($leave_permission);
            }

            $this->staffSupportSchool->builder()->where('user_id', $user->id)->delete();
            if ($request->school_id) {
                $data = array();
                foreach ($request->school_id as $key => $school) {
                    $data[] = [
                        'user_id'   => $user->id,
                        'school_id' => $school
                    ];
                }
                $this->staffSupportSchool->upsert($data, ['user_id', 'school_id'], ['user_id', 'school_id']);
            }

            DB::commit();
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function destroy($id) {
        ResponseService::noFeatureThenRedirect('Staff Management');
        ResponseService::noPermissionThenSendJson('staff-delete');
        try {
            DB::beginTransaction();
            $user = $this->user->findById($id);
            $this->user->builder()->where('id', $id)->withTrashed()->update(['status' => $user->status == 0 ? 1 : 0, 'deleted_at' => $user->status == 1 ? now() : null]);
            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function restore(int $id) {
        ResponseService::noFeatureThenRedirect('Staff Management');
        ResponseService::noPermissionThenSendJson('staff-delete');
        try {
            DB::beginTransaction();
            $staff = $this->user->findTrashedById($id);
            $this->user->builder()->where('id', $id)->withTrashed()->update(['status' => $staff->status == 0 ? 1 : 0, 'deleted_at' => $staff->status == 1 ? now() : null]);
            DB::commit();
            ResponseService::successResponse("Status Updated Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function trash($id) {
        ResponseService::noFeatureThenRedirect('Staff Management');
        ResponseService::noPermissionThenSendJson('staff-delete');
        try {
            $user = $this->user->findOnlyTrashedById($id);
            $user->staff->delete();
            $user->forceDelete();
            ResponseService::successResponse("Data Deleted Permanently");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function support() {
        ResponseService::noRoleThenRedirect('School Admin');
        $support_staffs = $this->staffSupportSchool->builder()->Owner()->with('user:id,first_name,last_name,mobile,email,image')->get();
        $super_admin = '';
        if (!count($support_staffs)) {
            $super_admin = $this->user->builder()->select('first_name', 'last_name', 'mobile', 'email', 'image')->orWhereNull('school_id')->role('Super Admin')->first();
        }
        $settings = [
            'mobile' => app(CachingService::class)->getSystemSettings()['mobile'] ?? '',
            'email'  => app(CachingService::class)->getSystemSettings()['mail_username'] ?? '',
        ];

        return view('staff.support', compact('support_staffs', 'settings', 'super_admin'));
    }

    public function changeStatusBulk(Request $request) {
        ResponseService::noFeatureThenRedirect('Staff Management');
        ResponseService::noPermissionThenRedirect('staff-create');
        try {
            DB::beginTransaction();
            $userIds = json_decode($request->ids);
            foreach ($userIds as $userId) {
                $staff = $this->user->findTrashedById($userId);
                $this->user->builder()->where('id', $userId)->withTrashed()->update(['status' => $staff->status == 0 ? 1 : 0, 'deleted_at' => $staff->status == 1 ? now() : null]);
            }
            DB::commit();
            ResponseService::successResponse("Status Updated Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

}
