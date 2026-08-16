<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB,Auth,Cache;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $data = DB::table('categories as c')->select('c.title','c.slug')->where('c.status',1)->get();
        return view('products.index',compact('data'));
    }
}
