<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSchoolStatus {

    public function handle(Request $request, Closure $next) {
        $user = Auth::user();
        if (isset(Auth::user()->school)) {
        // Check Student, Teacher status for app
        $requestURL = $request->getRequestUri();
        if (stripos($requestURL, 'api') !== false) { // Api routes
            if (Auth::user()->hasRole('Student') || Auth::user()->hasRole('Teacher')) {
                if ($user->school->status == 0 || $user->status == 0) {
                    $user = $request->user();
                    $user->fcm_id = '';
                    $user->save();
                    $user->currentAccessToken()->delete();
                    return response()->json(['error' => true, 'message' => trans('your_account_has_been_deactivated_please_contact_admin')]);
                }
            }
        } else {
            if ($user->hasRole('Student') || $user->hasRole('Parent')) {
                Auth::logout();
                $request->session()->flush();
                $request->session()->regenerate();
                return redirect()->route('login')->withErrors(trans('no_permission_message'));
            }

            if ($user->school->status == 0) {
                Auth::logout();
                $request->session()->flush();
                $request->session()->regenerate();
                return redirect()->route('login')->withErrors(trans('your_account_has_been_deactivated_please_contact_admin'));
            }
        }
        }

        return $next($request);
    }
}
