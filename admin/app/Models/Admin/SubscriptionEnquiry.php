<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionEnquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'plan_title',
        'name',
        'phone',
        'email',
        'organization_name',
        'business_type',
        'location',
        'preferred_delivery_time',
        'estimated_quantity',
        'message',
        'admin_notes',
        'source',
        'status',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}
