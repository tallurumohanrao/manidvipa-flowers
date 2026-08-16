<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Cache,DB,Validator;
   
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
            $categories = DB::table('categories')->select('title','slug','created_at')->orderByDesc('priority')->get();
            $data[] = ['loc'=>config('app.frontend_url'),'lastmod'=>date('Y-m-d\TH:i:sP'),'changefreq'=>'daily','priority'=>'1.0'];
            foreach($categories as $category){
                $data[] = ['loc'=>config('app.frontend_url').'/categories/'.$category->slug,'lastmod'=>date('Y-m-d\TH:i:sP',strtotime($category->created_at)),'changefreq'=>'daily','priority'=>'1.0'];
                #$data[] = ['title'=>$category->title,'slug'=>$category->slug,'date'=>str_replace('+00:00', 'Z', gmdate('c', strtotime($category->created_at)))];
            }
            #return $data;
        #});
        return response()->json(['success' => true,'data' => $data], 200);
    }
    public function settings()
    {
        $data = Cache::rememberForever('settings', function () {
            $result = DB::table('settings')->select('label','key','value')->where('status',1)->whereIn('type',['Site','Contact','Social Media'])->get();
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
        $data = Cache::rememberForever('api_static_page_'.$request->page_name, function () use($request){
            return DB::table('pages')->select(['name', 'description'])->where(['slug'=>$request->page_name,'status'=>1])->first();
        });
        return response()->json(['success' => true,'data' => $data], 200);
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
                })->get();
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
    
    public function seoMetaData(Request $request){
        $url = $request->url;
        $data = Cache::rememberForever('api_seo_meta_data'.$url, function () use($url){
            return DB::table('seo_urls')->select('url','page_title','meta_keywords','meta_description','robots')->where(['url'=>$url,'status'=>1])->first();
        });
        return response()->json(['success' => true,'data' => $data], 200);
    }
    
}
