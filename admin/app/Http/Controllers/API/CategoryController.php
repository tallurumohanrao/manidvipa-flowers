<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator,Cache,DB;

class CategoryController extends BaseController
{
    public function index(Request $request)
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $data = DB::table('categories as c')
            ->select([
                'c.id',
                'c.title',
                'c.slug',
                'c.short_description',
                'c.priority',
                'c.image',
                DB::raw('CASE WHEN c.image IS NULL OR c.image = "" THEN NULL ELSE CONCAT("' . $baseUrl . '/storage/categories/", c.image) END AS image_url'),
            ])
            ->where('c.status',1)
            ->orderByRaw('COALESCE(c.priority, 999999) ASC')
            ->orderBy('c.id')
            ->get();
        return response()->json(['success' => true,'data' => $data], 200);
    }
}
