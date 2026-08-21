<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'business_type',
        'subscription_type',
        'flower_grade',
        'short_description',
        'description',
        'starting_price',
        'price_suffix',
        'billing_cycle',
        'delivery_frequency',
        'included_quantity_text',
        'included_arrangement_count',
        'arrangement_size',
        'refresh_frequency',
        'flower_examples',
        'extra_quantity_note',
        'minimum_commitment',
        'included_items',
        'features',
        'ideal_for',
        'cta_label',
        'image',
        'sort_order',
        'is_featured',
        'status',
    ];

    protected $casts = [
        'starting_price' => 'decimal:2',
        'sort_order' => 'integer',
        'is_featured' => 'boolean',
        'status' => 'boolean',
    ];

    public function enquiries()
    {
        return $this->hasMany(SubscriptionEnquiry::class, 'plan_id');
    }
}
