<?php

namespace App\Services;

use App\Repositories\ExtraFormField\ExtraFormFieldsInterface;
use App\Repositories\Student\StudentInterface;
use App\Repositories\User\UserInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use JsonException;
use Throwable;

class UserService {
    private UserInterface $user;
    private StudentInterface $student;
    private ExtraFormFieldsInterface $extraFormFields;

    public function __construct(UserInterface $user, StudentInterface $student, ExtraFormFieldsInterface $extraFormFields) {
        $this->user = $user;
        $this->student = $student;
        $this->extraFormFields = $extraFormFields;
    }

    /**
     * @param $mobile
     * @return string
     */
    public function makeParentPassword($mobile) {
        return $mobile;
    }

    /**
     * @param $dob
     * @return string
     */
    public function makeStudentPassword($dob) {
        return str_replace('-', '', date('d-m-Y', strtotime($dob)));
    }

    /**
     * @param $first_name
     * @param $last_name
     * @param $email
     * @param $mobile
     * @param $gender
     * @param null $image
     * @return Model|null
     */
    public function createOrUpdateParent($first_name, $last_name, $email, $mobile, $gender, $image = null) {
        $password = $this->makeParentPassword($mobile);

        $parent = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'mobile'     => $mobile,
            'gender'     => $gender,
            'school_id'  => null
        );

        //NOTE : This line will return the old values if the user is already exists
        $user = $this->user->guardian()->where('email', $email)->first();
        if (!empty($image)) {
            $parent['image'] = UploadService::upload($image, 'guardian');
        }
        if (!empty($user)) {
            if ($user->image) {
                UploadService::delete($user->getRawOriginal('image'));
            }
            $user->update($parent);
        } else {
            $parent['password'] = Hash::make($password);
            $parent['email'] = $email;
            $user = $this->user->create($parent);
            $user->assignRole('Guardian');
        }

        return $user;
    }

    /**
     * @param string $first_name
     * @param string $last_name
     * @param string $admission_no
     * @param string|null $mobile
     * @param string $dob
     * @param string $gender
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile|null $image
     * @param int $classSectionID
     * @param string $admissionDate
     * @param null $current_address
     * @param null $permanent_address
     * @param int $sessionYearID
     * @param int $guardianID
     * @param array $extraFields
     * @param int $status
     * @return Model|null
     * @throws JsonException
     * @throws Throwable
     */

    public function createStudentUser(string $first_name, string $last_name, string $admission_no, string|null $mobile, string $dob, string $gender, \Symfony\Component\HttpFoundation\File\UploadedFile|null $image, int $classSectionID, string $admissionDate, $current_address = null, $permanent_address = null, int $sessionYearID, int $guardianID, array $extraFields = [], int $status) {
        $password = $this->makeStudentPassword($dob);
        //Create Student User First
        $user = $this->user->create([
            'first_name'        => $first_name,
            'last_name'         => $last_name,
            'email'             => $admission_no,
            'mobile'            => $mobile,
            'dob'               => date('Y-m-d', strtotime($dob)),
            'gender'            => $gender,
            'password'          => Hash::make($password),
            'school_id'         => Auth::user()->school_id,
            'image'             => $image,
            'status'            => $status,
            'current_address'   => $current_address,
            'permanent_address' => $permanent_address,
            'deleted_at'        => $status == 1 ? null : '1970-01-01 01:00:00'
        ]);
        $user->assignRole('Student');

        $roll_number_db = $this->student->builder()->select(DB::raw('max(roll_number)'))->where('class_section_id', $classSectionID)->first();
        $roll_number_db = $roll_number_db['max(roll_number)'];
        $roll_number = $roll_number_db + 1;

        $student = $this->student->create([
            'user_id'          => $user->id,
            'class_section_id' => $classSectionID,
            'admission_no'     => $admission_no,
            'roll_number'      => $roll_number,
            'admission_date'   => date('Y-m-d', strtotime($admissionDate)),
            'guardian_id'      => $guardianID,
            'session_year_id'  => $sessionYearID
        ]);

        // Store Extra Details
        $extraDetails = array();
        foreach ($extraFields as $fields) {
            $data = null;
            if (isset($fields['data'])) {
                $data = (is_array($fields['data']) ? json_encode($fields['data'], JSON_THROW_ON_ERROR) : $fields['data']);
            }
            $extraDetails[] = array(
                'student_id'    => $student->user_id,
                'form_field_id' => $fields['form_field_id'],
                'data'          => $data,
            );
        }
        if (!empty($extraDetails)) {
            $this->extraFormFields->createBulk($extraDetails);
        }

        $guardian = $this->user->guardian()->where('id', $guardianID)->firstOrFail();
        $parentPassword = $this->makeParentPassword($guardian->mobile);
        $this->sendRegistrationEmail($guardian->email, $guardian->full_name, $parentPassword, $user->full_name, $student->admission_no, $password);
        return $user;
    }

    /**
     * @param $userID
     * @param $first_name
     * @param $last_name
     * @param $mobile
     * @param $dob
     * @param $gender
     * @param $image
     * @param $sessionYearID
     * @param array $extraFields
     * @param null $guardianID
     * @param null $current_address
     * @param null $permanent_address
     * @return Model|null
     * @throws JsonException
     */
    public function updateStudentUser($userID, $first_name, $last_name, $mobile, $dob, $gender, $image, $sessionYearID, array $extraFields = [], $guardianID = null, $current_address = null, $permanent_address = null) {
        $studentUserData = array(
            'first_name'        => $first_name,
            'last_name'         => $last_name,
            'mobile'            => $mobile,
            'dob'               => date('Y-m-d', strtotime($dob)),
            'current_address'   => $current_address,
            'permanent_address' => $permanent_address,
            'gender'            => $gender,
        );

        if (!empty($current_address)) {
            $studentUserData['current_address'] = $current_address;
        }

        if (!empty($permanent_address)) {
            $studentUserData['permanent_address'] = $permanent_address;
        }


        if ($image) {
            $studentUserData['image'] = $image;
        }
        //Create Student User First
        $user = $this->user->update($userID, $studentUserData);

        $studentData = array(
            'guardian_id'     => $guardianID,
            'session_year_id' => $sessionYearID
        );

        $student = $this->student->update($user->student->id, $studentData);
        $extraDetails = [];
        foreach ($extraFields as $fields) {
            if ($fields['input_type'] == 'file') {
                if (isset($fields['data']) && $fields['data'] instanceof UploadedFile) {
                    $extraDetails[] = array(
                        'id'            => $fields['id'],
                        'student_id'    => $student->user_id,
                        'form_field_id' => $fields['form_field_id'],
                        'data'          => $fields['data']
                    );
                }
            } else {
                $data = null;
                if (isset($fields['data'])) {
                    $data = (is_array($fields['data']) ? json_encode($fields['data'], JSON_THROW_ON_ERROR) : $fields['data']);
                }
                $extraDetails[] = array(
                    'id'            => $fields['id'],
                    'student_id'    => $student->user_id,
                    'form_field_id' => $fields['form_field_id'],
                    'data'          => $data,
                );
            }
        }
        $this->extraFormFields->upsert($extraDetails, ['id'], ['data']);
        $user->assignRole('Student');
        DB::commit();
        return $user;
    }

    /**
     * @param $email
     * @param $name
     * @param $plainTextPassword
     * @param $childName
     * @param $childAdmissionNumber
     * @param $childPlainTextPassword
     * @return void
     * @throws Throwable
     */
    public function sendRegistrationEmail($email, $name, $plainTextPassword, $childName, $childAdmissionNumber, $childPlainTextPassword) {
        try {
            $school_name = Auth::user()->school->name;
            $data = [
                'subject'                => 'Welcome to ' . $school_name,
                'email'                  => $email,
                'name'                   => $name,
                'username'               => $email,
                'password'               => $plainTextPassword,
                'child_name'             => $childName,
                'child_admission_number' => $childAdmissionNumber,
                'child_password'         => $childPlainTextPassword,
            ];

            Mail::send('students.email', $data, static function ($message) use ($data) {
                $message->to($data['email'])->subject($data['subject']);
            });
        } catch (\Throwable $th) {

        }

    }

    /* Backup Code for Student CreateOrUpdate
    public function createOrUpdateStudentUser($first_name, $last_name, $admission_no, $mobile, $dob, $gender, $image, $classSectionID, $admissionDate, array $extraFields = [], $rollNumber = null, $guardianID = null) {
        $password = $this->makeStudentPassword($dob);
        $userExists = $this->user->builder()->where('email', $admission_no)->first();
        if (!empty($rollNumber)) {
            $rollNumber = $this->student->builder()->select(DB::raw('max(roll_number)'))->where('class_section_id', $classSectionID)->first();
            $rollNumber = $rollNumber['max(roll_number)'];
            ++$rollNumber;
        }
        $studentUserData = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $admission_no,
            'mobile'     => $mobile,
            'dob'        => date('Y-m-d', strtotime($dob)),
            'gender'     => $gender,
        );

        $studentData = array(
            'class_section_id' => $classSectionID,
            'admission_no'     => $admission_no,
            'roll_number'      => $rollNumber,
            'guardian_id'      => $guardianID
        );


        if (!$userExists) {
            //Create Student User
            $studentUserData = array_merge($studentUserData, [
                'password'  => Hash::make($password),
                'school_id' => Auth::user()->school_id,
                'image'     => $image
            ]);
            $user = $this->user->create($studentUserData);
            $user->assignRole('Student');

            $sessionYear = $this->sessionYear->default();
            $studentData = array_merge($studentData, [
                'user_id'         => $user->id,
                'admission_date'  => date('Y-m-d', strtotime($admissionDate)),
                'session_year_id' => $sessionYear->id
            ]);
            $student = $this->student->create($studentData);

        } else {
            //Update Student User
            if ($image) {
                $studentUserData['image'] = $image;
            }
            $user = $this->user->update($userExists->id, $studentUserData);
            $student = $this->student->update($user->student->id, $studentData);
        }

        // UPSERT EXTRA FIELDS
        $extraDetails = [];
        foreach ($extraFields as $fields) {
            // IF form_field_typ is file, and it's value is empty then skip that array
            if ($fields['input_type'] == 'file' && !isset($fields['data'])) {
                continue;
            }
            $data = null;
            if (isset($fields['data'])) {
                $data = (is_array($fields['data']) ? json_encode($fields['data'], JSON_THROW_ON_ERROR) : $fields['data']);
            }
            $extraDetails[] = array(
                'id'            => $fields['id'] ?? null,
                'student_id'    => $student->id,
                'form_field_id' => $fields['form_field_id'],
                'data'          => $data,
            );
        }

        $this->extraFormFields->upsert($extraDetails, ['student_id', 'form_field_id'], ['data']);
        DB::commit();

        if (!$userExists) {
            // Send Registration Email only if user is new. Already Existing user's parent will not receive email

                $guardian = $this->user->findById($guardianID);
                $password = $this->makeParentPassword($first_name, $mobile);
                $this->sendRegistrationEmail($guardian->email, $guardian->full_name, $password, $user->full_name, $student->admission_no, $password);
        }
        return $user;
    }*/
}
