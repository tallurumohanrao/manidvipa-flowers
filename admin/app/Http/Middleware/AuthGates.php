<?php

namespace App\Http\Middleware;
use Closure,DB;
use Illuminate\Support\Facades\Gate;

class AuthGates
{
    public function handle($request, Closure $next)
    {
        $admin = \Auth::user();
        if($admin){
            $roles = DB::table('admin_role')->where('admin_id',$admin->id)->get()->pluck('role_id')->toArray();
            if($roles){
                $permissions = DB::table('role_permissions')->whereIn('role_id',$roles)->get();
                foreach ($permissions as $permission) {
                    Gate::define($permission->permission, function () {
                        return true;
                    });
                }
            }
        }
        return $next($request);
    }
}
