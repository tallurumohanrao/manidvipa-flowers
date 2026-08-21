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
        $scope = $request->query('scope');
        $data = DB::table('categories as c')
            ->leftJoin('categories as parent', 'parent.id', '=', 'c.parent_id')
            ->select([
                'c.id',
                'c.title',
                'c.slug',
                'c.short_description',
                'c.parent_id',
                'parent.title as parent_title',
                'parent.slug as parent_slug',
                'c.home_category',
                'c.priority',
                'c.image',
                DB::raw('CASE WHEN c.image IS NULL OR c.image = "" THEN NULL ELSE CONCAT("' . $baseUrl . '/storage/categories/", c.image) END AS image_url'),
            ])
            ->where('c.status',1)
            ->when($scope === 'home', function ($query) {
                return $query->where('c.home_category', 1)->whereNull('c.parent_id');
            })
            ->orderByRaw('COALESCE(c.priority, 999999) ASC')
            ->orderBy('c.id')
            ->get()
            ->map(function ($category) {
                $routeSlugs = [
                    'primimum-flowers' => 'premium-flowers',
                    'premium' => 'premium-flowers',
                    'rare' => 'rare-flowers',
                    'garland' => 'garlands',
                ];

                $category->route_slug = $routeSlugs[$category->slug] ?? $category->slug;
                return $category;
            });
        return response()->json(['success' => true,'data' => $data], 200);
    }
}
