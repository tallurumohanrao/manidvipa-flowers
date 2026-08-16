<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $guard = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['category_id','name','title', 'price', 'image', 'icon', 'description', 'helpline_1', 'helpline_2', 'type', 'priority', 'status'];

    public function parent(){
        return $this->belongsTo(Category::class,'category_id');
    }
}
