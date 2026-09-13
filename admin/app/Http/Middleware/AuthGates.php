<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AuthGates
{
    public function handle($request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            $roleIds = DB::table('admin_role')
                ->join('roles', 'roles.id', '=', 'admin_role.role_id')
                ->where('admin_role.admin_id', $admin->id)
                ->where('roles.status', 1)
                ->pluck('roles.id');

            $activeAbilities = DB::table('permissions')
                ->where('status', 1)
                ->get(['view', 'create', 'edit', 'delete'])
                ->flatMap(fn ($permission) => [
                    $permission->view,
                    $permission->create,
                    $permission->edit,
                    $permission->delete,
                ])
                ->filter()
                ->unique()
                ->values();

            $grantedAbilities = $roleIds->isEmpty()
                ? collect()
                : DB::table('role_permissions')
                    ->whereIn('role_id', $roleIds)
                    ->whereIn('permission', $activeAbilities)
                    ->pluck('permission')
                    ->unique();

            foreach ($activeAbilities as $ability) {
                Gate::define($ability, fn () => $grantedAbilities->contains($ability));
            }
        }

        return $next($request);
    }
}
