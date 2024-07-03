<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckChild {
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next) {
        $children = $request->user()->guardianRelationChild()->where('id', $request->child_id)->first();
        if (empty($children)) {
            return response()->json(array(
                'error'   => true,
                'message' => "Invalid Child ID Passed.",
                'code'    => 105,
            ));
        }
        return $next($request);
    }
}
