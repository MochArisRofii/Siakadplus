<?php

namespace App\Http\Controllers;

use App\Repositories\Announcement\AnnouncementInterface;
use App\Repositories\ClassSchool\ClassSchoolInterface;
use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\Holiday\HolidayInterface;
use App\Repositories\Leave\LeaveInterface;
use App\Repositories\School\SchoolInterface;
use App\Repositories\Subscription\SubscriptionInterface;
use App\Repositories\Timetable\TimetableInterface;
use App\Repositories\User\UserInterface;
use App\Services\CachingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private UserInterface $user;
    private AnnouncementInterface $announcement;
    private SubscriptionInterface $subscription;
    private SchoolInterface $school;
    private LeaveInterface $leave;
    private HolidayInterface $holiday;
    private ExpenseInterface $expense;
    private CachingService $cache;
    private ClassSchoolInterface $class;
    private TimetableInterface $timetable;

    public function __construct(UserInterface $user, AnnouncementInterface $announcement, SubscriptionInterface $subscription, SchoolInterface $school, LeaveInterface $leave, HolidayInterface $holiday, ExpenseInterface $expense, CachingService $cache, ClassSchoolInterface $class, TimetableInterface $timetable)
    {
        $this->middleware('auth');
        $this->user = $user;
        $this->announcement = $announcement;
        $this->subscription = $subscription;
        $this->school = $school;
        $this->leave = $leave;
        $this->holiday = $holiday;
        $this->expense = $expense;
        $this->cache = $cache;
        $this->class = $class;
        $this->timetable = $timetable;
    }

    public function index()
    {
        $teacher = $student = $parent = $teachers = $subscription = null;
        $boys = $girls = $license_expire = 0;
        $previous_subscriptions = array();
        $announcement = array();
        $leaves = array();
        $holiday = array();
        $expense_months = array();
        $expense_amount = array();
        $total_students = $male_students = $female_students = $timetables = $classData = array();
        $settings = app(CachingService::class)->getSystemSettings();
        // School Admin Dashboard
        if (Auth::user()->hasRole('School Admin') || Auth::user()->school_id) {
            $teacher = $this->user->builder()->role("Teacher")->count();
            $student = $this->user->builder()->role("Student")->count();
            $parent = $this->user->guardian()->whereHas('child.user', function ($q) {
                $q->owner();
            })->count();
            // $teachers = $this->user->builder()->role("Teacher")->with('teacher')->get();
            if ($student > 0) {
                $boys_count = $this->user->builder()->role('Student')->where('gender', 'male')->count();
                $girls_count = $this->user->builder()->role('Student')->where('gender', 'female')->count();
                $boys = round((($boys_count * 100) / $student), 2);
                $girls = round(($girls_count * 100) / $student, 2);
            }

            $subscription = $this->subscription->default()->with('subscription_bill')->first();
            if ($subscription) {
                $license_expire = Carbon::now()->diffInDays(Carbon::parse($subscription->end_date)) + 1;
            }
            $previous_subscriptions = $this->subscription->builder()->with('subscription_bill.transaction','subscription_bill.subscription_bill_payment')->get()->whereIn('status', [3, 4, 5]);

            $leaves = $this->leave->builder()->with('user:id,first_name,last_name')->where('status', 1)->where('from_date','<=',Carbon::now()->format('Y-m-d'))->where('to_date','>=',Carbon::now()->format('Y-m-d'))->with(['leave_detail' => function($q) {
                $q->where('date',Carbon::now()->format('Y-m-d'));
            }])->get();

            $sessionYear = $this->cache->getDefaultSessionYear();

            $holiday = $this->holiday->builder()->whereDate('date','>=',Carbon::now()->format('Y-m-d'))->whereDate('date','<=',$sessionYear->end_date)->orderBy('date','ASC')->get();

            $announcement = $this->announcement->builder()->whereHas('announcement_class', function ($q) {
                $q->where('class_subject_id', null);
            })->limit(5)->get();

            // Expense graph
            $expense = $this->expense->builder()->select(DB::raw('MONTH(date) as month'), DB::raw('SUM(amount) as total_amount'))->where('session_year_id',$sessionYear->id)
            ->groupBy(DB::raw('MONTH(date)'));
            $expense = $expense->get()->pluck('total_amount','month')->toArray();

            $months = sessionYearWiseMonth();
            foreach ($months as $key => $month) {
                if (isset($expense[$key])) {
                    $expense_months[] = substr($months[$key], 0, 3);
                    $expense_amount[] = $expense[$key];
                }
            }

            // Attendance Graph
            $total_present = 0;
            $section_data = array();
            $classes = $this->class->builder()
            ->with('class_sections.class', 'class_sections.attendance','class_sections.section','class_sections.medium','stream')
            ->orderByRaw("CASE WHEN name REGEXP '^[0-9]+$' THEN LPAD(name, 10, '0') ELSE name END")
            ->get();
            $classData = $classes->map(function ($class) use ($sessionYear) {
                $stream = $class->stream ? $class->stream->name : null;
                $className = $class->name.''.($stream ? '('.$stream.')' : null).'-'.$class->class_sections->first()->medium->name;
                
                $sectionData = $class->class_sections->whereNull('deleted_at')->map(function ($section) use ($sessionYear) {
                    $sectionName = $section->section->name; // Replace with the actual column name for the section name
                    $attendances = $section->attendance;
                    // $className = '-'.$section->medium->name;
                    $totalPresent = $attendances->where('type', 1)
                        ->where('session_year_id', $sessionYear->id)
                        ->count();

                    $totalAttendance = $attendances->where('session_year_id', $sessionYear->id)->count();
                    $total_present = 0;
                    if ($totalAttendance) {
                        $total_present = number_format(($totalPresent * 100) / $totalAttendance, 2);
                    }

                    return [
                        'section_name' => $sectionName ?? null,
                        'total_attendance' => $totalAttendance,
                        'total_present' => $total_present,
                    ];
                });

                return [
                    'class_name' => $className ?? null,
                    'section_data' => $sectionData->toArray(),
                ];
            });

        }

        // Super admin dashboard
        $super_admin = [
            'total_school' => 0,
            'active_school' => 0,
            'deactive_school' => 0,
        ];
        if (Auth::user()->hasRole('Super Admin') || !Auth::user()->school_id) {
            $school = $this->school->builder()->get();
            $total_school = $school->count();
            $active_school = $school->where('status', 1)->count();
            $deactive_school = $school->where('status', 0)->count();

            $super_admin = [
                'total_school' => $total_school,
                'active_school' => $active_school,
                'deactive_school' => $deactive_school,
            ];
        }

        // Timetable
        $date = Carbon::now();
        $fullDayName = $date->format('l');
        $timetables = $this->timetable->builder()
        ->whereHas('subject_teacher',function($q) {
            $q->where('teacher_id',Auth::user()->id);
        })
        ->where('day',$fullDayName)->orderBy('start_time','ASC')
        ->with('subject:id,name,type','class_section.class','class_section.section','class_section.medium')->get();

        return view('dashboard', compact('teacher', 'parent', 'student', 'announcement', 'teachers', 'boys', 'girls', 'license_expire', 'subscription', 'previous_subscriptions', 'settings','super_admin','leaves','holiday','expense_months','expense_amount','timetables','classData'));
    }

}
