<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Str;

class Post extends Model
{
    protected $fillable = [ 'title','category', 'banner', 'image', 'short_description', 'description', 'twitter', 'linkedin', 'facebook','youtube','instagram', 'skype','slug', 'added_by', 'priority', 'status' ];

    public function setSlugAttribute(){
        $this->attributes['slug'] = Str::slug($this->title, "-");
    }

    public function comments(){
        return $this->hasMany(Comment::class);
    }
}
