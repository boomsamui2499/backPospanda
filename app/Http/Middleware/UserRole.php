<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;



class UserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $role)
    {
        $permission_lv = [
            'cashier'   => 1,
            'manager'   => 2,
            'owner'     => 3,
            'test'     => 2,
        ];

        $user = auth()->user();
        $user_permission_lv =  $permission_lv[$user->permission];
        $guard_permission_lv =  $permission_lv[$role];

        if ($user_permission_lv >= $guard_permission_lv) {
            return $next($request);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Access denied."], 403);
        }
    }
}
