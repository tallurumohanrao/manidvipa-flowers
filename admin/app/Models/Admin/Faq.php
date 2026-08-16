<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [ 'question', 'answer','status' ]; 
}
