<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('price_update_logs')) {
            Schema::create('price_update_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('product_weight_id')->nullable()->index();
                $table->string('product_title')->nullable();
                $table->string('weight_name')->nullable();
                $table->decimal('old_sell_price', 10, 2)->nullable();
                $table->decimal('new_sell_price', 10, 2)->nullable();
                $table->decimal('old_list_price', 10, 2)->nullable();
                $table->decimal('new_list_price', 10, 2)->nullable();
                $table->string('update_source', 50)->default('manual');
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        $this->ensurePermissionMenuColumns();
        $this->insertDailyPricePermission();
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->whereIn('permission', [
                    'dailyprices_view',
                    'dailyprices_create',
                    'dailyprices_edit',
                    'dailyprices_delete',
                ])
                ->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('route_name', 'dailyprices')
                ->orWhere('module', 'Daily Price Update')
                ->delete();
        }

        Schema::dropIfExists('price_update_logs');
    }

    private function ensurePermissionMenuColumns(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        if (! Schema::hasColumn('permissions', 'group_name')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('group_name')->nullable()->after('module');
            });
        }

        if (! Schema::hasColumn('permissions', 'route_name')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('route_name')->nullable()->after('group_name');
            });
        }

        if (! Schema::hasColumn('permissions', 'menu_status')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->boolean('menu_status')->default(true)->after('delete');
            });
        }

        if (! Schema::hasColumn('permissions', 'group_sort_order')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->unsignedInteger('group_sort_order')->default(99)->after('menu_status');
            });
        }

        if (! Schema::hasColumn('permissions', 'module_sort_order')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->unsignedInteger('module_sort_order')->default(99)->after('group_sort_order');
            });
        }

        if (! Schema::hasColumn('permissions', 'icon_class')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('icon_class')->nullable()->after('module_sort_order');
            });
        }
    }

    private function insertDailyPricePermission(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $columns = Schema::getColumnListing('permissions');
        $permission = [
            'module' => 'Daily Price Update',
            'route_name' => 'dailyprices',
            'view' => 'dailyprices_view',
            'create' => null,
            'edit' => 'dailyprices_edit',
            'delete' => null,
            'group_name' => 'Products',
            'menu_status' => 1,
            'group_sort_order' => 20,
            'module_sort_order' => 5,
            'icon_class' => 'fas fa-rupee-sign',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('permissions')->updateOrInsert(
            ['module' => $permission['module']],
            array_intersect_key($permission, array_flip($columns))
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $roleIds = DB::table('roles')->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach (['dailyprices_view', 'dailyprices_edit'] as $ability) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission' => $ability],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
};
