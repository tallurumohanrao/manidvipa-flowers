<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile', 20)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->boolean('status')->default(1)->after('password');
            }
        });
    }

    public function down(): void
    {
        // This migration is intentionally additive because existing installations
        // may already have either column before this migration runs.
    }
};
