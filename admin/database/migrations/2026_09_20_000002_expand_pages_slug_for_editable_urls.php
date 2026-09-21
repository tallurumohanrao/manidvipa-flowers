<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages') || ! Schema::hasColumn('pages', 'slug')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->string('slug', 190)->change();
        });
    }

    public function down(): void
    {
        // Existing installations used different slug lengths, so keep the safe expanded size.
    }
};
