<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Exam extends Model {
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'session_year_id',
        'description',
        'start_date',
        'end_date',
        'school_id',
        'publish'
    ];

    protected $appends = ["exam_status", "exam_status_name", "has_timetable"];

    public function class() {
        return $this->belongsTo(ClassSchool::class, 'class_id')->withTrashed();
    }

    public function session_year() {
        return $this->belongsTo(SessionYear::class, 'session_year_id')->withTrashed();
    }

    public function marks() {
        return $this->hasManyThrough(ExamMarks::class, ExamTimetable::class, 'exam_id', 'exam_timetable_id')->orderBy('date');
    }

    public function timetable() {
        return $this->hasMany(ExamTimetable::class);
    }

    public function results() {
        return $this->hasMany(ExamResult::class, 'exam_id');
    }

    public function semester() {
        return $this->belongsTo(Semester::class, 'semester_id')->withTrashed();
    }
    public function scopeOwner($query) {

        if (Auth::user()->school_id) {
            if (Auth::user()->hasRole('School Admin')) {
                return $query->where('school_id', Auth::user()->school_id);
            }

            if(Auth::user()->hasRole('Teacher')){
                $classTeacherData = ClassTeacher::where('teacher_id',Auth::user()->id)->with('class_section')->get();
                $classIds = $classTeacherData->pluck('class_id');
                return $query->whereIn('class_id',$classIds)->where('school_id', Auth::user()->school_id);
            }

            if (Auth::user()->hasRole('Student')) {
                return $query->where('school_id', Auth::user()->school_id);
            }
            return $query->where('school_id', Auth::user()->school_id);
        }
        if (!Auth::user()->school_id) {
            if (Auth::user()->hasRole('Super Admin')) {
                return $query;
            }
            if (Auth::user()->hasRole('Guardian')) {
                $childId = request('child_id');
                $studentAuth = Students::where('id',$childId)->first();
                return $query->where('school_id', $studentAuth->school_id);
            }
            return $query;
        }
        return $query;
    }


    public function getExamStatusAttribute() {
        if ($this->relationLoaded('timetable')) {
            $startDate = $this->timetable->min('date');
            $endDate = $this->timetable->max('date');

            $currentDate = now()->toDateString();
            if ($currentDate >= $startDate && $currentDate <= $endDate) {
                return "1"; // Upcoming = 0 , On Going = 1 , Completed = 2
            }

            if ($currentDate < $startDate) {
                return "0"; // Upcoming = 0 , On Going = 1 , Completed = 2
            }
            return "2"; // Upcoming = 0 , On Going = 1 , Completed = 2
        }

        return null;
    }

    public function getExamStatusNameAttribute() {
        if ($this->relationLoaded('timetable')) {
            $startDate = $this->timetable->min('date');
            $endDate = $this->timetable->max('date');

            $currentDate = now()->toDateString();
            if ($currentDate >= $startDate && $currentDate <= $endDate) {
                return "On Going"; // Upcoming = 0 , On Going = 1 , Completed = 2
            }

            if ($currentDate < $startDate) {
                return "Upcoming"; // Upcoming = 0 , On Going = 1 , Completed = 2
            }
            return "Completed"; // Upcoming = 0 , On Going = 1 , Completed = 2
        }

        return null;
    }

    public function getHasTimetableAttribute() {
        if ($this->relationLoaded('timetable')) {
            return count($this->timetable) > 0;
        }

        return false;
    }
}
