<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A placed order keeps the price that was confirmed at checkout, even
     * when the selected delivery date is several days in the future.
     */
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'price_locked_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('price_locked_at')->nullable()->after('amount')->index();
            });
        }

        // Existing orders already use their stored line-item prices. Mark
        // them as locked at their original creation time for a clear audit
        // trail in the admin panel.
        DB::table('orders')
            ->whereNull('price_locked_at')
            ->whereNotNull('created_at')
            ->update(['price_locked_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'price_locked_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('price_locked_at');
            });
        }
    }
};
