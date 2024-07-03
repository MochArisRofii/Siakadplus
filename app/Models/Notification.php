<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = ['title','message','image','send_to','session_year_id','school_id'];

    public function scopeOwner()
    {
        if (Auth::user()) {
            if (Auth::user()->school_id) {
                return $this->where('school_id',Auth::user()->school_id);
            }
            return $this;
        }
        return $this;
    }

    public function getImageAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        }
        return null;
    }
}
