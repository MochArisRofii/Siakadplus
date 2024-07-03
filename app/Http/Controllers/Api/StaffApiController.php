<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\Leave\LeaveInterface;
use App\Repositories\SchoolSetting\SchoolSettingInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\Timetable\TimetableInterface;
use App\Repositories\User\UserInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PDF;

class StaffApiController extends Controller
{
    //

    private ExpenseInterface $expense;
    private SchoolSettingInterface $schoolSetting;
    private CachingService $cache;
    private LeaveInterface $leave;
    private UserInterface $user;
    private StudentInterface $student;
    private TimetableInterface $timetable;

    public function __construct(ExpenseInterface $expense, SchoolSettingInterface $schoolSetting, CachingService $cache, LeaveInterface $leave, UserInterface $user, StudentInterface $student, TimetableInterface $timetable)
    {
        $this->expense = $expense;
        $this->schoolSetting = $schoolSetting;
        $this->cache = $cache;
        $this->leave = $leave;
        $this->user = $user;
        $this->student = $student;
        $this->timetable = $timetable;
    }

    public function myPayroll(Request $request)
    {
        
        try {
            $sql = $this->expense->builder()->select('id','staff_id','basic_salary','paid_leaves','month','year','title','amount','date')->where('staff_id',Auth::user()->staff->id)
            ->when($request->year, function($q) use($request) {
                $q->whereYear('date',$request->year);
            })->get();

            ResponseService::successResponse('Data Fetched Successfully', $sql);

        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

    public function myPayrollSlip(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        $validator = Validator::make($request->all(), [
            'leave_id' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $vertical_logo = $this->schoolSetting->builder()->where('name', 'vertical_logo')->first();
            $schoolSetting = $this->cache->getSchoolSettings();

            // Salary
            $salary = $this->expense->builder()->with('staff.user:id,first_name,last_name')->where('id',$request->leave_id)->first();
            if (!$salary) {
                return redirect()->back()->with('error',trans('no_data_found'));
            }
            // Get total leaves
            $leaves = $this->leave->builder()->where('status',1)->where('user_id',$salary->staff->user_id)->withCount(['leave_detail as full_leave' => function ($q) use ($salary) {
                $q->whereMonth('date', $salary->month)->whereYear('date',$salary->year)->where('type', 'Full');
            }])->withCount(['leave_detail as half_leave' => function ($q) use ($salary) {
                $q->whereMonth('date', $salary->month)->whereYear('date',$salary->year)->whereNot('type', 'Full');
            }])->get();

            $total_leaves = $leaves->sum('full_leave') + ($leaves->sum('half_leave') / 2);
            // Total days
            $days = Carbon::now()->year($salary->year)->month($salary->month)->daysInMonth;

            $pdf = PDF::loadView('payroll.slip',compact('vertical_logo','schoolSetting','salary','total_leaves','days'));
            return $pdf->stream($salary->title.'-'.$salary->staff->user->full_name.'.pdf');
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

    public function profile()
    {
        try {
            $sql = $this->user->findById(Auth::user()->id,['*'],['staff']);
            
            ResponseService::successResponse('Data Fetched Successfully', $sql);
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

    public function counter()
    {
        try {
            $students = $this->user->builder()->role('Student')->count();
            $teachers = $this->user->builder()->role('Teacher')->count();

            $staffs = $this->user->builder()->whereHas('roles', function ($q) {
                $q->where('custom_role', 1)->whereNot('name', 'Teacher');
            })->count();

            $leaves = $this->leave->builder()->where('status',0)->count();
            $data = [
                'students' => $students,
                'teachers' => $teachers,
                'staffs' => $staffs,
                'leaves' => $leaves
            ];
            ResponseService::successResponse('Data Fetched Successfully', $data);
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

    public function teacher(Request $request)
    {
        try {
            if ($request->teacher_id) {
                $sql = $this->user->findById($request->teacher_id,['*'],['staff']);
            } else {
                $sql = $this->user->builder()->role('Teacher')->with('staff')->get();
            }
            ResponseService::successResponse('Data Fetched Successfully', $sql);
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

    public function teacherTimetable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $timetable = $this->timetable->builder()
            ->whereHas('subject_teacher',function($q) use($request){
                $q->where('teacher_id',$request->teacher_id);
            })
            ->with('class_section.class.stream','class_section.section','subject')->orderBy('start_time', 'ASC')->get();

            ResponseService::successResponse('Data Fetched Successfully', $timetable);
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }

    public function staff(Request $request)
    {
        try {
            if ($request->staff_id) {
                $sql = $this->user->builder()->whereHas('roles', function ($q) {
                    $q->where('custom_role', 1)->whereNot('name', 'Teacher');
                })->with('staff', 'roles')->where('id',$request->staff_id)->withTrashed()->first();
            } else {
                $sql = $this->user->builder()->whereHas('roles', function ($q) {
                    $q->where('custom_role', 1)->whereNot('name', 'Teacher');
                })->with('staff', 'roles');
                if ($request->status == 2) {
                    $sql->onlyTrashed();
                } else if($request->status == 0) {
                    $sql->withTrashed();
                }
                $sql = $sql->get();
            }
            
            ResponseService::successResponse('Data Fetched Successfully', $sql);
        } catch (\Throwable $th) {
            ResponseService::logErrorResponse($th);
            ResponseService::errorResponse();
        }
    }
}
