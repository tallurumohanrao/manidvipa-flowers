<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_weights')) {
            Schema::table('product_weights', function (Blueprint $table) {
                if (! Schema::hasColumn('product_weights', 'quantity_value')) $table->decimal('quantity_value', 12, 3)->nullable();
                if (! Schema::hasColumn('product_weights', 'quantity_unit')) $table->string('quantity_unit', 30)->nullable();
                if (! Schema::hasColumn('product_weights', 'pricing_mode')) $table->string('pricing_mode', 20)->default('manual');
                if (! Schema::hasColumn('product_weights', 'unit_sell_price')) $table->decimal('unit_sell_price', 12, 4)->nullable();
                if (! Schema::hasColumn('product_weights', 'unit_list_price')) $table->decimal('unit_list_price', 12, 4)->nullable();
                if (! Schema::hasColumn('product_weights', 'unit_cost_price')) $table->decimal('unit_cost_price', 12, 4)->nullable();
                if (! Schema::hasColumn('product_weights', 'allow_custom_quantity')) $table->boolean('allow_custom_quantity')->default(false);
                if (! Schema::hasColumn('product_weights', 'minimum_custom_quantity')) $table->decimal('minimum_custom_quantity', 12, 3)->nullable();
                if (! Schema::hasColumn('product_weights', 'maximum_custom_quantity')) $table->decimal('maximum_custom_quantity', 12, 3)->nullable();
                if (! Schema::hasColumn('product_weights', 'custom_quantity_step')) $table->decimal('custom_quantity_step', 12, 3)->nullable();
            });
        }

        if (Schema::hasTable('carts') && ! Schema::hasColumn('carts', 'custom_quantity')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->decimal('custom_quantity', 12, 3)->nullable();
            });
        }

        if (Schema::hasTable('order_products') && ! Schema::hasColumn('order_products', 'stock_quantity')) {
            Schema::table('order_products', function (Blueprint $table) {
                $table->decimal('stock_quantity', 12, 3)->nullable();
            });
        }
    }

    public function down(): void
    {
        $this->dropExistingColumns('product_weights', [
            'quantity_value',
            'quantity_unit',
            'pricing_mode',
            'unit_sell_price',
            'unit_list_price',
            'unit_cost_price',
            'allow_custom_quantity',
            'minimum_custom_quantity',
            'maximum_custom_quantity',
            'custom_quantity_step',
        ]);
        $this->dropExistingColumns('carts', ['custom_quantity']);
        $this->dropExistingColumns('order_products', ['stock_quantity']);
    }

    private function dropExistingColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) return;

        $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($tableName, $column)));
        if (! $existing) return;

        Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($existing));
    }
};
