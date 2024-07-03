<?php

namespace App\Providers;

use App\Services\CachingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        $cache = app(CachingService::class);

        /*** Header File ***/
        View::composer('layouts.header', static function (\Illuminate\View\View $view) use ($cache) {
            $view->with('systemSettings', $cache->getSystemSettings());
            $view->with('languages', $cache->getLanguages());

            if (!empty(Auth::user()->school_id)) {
                $view->with('sessionYear', $cache->getDefaultSessionYear());
                $view->with('schoolSettings', $cache->getSchoolSettings());
                $view->with('semester', $cache->getDefaultSemesterData());
            }
        });

        /*** Include File ***/
        View::composer('layouts.include', static function (\Illuminate\View\View $view) use ($cache) {
            $view->with('systemSettings', $cache->getSystemSettings());
            if (!empty(Auth::user()->school_id)) {
                $view->with('schoolSettings', $cache->getSchoolSettings());
            }
        });
        View::composer('auth.login', static function (\Illuminate\View\View $view) use ($cache) {
            $view->with('systemSettings', $cache->getSystemSettings());
        });
        /*** Email  ***/

        View::composer('auth.passwords.email', static function (\Illuminate\View\View $view) use ($cache) {
            $view->with('systemSettings', $cache->getSystemSettings());
        });
        View::composer('auth.passwords.reset', static function (\Illuminate\View\View $view) use ($cache) {
            $view->with('systemSettings', $cache->getSystemSettings());
        });
        View::composer('auth.login', static function (\Illuminate\View\View $view) use ($cache) {
            $view->with('systemSettings', $cache->getSystemSettings());
        });
        View::composer('home', static function (\Illuminate\View\View $view) use ($cache) {
            $view->with('systemSettings', $cache->getSystemSettings());
        });

        View::composer('layouts.master', static function (\Illuminate\View\View $view) use ($cache) {
            $view->with('systemSettings', $cache->getSystemSettings());
        });

        /*** Footer File ***/
        View::composer('layouts.footer_js', static function (\Illuminate\View\View $view) use ($cache) {
            $view->with('systemSettings', $cache->getSystemSettings());
            if (!empty(Auth::user()->school_id)) {
                $view->with('schoolSettings', $cache->getSchoolSettings());
            }
        });
    }
}
