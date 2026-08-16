<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Str;

class Blog extends Model
{
    protected $guard = 'admin';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'tagline', 'cover_image', 'image', 'description', 'date_post', 'priority', 'changefreq', 'send_home', 'slug', 'status'];

    public function setSlugAttribute(){ 
        $this->attributes['slug'] = Str::Slug($this->name,'-');
    }
    
    public function comments(){
        return $this->hasMany(Comment::class,'post_id');
    }
}
