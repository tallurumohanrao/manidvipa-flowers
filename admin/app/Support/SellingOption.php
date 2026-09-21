<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SellingOption
{
    public const MANUAL = 'manual';
    public const AUTOMATIC = 'automatic';

    public static function units(): array
    {
        return [
            'flower' => 'Flower / Flowers',
            'piece' => 'Piece / Pieces',
            'stem' => 'Stem / Stems',
            'bunch' => 'Bunch / Bunches',
            'gram' => 'Gram / Grams',
            'kg' => 'KG',
            'ml' => 'ml',
            'liter' => 'Liter / Liters',
            'packet' => 'Packet / Packets',
            'box' => 'Box / Boxes',
            'basket' => 'Basket / Baskets',
            'set' => 'Set / Sets',
        ];
    }

    public static function pricingModes(): array
    {
        return [
            self::MANUAL => 'Manual total price',
            self::AUTOMATIC => 'Automatic: quantity × unit rate',
        ];
    }

    /**
     * Apply the one ordering used by every storefront surface.
     *
     * A product's default option always wins. If an older record has no
     * default yet, an available option with the lowest selling price is used
     * and the remaining options follow it. This keeps home, listings, detail
     * pages and cart responses in agreement while preserving out-of-stock
     * options for selection.
     */
    public static function sortOptions(\Illuminate\Support\Collection $options): \Illuminate\Support\Collection
    {
        return $options->sort(function ($left, $right) {
            $leftDefault = (int) data_get($left, 'is_default', 0);
            $rightDefault = (int) data_get($right, 'is_default', 0);
            if ($leftDefault !== $rightDefault) {
                return $rightDefault <=> $leftDefault;
            }

            $leftOut = self::isOutOfStock($left) ? 1 : 0;
            $rightOut = self::isOutOfStock($right) ? 1 : 0;
            if ($leftOut !== $rightOut) {
                return $leftOut <=> $rightOut;
            }

            $priceCompare = (float) data_get($left, 'sell_price', 0) <=> (float) data_get($right, 'sell_price', 0);
            if ($priceCompare !== 0) {
                return $priceCompare;
            }

            return (int) data_get($left, 'id', 0) <=> (int) data_get($right, 'id', 0);
        })->values();
    }

    public static function defaultOption(\Illuminate\Support\Collection $options): ?object
    {
        return self::sortOptions($options)->first();
    }

    public static function isStructured(object|array $option): bool
    {
        return (float) data_get($option, 'quantity_value', 0) > 0
            && ((int) data_get($option, 'unit_id', 0) > 0 || trim((string) data_get($option, 'quantity_unit')) !== '');
    }

    public static function label(object|array $option, $quantityOverride = null): string
    {
        if (! self::isStructured($option)) {
            return trim((string) data_get($option, 'name')) ?: 'Selected option';
        }

        $quantity = $quantityOverride !== null
            ? (float) $quantityOverride
            : (float) data_get($option, 'quantity_value');

        if ($quantityOverride === null && trim((string) data_get($option, 'name')) !== '') {
            return trim((string) data_get($option, 'name'));
        }

        return self::formatNumber($quantity).' '.self::unitLabelForOption($option, $quantity);
    }

    public static function unitLabelForOption(object|array $option, float $quantity): string
    {
        $single = abs($quantity - 1.0) < 0.00001;
        $label = $single ? data_get($option, 'unit_singular') : data_get($option, 'unit_plural');

        return trim((string) $label) ?: self::unitLabel((string) data_get($option, 'quantity_unit'), $quantity);
    }

    public static function unitLabel(string $unit, float $quantity): string
    {
        $single = abs($quantity - 1.0) < 0.00001;

        return match ($unit) {
            'flower' => $single ? 'Flower' : 'Flowers',
            'piece' => $single ? 'Piece' : 'Pieces',
            'stem' => $single ? 'Stem' : 'Stems',
            'bunch' => $single ? 'Bunch' : 'Bunches',
            'gram' => $single ? 'Gram' : 'Grams',
            'kg' => 'KG',
            'ml' => 'ml',
            'liter' => $single ? 'Liter' : 'Liters',
            'packet' => $single ? 'Packet' : 'Packets',
            'box' => $single ? 'Box' : 'Boxes',
            'basket' => $single ? 'Basket' : 'Baskets',
            'set' => $single ? 'Set' : 'Sets',
            default => $single ? 'Unit' : 'Units',
        };
    }

    public static function customQuantityError(object|array $option, $customQuantity): ?string
    {
        if ($customQuantity === null || $customQuantity === '') return null;
        if (! self::isStructured($option) || ! (bool) data_get($option, 'allow_custom_quantity')) {
            return 'Custom quantity is not enabled for this option.';
        }
        if (! is_numeric($customQuantity) || (float) $customQuantity <= 0) {
            return 'Enter a valid custom quantity.';
        }

        $quantity = (float) $customQuantity;
        $minimum = (float) (data_get($option, 'minimum_custom_quantity') ?: 1);
        $maximum = data_get($option, 'maximum_custom_quantity');
        $step = (float) (data_get($option, 'custom_quantity_step') ?: 1);

        if ($quantity < $minimum) return 'Minimum custom quantity is '.self::formatNumber($minimum).'.';
        if ($maximum !== null && $maximum !== '' && $quantity > (float) $maximum) {
            return 'Maximum custom quantity is '.self::formatNumber((float) $maximum).'.';
        }

        $steps = ($quantity - $minimum) / max($step, 0.001);
        if (abs($steps - round($steps)) > 0.00001) {
            return 'Custom quantity must increase by '.self::formatNumber($step).'.';
        }

        return null;
    }

    public static function price(object|array $option, string $type = 'sell', $customQuantity = null): float
    {
        $totalField = $type.'_price';
        if ($customQuantity === null || $customQuantity === '' || ! self::isStructured($option)) {
            return round((float) data_get($option, $totalField), 2);
        }

        $rateField = 'unit_'.$type.'_price';
        $rate = data_get($option, $rateField);
        if ($rate === null || $rate === '') {
            $presetQuantity = (float) data_get($option, 'quantity_value');
            $rate = $presetQuantity > 0 ? (float) data_get($option, $totalField) / $presetQuantity : 0;
        }

        return round((float) $rate * (float) $customQuantity, 2);
    }

    public static function requiredStock(object|array $option, int $packQuantity, $customQuantity = null): float
    {
        $packs = max(1, $packQuantity);
        if (! self::isStructured($option)) return (float) $packs;

        $unitsPerPack = ($customQuantity !== null && $customQuantity !== '')
            ? (float) $customQuantity
            : (float) data_get($option, 'quantity_value');

        $optionFactor = max(0.000001, (float) (data_get($option, 'unit_conversion_factor') ?: 1));
        $inventoryFactor = max(0.000001, (float) (data_get($option, 'inventory_unit_conversion_factor') ?: 1));
        $optionBase = (string) data_get($option, 'unit_base_code');
        $inventoryBase = (string) data_get($option, 'inventory_unit_base_code');

        if ((int) data_get($option, 'inventory_pool_id', 0) > 0 && $optionBase !== '' && $optionBase === $inventoryBase) {
            return round(($unitsPerPack * $optionFactor / $inventoryFactor) * $packs, 3);
        }

        return round($unitsPerPack * $packs, 3);
    }

    public static function hydrateInventory(object $option): object
    {
        $unit = null;
        if (! empty($option->unit_id)) {
            $unit = DB::table('measurement_units')->where('id', $option->unit_id)->first();
        } elseif (! empty($option->quantity_unit)) {
            $unit = DB::table('measurement_units')->where('code', $option->quantity_unit)->first();
        }
        if ($unit) {
            $option->unit_id = $unit->id;
            $option->quantity_unit = $unit->code;
            $option->unit_singular = $unit->singular_name;
            $option->unit_plural = $unit->plural_name;
            $option->unit_base_code = $unit->base_code;
            $option->unit_conversion_factor = (float) $unit->conversion_factor;
        }

        if (! empty($option->inventory_pool_id)) {
            $pool = DB::table('product_inventory_pools as pip')
                ->join('measurement_units as mu', 'mu.id', '=', 'pip.unit_id')
                ->where('pip.id', $option->inventory_pool_id)
                ->select('pip.qty as inventory_qty', 'pip.track_stock as inventory_track_stock',
                    'pip.status as inventory_status', 'mu.code as inventory_unit_code',
                    'mu.singular_name as inventory_unit_singular', 'mu.plural_name as inventory_unit_plural',
                    'mu.base_code as inventory_unit_base_code', 'mu.conversion_factor as inventory_unit_conversion_factor')
                ->first();
            if ($pool) foreach ((array) $pool as $key => $value) $option->{$key} = $value;
        }

        return $option;
    }

    public static function hydrateInventoryCollection(\Illuminate\Support\Collection $options): \Illuminate\Support\Collection
    {
        if ($options->isEmpty()) return $options;

        $unitIds = $options->pluck('unit_id')->filter()->unique()->values();
        $unitCodes = $options->pluck('quantity_unit')->filter()->unique()->values();
        $units = $unitIds->isEmpty() && $unitCodes->isEmpty() ? collect() : DB::table('measurement_units')
            ->where(function ($query) use ($unitIds, $unitCodes) {
                if ($unitIds->isNotEmpty()) $query->whereIn('id', $unitIds);
                if ($unitCodes->isNotEmpty()) $query->orWhereIn('code', $unitCodes);
            })->get();
        $unitsById = $units->keyBy('id');
        $unitsByCode = $units->keyBy('code');

        $poolIds = $options->pluck('inventory_pool_id')->filter()->unique()->values();
        $pools = $poolIds->isEmpty() ? collect() : DB::table('product_inventory_pools as pip')
            ->join('measurement_units as mu', 'mu.id', '=', 'pip.unit_id')
            ->whereIn('pip.id', $poolIds)
            ->select('pip.id', 'pip.qty as inventory_qty', 'pip.track_stock as inventory_track_stock',
                'pip.status as inventory_status', 'mu.code as inventory_unit_code',
                'mu.singular_name as inventory_unit_singular', 'mu.plural_name as inventory_unit_plural',
                'mu.base_code as inventory_unit_base_code', 'mu.conversion_factor as inventory_unit_conversion_factor')
            ->get()->keyBy('id');

        return $options->map(function ($option) use ($unitsById, $unitsByCode, $pools) {
            $unit = ! empty($option->unit_id)
                ? $unitsById->get($option->unit_id)
                : $unitsByCode->get($option->quantity_unit ?? '');
            if ($unit) {
                $option->unit_id = $unit->id;
                $option->quantity_unit = $unit->code;
                $option->unit_singular = $unit->singular_name;
                $option->unit_plural = $unit->plural_name;
                $option->unit_base_code = $unit->base_code;
                $option->unit_conversion_factor = (float) $unit->conversion_factor;
            }
            $pool = $pools->get($option->inventory_pool_id ?? 0);
            if ($pool) foreach ((array) $pool as $key => $value) if ($key !== 'id') $option->{$key} = $value;

            return $option;
        });
    }

    public static function tracksStock(object|array $option): bool
    {
        if ((int) data_get($option, 'inventory_pool_id', 0) > 0 && data_get($option, 'inventory_track_stock') !== null) {
            return (bool) data_get($option, 'inventory_track_stock');
        }

        return (bool) data_get($option, 'stock');
    }

    public static function availableStock(object|array $option): float
    {
        if ((int) data_get($option, 'inventory_pool_id', 0) > 0 && data_get($option, 'inventory_qty') !== null) {
            return (float) data_get($option, 'inventory_qty');
        }

        return (float) data_get($option, 'qty', 0);
    }

    public static function hasAvailableStock(object|array $option, int $packs = 1, $customQuantity = null): bool
    {
        if ((int) data_get($option, 'inventory_pool_id', 0) > 0
            && data_get($option, 'inventory_status') !== null
            && ! (bool) data_get($option, 'inventory_status')) {
            return false;
        }

        return ! self::tracksStock($option)
            || self::availableStock($option) >= self::requiredStock($option, $packs, $customQuantity);
    }

    public static function isOutOfStock(object|array $option): bool
    {
        return ! self::hasAvailableStock($option);
    }

    public static function inventoryUnitLabel(object|array $option, float $quantity): string
    {
        $single = abs($quantity - 1.0) < 0.00001;
        $label = $single
            ? data_get($option, 'inventory_unit_singular')
            : data_get($option, 'inventory_unit_plural');

        if (trim((string) $label) !== '') {
            return trim((string) $label);
        }

        return self::isStructured($option)
            ? self::unitLabelForOption($option, $quantity)
            : 'items';
    }

    public static function publicData(object $option): object
    {
        if ((int) ($option->inventory_pool_id ?? 0) > 0 && ! isset($option->inventory_qty)) {
            self::hydrateInventory($option);
        }
        $option->display_name = self::label($option);
        $option->is_default = (bool) ($option->is_default ?? false);
        $option->is_structured = self::isStructured($option);
        $option->allow_custom_quantity = (bool) ($option->allow_custom_quantity ?? false);
        $option->stock = self::tracksStock($option) ? 1 : 0;
        $option->qty = self::availableStock($option);
        $option->required_stock = self::requiredStock($option, 1);
        $option->is_out_of_stock = ! self::hasAvailableStock($option);

        return $option;
    }

    public static function formatNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 3, '.', ''), '0'), '.');
    }
}
