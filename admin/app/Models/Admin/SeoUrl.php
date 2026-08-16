<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Config;

class SeoUrl extends Model
{
    
    protected $fillable = ['url', 'alias', 'page_title', 'meta_keywords', 'meta_description', 'robots','status'];

}