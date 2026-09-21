<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('measurement_units')) {
            Schema::create('measurement_units', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('singular_name', 80);
                $table->string('plural_name', 80);
                $table->string('type', 30)->default('count');
                $table->string('base_code', 40);
                $table->decimal('conversion_factor', 14, 6)->default(1);
                $table->boolean('allows_decimal')->default(false);
                $table->unsignedInteger('priority')->default(100);
                $table->boolean('status')->default(true);
                $table->timestamps();
                $table->index(['status', 'priority']);
                $table->index(['type', 'base_code']);
            });
        }

        $this->seedUnits();

        if (! Schema::hasTable('product_inventory_pools')) {
            Schema::create('product_inventory_pools', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('unit_id');
                $table->decimal('qty', 14, 3)->default(0);
                $table->boolean('track_stock')->default(true);
                $table->boolean('status')->default(true);
                $table->timestamps();
                $table->unique(['product_id', 'unit_id'], 'product_inventory_product_unit_unique');
                $table->index('product_id');
                $table->index('unit_id');
            });
        }

        if (Schema::hasTable('product_weights')) {
            Schema::table('product_weights', function (Blueprint $table) {
                if (! Schema::hasColumn('product_weights', 'unit_id')) {
                    $table->unsignedBigInteger('unit_id')->nullable()->after('quantity_unit')->index();
                }
                if (! Schema::hasColumn('product_weights', 'inventory_pool_id')) {
                    $table->unsignedBigInteger('inventory_pool_id')->nullable()->after('unit_id')->index();
                }
            });
        }

        $this->backfillStructuredOptions();
    }

    public function down(): void
    {
        if (Schema::hasTable('product_weights')) {
            $columns = array_values(array_filter(['inventory_pool_id', 'unit_id'], fn ($column) => Schema::hasColumn('product_weights', $column)));
            if ($columns) {
                Schema::table('product_weights', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
        Schema::dropIfExists('product_inventory_pools');
        Schema::dropIfExists('measurement_units');
    }

    private function seedUnits(): void
    {
        $now = now();
        $units = [
            ['flower', 'Flower', 'Flowers', 'count', 'flower', 1, false, 10],
            ['piece', 'Piece', 'Pieces', 'count', 'piece', 1, false, 20],
            ['stem', 'Stem', 'Stems', 'count', 'stem', 1, false, 30],
            ['bunch', 'Bunch', 'Bunches', 'package', 'bunch', 1, false, 40],
            ['gram', 'Gram', 'Grams', 'weight', 'gram', 1, true, 50],
            ['kg', 'KG', 'KG', 'weight', 'gram', 1000, true, 60],
            ['ml', 'ml', 'ml', 'volume', 'ml', 1, true, 70],
            ['liter', 'Liter', 'Liters', 'volume', 'ml', 1000, true, 80],
            ['packet', 'Packet', 'Packets', 'package', 'packet', 1, false, 90],
            ['box', 'Box', 'Boxes', 'package', 'box', 1, false, 100],
            ['basket', 'Basket', 'Baskets', 'package', 'basket', 1, false, 110],
            ['set', 'Set', 'Sets', 'package', 'set', 1, false, 120],
        ];

        foreach ($units as [$code, $singular, $plural, $type, $baseCode, $factor, $decimal, $priority]) {
            DB::table('measurement_units')->updateOrInsert(['code' => $code], [
                'singular_name' => $singular,
                'plural_name' => $plural,
                'type' => $type,
                'base_code' => $baseCode,
                'conversion_factor' => $factor,
                'allows_decimal' => $decimal,
                'priority' => $priority,
                'status' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
    }

    private function backfillStructuredOptions(): void
    {
        if (! Schema::hasTable('product_weights') || ! Schema::hasTable('product_inventory_pools')) return;

        $units = DB::table('measurement_units')->get()->keyBy('code');
        $rows = DB::table('product_weights')
            ->whereNotNull('quantity_value')
            ->whereNotNull('quantity_unit')
            ->orderBy('product_id')
            ->orderBy('id')
            ->get();

        foreach ($rows->groupBy(function ($row) use ($units) {
            $unit = $units->get($row->quantity_unit);

            return $row->product_id.'|'.($unit->base_code ?? $row->quantity_unit);
        }) as $group) {
            $first = $group->first();
            $firstUnit = $units->get($first->quantity_unit);
            $baseUnit = $firstUnit ? $units->get($firstUnit->base_code) : null;
            if (! $firstUnit || ! $baseUnit) continue;

            $poolId = DB::table('product_inventory_pools')->where([
                'product_id' => $first->product_id,
                'unit_id' => $baseUnit->id,
            ])->value('id');

            $baseQuantity = $group->max(function ($row) use ($units, $baseUnit) {
                $unit = $units->get($row->quantity_unit);
                $factor = (float) ($unit->conversion_factor ?? 1);

                return (float) ($row->qty ?? 0) * $factor / (float) $baseUnit->conversion_factor;
            });

            if (! $poolId) {
                $poolId = DB::table('product_inventory_pools')->insertGetId([
                    'product_id' => $first->product_id,
                    'unit_id' => $baseUnit->id,
                    'qty' => $baseQuantity,
                    'track_stock' => $group->contains(fn ($row) => (int) ($row->stock ?? 0) === 1),
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($group as $row) {
                $unit = $units->get($row->quantity_unit);
                DB::table('product_weights')->where('id', $row->id)->update([
                    'unit_id' => $unit?->id,
                    'inventory_pool_id' => $poolId,
                ]);
            }
        }
    }
};
