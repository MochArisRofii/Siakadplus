<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model {
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'tagline',
        'student_charge',
        'staff_charge',
        'status',
        'is_trial',
        'highlight',
        'rank',
        'days'
    ];

    public function package_feature() {
        return $this->hasMany(PackageFeature::class);
    }

    /**
     * Get all of the subscription for the Package
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscription()
    {
        return $this->hasMany(Subscription::class);
    }
}
