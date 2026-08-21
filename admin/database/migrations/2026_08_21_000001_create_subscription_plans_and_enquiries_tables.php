<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('business_type', 80)->default('business');
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->decimal('starting_price', 10, 2)->default(0);
            $table->string('price_suffix', 50)->default('/ month');
            $table->string('billing_cycle', 50)->default('Monthly');
            $table->string('delivery_frequency', 120)->nullable();
            $table->text('included_items')->nullable();
            $table->text('features')->nullable();
            $table->text('ideal_for')->nullable();
            $table->string('cta_label', 80)->default('Request Plan');
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('subscription_enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('plan_title')->nullable();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('business_type', 80)->nullable();
            $table->string('location')->nullable();
            $table->string('preferred_delivery_time')->nullable();
            $table->string('estimated_quantity')->nullable();
            $table->text('message')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('source', 80)->default('website');
            $table->string('status', 40)->default('New');
            $table->timestamps();
        });

        $this->ensurePermissionMenuColumns();
        $this->insertSubscriptionPermissions();
        $this->insertDefaultPlans();
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('route_name', ['subscriptionplans', 'subscriptionenquiries'])
            ->orWhereIn('module', ['Subscription Plans', 'Subscription Enquiries'])
            ->delete();

        Schema::dropIfExists('subscription_enquiries');
        Schema::dropIfExists('subscription_plans');
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

    private function insertSubscriptionPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $columns = Schema::getColumnListing('permissions');
        $permissions = [
            [
                'module' => 'Subscription Plans',
                'route_name' => 'subscriptionplans',
                'view' => 'subscriptionplans_view',
                'create' => 'subscriptionplans_create',
                'edit' => 'subscriptionplans_edit',
                'delete' => 'subscriptionplans_delete',
                'group_name' => 'Business',
                'menu_status' => 1,
                'group_sort_order' => 35,
                'module_sort_order' => 10,
                'icon_class' => 'fas fa-sync-alt',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module' => 'Subscription Enquiries',
                'route_name' => 'subscriptionenquiries',
                'view' => 'subscriptionenquiries_view',
                'create' => null,
                'edit' => 'subscriptionenquiries_edit',
                'delete' => 'subscriptionenquiries_delete',
                'group_name' => 'Business',
                'menu_status' => 1,
                'group_sort_order' => 35,
                'module_sort_order' => 20,
                'icon_class' => 'fas fa-clipboard-list',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $permission) {
            $payload = array_intersect_key($permission, array_flip($columns));
            DB::table('permissions')->updateOrInsert(
                ['module' => $permission['module']],
                $payload
            );
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $roleIds = DB::table('roles')->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissions as $permission) {
                foreach (['view', 'create', 'edit', 'delete'] as $abilityKey) {
                    $ability = $permission[$abilityKey] ?? null;

                    if (! $ability) {
                        continue;
                    }

                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission' => $ability],
                        ['created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }
    }

    private function insertDefaultPlans(): void
    {
        if (DB::table('subscription_plans')->exists()) {
            return;
        }

        $now = now();
        DB::table('subscription_plans')->insert([
            [
                'title' => 'Corporate Office Flower Subscription',
                'slug' => 'corporate-office-flower-subscription',
                'business_type' => 'Corporate Offices',
                'short_description' => 'Fresh reception, desk and meeting-room flowers for offices.',
                'description' => 'A reliable flower supply plan for corporate offices that need reception flowers, meeting-room arrangements and regular festive support.',
                'starting_price' => 2499,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => 'Daily or 3 days per week',
                'included_items' => "Reception flower bowl\nDesk flowers\nFestival add-ons\nMonthly billing support",
                'features' => "Dedicated WhatsApp support\nCustom delivery timing\nBulk quantity planning\nPause or upgrade anytime",
                'ideal_for' => "Corporate offices\nCo-working spaces\nShowrooms\nReception areas",
                'cta_label' => 'Request Corporate Plan',
                'sort_order' => 10,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Hospital Fresh Flowers Supply',
                'slug' => 'hospital-fresh-flowers-supply',
                'business_type' => 'Hospitals',
                'short_description' => 'Fresh flowers for reception, prayer rooms and patient-care spaces.',
                'description' => 'A practical flower plan for hospitals that need clean, fresh and consistent floral supply for reception, pooja areas and visitor spaces.',
                'starting_price' => 2999,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => 'Morning delivery, daily or alternate days',
                'included_items' => "Reception arrangements\nPuja flowers and leaves\nFresh replacement schedule\nBulk festive support",
                'features' => "Hygienic packing\nFixed delivery window\nCustom quantity\nMonthly billing support",
                'ideal_for' => "Hospitals\nClinics\nWellness centers\nDiagnostic centers",
                'cta_label' => 'Request Hospital Plan',
                'sort_order' => 20,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Temple Daily Flower Subscription',
                'slug' => 'temple-daily-flower-subscription',
                'business_type' => 'Temples',
                'short_description' => 'Bulk pooja flowers, garlands and leaves delivered every morning.',
                'description' => 'A dependable temple subscription for daily pooja flowers, garlands, patri and festival bulk requirements.',
                'starting_price' => 1199,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => 'Daily morning delivery',
                'included_items' => "Loose flowers\nGarlands\nPatri and leaves\nFestival bulk planning",
                'features' => "Fresh morning sourcing\nCustom deity-wise flowers\nBulk quantity support\nSame-day adjustment support",
                'ideal_for' => "Temples\nPuja mandirs\nCommunity prayer halls",
                'cta_label' => 'Request Temple Plan',
                'sort_order' => 30,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Hotel Lobby Flower Plan',
                'slug' => 'hotel-lobby-flower-plan',
                'business_type' => 'Hotels',
                'short_description' => 'Elegant lobby and front-desk flowers for hospitality spaces.',
                'description' => 'A premium flower subscription for hotels that need fresh lobby arrangements, restaurant table flowers and event support.',
                'starting_price' => 3999,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => 'Daily, alternate day or weekly refresh',
                'included_items' => "Lobby arrangements\nFront desk flowers\nRestaurant table flowers\nEvent upgrade support",
                'features' => "Premium flower options\nRefresh schedule\nCustom arrangement size\nDedicated support",
                'ideal_for' => "Hotels\nRestaurants\nBanquet halls\nGuest houses",
                'cta_label' => 'Request Hotel Plan',
                'sort_order' => 40,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Business Bulk Flower Plan',
                'slug' => 'business-bulk-flower-plan',
                'business_type' => 'Business Bulk',
                'short_description' => 'Custom bulk flower supply for regular business requirements.',
                'description' => 'A flexible plan for businesses that need bulk flowers, garlands or custom flower boxes based on weekly or monthly usage.',
                'starting_price' => 4999,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Custom',
                'delivery_frequency' => 'As per business requirement',
                'included_items' => "Bulk loose flowers\nCustom garlands\nEvent support\nMonthly planning",
                'features' => "Custom price quote\nVolume-based planning\nPriority sourcing\nDedicated WhatsApp support",
                'ideal_for' => "Corporate events\nRetail shops\nFunction halls\nBulk buyers",
                'cta_label' => 'Request Bulk Quote',
                'sort_order' => 50,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
};
