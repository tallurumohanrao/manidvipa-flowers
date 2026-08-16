<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $guard = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'description', 'architects', 'location', 'category', 'area', 'project_year', 'manufactures', 'type', 'slug', 'status'];

    public function images(){
        return $this->hasMany(ProjectImage::class);
    }

    public function activeImages(){
        return $this->hasMany(ProjectImage::class)->whereStatus(1)->orderBy('priority');
    }

    public function latestImage(){
        return $this->hasOne(ProjectImage::class)->orderBy('priority');
    }

}
