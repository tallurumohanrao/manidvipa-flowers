<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'posts', 'subscription_plans'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'short_description')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->text('short_description')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'short_description')) {
            Schema::table('categories', fn (Blueprint $table) => $table->string('short_description', 191)->nullable()->change());
        }
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'short_description')) {
            Schema::table('posts', fn (Blueprint $table) => $table->string('short_description', 255)->nullable()->change());
        }
        if (Schema::hasTable('subscription_plans') && Schema::hasColumn('subscription_plans', 'short_description')) {
            Schema::table('subscription_plans', fn (Blueprint $table) => $table->string('short_description', 500)->nullable()->change());
        }
    }
};
