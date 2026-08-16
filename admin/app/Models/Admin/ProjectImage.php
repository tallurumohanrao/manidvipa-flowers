<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ProjectImage extends Model
{
    protected $fillable = ['project_id', 'title', 'alt', 'image', 'status', 'priority'];
}
