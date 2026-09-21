<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_weights') || ! Schema::hasColumn('product_weights', 'qty')) {
            return;
        }

        Schema::table('product_weights', function (Blueprint $table) {
            $table->decimal('qty', 12, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_weights') || ! Schema::hasColumn('product_weights', 'qty')) {
            return;
        }

        Schema::table('product_weights', function (Blueprint $table) {
            $table->integer('qty')->nullable()->change();
        });
    }
};
