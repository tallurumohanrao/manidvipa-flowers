<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('theater_id')->nullable();
            $table->bigInteger('decoration_id')->nullable();
            $table->bigInteger('cakes_id')->nullable();
            $table->bigInteger('slot_id')->nullable();
            $table->decimal('theater_price',10,2)->nulllable();
            $table->decimal('decoration_price',10,2)->nulllable();
            $table->decimal('cake_price',10,2)->nulllable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
