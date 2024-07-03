<?php

namespace App\Services;

use App\Models\Feature;
use App\Repositories\School\SchoolInterface;
use Illuminate\Support\Facades\Auth;

class FeaturesService {
    public function __construct() {
        // $this->features = app(UserInterface::class)->features();
    }

    public static function getFeatures($schoolID = null) {
        // Fetch All the Features of the School in which User is associated. Then Cache that result for 30 minutes
        $schoolID = !empty($schoolID) ? $schoolID : Auth::user()->school_id;
        if (!empty($schoolID)) {
            return app(CachingService::class)->schoolLevelCaching(config('constants.CACHE.SCHOOL.FEATURES'), function () use ($schoolID) {
                $school = app(SchoolInterface::class)->findById($schoolID, ['*'], ['features', 'addon']);
                $packageFeatures = $addon = [];
                if (!empty($school->features)) {
                    $packageFeatures = $school->features->pluck('feature_id')->toArray();
                }
                if (!empty($school->addon)) {
                    $addon = $school->addon->pluck('id')->toArray();
                }
                $features = array_merge($packageFeatures, $addon);
                if (!empty($features)) {
                    return Feature::whereIn('id', array_unique($features))->pluck('name', 'id')->toArray();
                }
                return [];
            }, $schoolID);
        }

//        if (empty(Auth::user()->school_id)) {
//            // IF it's a Super Admin or Staff then Fetch all the Features
//            return Feature::pluck('name', 'id')->toArray();
//        }

        return [];
    }

    /**
     * @param $argument
     * @return bool
     */
    public static function hasFeature($argument) {
        $features = self::getFeatures();
        return in_array($argument, $features);
    }

    /**
     * @param array $argument
     * @return bool
     */
    public static function hasAnyFeature(array $argument) {
        $features = self::getFeatures();
        return !empty(array_intersect($argument, $features));
    }

    /**
     * @param array $argument
     * @return bool
     */
    public static function hasAllFeature(array $argument) {
        $features = self::getFeatures();

        return empty(array_diff($argument, $features));
    }
}
