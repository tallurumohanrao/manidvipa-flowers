<?php

namespace App\Http\Middleware;

use App\Support\AdminAccessCache;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthGates
{
    public function handle($request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            $activeAbilities = AdminAccessCache::activePermissions()
                ->flatMap(fn ($permission) => [
                    $permission->view,
                    $permission->create,
                    $permission->edit,
                    $permission->delete,
                ])
                ->filter()
                ->unique()
                ->values();
            $grantedAbilities = AdminAccessCache::abilitiesForAdmin((int) $admin->id);

            foreach ($activeAbilities as $ability) {
                Gate::define($ability, fn () => $grantedAbilities->contains($ability));
            }
        }

        return $next($request);
    }
}
