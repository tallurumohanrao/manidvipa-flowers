<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_urls') || Schema::hasColumn('seo_urls', 'schema_markup')) {
            return;
        }

        Schema::table('seo_urls', function (Blueprint $table) {
            $table->longText('schema_markup')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo_urls') || ! Schema::hasColumn('seo_urls', 'schema_markup')) {
            return;
        }

        Schema::table('seo_urls', function (Blueprint $table) {
            $table->dropColumn('schema_markup');
        });
    }
};
