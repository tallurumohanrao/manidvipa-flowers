<?php

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
        $statusPermissions = [
            [
                'group_name' => 'Orders',
                'module' => 'Order Statuses',
                'route_name' => 'orderstatuses',
                'view' => 'orderstatuses_view',
                'create' => 'orderstatuses_create',
                'edit' => 'orderstatuses_edit',
                'delete' => 'orderstatuses_delete',
                'group_sort_order' => 1,
                'module_sort_order' => 20,
                'icon_class' => 'fas fa-list',
                'status' => 1,
                'menu_status' => 1,
            ],
            [
                'group_name' => 'Orders',
                'module' => 'Payment Statuses',
                'route_name' => 'paymentstatuses',
                'view' => 'paymentstatuses_view',
                'create' => 'paymentstatuses_create',
                'edit' => 'paymentstatuses_edit',
                'delete' => 'paymentstatuses_delete',
                'group_sort_order' => 1,
                'module_sort_order' => 30,
                'icon_class' => 'fas fa-credit-card',
                'status' => 1,
                'menu_status' => 1,
            ],
            [
                'group_name' => 'Orders',
                'module' => 'Shipping Statuses',
                'route_name' => 'shippingstatuses',
                'view' => 'shippingstatuses_view',
                'create' => 'shippingstatuses_create',
                'edit' => 'shippingstatuses_edit',
                'delete' => 'shippingstatuses_delete',
                'group_sort_order' => 1,
                'module_sort_order' => 40,
                'icon_class' => 'fas fa-shipping-fast',
                'status' => 1,
                'menu_status' => 1,
            ],
        ];

        foreach ($statusPermissions as $permission) {
            $permission['updated_at'] = $now;
            if (in_array('created_at', $columns, true)) {
                $permission['created_at'] = $now;
            }

            DB::table('permissions')->updateOrInsert(
                ['route_name' => $permission['route_name']],
                array_intersect_key($permission, array_flip($columns))
            );
        }

        $this->grantToSuperAdmin($statusPermissions, $now);
    }

    public function down(): void
    {
        // Keep these menu permissions active.
    }

    private function grantToSuperAdmin(array $statusPermissions, $now): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $roleIds = DB::table('roles')->where('name', 'Super Admin')->pluck('id');
        if ($roleIds->isEmpty()) {
            return;
        }

        foreach ($roleIds as $roleId) {
            foreach ($statusPermissions as $permission) {
                foreach (array_filter([$permission['view'], $permission['create'], $permission['edit'], $permission['delete']]) as $ability) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission' => $ability],
                        ['created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }
    }
};
