<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Expense extends Model
{
    use HasFactory;
    protected $fillable = ['category_id', 'ref_no', 'staff_id', 'month', 'year', 'title', 'description', 'amount', 'date', 'school_id', 'session_year_id'];

    public function scopeOwner()
    {
        if (Auth::user()->school_id) {
            return $this->where('school_id', Auth::user()->school_id);
        }
        if (!Auth::user()->school_id) {
            return $this;
        }
        return $this;
    }

    /**
     * Get the category that owns the Expense
     *
     * @return BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class,'category_id','id')->withTrashed();
    }

    // public function getMonthAttribute($value)
    // {
    //     if ($value == null) {
    //         $value = rand(13,100);
    //     }
    //     return $value;
    // }

    /**
     * Get the staff that owns the Expense
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }


}
