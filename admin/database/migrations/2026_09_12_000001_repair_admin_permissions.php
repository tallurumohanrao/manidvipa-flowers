<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureRolePermissionsTable();
        $this->migrateLegacyRolePermissions();
        $this->ensurePermissionsModule();

        foreach (['admins', 'roles', 'permissions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'status')) {
                DB::table($table)->where('status', 2)->update(['status' => 0]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->whereIn('permission', [
                    'permissions_view',
                    'permissions_create',
                    'permissions_edit',
                    'permissions_delete',
                ])
                ->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('module', 'Permissions')->delete();
        }
    }

    private function ensureRolePermissionsTable(): void
    {
        if (Schema::hasTable('role_permissions')) {
            return;
        }

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id')->index();
            $table->string('permission', 100)->index();
            $table->timestamps();
            $table->unique(['role_id', 'permission']);
        });
    }

    private function migrateLegacyRolePermissions(): void
    {
        if (! Schema::hasTable('permission_role') || ! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $legacyRows = DB::table('permission_role')
            ->join('permissions', 'permissions.id', '=', 'permission_role.permission_id')
            ->get(['permission_role.role_id', 'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete']);

        foreach ($legacyRows as $row) {
            foreach (array_filter([$row->view, $row->create, $row->edit, $row->delete]) as $ability) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $row->role_id, 'permission' => $ability],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    private function ensurePermissionsModule(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $columns = Schema::getColumnListing('permissions');
        $permission = [
            'module' => 'Permissions',
            'group_name' => 'Users',
            'route_name' => 'permissions',
            'view' => 'permissions_view',
            'create' => 'permissions_create',
            'edit' => 'permissions_edit',
            'delete' => 'permissions_delete',
            'menu_status' => 1,
            'group_sort_order' => 80,
            'module_sort_order' => 30,
            'icon_class' => 'fas fa-key',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('permissions')->updateOrInsert(
            ['module' => 'Permissions'],
            array_intersect_key($permission, array_flip($columns))
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $privilegedRoleIds = DB::table('roles')
            ->where('name', 'Super Admin')
            ->pluck('id')
            ->merge(
                DB::table('role_permissions')
                    ->whereIn('permission', ['roles_edit', 'admins_edit'])
                    ->pluck('role_id')
            )
            ->unique();

        foreach ($privilegedRoleIds as $roleId) {
            foreach (['permissions_view', 'permissions_create', 'permissions_edit', 'permissions_delete'] as $ability) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission' => $ability],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
};
