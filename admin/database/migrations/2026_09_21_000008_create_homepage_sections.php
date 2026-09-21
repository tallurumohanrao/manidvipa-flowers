<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            Schema::create('homepage_sections', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150);
                $table->string('subtitle', 300)->nullable();
                $table->string('section_type', 40)->default('category_products');
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->text('product_ids')->nullable();
                $table->string('button_text', 80)->nullable();
                $table->string('button_url', 255)->nullable();
                $table->unsignedSmallInteger('max_items')->default(6);
                $table->unsignedInteger('priority')->default(0);
                $table->boolean('status')->default(true)->index();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('homepage_sections', 'product_ids')) {
            Schema::table('homepage_sections', function (Blueprint $table) {
                $table->text('product_ids')->nullable()->after('category_id');
            });
        }

        $this->ensurePermissionMenuColumns();
        $this->insertPermission();
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->whereIn('permission', [
                'home_sections_view',
                'home_sections_create',
                'home_sections_edit',
                'home_sections_delete',
            ])->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('route_name', 'home_sections')
                ->orWhere('module', 'Homepage Sections')
                ->delete();
        }

        Schema::dropIfExists('homepage_sections');
    }

    private function ensurePermissionMenuColumns(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'group_name')) {
                $table->string('group_name')->nullable()->after('module');
            }
            if (! Schema::hasColumn('permissions', 'route_name')) {
                $table->string('route_name')->nullable()->after('group_name');
            }
            if (! Schema::hasColumn('permissions', 'menu_status')) {
                $table->boolean('menu_status')->default(true)->after('delete');
            }
            if (! Schema::hasColumn('permissions', 'group_sort_order')) {
                $table->unsignedInteger('group_sort_order')->default(99)->after('menu_status');
            }
            if (! Schema::hasColumn('permissions', 'module_sort_order')) {
                $table->unsignedInteger('module_sort_order')->default(99)->after('group_sort_order');
            }
            if (! Schema::hasColumn('permissions', 'icon_class')) {
                $table->string('icon_class')->nullable()->after('module_sort_order');
            }
        });
    }

    private function insertPermission(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $permission = [
            'module' => 'Homepage Sections',
            'group_name' => 'Website',
            'route_name' => 'home_sections',
            'view' => 'home_sections_view',
            'create' => 'home_sections_create',
            'edit' => 'home_sections_edit',
            'delete' => 'home_sections_delete',
            'menu_status' => 1,
            'group_sort_order' => 20,
            'module_sort_order' => 25,
            'icon_class' => 'fas fa-layer-group',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $columns = Schema::getColumnListing('permissions');

        DB::table('permissions')->updateOrInsert(
            ['module' => 'Homepage Sections'],
            array_intersect_key($permission, array_flip($columns))
        );

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        foreach (DB::table('roles')->pluck('id') as $roleId) {
            foreach (['view', 'create', 'edit', 'delete'] as $abilityKey) {
                $ability = $permission[$abilityKey];
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission' => $ability],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        if (class_exists(\App\Support\AdminAccessCache::class)) {
            \App\Support\AdminAccessCache::invalidate();
        }
    }
};
