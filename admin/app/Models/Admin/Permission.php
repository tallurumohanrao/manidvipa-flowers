<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['group_name', 'module', 'route_name', 'view', 'create', 'edit', 'delete', 'menu_status', 'group_sort_order', 'module_sort_order', 'icon_class', 'status'];
}
