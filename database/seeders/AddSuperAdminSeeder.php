<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AddSuperAdminSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        //Add Super Admin User
        $super_admin_role = Role::where('name', 'Super Admin')->first();
        $user = User::updateOrCreate(['id' => 1], [
            'first_name' => 'super',
            'last_name'  => 'admin',
            'email'      => 'superadmin@gmail.com',
            'password'   => Hash::make('superadmin'),
            'gender'     => 'male',
            'image'      => 'logo.svg',
            'mobile'     => ""
        ]);
        $user->assignRole([$super_admin_role->id]);

//        SessionYear::updateOrCreate(['id' => 1], [
//            'name'       => '2022-23',
//            'default'    => 1,
//            'start_date' => '2022-06-01',
//            'end_date'   => '2023-04-30',
//        ]);

        SystemSetting::upsert([
            ["id" => 1, "name" => "time_zone", "data" => "Asia/Kolkata", "type" => "string"],
            ["id" => 2, "name" => "date_format", "data" => "d-m-Y", "type" => "date"],
            ["id" => 3, "name" => "time_format", "data" => "h:i A", "type" => "time"],
            ["id" => 4, "name" => "theme_color", "data" => "#22577A", "type" => "string"],
            ["id" => 5, "name" => "session_year", "data" => 1, "type" => "string"],
            ["id" => 6, "name" => "system_version", "data" => "1.2.0", "type" => "string"],
            ["id" => 7, "name" => "email_verified", "data" => 0, "type" => "boolean"],
            ["id" => 8, "name" => "subscription_alert", "data" => 7, "type" => "integer"],
            ["id" => 9, "name" => "currency_code", "data" => "USD", "type" => "string"],
            ["id" => 10, "name" => "currency_symbol", "data" => "$", "type" => "string"],
            ["id" => 11, "name" => "additional_billing_days", "data" => "5", "type" => "integer"],
            ["id" => 12, "name" => "system_name", "data" => "eSchool Saas - School Management System", "type" => "string"],
            ["id" => 13, "name" => "address", "data" => "#262-263, Time Square Empire, SH 42 Mirjapar highway, Bhuj - Kutch 370001 Gujarat India.", "type" => "string"],
            ["id" => 14, "name" => "billing_cycle_in_days", "data" => "30", "type" => "integer"],
            ["id" => 15, "name" => "current_plan_expiry_warning_days", "data" => "7", "type" => "integer"],
            ["id" => 16, "name" => "front_site_theme_color", "data" => "#e9f9f3", "type" => "text"],
            ["id" => 17, "name" => "primary_color", "data" => "#3ccb9b", "type" => "text"],
            ["id" => 18, "name" => "secondary_color", "data" => "#245a7f", "type" => "text"],
            ["id" => 19, "name" => "short_description", "data" => "eSchool-Saas - Manage Your School", "type" => "text"],
            ["id" => 20, "name" => "facebook", "data" => "https://www.facebook.com/wrteam.in/", "type" => "text"],
            ["id" => 21, "name" => "instagram", "data" => "https://www.instagram.com/wrteam.in/", "type" => "text"],
            ["id" => 22, "name" => "linkedin", "data" => "https://in.linkedin.com/company/wrteam", "type" => "text"],
            ["id" => 23, "name" => "footer_text", "data" => "<p>&copy;&nbsp;<strong><a href='https://wrteam.in/' target='_blank' rel='noopener noreferrer'>WRTeam</a></strong>. All Rights Reserved</p>", "type" => "text"],
            ["id" => 24, "name" => "tagline", "data" => "We Provide the best Education", "type" => "text"],

        ], ['id'], ['name', 'data', 'type']);

        $systemSettings = [
            [
                'name' => 'hero_title_1',
                'data' => 'Opt for eSchool Saas 14+ robust features for an enhanced educational experience.',
                'type' => 'text'
            ],
            [
                'name' => 'hero_title_2',
                'data' => 'Top Rated Instructors',
                'type' => 'text'
            ],
            [
                'name' => 'about_us_title',
                'data' => 'A modern and unique style',
                'type' => 'text'
            ],
            [
                'name' => 'about_us_heading',
                'data' => 'Why it is best?',
                'type' => 'text'
            ],
            [
                'name' => 'about_us_description',
                'data' => 'eSchool is the pinnacle of school management, offering advanced technology, user-friendly features, and personalized solutions. It simplifies communication, streamlines administrative tasks, and elevates the educational experience for all stakeholders. With eSchool, excellence in education management is guaranteed.',
                'type' => 'text'
            ],
            [
                'name' => 'about_us_points',
                'data' => 'Affordable price,Easy to manage admin panel,Data Security',
                'type' => 'text'
            ],
            [
                'name' => 'custom_package_status',
                'data' => '1',
                'type' => 'text'
            ],
            [
                'name' => 'custom_package_description',
                'data' => 'Tailor your experience with our custom package options. From personalized services to bespoke solutions, we offer flexibility to meet your unique needs.',
                'type' => 'text'
            ],
            [
                'name' => 'download_our_app_description',
                'data' => 'Join the ranks of true trivia champions and quench your thirst for knowledge with Masters of Trivia - the ultimate quiz app designed to test your wits and unlock a world of fun facts. Challenge your brain, compete with friends, and discover fascinating tidbits from diverse categories. Don\'t miss out on the exhilarating experience that awaits you - get started now!Join the ranks of true trivia champions and quench your thirst for knowledge with Masters of Trivia - the ultimate quiz app designed to test your wits and unlock a world of fun facts.',
                'type' => 'text'
            ],
            [
                'name' => 'theme_primary_color',
                'data' => '#56cc99',
                'type' => 'text'
            ],
            [
                'name' => 'theme_secondary_color',
                'data' => '#215679',
                'type' => 'text'
            ],
            [
                'name' => 'theme_secondary_color_1',
                'data' => '#38a3a5',
                'type' => 'text'
            ],
            [
                'name' => 'theme_primary_background_color',
                'data' => '#f2f5f7',
                'type' => 'text'
            ],
            [
                'name' => 'theme_text_secondary_color',
                'data' => '#5c788c',
                'type' => 'text'
            ],
            [
                'name' => 'tag_line',
                'data' => 'Transform School Management With eSchool SaaS',
                'type' => 'text'
            ],
            [
                'name' => 'mobile',
                'data' => 'xxxxxxxxxx',
                'type' => 'text'
            ],
            [
                'name' => 'hero_description',
                'data' => 'Experience the future of education with our eSchool SaaS platform. Streamline attendance, assignments, exams, and more. Elevate your school\'s efficiency and engagement.',
                'type' => 'text'
            ],
        ];

        SystemSetting::upsert($systemSettings, ["name"], ["data","type"]);
        Cache::flush();
    }
}
