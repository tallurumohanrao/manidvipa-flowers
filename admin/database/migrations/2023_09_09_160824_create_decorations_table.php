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
        Schema::create('decorations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nulllable();
            $table->decimal('price',10,2)->nulllable();
            $table->integer('priority')->nulllable();
            $table->boolean('status')->nulllable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decorations');
    }
};
