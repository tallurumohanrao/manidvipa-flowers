<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PriceVisibility
{
    public const INHERIT = 'inherit';
    public const SHOW_EVERYWHERE = 'show_everywhere';
    public const DETAILS_ONLY = 'details_only';
    public const SHOW_AFTER_SELECTION = 'show_after_selection';
    public const ENQUIRY_ONLY = 'enquiry_only';
    public const COMING_SOON = 'coming_soon';

    public static function modes(bool $includeInherit = true): array
    {
        $modes = [
            self::SHOW_EVERYWHERE => 'Show price everywhere',
            self::DETAILS_ONLY => 'Show on details page only',
            self::SHOW_AFTER_SELECTION => 'Show after option selection',
            self::ENQUIRY_ONLY => 'Enquiry only - hide price and ordering',
            self::COMING_SOON => 'Coming soon - hide price and ordering',
        ];

        return $includeInherit
            ? [self::INHERIT => 'Use global default'] + $modes
            : $modes;
    }

    public static function subscriptionModes(): array
    {
        return [
            self::INHERIT => 'Use global default',
            self::SHOW_EVERYWHERE => 'Show price',
            self::ENQUIRY_ONLY => 'Enquiry only - hide price',
            self::COMING_SOON => 'Coming soon - hide price',
        ];
    }

    public static function forProduct(object|array $product, string $context = 'listing'): array
    {
        return self::resolve(
            data_get($product, 'price_visibility'),
            data_get($product, 'price_visible_from'),
            self::setting('PRODUCT_PRICE_VISIBILITY_DEFAULT', self::SHOW_EVERYWHERE),
            $context
        );
    }

    public static function forSubscription(object|array $plan): array
    {
        return self::resolve(
            data_get($plan, 'price_visibility'),
            data_get($plan, 'price_visible_from'),
            self::setting('SUBSCRIPTION_PRICE_VISIBILITY_DEFAULT', self::ENQUIRY_ONLY),
            'listing'
        );
    }

    public static function productCanPurchase(object|array $product): bool
    {
        return self::forProduct($product, 'cart')['can_purchase'];
    }

    public static function clearSettingsCache(): void
    {
        Cache::forget('price_visibility_settings');
    }

    private static function resolve(?string $configuredMode, $visibleFrom, string $defaultMode, string $context): array
    {
        $configuredMode = self::validMode($configuredMode) ? $configuredMode : self::INHERIT;
        $defaultMode = self::validMode($defaultMode, false) ? $defaultMode : self::SHOW_EVERYWHERE;
        $baseMode = $configuredMode === self::INHERIT ? $defaultMode : $configuredMode;
        $effectiveMode = $baseMode;
        $revealAt = null;

        if ($visibleFrom) {
            try {
                $revealAt = Carbon::parse($visibleFrom, config('app.timezone'));
                if ($revealAt->isPast()) {
                    $effectiveMode = self::SHOW_EVERYWHERE;
                } elseif ($baseMode === self::SHOW_EVERYWHERE) {
                    $effectiveMode = self::COMING_SOON;
                }
            } catch (\Throwable) {
                $revealAt = null;
            }
        }

        $canPurchase = in_array($effectiveMode, [
            self::SHOW_EVERYWHERE,
            self::DETAILS_ONLY,
            self::SHOW_AFTER_SELECTION,
        ], true);
        $showPrice = $effectiveMode === self::SHOW_EVERYWHERE
            || ($context === 'detail' && $effectiveMode === self::DETAILS_ONLY)
            || ($context === 'cart' && $canPurchase);
        $includePriceData = $showPrice
            || ($context === 'detail' && $effectiveMode === self::SHOW_AFTER_SELECTION)
            || $context === 'cart';

        return [
            'configured_mode' => $configuredMode,
            'effective_mode' => $effectiveMode,
            'show_price' => $showPrice,
            'include_price_data' => $includePriceData,
            'can_purchase' => $canPurchase,
            'message' => self::message($effectiveMode, $revealAt),
            'cta_label' => self::ctaLabel($effectiveMode),
            'visible_from' => $revealAt?->toIso8601String(),
        ];
    }

    private static function message(string $mode, ?Carbon $revealAt): string
    {
        if ($mode === self::COMING_SOON) {
            return $revealAt && $revealAt->isFuture()
                ? 'Price available from '.$revealAt->format('d-m-Y')
                : self::setting('PRICE_COMING_SOON_LABEL', 'Coming soon');
        }

        return match ($mode) {
            self::DETAILS_ONLY => 'View product for price',
            self::SHOW_AFTER_SELECTION => 'Select an option to see price',
            self::ENQUIRY_ONLY => self::setting('PRICE_ENQUIRY_LABEL', 'Contact us for price'),
            default => '',
        };
    }

    private static function ctaLabel(string $mode): string
    {
        return match ($mode) {
            self::DETAILS_ONLY => 'View Price',
            self::SHOW_AFTER_SELECTION => 'Choose Options',
            self::ENQUIRY_ONLY => self::setting('PRICE_ENQUIRY_BUTTON_LABEL', 'Enquire Now'),
            self::COMING_SOON => 'Coming Soon',
            default => 'Add to Cart',
        };
    }

    private static function validMode(?string $mode, bool $allowInherit = true): bool
    {
        $valid = array_keys(self::modes($allowInherit));

        return in_array($mode, $valid, true);
    }

    private static function setting(string $key, string $fallback): string
    {
        $configured = config($key);
        if (is_string($configured) && trim($configured) !== '') {
            return trim($configured);
        }

        $settings = Cache::remember('price_visibility_settings', now()->addMinutes(5), function () {
            return DB::table('settings')
                ->whereIn('key', [
                    'PRODUCT_PRICE_VISIBILITY_DEFAULT',
                    'SUBSCRIPTION_PRICE_VISIBILITY_DEFAULT',
                    'PRICE_ENQUIRY_LABEL',
                    'PRICE_ENQUIRY_BUTTON_LABEL',
                    'PRICE_COMING_SOON_LABEL',
                ])
                ->pluck('value', 'key')
                ->all();
        });

        return trim((string) ($settings[$key] ?? '')) ?: $fallback;
    }
}
