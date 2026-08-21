<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Admin\SubscriptionEnquiry;
use App\Models\Admin\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SubscriptionController extends Controller
{
    public function plans(Request $request)
    {
        $cacheKey = $request->boolean('featured')
            ? 'api_subscription_plans_featured'
            : 'api_subscription_plans';

        $plans = Cache::remember($cacheKey, 300, function () use ($request) {
            return SubscriptionPlan::query()
                ->where('status', 1)
                ->when($request->boolean('featured'), fn ($query) => $query->where('is_featured', 1))
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get()
                ->map(fn ($plan) => $this->formatPlan($plan))
                ->values();
        });

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    public function plan(Request $request)
    {
        $slug = $request->query('slug');

        if (! $slug) {
            return response()->json([
                'success' => false,
                'message' => 'Plan slug is required.',
            ], 422);
        }

        $plan = SubscriptionPlan::where('slug', $slug)->where('status', 1)->first();

        if (! $plan) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatPlan($plan),
        ]);
    }

    public function enquiry(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'nullable|integer|exists:subscription_plans,id',
            'name' => 'required|string|max:191',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:191',
            'organization_name' => 'nullable|string|max:191',
            'business_type' => 'nullable|string|max:80',
            'location' => 'nullable|string|max:191',
            'preferred_delivery_time' => 'nullable|string|max:191',
            'estimated_quantity' => 'nullable|string|max:191',
            'message' => 'nullable|string|max:5000',
        ]);

        $plan = null;

        if (! empty($validated['plan_id'])) {
            $plan = SubscriptionPlan::find($validated['plan_id']);
        }

        $enquiry = SubscriptionEnquiry::create([
            ...$validated,
            'plan_title' => $plan?->title,
            'business_type' => $validated['business_type'] ?? $plan?->business_type,
            'source' => 'website',
            'status' => 'New',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription enquiry submitted successfully. Our team will contact you shortly.',
            'data' => [
                'id' => $enquiry->id,
                'status' => $enquiry->status,
            ],
        ]);
    }

    private function formatPlan(SubscriptionPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'slug' => $plan->slug,
            'business_type' => $plan->business_type,
            'subscription_type' => $plan->subscription_type,
            'flower_grade' => $plan->flower_grade,
            'short_description' => $plan->short_description,
            'description' => $plan->description,
            'starting_price' => (float) $plan->starting_price,
            'price_label' => $this->formatPriceLabel($plan->starting_price),
            'price_suffix' => $plan->price_suffix,
            'billing_cycle' => $plan->billing_cycle,
            'delivery_frequency' => $plan->delivery_frequency,
            'included_quantity_text' => $plan->included_quantity_text,
            'included_arrangement_count' => $plan->included_arrangement_count,
            'arrangement_size' => $plan->arrangement_size,
            'refresh_frequency' => $plan->refresh_frequency,
            'flower_examples' => $this->splitLines($plan->flower_examples),
            'extra_quantity_note' => $plan->extra_quantity_note,
            'minimum_commitment' => $plan->minimum_commitment,
            'included_items' => $this->splitLines($plan->included_items),
            'features' => $this->splitLines($plan->features),
            'ideal_for' => $this->splitLines($plan->ideal_for),
            'cta_label' => $plan->cta_label ?: 'Request Plan',
            'image' => $plan->image,
            'image_url' => $this->imageUrl($plan),
            'sort_order' => $plan->sort_order,
            'is_featured' => (bool) $plan->is_featured,
        ];
    }

    private function splitLines(?string $value): array
    {
        return array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', (string) $value)
        )));
    }

    private function imageUrl(SubscriptionPlan $plan): ?string
    {
        if (! $plan->image) {
            return null;
        }

        if (str_starts_with($plan->image, 'http') || str_starts_with($plan->image, '/')) {
            return $plan->image;
        }

        return asset('storage/subscriptionplans/'.$plan->image);
    }

    private function formatPriceLabel($price): string
    {
        $amount = (float) $price;

        if ($amount <= 0) {
            return 'Custom Quote';
        }

        return '₹'.number_format($amount, 0);
    }
}
