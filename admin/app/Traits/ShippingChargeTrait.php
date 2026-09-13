<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait ShippingChargeTrait
{
    protected function calculateShippingCharge(?float $distance, float $subTotal): array
    {
        $distance = $distance === null ? null : round($distance, 2);
        $subTotal = max(0, $subTotal);
        $activePrices = DB::table('shipping_prices')->where('status', 1);
        $hasDistancePricing = (clone $activePrices)
            ->whereNotNull('from_km')
            ->whereNotNull('to_km')
            ->exists();

        if($hasDistancePricing && $distance === null){
            return $this->shippingUnavailable('Unable to calculate delivery charges for the selected address.', $distance);
        }

        $distancePrice = null;
        if($hasDistancePricing){
            $distancePrice = $this->matchingDistancePrice($distance, $subTotal)->first();

            if(!$distancePrice){
                $nearestDistancePrice = (clone $activePrices)
                    ->whereNotNull('from_km')
                    ->whereNotNull('to_km')
                    ->where('from_km', '<=', $distance)
                    ->where('to_km', '>=', $distance)
                    ->orderBy('to_km')
                    ->orderBy('from_km')
                    ->first();

                if($nearestDistancePrice){
                    if($nearestDistancePrice->min_order_amount !== null && $subTotal < (float) $nearestDistancePrice->min_order_amount){
                        return $this->shippingUnavailable(
                            'Minimum order amount for delivery in this area is ₹'.number_format((float) $nearestDistancePrice->min_order_amount, 2).'.',
                            $distance
                        );
                    }

                    if($nearestDistancePrice->max_order_amount !== null && $subTotal > (float) $nearestDistancePrice->max_order_amount){
                        return $this->shippingUnavailable(
                            'Delivery price is not configured for this order amount.',
                            $distance
                        );
                    }
                }

                $maxDistance = (clone $activePrices)->whereNotNull('to_km')->max('to_km');
                $message = $maxDistance
                    ? 'Delivery is not available for this address. Current service range is up to '.$maxDistance.' KM.'
                    : 'Delivery is not available for this address.';

                return $this->shippingUnavailable($message, $distance);
            }
        }

        $freeShipping = $this->matchingFreeShippingPrice($distance, $subTotal, $hasDistancePricing)->first();
        if($freeShipping && (!$hasDistancePricing || $distancePrice)){
            return $this->shippingAvailable($freeShipping, $distance, true);
        }

        if($distancePrice){
            return $this->shippingAvailable($distancePrice, $distance, false);
        }

        $amountPrice = (clone $activePrices)
            ->where(function($query) use($subTotal){
                $query->whereNull('min_order_amount')->orWhere('min_order_amount', '<=', $subTotal);
            })
            ->where(function($query) use($subTotal){
                $query->whereNull('max_order_amount')->orWhere('max_order_amount', '>=', $subTotal);
            })
            ->orderBy('shipping_amount')
            ->first();

        if($amountPrice){
            return $this->shippingAvailable($amountPrice, $distance, (float) $amountPrice->shipping_amount === 0.0);
        }

        return $this->shippingUnavailable('Delivery charge is not configured for this order.', $distance);
    }

    protected function matchingDistancePrice(float $distance, float $subTotal)
    {
        return DB::table('shipping_prices')
            ->where('status', 1)
            ->whereNotNull('from_km')
            ->whereNotNull('to_km')
            ->where('from_km', '<=', $distance)
            ->where('to_km', '>=', $distance)
            ->where(function($query) use($subTotal){
                $query->whereNull('min_order_amount')->orWhere('min_order_amount', '<=', $subTotal);
            })
            ->where(function($query) use($subTotal){
                $query->whereNull('max_order_amount')->orWhere('max_order_amount', '>=', $subTotal);
            })
            ->orderBy('to_km')
            ->orderBy('from_km');
    }

    protected function matchingFreeShippingPrice(?float $distance, float $subTotal, bool $hasDistancePricing)
    {
        return DB::table('shipping_prices')
            ->where('status', 1)
            ->where('shipping_amount', 0)
            ->where(function($query) use($subTotal){
                $query->whereNull('min_order_amount')->orWhere('min_order_amount', '<=', $subTotal);
            })
            ->where(function($query) use($subTotal){
                $query->whereNull('max_order_amount')->orWhere('max_order_amount', '>=', $subTotal);
            })
            ->where(function($query) use($distance, $hasDistancePricing){
                $query->where(function($freeForAll){
                    $freeForAll->whereNull('from_km')->whereNull('to_km');
                });

                if($distance !== null && $hasDistancePricing){
                    $query->orWhere(function($distanceFree) use($distance){
                        $distanceFree->whereNotNull('from_km')
                            ->whereNotNull('to_km')
                            ->where('from_km', '<=', $distance)
                            ->where('to_km', '>=', $distance);
                    });
                }
            })
            ->orderByDesc('min_order_amount')
            ->orderBy('id');
    }

    protected function shippingAvailable($row, ?float $distance, bool $freeShipping): array
    {
        $amount = (float) $row->shipping_amount;
        $title = $freeShipping || $amount === 0.0 ? 'Free Shipping' : ($row->title ?: 'Delivery Charges');

        return [
            'available' => true,
            'id' => $row->id ?? null,
            'title' => $title,
            'price_title' => $row->title ?? $title,
            'amount' => $amount,
            'distance' => $distance,
            'free_shipping_applied' => $freeShipping || $amount === 0.0,
            'message' => null,
        ];
    }

    protected function shippingUnavailable(string $message, ?float $distance): array
    {
        return [
            'available' => false,
            'id' => null,
            'title' => null,
            'price_title' => null,
            'amount' => 0,
            'distance' => $distance,
            'free_shipping_applied' => false,
            'message' => $message,
        ];
    }
}
