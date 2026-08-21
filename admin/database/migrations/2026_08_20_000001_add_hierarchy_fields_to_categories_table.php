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
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (!Schema::hasColumn('categories', 'short_description')) {
                $table->text('short_description')->nullable()->after('image');
            }

            if (!Schema::hasColumn('categories', 'home_category')) {
                $table->boolean('home_category')->default(0)->after('description');
            }

            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('home_category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter(
            ['name', 'short_description', 'parent_id', 'home_category'],
            fn ($column) => Schema::hasColumn('categories', $column)
        ));

        if ($columns) {
            Schema::table('categories', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
