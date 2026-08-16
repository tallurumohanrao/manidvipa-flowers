<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Admin;
use Str;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'post_id', 'name', 'email', 'message','is_visable' ];

    public function parent(){
        return $this->belongsTo(Post::class, 'post_id');
    } 
}
