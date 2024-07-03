<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'description', 'school_id'];

    public function scopeOwner()
    {
        return $this->where('school_id',Auth::user()->school_id);
    }

    /**
     * Get all of the expense for the ExpenseCategory
     *
     * @return HasMany
     */
    public function expense()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
