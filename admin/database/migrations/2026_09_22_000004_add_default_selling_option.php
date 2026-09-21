<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_weights')) {
            return;
        }

        if (! Schema::hasColumn('product_weights', 'is_default')) {
            Schema::table('product_weights', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('status')->index();
            });
        }

        // Give existing products a stable default without changing their data.
        DB::table('product_weights')
            ->select('product_id')
            ->where('status', 1)
            ->groupBy('product_id')
            ->orderBy('product_id')
            ->pluck('product_id')
            ->each(function ($productId) {
                if (DB::table('product_weights')->where('product_id', $productId)->where('is_default', 1)->exists()) {
                    return;
                }

                $id = DB::table('product_weights')
                    ->where('product_id', $productId)
                    ->where('status', 1)
                    ->orderByRaw('CASE WHEN stock = 1 AND qty > 0 THEN 0 ELSE 1 END')
                    ->orderByRaw('CAST(sell_price AS DECIMAL(12,2)) ASC')
                    ->orderBy('id')
                    ->value('id');

                if ($id) {
                    DB::table('product_weights')->where('id', $id)->update(['is_default' => 1]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('product_weights') && Schema::hasColumn('product_weights', 'is_default')) {
            Schema::table('product_weights', fn (Blueprint $table) => $table->dropColumn('is_default'));
        }
    }
};
