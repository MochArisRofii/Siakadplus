<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallationSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {

        /**** Create All the Permission ****/
        $this->createPermissions();

        $this->createSuperAdminRole();

        $this->createSchoolAdminRole();

        $this->createTeacherRole();


        // System Features
        $this->systemFeatures();

        Role::updateOrCreate(['name' => 'School Admin']);

        //Change system version here
        Language::updateOrCreate(['id' => 1], ['name' => 'English', 'code' => 'en', 'file' => 'en.json', 'status' => 1, 'is_rtl' => 0]);
        //clear cache
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
    }


    public function createPermissions() {

        $permissions = [
            ...self::permission('role'),
            ...self::permission('medium'),
            ...self::permission('section'),
            ...self::permission('class'),
            ...self::permission('class-section'),
            ...self::permission('subject'),
            ...self::permission('teacher'),
            ...self::permission('guardian'),
            ...self::permission('session-year'),
            ...self::permission('student'),
            ...self::permission('timetable'),
            ...self::permission('attendance'),
            ...self::permission('holiday'),
            ...self::permission('announcement'),
            ...self::permission('slider'),
            ...self::permission('promote-student'),
            ...self::permission('language'),
            ...self::permission('lesson'),
            ...self::permission('topic'),
            ...self::permission('schools'),
            ...self::permission('form-fields'),
            ...self::permission('grade'),
            ...self::permission('package'),
            ...self::permission('addons'),
            ...self::permission('guidance'),


            ...self::permission('assignment'),
            ['name' => 'assignment-submission'],

            ...self::permission('exam'),
            ...self::permission('exam-timetable'),
            ['name' => 'exam-upload-marks'],
            ['name' => 'exam-result'],

            ['name' => 'system-setting-manage'],
            ['name' => 'fcm-setting-create'],
            ['name' => 'email-setting-create'],
            ['name' => 'privacy-policy'],
            ['name' => 'contact-us'],
            ['name' => 'about-us'],
            ['name' => 'terms-condition'],

            ['name' => 'class-teacher'],
            ['name' => 'student-reset-password'],
            ['name' => 'reset-password-list'],
            ['name' => 'student-change-password'],

            ['name' => 'update-admin-profile'],

            ['name' => 'fees-classes'],
            ['name' => 'fees-paid'],
            ['name' => 'fees-config'],

            ['name' => 'school-setting-manage'],
            ['name' => 'app-settings'],
            ['name' => 'subscription-view'],

            ...self::permission('online-exam'),
            ...self::permission('online-exam-questions'),
            ['name' => 'online-exam-result-list'],
            ...self::permission('fees-type'),
            ...self::permission('fees-class'),
            ...self::permission('role'),
            ...self::permission('staff'),
            ...self::permission('expense-category'),
            ...self::permission('expense'),
            ...self::permission('semester'),
            ...self::permission('payroll'),
            ...self::permission('stream'),
            ...self::permission('shift'),
            ...self::permission('leave'),
            ['name' => 'approve-leave'],
            ...self::permission('faqs'),


            ['name' => 'fcm-setting-manage'],
            // ['name' => 'front-site-setting'],

            ...self::permission('fees'),
            ...self::permission('transfer-student'),
            ...self::permission('gallery'),
            ...self::permission('notification'),

            ['name' => 'payment-settings'],

            ['name' => 'subscription-settings'],
            ['name' => 'subscription-change-bills'],
            ['name' => 'school-terms-condition'],

            ['name' => 'student-id-card-settings'],

            ['name' => 'subscription-bill-payment'],
            ['name' => 'web-settings'],

            

        ];
        $permissions = array_map(static function ($data) {
            $data['guard_name'] = 'web';
            return $data;
        }, $permissions);
        Permission::upsert($permissions, ['name'], ['name']);
    }


    public function createSuperAdminRole() {
        $role = Role::withoutGlobalScope('school')->updateOrCreate(['name' => 'Super Admin', 'custom_role' => 0, 'editable' => 0]);
        $superAdminHasAccessTo = [
            'schools-list',
            'schools-create',
            'schools-edit',
            'schools-delete',

            'package-list',
            'package-create',
            'package-edit',
            'package-delete',

            'email-setting-create',
            'privacy-policy',
            'terms-condition',
            'contact-us',
            'about-us',
            'fcm-setting-create',
            'language-list',
            'language-create',
            'language-edit',
            'language-delete',
            'system-setting-manage',
            'app-settings',

            'role-list',
            'role-create',
            'role-edit',
            'role-delete',

            'staff-list',
            'staff-create',
            'staff-edit',
            'staff-delete',

            'addons-list',
            'addons-create',
            'addons-edit',
            'addons-delete',

            'subscription-view',

            'faqs-list',
            'faqs-create',
            'faqs-edit',
            'faqs-delete',

            'fcm-setting-manage',

            // 'front-site-setting',

            'update-admin-profile',
            'subscription-settings',
            'subscription-change-bills',
            'school-terms-condition',

            'guidance-list',
            'guidance-create',
            'guidance-edit',
            'guidance-delete',

            'subscription-bill-payment',
            'web-settings'

        ];
        $role->syncPermissions($superAdminHasAccessTo);
    }


    public function createSchoolAdminRole() {
        $role = Role::withoutGlobalScope('school')->updateOrCreate(['name' => 'School Admin', 'custom_role' => 0, 'editable' => 0]);
        $SchoolAdminHasAccessTo = [
            'medium-list',
            'medium-create',
            'medium-edit',
            'medium-delete',

            'section-list',
            'section-create',
            'section-edit',
            'section-delete',

            'class-list',
            'class-create',
            'class-edit',
            'class-delete',

            'class-section-list',
            'class-section-create',
            'class-section-edit',
            'class-section-delete',

            'subject-list',
            'subject-create',
            'subject-edit',
            'subject-delete',

            'teacher-list',
            'teacher-create',
            'teacher-edit',
            'teacher-delete',

            'guardian-list',
            'guardian-create',
            'guardian-edit',
            'guardian-delete',

            'session-year-list',
            'session-year-create',
            'session-year-edit',
            'session-year-delete',

            'student-list',
            'student-create',
            'student-edit',
            'student-delete',

            'timetable-list',
            'timetable-create',
            'timetable-edit',
            'timetable-delete',

            'attendance-list',

            'holiday-list',
            'holiday-create',
            'holiday-edit',
            'holiday-delete',

            'announcement-list',
            'announcement-create',
            'announcement-edit',
            'announcement-delete',

            'slider-list',
            'slider-create',
            'slider-edit',
            'slider-delete',

            'exam-create',
            'exam-list',
            'exam-edit',
            'exam-delete',

            'exam-timetable-create',
            'exam-timetable-list',
            'exam-timetable-delete',

            'exam-result',

            'assignment-submission',

            'student-reset-password',
            'reset-password-list',
            'student-change-password',

            'promote-student-list',
            'promote-student-create',
            'promote-student-edit',
            'promote-student-delete',

            'transfer-student-list',
            'transfer-student-create',
            'transfer-student-edit',
            'transfer-student-delete',

            'update-admin-profile',

            'fees-paid',
            'fees-config',

            'form-fields-list',
            'form-fields-create',
            'form-fields-edit',
            'form-fields-delete',

            'grade-create',
            'grade-list',
            'grade-edit',
            'grade-delete',

            'school-setting-manage',

            'fees-type-list',
            'fees-type-create',
            'fees-type-edit',
            'fees-type-delete',

            'fees-class-list',
            'fees-class-create',
            'fees-class-edit',
            'fees-class-delete',


            'online-exam-create',
            'online-exam-list',
            'online-exam-edit',
            'online-exam-delete',
            'online-exam-questions-create',
            'online-exam-questions-list',
            'online-exam-questions-edit',
            'online-exam-questions-delete',
            'online-exam-result-list',

            'role-list',
            'role-create',
            'role-edit',
            'role-delete',

            'staff-list',
            'staff-create',
            'staff-edit',
            'staff-delete',

            'expense-category-list',
            'expense-category-create',
            'expense-category-edit',
            'expense-category-delete',

            'expense-list',
            'expense-create',
            'expense-edit',
            'expense-delete',

            'fees-list',
            'fees-create',
            'fees-edit',
            'fees-delete',

            'semester-list',
            'semester-create',
            'semester-edit',
            'semester-delete',

            'payroll-list',
            'payroll-create',
            'payroll-edit',
            'payroll-delete',

            'stream-list',
            'stream-create',
            'stream-edit',
            'stream-delete',

            'shift-list',
            'shift-create',
            'shift-edit',
            'shift-delete',

            'approve-leave',
            'student-id-card-settings',

            'gallery-list',
            'gallery-create',
            'gallery-edit',
            'gallery-delete',

            'notification-list',
            'notification-create',            
            'notification-delete',

        ];

        $role->syncPermissions($SchoolAdminHasAccessTo);
    }

    public function createTeacherRole() {
        //Add Teacher Role
        $teacher_role = Role::updateOrCreate(['name' => 'Teacher']);
        $TeacherHasAccessTo = [
            'class-section-list',
            'student-list',
            'timetable-list',

            'attendance-list',
            'attendance-create',
            'attendance-edit',
            'attendance-delete',

            'holiday-list',

            'announcement-list',
            'announcement-create',
            'announcement-edit',
            'announcement-delete',

            'assignment-create',
            'assignment-list',
            'assignment-edit',
            'assignment-delete',
            'assignment-submission',

            'lesson-list',
            'lesson-create',
            'lesson-edit',
            'lesson-delete',

            'topic-list',
            'topic-create',
            'topic-edit',
            'topic-delete',

            'online-exam-create',
            'online-exam-list',
            'online-exam-edit',
            'online-exam-delete',
            'online-exam-questions-create',
            'online-exam-questions-list',
            'online-exam-questions-edit',
            'online-exam-questions-delete',
            'online-exam-result-list',

            'exam-upload-marks',
            'exam-result',

            'leave-list',
            'leave-create',
            'leave-edit',
            'leave-delete',
        ];
        $teacher_role->syncPermissions($TeacherHasAccessTo);
    }


    /**
     * Generate List , Create , Edit , Delete Permissions
     * @param $prefix
     * @param array $customPermissions - Prefix will be set Automatically
     * @return string[]
     */
    public static function permission($prefix, array $customPermissions = []) {

        $list = [["name" => $prefix . '-list']];
        $create = [["name" => $prefix . '-create']];
        $edit = [["name" => $prefix . '-edit']];
        $delete = [["name" => $prefix . '-delete']];

        $finalArray = array_merge($list, $create, $edit, $delete);
        foreach ($customPermissions as $customPermission) {
            $finalArray[] = ["name" => $prefix . "-" . $customPermission];
        }
        return $finalArray;
    }

    // System Features
    public function systemFeatures() {
        $features = [
            ['name' => 'Student Management', 'is_default' => 1],
            ['name' => 'Academics Management', 'is_default' => 1],
            ['name' => 'Slider Management', 'is_default' => 0],
            ['name' => 'Teacher Management', 'is_default' => 1],
            ['name' => 'Session Year Management', 'is_default' => 1],
            ['name' => 'Holiday Management', 'is_default' => 0],
            ['name' => 'Timetable Management', 'is_default' => 0],
            ['name' => 'Attendance Management', 'is_default' => 0],
            ['name' => 'Exam Management', 'is_default' => 0],
            ['name' => 'Lesson Management', 'is_default' => 0],
            ['name' => 'Assignment Management', 'is_default' => 0],
            ['name' => 'Announcement Management', 'is_default' => 0],
            ['name' => 'Staff Management', 'is_default' => 0],
            ['name' => 'Expense Management', 'is_default' => 0],
            ['name' => 'Staff Leave Management', 'is_default' => 0],
            ['name' => 'Fees Management', 'is_default' => 0],
            ['name' => 'School Gallery Management', 'is_default' => 0],
        ];

        foreach ($features as $key => $feature) {
            Feature::updateOrCreate(['id' => ($key + 1)], $feature);
        }
    }
}
