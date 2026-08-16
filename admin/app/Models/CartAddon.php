<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartAddon extends Model
{
    use HasFactory;

    protected $fillable = ['cart_id', 'addon_id', 'addon_name', 'price'];
}
