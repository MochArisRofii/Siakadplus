<?php

namespace App\Http\Controllers;

use App\Repositories\Attendance\AttendanceInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\Student\StudentInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AttendanceController extends Controller {

    private AttendanceInterface $attendance;
    private ClassSectionInterface $classSection;
    private StudentInterface $student;
    private CachingService $cache;

    public function __construct(AttendanceInterface $attendance, ClassSectionInterface $classSection, StudentInterface $student, CachingService $cachingService) {
        $this->attendance = $attendance;
        $this->classSection = $classSection;
        $this->student = $student;
        $this->cache = $cachingService;
    }


    public function index() {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher','attendance-list']);
        $classSections = $this->classSection->builder()->ClassTeacher()->with('class', 'class.stream', 'section','medium')->get();
        return view('attendance.index', compact('classSections'));
    }


    public function view() {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher','attendance-list']);
        $class_sections = $this->classSection->builder()->ClassTeacher()->with('class', 'class.stream', 'section','medium')->get();
        return view('attendance.view', compact('class_sections'));
    }

    public function getAttendanceData(Request $request) {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        $response = $this->attendance->builder()->select('type')->where(['date' => date('Y-m-d', strtotime($request->date)), 'class_section_id' => $request->class_section_id])->pluck('type')->first();
        return response()->json($response);
    }

    public function store(Request $request) {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher','attendance-create', 'attendance-edit']);
        $request->validate([
            'class_section_id' => 'required',
            'date'             => 'required',
        ]);
        try {
            DB::beginTransaction();
            $attendanceData = array();
            $sessionYear = $this->cache->getDefaultSessionYear();
            foreach ($request->attendance_data as $value) {
                $data = (object)$value;
                $attendanceData[] = array(
                    "id"               => $data->id,
                    'class_section_id' => $request->class_section_id,
                    'student_id'       => $data->student_id,
                    'session_year_id'  => $sessionYear->id,
                    'type'             => $request->holiday ?? $data->type,
                    'date'             => date('Y-m-d', strtotime($request->date)),
                );
            }
            $this->attendance->upsert($attendanceData, ["id"], ["class_section_id", "student_id", "session_year_id", "type", "date"]);
            DB::commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollback();
            ResponseService::logErrorResponse($e, "Attendance Controller -> Store method");
            ResponseService::errorResponse();
        }
    }

    public function show(Request $request) {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher','attendance-list']);

//        $offset = $request->input('offset', 0);
//        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'roll_number');
        $order = $request->input('order', 'ASC');
        $search = $request->input('search');

        $class_section_id = $request->class_section_id;
        $date = date('Y-m-d', strtotime($request->date));
        $sessionYear = $this->cache->getDefaultSessionYear();

        $attendanceQuery = $this->attendance->builder()->with('user.student')->where(['date' => $date, 'class_section_id' => $class_section_id, 'session_year_id' => $sessionYear->id])->whereHas('user', function ($q) {
            $q->whereNull('deleted_at');
        })->whereHas('user.student',function($q) use($sessionYear){
            $q->where('session_year_id',$sessionYear->id);
        });

        if ($date != '' && $attendanceQuery->count() > 0) {
            $attendanceQuery->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")->orWhereHas('user', function ($q) use ($search) {
                    $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'");
                });
            })->where('date', $date)->whereHas('user.student',function($q) use($sessionYear){
                $q->where('session_year_id',$sessionYear->id);
            });

            $total = $attendanceQuery->count();
            $attendanceData = $attendanceQuery->get();
        } else if($class_section_id){
            $studentQuery = $this->student->builder()->where('session_year_id',$sessionYear->id)->where('class_section_id', $class_section_id)->with('user')
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->when($search, function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")->orWhereHas('user', function ($q) use ($search) {
                        $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'")->where('deleted_at',NULL);
                    });
                })->where('session_year_id',$sessionYear->id)->where('class_section_id', $class_section_id);

            $total = $studentQuery->count();
            // $studentQuery->orderBy($sort, $order)->skip($offset)->take($limit);
            $studentQuery->orderBy($sort, $order);
            $attendanceData = $studentQuery->get();
        }

        $rows = [];
        $no = 1;

        // dd($attendanceData->toArray());
        foreach ($attendanceData as $row) {
            $type = $row->type ?? NULL;
            // TODO : understand this code
            $rows[] = [
                'id'           => $attendanceQuery->count() ? $row->id : null,
                'no'           => $no,
                'student_id'   => $attendanceQuery->count() ? $row->student_id : $row->user_id,
                'user_id'      => $attendanceQuery->count() ? $row->student_id : $row->user_id,
                'admission_no' => $row->user ? ($row->user->student->admission_no ?? '') : ($row->admission_no ?? ''),
                'roll_no'      => $row->user ? ($row->user->student->roll_number ?? '') : ($row->roll_number ?? ''),
                'name' => '<input type="hidden" value="' . ($row->student_id ? $row->user_id : 'null') . '" name="attendance_data[' . $no . '][id]"><input type="hidden" value="' . ($row->student_id ?? $row->user_id) . '" name="attendance_data[' . $no . '][student_id]">' . ($row->user->first_name ?? '') . ' ' . ($row->user->last_name ?? ''),
                'type'         => $type,
            ];
            $no++;
        }

        $bulkData['total'] = $total;
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);

    }


    public function attendance_show(Request $request) {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher','attendance-list']);

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'student_id');
        $order = request('order', 'ASC');
        $search = request('search');
        $attendanceType = request('attendance_type');

        $class_section_id = request('class_section_id');
        $date = date('Y-m-d', strtotime(request('date')));

        $validator = Validator::make($request->all(), ['class_section_id' => 'required', 'date' => 'required',]);
        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        $sessionYear = $this->cache->getDefaultSessionYear();

        $sql = $this->attendance->builder()->where(['date' => $date, 'class_section_id' => $class_section_id])->with('user.student')
            ->where(function ($query) use ($search) {
                $query->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")
                        ->orwhere('student_id', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'")
                                ->orwhere('first_name', 'LIKE', "%$search%")
                                ->orwhere('last_name', 'LIKE', "%$search%");
                        })->orWhereHas('user.student', function ($q) use ($search) {
                            $q->where('admission_no', 'LIKE', "%$search%")
                                ->orwhere('id', 'LIKE', "%$search%")
                                ->orwhere('user_id', 'LIKE', "%$search%")
                                ->orwhere('roll_number', 'LIKE', "%$search%");
                        });
                });
                });
            })
            ->when($attendanceType != null, function ($query) use ($attendanceType) {
                $query->where('type', $attendanceType);
            });
        $sql = $sql->whereHas('user.student',function($q) use($sessionYear) {
            $q->where('session_year_id',$sessionYear->id);
        });
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }
}
