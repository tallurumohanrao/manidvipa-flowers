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
            return;
        }

        Schema::table('homepage_sections', function (Blueprint $table) {
            if (! Schema::hasColumn('homepage_sections', 'section_key')) {
                $table->string('section_key', 60)->nullable()->index()->after('section_type');
            }
            if (! Schema::hasColumn('homepage_sections', 'is_system')) {
                $table->boolean('is_system')->default(false)->index()->after('section_key');
            }
        });

        $defaults = [
            ['hero', 'Hero banner', 10],
            ['shop_by_category', 'Shop by Category', 20],
            ['subscriptions', 'Business Flower Subscriptions', 30],
            ['fresh_arrivals', 'Fresh Arrivals & Collections', 40],
            ['puja_box', 'Build Your Own Puja Flower Box', 50],
            ['shop_by_occasion', 'Shop by Occasion', 60],
            ['decorations', 'Flower Decoration Services', 70],
            ['why_manidvipa', 'Why Manidvipa Flowers?', 80],
            ['recent_decorations', 'Recent Decorations & Reviews', 90],
            ['testimonials', 'Customer Testimonials', 100],
            ['instagram', 'Fresh From Manidvipa', 110],
            ['faqs', 'Frequently Asked Questions', 120],
            ['final_cta', 'Next Morning Flower Delivery', 130],
        ];

        $now = now();
        foreach ($defaults as [$key, $title, $priority]) {
            DB::table('homepage_sections')->updateOrInsert(
                ['section_key' => $key],
                [
                    'title' => $title,
                    'section_type' => $key,
                    'is_system' => 1,
                    'priority' => $priority,
                    'status' => 1,
                    'max_items' => 6,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        DB::table('homepage_sections')->where('is_system', 1)->delete();

        Schema::table('homepage_sections', function (Blueprint $table) {
            if (Schema::hasColumn('homepage_sections', 'is_system')) {
                $table->dropColumn('is_system');
            }
            if (Schema::hasColumn('homepage_sections', 'section_key')) {
                $table->dropColumn('section_key');
            }
        });
    }
};
