<?php

use App\Support\AdminAccessCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $columns = Schema::getColumnListing('permissions');
        $permission = [
            'group_name' => 'Reports',
            'module' => 'Reports',
            'route_name' => 'reports',
            'view' => 'reports_view',
            'create' => null,
            'edit' => null,
            'delete' => null,
            'group_sort_order' => 90,
            'module_sort_order' => 10,
            'icon_class' => 'fas fa-chart-line',
            'status' => 1,
            'menu_status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('permissions')->updateOrInsert(
            ['route_name' => 'reports'],
            array_intersect_key($permission, array_flip($columns))
        );

        if (Schema::hasTable('roles') && Schema::hasTable('role_permissions')) {
            $roleIds = DB::table('roles')->where('name', 'Super Admin')->pluck('id');
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission' => 'reports_view'],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        AdminAccessCache::invalidate();
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->where('permission', 'reports_view')->delete();
        }
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('route_name', 'reports')->delete();
        }
        AdminAccessCache::invalidate();
    }
};
