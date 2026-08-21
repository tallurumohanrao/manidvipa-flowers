<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_plans', 'subscription_type')) {
                $table->string('subscription_type', 80)->default('Premium Arrangements')->after('business_type');
            }

            if (! Schema::hasColumn('subscription_plans', 'flower_grade')) {
                $table->string('flower_grade', 80)->nullable()->after('subscription_type');
            }

            if (! Schema::hasColumn('subscription_plans', 'included_quantity_text')) {
                $table->string('included_quantity_text', 255)->nullable()->after('delivery_frequency');
            }

            if (! Schema::hasColumn('subscription_plans', 'included_arrangement_count')) {
                $table->string('included_arrangement_count', 255)->nullable()->after('included_quantity_text');
            }

            if (! Schema::hasColumn('subscription_plans', 'arrangement_size')) {
                $table->string('arrangement_size', 120)->nullable()->after('included_arrangement_count');
            }

            if (! Schema::hasColumn('subscription_plans', 'refresh_frequency')) {
                $table->string('refresh_frequency', 120)->nullable()->after('arrangement_size');
            }

            if (! Schema::hasColumn('subscription_plans', 'flower_examples')) {
                $table->text('flower_examples')->nullable()->after('refresh_frequency');
            }

            if (! Schema::hasColumn('subscription_plans', 'extra_quantity_note')) {
                $table->text('extra_quantity_note')->nullable()->after('flower_examples');
            }

            if (! Schema::hasColumn('subscription_plans', 'minimum_commitment')) {
                $table->string('minimum_commitment', 120)->nullable()->after('extra_quantity_note');
            }
        });

        $this->upsertClearPackages();
        Cache::forget('api_subscription_plans');
        Cache::forget('api_subscription_plans_featured');
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            foreach ([
                'minimum_commitment',
                'extra_quantity_note',
                'flower_examples',
                'refresh_frequency',
                'arrangement_size',
                'included_arrangement_count',
                'included_quantity_text',
                'flower_grade',
                'subscription_type',
            ] as $column) {
                if (Schema::hasColumn('subscription_plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Cache::forget('api_subscription_plans');
        Cache::forget('api_subscription_plans_featured');
    }

    private function upsertClearPackages(): void
    {
        $now = now();
        $plans = [
            [
                'title' => 'Corporate Starter Premium Arrangement',
                'slug' => 'corporate-office-flower-subscription',
                'business_type' => 'Corporate Offices',
                'subscription_type' => 'Premium Arrangements',
                'flower_grade' => 'Premium',
                'short_description' => 'A compact premium reception arrangement for small offices and clinics.',
                'description' => 'Designed for businesses that need a professional reception look without buying loose flowers. This package is arrangement-based and refreshed on a fixed schedule.',
                'starting_price' => 6999,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => '2 refreshes per week',
                'included_quantity_text' => 'Not sold by kg; includes finished arrangement service',
                'included_arrangement_count' => '1 reception arrangement per refresh',
                'arrangement_size' => 'Small / Medium reception bowl or vase',
                'refresh_frequency' => '2 refreshes per week',
                'flower_examples' => "Gerbera\nPremium roses\nLilies based on availability\nSeasonal fillers",
                'extra_quantity_note' => 'Extra desk or cabin arrangements are quoted separately based on size and flower choice.',
                'minimum_commitment' => '1 month',
                'included_items' => "1 finished reception arrangement\nPremium seasonal flower selection\nVase/bowl styling support\nScheduled refresh service",
                'features' => "Professional office presentation\nNo loose flower handling required\nDedicated WhatsApp coordination\nFlower varieties depend on market availability",
                'ideal_for' => "Small corporate offices\nClinics\nShowrooms\nReception desks",
                'cta_label' => 'Request Starter Plan',
                'sort_order' => 10,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Corporate Premium Flower Arrangement Plan',
                'slug' => 'corporate-premium-flower-arrangement-plan',
                'business_type' => 'Corporate Offices',
                'subscription_type' => 'Premium Arrangements',
                'flower_grade' => 'Premium / Imported Mix',
                'short_description' => 'Reception plus desk/lounge arrangements using lilies, orchids and premium roses.',
                'description' => 'Built for corporate offices that want a consistent premium look in reception, lounge or boardroom spaces. This is arrangement-based, not loose flower supply.',
                'starting_price' => 14999,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => '3 refreshes per week',
                'included_quantity_text' => 'Not sold by kg; includes multiple finished arrangements',
                'included_arrangement_count' => '1 reception arrangement + 2 desk/lounge arrangements',
                'arrangement_size' => 'Medium reception + small desk arrangements',
                'refresh_frequency' => '3 refreshes per week',
                'flower_examples' => "Oriental lilies\nOrchids\nPremium roses\nAnthuriums\nGerbera\nSeasonal fillers",
                'extra_quantity_note' => 'Imported tulips or exotic flowers are added as per availability and quoted before confirmation.',
                'minimum_commitment' => '1 month recommended; 3 months for fixed imported flower planning',
                'included_items' => "Reception arrangement\n2 desk or lounge arrangements\nPremium flower mix\nRefresh and replacement planning",
                'features' => "Premium business presentation\nCustom color theme\nMonthly billing support\nPriority sourcing for premium flowers",
                'ideal_for' => "Corporate offices\nHospitals\nPremium clinics\nBusiness lounges",
                'cta_label' => 'Request Premium Plan',
                'sort_order' => 20,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Elite Imported Flower Arrangement Plan',
                'slug' => 'elite-imported-flower-arrangement-plan',
                'business_type' => 'Corporate Offices',
                'subscription_type' => 'Premium Arrangements',
                'flower_grade' => 'Imported / Exotic',
                'short_description' => 'Large lobby, boardroom and cabin arrangements with imported premium flowers.',
                'description' => 'For corporates, hotels and premium spaces that need a high-end floral presentation with imported flowers and custom refresh schedules.',
                'starting_price' => 25000,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => 'Custom refresh schedule',
                'included_quantity_text' => 'Custom arrangement scope; finalized after site requirement',
                'included_arrangement_count' => 'Lobby arrangement + boardroom/cabin arrangements as quoted',
                'arrangement_size' => 'Large lobby / premium boardroom / custom',
                'refresh_frequency' => '2-5 refreshes per week based on flower type',
                'flower_examples' => "Oriental lilies\nImported orchids\nTulips\nAnthuriums\nImported roses\nHydrangea on request",
                'extra_quantity_note' => 'Imported flower pricing changes by season and availability. Final quotation confirms exact varieties.',
                'minimum_commitment' => '3 months recommended',
                'included_items' => "Premium lobby arrangement\nBoardroom or cabin arrangements\nImported flower planning\nCustom color/theme direction",
                'features' => "High-end corporate presentation\nImported flower sourcing\nDedicated account support\nCustom billing and delivery schedule",
                'ideal_for' => "Corporate headquarters\nHotels\nLuxury showrooms\nPremium hospitals",
                'cta_label' => 'Request Elite Quote',
                'sort_order' => 30,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Hospital & Clinic Floral Service',
                'slug' => 'hospital-fresh-flowers-supply',
                'business_type' => 'Hospitals',
                'subscription_type' => 'Premium Arrangements',
                'flower_grade' => 'Standard / Premium',
                'short_description' => 'Reception and prayer-area floral refresh for hospitals and clinics.',
                'description' => 'A clean, predictable flower service for hospital reception areas, prayer rooms and visitor spaces. Plans are configured by arrangement count and refresh schedule.',
                'starting_price' => 9999,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => '2-3 refreshes per week',
                'included_quantity_text' => 'Finished arrangements; optional puja flowers can be added',
                'included_arrangement_count' => '1 reception arrangement + optional puja flower add-on',
                'arrangement_size' => 'Medium reception arrangement',
                'refresh_frequency' => '2-3 refreshes per week',
                'flower_examples' => "Lilies\nGerbera\nRoses\nOrchids on request\nSeasonal fillers",
                'extra_quantity_note' => 'Daily puja flowers, garlands or additional reception arrangements are billed separately.',
                'minimum_commitment' => '1 month',
                'included_items' => "Reception arrangement\nPrayer-area flower option\nScheduled refresh support\nHygienic packing",
                'features' => "Clean presentation\nFixed delivery window\nMonthly billing support\nCustom quantity add-ons",
                'ideal_for' => "Hospitals\nClinics\nWellness centers\nDiagnostic centers",
                'cta_label' => 'Request Hospital Plan',
                'sort_order' => 40,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Hotel Lobby Premium Arrangement Plan',
                'slug' => 'hotel-lobby-flower-plan',
                'business_type' => 'Hotels',
                'subscription_type' => 'Premium Arrangements',
                'flower_grade' => 'Premium / Imported Mix',
                'short_description' => 'Premium lobby and front-desk arrangements for hospitality spaces.',
                'description' => 'A premium arrangement service for hotels, restaurants and banquet spaces that need fresh lobby or front-desk presentation.',
                'starting_price' => 18999,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => '2-4 refreshes per week',
                'included_quantity_text' => 'Arrangement-based service; not kg-based',
                'included_arrangement_count' => '1 lobby/front desk arrangement per refresh',
                'arrangement_size' => 'Large front desk / lobby arrangement',
                'refresh_frequency' => '2-4 refreshes per week',
                'flower_examples' => "Oriental lilies\nOrchids\nAnthuriums\nPremium roses\nSeasonal exotic fillers",
                'extra_quantity_note' => 'Restaurant table flowers and banquet upgrades are quoted separately.',
                'minimum_commitment' => '1 month',
                'included_items' => "Lobby/front desk arrangement\nPremium flower selection\nRefresh schedule\nTheme support",
                'features' => "Hospitality-grade presentation\nCustom color palette\nEvent upgrade support\nDedicated coordination",
                'ideal_for' => "Hotels\nRestaurants\nBanquet halls\nGuest houses",
                'cta_label' => 'Request Hotel Plan',
                'sort_order' => 50,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Temple Daily Loose Flower Plan',
                'slug' => 'temple-daily-flower-subscription',
                'business_type' => 'Temples',
                'subscription_type' => 'Loose Flowers',
                'flower_grade' => 'Fresh Daily',
                'short_description' => 'Loose flowers, garlands and patri for daily temple pooja.',
                'description' => 'A quantity-based subscription for temples and daily pooja needs. This is the correct plan type when pricing depends on kg, garlands or leaves.',
                'starting_price' => 1199,
                'price_suffix' => '/ month',
                'billing_cycle' => 'Monthly',
                'delivery_frequency' => 'Daily morning delivery',
                'included_quantity_text' => 'Starts with 500g loose flowers per delivery',
                'included_arrangement_count' => 'Garlands and leaves can be added as required',
                'arrangement_size' => 'Not applicable',
                'refresh_frequency' => 'Daily delivery',
                'flower_examples' => "Chamanthi\nBanthi\nKanakambaram\nJasmine\nTulasi\nBilva leaves",
                'extra_quantity_note' => 'Extra kg, garlands and patri are billed as per daily market price.',
                'minimum_commitment' => '1 month',
                'included_items' => "Loose flowers\nPatri and leaves option\nGarlands option\nFestival bulk planning",
                'features' => "Quantity-based pricing\nDaily fresh sourcing\nCustom flower mix\nFestival bulk support",
                'ideal_for' => "Temples\nDaily pooja\nApartments\nPuja mandirs",
                'cta_label' => 'Request Temple Plan',
                'sort_order' => 60,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Custom Enterprise Flower Plan',
                'slug' => 'business-bulk-flower-plan',
                'business_type' => 'Business Bulk',
                'subscription_type' => 'Custom / Hybrid',
                'flower_grade' => 'As per requirement',
                'short_description' => 'Custom premium arrangements, loose flowers or bulk flower supply for large accounts.',
                'description' => 'For businesses with mixed needs: premium arrangements, daily puja flowers, garlands, events or imported flower requirements.',
                'starting_price' => 0,
                'price_suffix' => '',
                'billing_cycle' => 'Custom',
                'delivery_frequency' => 'Custom schedule',
                'included_quantity_text' => 'Final quantity and arrangement scope decided after requirement discussion',
                'included_arrangement_count' => 'Custom',
                'arrangement_size' => 'Custom',
                'refresh_frequency' => 'Custom',
                'flower_examples' => "Oriental lilies\nOrchids\nTulips\nLoose pooja flowers\nGarlands\nSeasonal premium flowers",
                'extra_quantity_note' => 'Quote depends on flower type, quantity, location, refresh frequency and imported flower availability.',
                'minimum_commitment' => 'Discuss with team',
                'included_items' => "Custom plan design\nPremium or loose flower supply\nBulk/event support\nFlexible delivery schedule",
                'features' => "Best for unclear requirements\nQuote after discussion\nSupports premium and loose flowers\nDedicated account support",
                'ideal_for' => "Large corporate offices\nHospitals\nHotels\nEvent venues\nBulk buyers",
                'cta_label' => 'Request Custom Quote',
                'sort_order' => 70,
                'is_featured' => 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
};
