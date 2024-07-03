<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Repositories\ClassSchool\ClassSchoolInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\ClassSubject\ClassSubjectInterface;
use App\Repositories\Exam\ExamInterface;
use App\Repositories\ExamMarks\ExamMarksInterface;
use App\Repositories\ExamResult\ExamResultInterface;
use App\Repositories\ExamTimetable\ExamTimetableInterface;
use App\Repositories\Medium\MediumInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Repositories\StudentSubject\StudentSubjectInterface;
use App\Repositories\Subject\SubjectInterface;
use App\Repositories\User\UserInterface;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExamController extends Controller {
    private ExamInterface $exam;
    private ClassSchoolInterface $class;
    private SessionYearInterface $sessionYear;
    private SubjectInterface $subject;
    private ExamTimetableInterface $examTimetable;
    private ClassSectionInterface $classSection;
    private ExamMarksInterface $examMarks;
    private ExamResultInterface $examResult;
    private StudentSubjectInterface $studentSubject;
    private ClassSubjectInterface $classSubject;
    private UserInterface $users;
    private CachingService $cache;
    private MediumInterface $mediums;

    public function __construct(ExamInterface $exam, ClassSchoolInterface $class, SessionYearInterface $sessionYear, SubjectInterface $subject, ExamTimetableInterface $examTimetable, ClassSectionInterface $classSection, ExamMarksInterface $examMarks, ExamResultInterface $examResult, StudentSubjectInterface $studentSubject, ClassSubjectInterface $classSubject, UserInterface $users, CachingService $cache, MediumInterface $mediums) {
        $this->exam = $exam;
        $this->class = $class;
        $this->sessionYear = $sessionYear;
        $this->subject = $subject;
        $this->examTimetable = $examTimetable;
        $this->classSection = $classSection;
        $this->examMarks = $examMarks;
        $this->examResult = $examResult;
        $this->studentSubject = $studentSubject;
        $this->classSubject = $classSubject;
        $this->users = $users;
        $this->cache = $cache;
        $this->mediums = $mediums;
    }

    public function index() {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenRedirect('exam-create');
        $classes = $this->class->all(['*'], ['stream', 'medium', 'stream']);
        $subjects = $this->subject->builder()->orderBy('id', 'DESC')->get();
        $session_year_all = $this->sessionYear->all();
        $mediums = $this->mediums->builder()->pluck('name','id');
        return response(view('exams.index', compact('classes', 'subjects', 'session_year_all','mediums')));
    }

    public function store(Request $request) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('exam-create');
        $request->validate(['name' => 'required', 'session_year_id' => 'required',]);

        try {
            $examData = array(); // Initialize examData with Empty Array
            // Loop towards Classes
            foreach ($request->class_id as $classId) {
                $examData[] = array(
                    'name'            => $request->name,
                    'description'     => $request->description,
                    'class_id'        => $classId,
                    'session_year_id' => $request->session_year_id
                );
            }
            $this->exam->createBulk($examData); // Store The Exam Data According to Class

            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Exam Controller -> Store method");
            ResponseService::errorResponse();
        }
    }

    public function show() {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('exam-list');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');
        $showDeleted = request('show_deleted');
        $medium_id = request('medium_id');

        $sql = $this->exam->builder()->with('class.medium', 'class.stream', 'timetable')->when($search, function ($query) use ($search) {
            $query->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")->orwhere('description', 'LIKE', "%$search%")->orwhere('created_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")->orwhere('updated_at', 'LIKE', "%" . date('Y-m-d H:i:s', strtotime($search)) . "%")->orWhereHas('session_year', function ($q) use ($search) {
                        $q->where('name', 'LIKE', "%$search%");
                    });
                });
            });
        })->when(request('session_year_id') != null, function ($query) {
            $query->where('session_year_id', request('session_year_id'));
        })->when($medium_id, function ($query) use($medium_id) {
            $query->whereHas('class',function($q) use($medium_id){
                $q->where('medium_id',$medium_id);
            });
        })
        ->when(!empty($showDeleted), function ($query) {
            $query->onlyTrashed();
        });

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = '';

            // Check the params of Show Deleted
            if ($showDeleted) {
                // Show Restore And Trash Button
                $operate .= BootstrapTableService::restoreButton(route('exams.restore', $row->id));
                $operate .= BootstrapTableService::trashButton(route('exams.trash', $row->id));
            } else if ($row->publish == 0) {
                $operate .= BootstrapTableService::button('fa fa-calendar', route('exam.timetable.edit', $row->id), ['btn-gradient-info'], ['title' => 'Timetable']); // Timetable Button
                $operate .= BootstrapTableService::button('fa fa-check-circle', '#', ['btn', 'btn-xs', 'btn-gradient-success', 'btn-rounded', 'btn-icon', 'publish-exam-result'], ['data-id' => $row->id, 'title' => 'Publish Exam Result']); // Publish Button
                // If Exam Status is Upcoming And Should be published Status 0
                if (($row->exam_status == 0)) {
                    $operate .= BootstrapTableService::editButton(route('exams.update', $row->id)); // Edit Button
                }
                $operate .= BootstrapTableService::deleteButton(route('exams.destroy', $row->id)); // Delete Button
            } else if ($row->publish == 1) {
                // If Publish Status is 1 Then Show only UnPublish Exam
                // Undo Publish Button
                $operate .= BootstrapTableService::button('fa fa-times-circle', '#', ['btn', 'btn-xs', 'btn-gradient-warning', 'btn-rounded', 'btn-icon', 'publish-exam-result'], ['data-id' => $row->id, 'title' => 'Unpublished Exam Result']);
            }

            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function update($id, Request $request) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('exam-edit');
        $request->validate(['name' => 'required']);
        try {
            $this->exam->update($id, $request->all());
            ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Exam Controller -> Update method");
            ResponseService::errorResponse();
        }
    }

    public function destroy($id) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('exam-delete');
        try {
            $this->exam->deleteById($id);
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Exam Controller -> Delete method");
            ResponseService::errorResponse();
        }
    }

    public function restore(int $id) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('exam-delete');
        try {
            $this->exam->findOnlyTrashedById($id)->restore();
            ResponseService::successResponse("Data Restored Successfully");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e);
            ResponseService::errorResponse();
        }
    }

    public function trash($id) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('exam-delete');
        try {
            $this->exam->findOnlyTrashedById($id)->forceDelete();
            ResponseService::successResponse("Data Deleted Permanently");
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Exam Controller ->Trash Method", 'Can not Delete this because marks are already submitted');

            ResponseService::errorResponse();
        }
    }

    // -----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    /*** Upload Marks ***/
    public function uploadMarks() {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenRedirect('exam-upload-marks');

        $teacherId = Auth::user()->teacher->user_id;
        $classes = $this->classSection->builder()->whereHas('class_teachers', function ($query) use ($teacherId) {
            $query->where('teacher_id', $teacherId);
        })->with('class', 'section', 'medium')->get();

        //        $exams = $this->exam->builder()
        //            ->with(['timetable' => function ($query) {
        //                $query->where('date', '<', date('Y-m-d'))
        //                    ->orWhere(function($q) {
        //                        $q->whereDate('date','=', date('Y-m-d'))->where('end_time', '<=', date('H:i:s'));
        //                    })->with('class_subject.subject');
        //            }])->where('publish', 0)
        //            ->get();
        //
        //        $exams = $this->exam->builder()
        //            ->with(['timetable' => function ($query) {
        //                $query->where('date', '<', date('Y-m-d'))
        //                    ->orWhere(function ($q) {
        //                        $q->whereDate('date', '=', date('Y-m-d'))->where('end_time', '<=', date('H:i:s'));
        //                    })->with('class_subject.subject');
        //            }])
        //            ->where('publish', 0)
        //            ->get();
        $exams = $this->exam->builder()->with(['timetable' => function ($query) {
            $query->where('date', '<', date('Y-m-d'))->orWhere(function ($q) {
                $q->whereDate('date', '=', date('Y-m-d'))->where('end_time', '<=', date('H:i:s'));
            })->with('class_subject.subject');
        }])->where('publish', 0)->get();


        return response()->view('exams.upload-marks', compact('exams', 'classes'));
    }

    public function marksList(Request $request) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('class-teacher');

        $request->validate(['class_section_id' => 'required', 'exam_id' => 'required', 'class_subject_id' => 'required',], ['class_section_id.required' => 'Class section field is required', 'exam_id.required' => 'Exam field is required', 'class_subject_id.required' => 'Class subject field is required',]);

        try {

            // Sorting and limit settings
            $sort = $request->input('sort', 'id');
            $order = $request->input('order', 'ASC');
            $search = $request->input('search');

            // Get Exam with timetable id and date to get exam status also
            $exam = $this->exam->builder()->with('timetable:id,date')->where('id', $request->exam_id)->first();

            // Get Student ids according to Subject is elective or compulsory
            $classSubject = $this->classSubject->findById($request->class_subject_id);
            if ($classSubject->type == "Elective") {
                $studentIds = $this->studentSubject->builder()->where(['class_section_id' => $request->class_section_id, 'class_subject_id' => $classSubject->id])->pluck('student_id');
            } else {
                $studentIds = $this->users->builder()->role('student')->whereHas('student', function ($query) use ($request) {
                    $query->where('class_section_id', $request->class_section_id);
                })->pluck('id');
            }

            // Get Timetable Data
            $timetable = $exam->timetable()->where('class_subject_id', $request->class_subject_id)->first();

            // IF Timetable is empty then show error message
            if (!$timetable) {
                return response()->json(['error' => true, 'message' => trans('Exam Timetable Does not Exists')]);
            }

            // IF Exam status is not 2 that is exam not completed then show error message
            if ($exam->exam_status != 2) {
                ResponseService::errorResponse('Exam not completed yet');
            }

            $sessionYear = $this->cache->getDefaultSessionYear();// Get Students Data on the basis of Student ids
            $students = $this->users->builder()->role('Student')->whereIn('id', $studentIds)->with(['exam_marks' => function ($query) use ($timetable) {
                $query->where('exam_timetable_id', $timetable->id);
            }])->when($search, function ($q) use ($search) {
                $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'");
            })
                ->whereHas('student', function ($q) use ($sessionYear) {
                    $q->where('session_year_id', $sessionYear->id);
                })
                ->orderBy($sort, $order)->get();

            // Loop on the Students Data
            $rows = [];
            foreach ($students as $no => $student) {
                $rows[] = ['id' => $student->id, 'no' => $no + 1, 'student_name' => $student->full_name, 'total_marks' => $timetable->total_marks, 'exam_marks_id' => $student->exam_marks[0]->id ?? '', 'obtained_marks' => $student->exam_marks[0]->obtained_marks ?? '', 'operate' => '<a href=' . route('exams.edit', $student->id) . ' class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id=' . $student->id . ' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;<a href=' . route('exams.destroy', $student->id) . ' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" data-id=' . $student->id . '><i class="fa fa-trash"></i></a>',];
            }

            // Return Data as bulk-data
            $bulkData['rows'] = $rows;
            return response()->json($bulkData);

        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Exam Controller -> Get Exam Subjects");
            ResponseService::errorResponse();
        }
    }

    public function submitMarks(Request $request) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        $request->validate(['exam_id' => 'required|numeric', 'class_subject_id' => 'required|numeric', 'exam_marks' => 'required|array',], ['class_id.required' => 'Class section field is required.', 'exam_id.required' => 'Exam field is required.', 'class_subject_id.required' => 'Subject field is required.', 'exam_marks.required' => 'No records found.',]);

        try {
            $exam_timetable = $this->examTimetable->builder()->where(['exam_id' => $request->exam_id, 'class_subject_id' => $request->class_subject_id])->firstOrFail();

            foreach ($request->exam_marks as $examMarks) {
                $passing_marks = $exam_timetable->passing_marks;
                if ($examMarks['obtained_marks'] >= $passing_marks) {
                    $status = 1;
                } else {
                    $status = 0;
                }
                $marks_percentage = ($examMarks['obtained_marks'] / $examMarks['total_marks']) * 100;
                $exam_grade = findExamGrade($marks_percentage);

                if ($exam_grade == null) {
                    ResponseService::errorResponse('Grades data does not exists');
                }

                $this->examMarks->updateOrCreate(['id' => $examMarks['exam_marks_id'] ?? null], ['exam_timetable_id' => $exam_timetable->id, 'student_id' => $examMarks['student_id'], 'class_subject_id' => $request->class_subject_id, 'obtained_marks' => $examMarks['obtained_marks'], 'passing_status' => $status, 'session_year_id' => $exam_timetable->session_year_id, 'grade' => $exam_grade,]);
            }
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Exam Controller -> Get Exam Subjects");
            ResponseService::errorResponse();
        }
    }

    public function getSubjectByExam($exam_id) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        try {
            $exam_timetable = ExamTimetable::with('subject')->where('exam_id', $exam_id)->get();
            $response = array('error' => false, 'message' => trans('data_fetch_successfully'), 'data' => $exam_timetable);
        } catch (Throwable $e) {
            ResponseService::logErrorResponse($e, "Exam Controller -> Get Exam Subjects");
            ResponseService::errorResponse();
        }
        return response()->json($response);
    }


    // -----------------------------------------------------------------------------------------------------

    /*** Exam Result ***/

    public function getExamResultIndex() {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenRedirect('exam-result');
        $exams = $this->exam->builder()->where('publish', 1)->get();
        $sessionYears = $this->sessionYear->all();
        $classSections = $this->classSection->all(['*'], ['class', 'class.stream', 'section', 'medium']);
        return view('exams.show_exam_result', compact('exams', 'sessionYears', 'classSections'));
    }

    public function showExamResult(Request $request) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('exam-result');
        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $search = request('search');

        $sql = $this->examResult->builder()->with(['user:id,first_name,last_name,school_id', 'user.exam_marks' => function ($q) {
            $q->with('timetable', 'subject');
        }])->when(request('exam_id') != null, function ($query) use ($request) {
            $query->where('exam_id', $request->exam_id);
        })->when(request('session_year_id') != null, function ($query) use ($request) {
            $query->where('session_year_id', $request->session_year_id);
        })->when($search, function($q) use($search, $request) {
            $q->where(function($q) use($search){
              $q->where('id', 'LIKE', "%$search%")
                ->orwhere('total_marks', 'LIKE', "%$search%")
                ->orwhere('grade', 'LIKE', "%$search%")
                ->orwhere('obtained_marks', 'LIKE', "%$search%")
                ->orwhere('percentage', 'LIKE', "%$search%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'");
                });
            })->when($request->exam_id, function ($q) use ($request) {
                $q->where('exam_id', $request->exam_id);
            })->Owner();
        });

        

        if ($request->class_section_id) {
            $sql = $sql->where('class_section_id', $request->class_section_id);
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;
        foreach ($res as $row) {
            $operate = '';
            if (Auth::user()->can('class-teacher')) {
                $operate = BootstrapTableService::button('fa fa-edit', '#', ['btn-gradient-primary', 'btn-xs', 'btn-rounded', 'btn-icon', 'edit-data'], ['data-id' => $row->id, 'data-student_id' => $row->student_id, 'title' => 'Edit', 'data-toggle' => 'modal', 'data-target' => '#editModal']);
            }
            $tempRow = $row->toArray();
            $tempRow['no'] = $no++;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function updateExamResultMarks(Request $request) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        ResponseService::noPermissionThenSendJson('class-teacher');
        $request->validate([
            'edit.*.marks_id'       => 'required|numeric',
            'edit.*.obtained_marks' => 'required|numeric|lte:edit.*.total_marks'
        ]);
        try {
            DB::beginTransaction();
            // Loop Through Request Data
            foreach ($request->edit as $data) {
                $passingMarks = $data['passing_marks']; // Get Passing Marks
                $marksPercentage = ($data['obtained_marks'] / $data['total_marks']) * 100; // Get Percentage

                // Get Percentage And Check that Grades Should not be NULL
                $grade = findExamGrade($marksPercentage);
                if ($grade == null) {
                    ResponseService::errorResponse("Grades data does not exists");
                }

                // Array for Update Marks
                $updateMarksData = array(
                    'obtained_marks' => $data['obtained_marks'],
                    'passing_status' => $data['obtained_marks'] >= $passingMarks ? 1 : 0, 'grade' => $grade
                );

                $this->examMarks->update($data['marks_id'], $updateMarksData); // Update Exam Marks

                $examResultId = $this->examResult->builder()->where(['exam_id' => $data['exam_id'], 'student_id' => $data['student_id']])->value('id'); // Get Exam Result ID

                // Query Data From Exam Table To Get Exam Marks According to Exam ID

                DB::enableQueryLog();
                $exam = $this->exam->builder()->with(['marks' => function ($query) use ($data) {
                    $query->with('user.student:id,user_id,class_section_id')->selectRaw('SUM(obtained_marks) as total_obtained_marks,student_id')->selectRaw('SUM(total_marks) as total_marks')->where('student_id', $data['student_id'])->groupBy('student_id');
                }, 'timetable'                                => function ($query) use ($data) {
                    $query->where(['exam_id' => $data['exam_id']]);
                }])->where('id', $data['exam_id'])->first();

                // Loop through Exam Marks Data
                foreach ($exam->marks as $examMarks) {
                    $percentage = ($examMarks['total_obtained_marks'] * 100) / $examMarks['total_marks']; // Get Percentage

                    // Get Percentage And Check that Grades Should not be NULL
                    $grade = findExamGrade($percentage);
                    if ($grade == null) {
                        ResponseService::errorResponse("Grades data does not exists");
                    }

                    // Array For Update Exam Result Data
                    $examResultData = array("obtained_marks" => $examMarks['total_obtained_marks'], "percentage" => round($percentage, 2), "grade" => $grade,);

                    $this->examResult->update($examResultId, $examResultData); // Update Exam Result
                }
            }
            DB::commit();
            ResponseService::successResponse("Data Updated Successfully");
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Exam Controller -> updateExamResultMarks method");
            ResponseService::errorResponse();
        }
    }

    // -----------------------------------------------------------------------------------------------------

    public function publishExamResult($id) {
        ResponseService::noFeatureThenRedirect('Exam Management');
        try {

            // Get The Exam Data with Marks and Timetable
            $exam = $this->exam->builder()->with(['marks' => function ($query) {
                $query->with('user:id,first_name,last_name,image', 'user.student:id,user_id,class_section_id')->selectRaw('SUM(obtained_marks) as total_obtained_marks, student_id')->selectRaw('SUM(total_marks) as total_marks')->groupBy('student_id');
            }, 'timetable:id,exam_id,start_time,end_time'])->with(['timetable' => function ($q) {
                $q->doesntHave('exam_marks');
            }])->findOrFail($id);

            if (count($exam->timetable)) {
                ResponseService::errorResponse("Marks are not uploaded yet.");
            }

            DB::beginTransaction();
            if ($exam->exam_status == 2 && $exam->marks->isNotEmpty()) {

                if ($exam->publish == 0) {


                    // If exam is Unpublished then Insert ExamResult records and Publish the Exam
                    $examResult = $exam->marks->map(function ($examMarks) use ($exam) {
                        $percentage = ($examMarks['total_obtained_marks'] * 100) / $examMarks['total_marks'];
                        $grade = findExamGrade($percentage);

                        if ($grade === null) {
                            ResponseService::errorResponse("Grades data does not exists");
                        }

                        return [
                            'exam_id'          => $exam->id,
                            'class_section_id' => $examMarks['user']['student']['class_section_id'],
                            'student_id'       => $examMarks['student_id'],
                            'total_marks'      => $examMarks['total_marks'],
                            'obtained_marks'   => $examMarks['total_obtained_marks'],
                            'percentage'       => round($percentage, 2),
                            'grade'            => $grade,
                            'session_year_id'  => $exam->session_year_id];
                    });

                    $this->examResult->createBulk($examResult->toArray()); // Add Data in Exam Result
                    $this->exam->update($id, ['publish' => 1]); // Update Exam with Publish status 1
                } else {
                    ExamResult::where('exam_id', $id)->delete(); // If Exam is already published then unpublished it and delete Exam Result
                    $this->exam->update($id, ['publish' => 0]); // Update Exam with Publish status 0
                }
                DB::commit();
                ResponseService::successResponse('Data Stored Successfully');
            } else {
                ResponseService::errorResponse('Exam not completed yet');
            }
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Exam Controller -> publishExamResult method");
            ResponseService::errorResponse();
        }
    }

    public function deleteExamTimetable($id) {
        ResponseService::noPermissionThenSendJson('exam-timetable-delete');
        try {
            DB::beginTransaction();
            $this->examTimetable->deleteById($id);
            DB::commit();
            ResponseService::successResponse('Data Deleted Successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "Exam Controller -> DeleteTimetable method",trans('cannot_delete_because_data_is_associated_with_other_data'));
            ResponseService::errorResponse();
        }
    }
}
