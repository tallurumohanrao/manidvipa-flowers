<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class AdminAccessCache
{
    private const VERSION_KEY = 'admin_access_version';

    public static function activePermissions(): Collection
    {
        $version = self::version();

        return Cache::rememberForever('admin_access_permissions_'.$version, function () {
            return DB::table('permissions')
                ->where('status', 1)
                ->orderBy('group_sort_order')
                ->orderBy('module_sort_order')
                ->get();
        });
    }

    public static function abilitiesForAdmin(int $adminId): Collection
    {
        $version = self::version();
        $activeAbilities = self::activeAbilities();

        if ($activeAbilities->isEmpty()) {
            return collect();
        }

        return Cache::rememberForever('admin_access_abilities_'.$version.'_'.$adminId, function () use ($adminId, $activeAbilities) {
            return DB::table('admin_role')
                ->join('roles', 'roles.id', '=', 'admin_role.role_id')
                ->join('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
                ->where('admin_role.admin_id', $adminId)
                ->where('roles.status', 1)
                ->whereIn('role_permissions.permission', $activeAbilities)
                ->distinct()
                ->pluck('role_permissions.permission');
        });
    }

    public static function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function activeAbilities(): Collection
    {
        return self::activePermissions()
            ->flatMap(fn ($permission) => [
                $permission->view,
                $permission->create,
                $permission->edit,
                $permission->delete,
            ])
            ->filter()
            ->unique()
            ->values();
    }

    private static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }
}
