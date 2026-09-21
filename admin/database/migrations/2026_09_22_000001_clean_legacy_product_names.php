<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        // These are unambiguous spelling/encoding corrections. Slugs and SKUs
        // remain unchanged so existing storefront links and integrations stay valid.
        $renames = [
            'RRJ' => 'Red Roses',
            'WRJ' => 'White Roses',
            'DCRCH' => 'Decoration Chamanthi',
            'LLY' => 'Lily Flowers',
            'BTL' => 'Betel Leaves',
            'MND' => 'Hibiscus Flowers',
            'TLS' => 'Tulasi Patri',
            'ART' => 'Banana Leaves',
            'MRM' => 'Maruvam',
            'DVNM' => 'Dhavanam',
        ];

        foreach ($renames as $sku => $title) {
            DB::table('products')
                ->where('sku', $sku)
                ->update([
                    'title' => $title,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // The original values include corrupted text and cannot be restored reliably.
    }
};
