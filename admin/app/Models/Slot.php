<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Slot extends Model
{
    use HasFactory;

    protected $fillable = ['start_time', 'timings', 'status'];

}
