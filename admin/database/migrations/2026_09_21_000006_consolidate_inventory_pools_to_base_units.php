<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('measurement_units') || ! Schema::hasTable('product_inventory_pools')
            || ! Schema::hasColumn('product_weights', 'inventory_pool_id')) {
            return;
        }

        $units = DB::table('measurement_units')->get()->keyBy('id');
        $unitsByCode = DB::table('measurement_units')->get()->keyBy('code');
        $pools = DB::table('product_inventory_pools')->orderBy('id')->get();

        foreach ($pools->groupBy(function ($pool) use ($units) {
            $unit = $units->get($pool->unit_id);

            return $pool->product_id.'|'.($unit->base_code ?? $unit->code ?? $pool->unit_id);
        }) as $group) {
            $first = $group->first();
            $firstUnit = $units->get($first->unit_id);
            $baseUnit = $firstUnit ? $unitsByCode->get($firstUnit->base_code) : null;
            if (! $baseUnit) continue;

            $basePool = $group->firstWhere('unit_id', $baseUnit->id);
            $baseQuantity = $group->max(function ($pool) use ($units, $baseUnit) {
                $unit = $units->get($pool->unit_id);

                return (float) $pool->qty * (float) ($unit->conversion_factor ?? 1)
                    / (float) $baseUnit->conversion_factor;
            });
            $trackStock = $group->contains(fn ($pool) => (int) $pool->track_stock === 1);
            $status = $group->contains(fn ($pool) => (int) $pool->status === 1);

            if (! $basePool) {
                $basePoolId = DB::table('product_inventory_pools')->insertGetId([
                    'product_id' => $first->product_id,
                    'unit_id' => $baseUnit->id,
                    'qty' => $baseQuantity,
                    'track_stock' => $trackStock,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $basePoolId = $basePool->id;
                DB::table('product_inventory_pools')->where('id', $basePoolId)->update([
                    'qty' => $baseQuantity,
                    'track_stock' => $trackStock,
                    'status' => $status,
                    'updated_at' => now(),
                ]);
            }

            $poolIds = $group->pluck('id')->all();
            DB::table('product_weights')->whereIn('inventory_pool_id', $poolIds)->update([
                'inventory_pool_id' => $basePoolId,
                'updated_at' => now(),
            ]);
            DB::table('product_inventory_pools')->whereIn('id', $poolIds)->where('id', '<>', $basePoolId)->delete();
        }
    }

    public function down(): void
    {
        // Consolidation prevents overselling and cannot be safely split back into independent stock pools.
    }
};
