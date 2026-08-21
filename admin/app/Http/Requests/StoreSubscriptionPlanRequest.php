<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:191',
            'business_type' => 'required|string|max:80',
            'subscription_type' => 'required|string|max:80',
            'flower_grade' => 'nullable|string|max:80',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'starting_price' => 'required|numeric|min:0',
            'price_suffix' => 'nullable|string|max:50',
            'billing_cycle' => 'required|string|max:50',
            'delivery_frequency' => 'nullable|string|max:120',
            'included_quantity_text' => 'nullable|string|max:255',
            'included_arrangement_count' => 'nullable|string|max:255',
            'arrangement_size' => 'nullable|string|max:120',
            'refresh_frequency' => 'nullable|string|max:120',
            'flower_examples' => 'nullable|string',
            'extra_quantity_note' => 'nullable|string',
            'minimum_commitment' => 'nullable|string|max:120',
            'included_items' => 'nullable|string',
            'features' => 'nullable|string',
            'ideal_for' => 'nullable|string',
            'cta_label' => 'nullable|string|max:80',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'sort_order' => 'nullable|integer|min:0',
            'is_featured' => 'required|in:0,1',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Plan title is required.',
            'business_type.required' => 'Business type is required.',
            'subscription_type.required' => 'Package type is required.',
            'starting_price.required' => 'Starting price is required.',
            'billing_cycle.required' => 'Billing cycle is required.',
            'is_featured.required' => 'Featured status is required.',
            'status.required' => 'Status is required.',
        ];
    }
}
