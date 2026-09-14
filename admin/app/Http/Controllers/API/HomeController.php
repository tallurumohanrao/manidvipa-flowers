<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Cache,DB,Validator;
use Illuminate\Support\Facades\Schema;
   
class HomeController extends BaseController
{
    public function cities(){
        $data[] = ['name'=>'Hyderabad'];
        return response()->json(['success' => true,'data' => $data], 200);
    }
    public function states(){
        $data[] = ['name'=>'Telangana'];
        return response()->json(['success' => true,'data' => $data], 200);
    }
    public function sitemap(){
        #$data = Cache::rememberForever('sitemap', function () {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');
            $categories = DB::table('categories')->select('title','slug','created_at','updated_at')->where('status', 1)->orderByDesc('priority')->get();
            $products = DB::table('products')->select('title','slug','created_at','updated_at')->where('status', 1)->orderByDesc('id')->limit(500)->get();
            $staticRoutes = [
                ['path' => '', 'changefreq' => 'daily', 'priority' => '1.0'],
                ['path' => '/flowers', 'changefreq' => 'daily', 'priority' => '0.95'],
                ['path' => '/puja-flowers', 'changefreq' => 'daily', 'priority' => '0.95'],
                ['path' => '/premium-flowers', 'changefreq' => 'daily', 'priority' => '0.9'],
                ['path' => '/rare-flowers', 'changefreq' => 'daily', 'priority' => '0.9'],
                ['path' => '/garlands', 'changefreq' => 'daily', 'priority' => '0.85'],
                ['path' => '/decorations', 'changefreq' => 'weekly', 'priority' => '0.85'],
                ['path' => '/gifts', 'changefreq' => 'daily', 'priority' => '0.85'],
                ['path' => '/subscriptions', 'changefreq' => 'weekly', 'priority' => '0.9'],
                ['path' => '/contact-us', 'changefreq' => 'monthly', 'priority' => '0.7'],
                ['path' => '/about', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ];
            foreach($staticRoutes as $route){
                $data[] = ['loc'=>$frontendUrl.$route['path'],'lastmod'=>date('Y-m-d\TH:i:sP'),'changefreq'=>$route['changefreq'],'priority'=>$route['priority']];
            }
            foreach($categories as $category){
                $date = $category->updated_at ?: $category->created_at;
                $data[] = ['loc'=>$frontendUrl.'/products/'.$category->slug,'lastmod'=>date('Y-m-d\TH:i:sP',strtotime($date)),'changefreq'=>'daily','priority'=>'0.8'];
                #$data[] = ['title'=>$category->title,'slug'=>$category->slug,'date'=>str_replace('+00:00', 'Z', gmdate('c', strtotime($category->created_at)))];
            }
            foreach($products as $product){
                $date = $product->updated_at ?: $product->created_at;
                $data[] = ['loc'=>$frontendUrl.'/product-details/'.$product->slug,'lastmod'=>date('Y-m-d\TH:i:sP',strtotime($date)),'changefreq'=>'daily','priority'=>'0.72'];
            }
            #return $data;
        #});
        return response()->json(['success' => true,'data' => $data], 200);
    }
    public function settings()
    {
        $data = Cache::rememberForever('settings', function () {
            $publicDeveloperKeys = [
                'GOOGLE_ANALYTICS_ID',
                'GOOGLE_SEARCH_CONSOLE_VERIFICATION',
            ];

            $result = DB::table('settings')
                ->select('label','key','value')
                ->where('status',1)
                ->where(function ($query) use ($publicDeveloperKeys) {
                    $query->whereIn('type',['Site','Contact','Social Media'])
                        ->orWhereIn('key', $publicDeveloperKeys);
                })
                ->get();
            foreach($result as $v){
                if(in_array($v->key,['SITE_LOGO','SITE_LOGO2','SITE_FAVICON'])){
                    $data[$v->key] = config('app.url') . '/storage/website/'.$v->value;
                }else{
                    $data[$v->key] = $v->value;
                }
            }
            return $data;
        });
        return response()->json(['success' => true,'data' => $data], 200);
    }
    public function banners(Request $request)
    {
        $page = $request->query('page', 'home');
        $page = $page === 'all' ? null : $page;
        $cacheKey = 'api_banners_' . ($page ?: 'all');
        $baseUrl = rtrim(config('app.url'), '/');

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($page, $baseUrl) {
            return DB::table('banners')
                ->select([
                    'id',
                    'title',
                    'alt',
                    'banner_text',
                    'url',
                    'button_text',
                    'page',
                    'parent_div_class',
                    'priority',
                    DB::raw('CASE WHEN image IS NULL OR image = "" THEN NULL ELSE CONCAT("' . $baseUrl . '/storage/banners/", image) END AS image_url'),
                ])
                ->where('status', 1)
                ->when($page, function ($query) use ($page) {
                    return $query->where('page', $page);
                })
                ->orderByRaw('COALESCE(priority, 999999) ASC')
                ->orderByDesc('id')
                ->get();
        });
        return response()->json(['success' => true,'data' => $data], 200);
    }
    public function contactStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'mobile' => 'required',
            'email' => 'required'
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $input = $request->all();
        if(DB::table('contacts')->insert($input)){
            return response()->json(['success' => true,'data' => 'Your request has been sent successfully.'], 200);
        }
        return response()->json(['success' => false,'message'=>'Server Error'], 500);
    }
    
    public function testimonials()
    {
        $data = Cache::rememberForever('api_testimonials', function () {
            return DB::table('testimonials')->select(['name', 'location', 'description',DB::raw('CONCAT("' . config('app.url') . '/storage/testimonials/", image) AS image_url')])->where('status',1)->get();
        });
        return response()->json(['success' => true,'data' => $data], 200);
    }
    
    public function staticPage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_name' => 'required',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $pageName = $request->page_name === 'refund-policy'
            ? 'refund-return-policy'
            : $request->page_name;

        $data = Cache::rememberForever('api_static_page_'.$pageName, function () use($pageName){
            return DB::table('pages')->select(['name', 'description'])->where(['slug'=>$pageName,'status'=>1])->first();
        });
        return response()->json(['success' => true,'data' => $data], 200);
    }

    public function faqs()
    {
        $data = Cache::rememberForever('api_faqs', function () {
            return DB::table('faqs')
                ->select(['id', 'question', 'answer', 'updated_at'])
                ->where('status', 1)
                ->orderBy('id')
                ->limit(12)
                ->get();
        });

        return response()->json(['success' => true,'data' => $data], 200);
    }

    public function dailyPriceStatus()
    {
        $latestLog = Schema::hasTable('price_update_logs')
            ? DB::table('price_update_logs')->orderByDesc('created_at')->orderByDesc('id')->first()
            : null;

        $now = now(config('app.timezone'));
        $lastUpdatedAt = $latestLog?->created_at
            ? \Carbon\Carbon::parse($latestLog->created_at)->timezone(config('app.timezone'))
            : null;
        $isUpdatedToday = $lastUpdatedAt?->isSameDay($now) ?? false;

        $data = [
            'is_updated_today' => $isUpdatedToday,
            'status_text' => $isUpdatedToday ? 'Prices Updated Today' : 'Prices Not Updated Today',
            'status_type' => $isUpdatedToday ? 'updated' : 'not_updated',
            'updated_time' => $isUpdatedToday ? $lastUpdatedAt->format('h:i A') : null,
            'last_updated_relative' => $lastUpdatedAt ? $this->relativePriceUpdateTime($lastUpdatedAt, $now) : null,
        ];

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    private function relativePriceUpdateTime(\Carbon\Carbon $lastUpdatedAt, \Carbon\Carbon $now): string
    {
        $diffInDays = (int) $lastUpdatedAt->copy()->startOfDay()->diffInDays($now->copy()->startOfDay());

        if ($diffInDays === 0) {
            return 'today';
        }

        if ($diffInDays === 1) {
            return '1 day ago';
        }

        return '2 days ago';
    }
    
    public function brands()
    {
        $data = Cache::rememberForever('api_brands', function () {
            return DB::table('brands')->where('status',1)->get();
        });
        return response()->json(['success' => true,'data' => $data], 200);
    }
    public function clients()
    {
        $data = Cache::rememberForever('api_clients', function () {
            return DB::table('clients')->select(DB::raw('CONCAT("' . config('app.url') . '/storage/categories/", image) AS image_url'))->where('status',1)->orderBy('priority')->get();
        });
        return response()->json(['success' => true,'data' => $data], 200);
    }
    
    public function featuredProducts()
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $query = DB::table('products')->select('products.id as product_id','title','slug','product_weights.sell_price','product_weights.list_price','product_images.name as image_name');
                if($user_id){
                    $query->addSelect(DB::raw("(SELECT id FROM wishlist WHERE wishlist.product_id  = products.id and user_id = $user_id) as wishlist_id"));
                }
                $data = $query->join('featured_products', 'products.id', '=', 'featured_products.product_id')
                ->leftJoin('product_weights', function ($weightsjoin) {
                    $weightsjoin->on('product_weights.id', '=', DB::raw('(SELECT id FROM product_weights WHERE product_weights.product_id = products.id LIMIT 1)'));
                })
                ->leftJoin('product_images', function ($imgjoin) {
                    $imgjoin->on('product_images.id', '=', DB::raw('(SELECT id FROM product_images WHERE product_images.product_id = products.id LIMIT 1)'));
                })
                ->where('products.status', 1)
                ->orderByRaw('COALESCE(featured_products.priority, 999999) ASC')
                ->orderByDesc('featured_products.id')
                ->get();
        return response()->json(['success' => true,'data' => $data], 200);
    }
    
    public function featuredProducts1()
    {
        $data = Cache::rememberForever('api_featured_products', function () {
            $homeCategories = null;
            $categories_products = DB::table('categories')
                ->join('category_product', 'category_product.category_id', '=', 'categories.id')
                ->join('featured_products', 'category_product.product_id', '=', 'featured_products.product_id')
                ->join('products', 'featured_products.product_id', '=', 'products.id')
                ->leftJoin('product_weights', function ($weightsjoin) {
                    $weightsjoin->on('product_weights.id', '=', DB::raw('(SELECT id FROM product_weights WHERE product_weights.product_id = products.id LIMIT 1)'));
                })
                #->where('categories.home_category',1)
                ->select('categories.title as category_title','products.title as product_title','product_weights.sell_price','product_weights.list_price','products.id as product_id')
                ->get();
                foreach($categories_products as $categories_product){
                    $pimage = DB::table('product_images')->where(['product_id'=>$categories_product->product_id,'status'=>1])->orderBy('priority')->first(); 
                    #$psize = DB::table('sizes')->where(['product_id'=>$categories_product->product_id,'status'=>1])->orderBy('sell_price')->first(); 
                    $homeCategories[$categories_product->category_title][] = ['product_title'=>$categories_product->product_title,'product_id'=>$categories_product->product_id,'sell_price'=>$categories_product->sell_price,'list_price'=>$categories_product->list_price,'discount'=>round(100-($categories_product->sell_price / $categories_product->list_price) * 100).' % off','image_url'=>$pimage->name];
                } 
                return $homeCategories;
        });
        return response()->json(['success' => true,'data' => $data], 200);
    }
    
    private function seoUrlCandidates(?string $url): array
    {
        $path = parse_url((string) $url, PHP_URL_PATH) ?: (string) $url;
        $path = '/'.trim($path, '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $candidates = [$path];

        if (preg_match('#^/product-details/(.+)$#', $path, $matches)) {
            $candidates[] = '/productDetails/'.$matches[1];
            $candidates[] = '/'.$matches[1];
        }

        if (preg_match('#^/productDetails/(.+)$#', $path, $matches)) {
            $candidates[] = '/product-details/'.$matches[1];
            $candidates[] = '/'.$matches[1];
        }

        if (preg_match('#^/products/(.+)$#', $path, $matches)) {
            $candidates[] = '/'.$matches[1];
        }

        return array_values(array_unique($candidates));
    }

    public function seoMetaData(Request $request){
        $url = $request->url;
        $data = Cache::rememberForever('api_seo_meta_data'.$url, function () use($url){
            $candidates = $this->seoUrlCandidates($url);
            $rows = DB::table('seo_urls')
                ->select('url','page_title','meta_keywords','meta_description','schema_markup','robots')
                ->whereIn('url', $candidates)
                ->where('status', 1)
                ->get()
                ->keyBy('url');

            foreach ($candidates as $candidate) {
                if ($rows->has($candidate)) {
                    return $rows->get($candidate);
                }
            }

            return null;
        });
        return response()->json(['success' => true,'data' => $data], 200);
    }
    
}
