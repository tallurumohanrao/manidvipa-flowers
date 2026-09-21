<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SETTINGS = [
        'PRODUCT_PRICE_VISIBILITY_DEFAULT' => ['Default Product Price Display', 'show_everywhere', 'select', 40],
        'SUBSCRIPTION_PRICE_VISIBILITY_DEFAULT' => ['Default Subscription Price Display', 'enquiry_only', 'select', 41],
        'PRICE_ENQUIRY_LABEL' => ['Hidden Price Message', 'Contact us for price', 'text', 42],
        'PRICE_ENQUIRY_BUTTON_LABEL' => ['Hidden Price Button', 'Enquire Now', 'text', 43],
        'PRICE_COMING_SOON_LABEL' => ['Coming Soon Message', 'Coming soon', 'text', 44],
    ];

    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'price_visibility')) {
                    $table->string('price_visibility', 40)->nullable()->after('priority');
                }
                if (! Schema::hasColumn('products', 'price_visible_from')) {
                    $table->timestamp('price_visible_from')->nullable()->after('price_visibility');
                }
            });
        }

        if (Schema::hasTable('subscription_plans')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                if (! Schema::hasColumn('subscription_plans', 'price_visibility')) {
                    $table->string('price_visibility', 40)->nullable()->after('price_suffix');
                }
                if (! Schema::hasColumn('subscription_plans', 'price_visible_from')) {
                    $table->timestamp('price_visible_from')->nullable()->after('price_visibility');
                }
            });
        }

        if (Schema::hasTable('settings')) {
            foreach (self::SETTINGS as $key => [$label, $value, $input, $sortOrder]) {
                $values = [
                    'label' => $label,
                    'value' => $value,
                    'type' => 'Pricing',
                    'input' => $input,
                    'sort_order' => (string) $sortOrder,
                    'status' => '1',
                ];
                $exists = DB::table('settings')->where('key', $key)->exists();

                if (Schema::hasColumn('settings', 'updated_at')) {
                    $values['updated_at'] = now();
                }

                if ($exists) {
                    DB::table('settings')->where('key', $key)->update($values);
                } else {
                    if (Schema::hasColumn('settings', 'created_at')) {
                        $values['created_at'] = now();
                    }
                    DB::table('settings')->insert(['key' => $key] + $values);
                }
            }
        }

        Cache::forget('settings');
        Cache::forget('configurations');
        Cache::forget('price_visibility_settings');
        Cache::forget('api_subscription_plans');
        Cache::forget('api_subscription_plans_featured');
        Cache::forget('api_featured_products');
        Cache::forget('home');
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $columns = array_values(array_filter(
                    ['price_visibility', 'price_visible_from'],
                    fn ($column) => Schema::hasColumn('products', $column)
                ));
                if ($columns) $table->dropColumn($columns);
            });
        }

        if (Schema::hasTable('subscription_plans')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $columns = array_values(array_filter(
                    ['price_visibility', 'price_visible_from'],
                    fn ($column) => Schema::hasColumn('subscription_plans', $column)
                ));
                if ($columns) $table->dropColumn($columns);
            });
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereIn('key', array_keys(self::SETTINGS))->delete();
        }
    }
};
