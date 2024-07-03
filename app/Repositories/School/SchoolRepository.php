<?php

namespace App\Repositories\School;

use App\Models\School;
use App\Models\User;
use App\Repositories\Base\BaseRepository;
use App\Services\UploadService;
use Illuminate\Support\Facades\Hash;

class SchoolRepository extends BaseRepository implements SchoolInterface {
    public function __construct(School $model) {
        parent::__construct($model, 'school');
        $this->model = $model;
    }

    public function forceDelete(int $modelId): bool {
        $school_query = School::where('id', $modelId); // Query for School
        $admin_id = $school_query->pluck('admin_id')->first(); // Get the admin id
        $school_query->forceDelete(); // Delete School

        $user = User::findOrFail($admin_id);
        $user->forceDelete(); // Soft Delete the user
        return true;
    }


    public function updateSchoolAdmin($array, $image = null) {
        $data = (object)$array;

        // Delete old Admin
        $admin_id = $this->all()->where('id', $data->school_id)->pluck('admin_id')->first();
        $user = User::findOrFail($admin_id);
        $user->school_id = null; // Update the school_id to null
        $user->save(); // Save the Changes
        $user->delete(); // Soft Delete the user
        

        // Check that email is not ID
        if (!is_numeric($data->email)) {
            // For image
            $folder = 'user';
            if ($image) {
                $image_path = UploadService::upload($image, $folder);
                $array['image'] = $image_path;
            }

            // Add New Admin
            $admin = new User();
            $admin->password = Hash::make($data->contact);
            $admin->school_id = $data->school_id;
            $admin->mobile = $data->contact ?? null;
            $admin->fill($array);
            $admin->save();
            $admin->assignRole('School Admin');

            //Add New Admin ID to School
            $school = School::findOrFail($data->school_id);
            $school->admin_id = $admin->id;
            $school->save();
        } else {

            //Change Admin ID
            $school = School::findOrFail($data->school_id);
            $school->admin_id = $data->email;
            $school->save();

            // Add School ID to Respective Admins ID
            $user = User::withTrashed()->findOrFail($data->email);
            $user->school_id = $data->school_id; // Update the school_id
            $user->mobile = $data->contact ?? null;
            $user->deleted_at = null;
            $user->save();
            $user->assignRole('School Admin');
        }
    }


    public function active() {
        return $this->defaultModel()->where('status', 1);
    }
}
