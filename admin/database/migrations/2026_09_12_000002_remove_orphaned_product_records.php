<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        foreach (['product_weights', 'product_sizes', 'product_images', 'category_product', 'featured_products', 'review_ratings', 'carts'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'product_id')) {
                continue;
            }

            DB::table($table)
                ->whereNotExists(function ($query) use ($table) {
                    $query->select(DB::raw(1))
                        ->from('products')
                        ->whereColumn('products.id', $table.'.product_id');
                })
                ->delete();
        }
    }

    public function down(): void
    {
        // Orphaned rows cannot be restored safely.
    }
};
