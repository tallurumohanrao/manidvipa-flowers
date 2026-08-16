<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Validator,Cache,DB;
use App\Http\Resources\Product as ProductResource;
   
class ProductController extends BaseController
{
    public function search(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
         $category_slug = $request->category_slug;
         $perpage = config('PER_PAGE'); 
         $order = $request->orderby;
         $query = DB::table('products','p');
         $query->join('category_product', 'p.id', '=', 'category_product.product_id');
         $query->join('categories', 'categories.id', '=', 'category_product.category_id');
 
         $query->select(
             'p.id',
             'p.id as product_id',
             'p.title',
             'p.sku',
             'p.slug',
             'product_weights.id as weight_id',
             'product_weights.name as weight_name',
             'product_weights.sell_price',
             'product_weights.list_price',
             'product_images.name as image_name',
             'categories.slug as category_slug',
             'categories.title as category_title'
         );
         if($user_id){
            $query->addSelect(DB::raw("(SELECT id FROM wishlist WHERE wishlist.product_id  = p.id and user_id = $user_id) as wishlist_id"));
        }
         $query->leftJoin('product_images', function ($imgjoin) {
                $imgjoin->on('product_images.id', '=', DB::raw('(SELECT id FROM product_images WHERE product_images.product_id = p.id LIMIT 1)'));
            });
         $query->leftJoin('product_weights', function ($weightsjoin) {
                $weightsjoin->on('product_weights.id', '=', DB::raw('(SELECT id FROM product_weights WHERE product_weights.product_id = p.id LIMIT 1)'));
            });
 
         $query->where('p.status', 1);
         if($request->filled('category_slug')){
            $query->where('categories.slug', $category_slug);
         }
         if($request->filled('q')){
             $query->where(function($searchQuery) use ($request) {
                 $searchQuery->where('p.title', 'like', $request->q .'%')
                     ->orWhere('p.sku', 'like', $request->q .'%');
             });
         }
 
         if($order == 'price-asc'){
             $query->orderBy(function ($obq) {
                 $obq->selectRaw('MIN(sell_price) as min_sell_price')->from('product_weights')->whereColumn('product_id', 'p.id');
             }, 'asc');
         }else if($order == 'price-desc'){
             $query->orderBy(function ($obq) {
                 $obq->selectRaw('MIN(sell_price) as min_sell_price')->from('product_weights')->whereColumn('product_id', 'p.id');
             }, 'desc');
         }else{
             $query->orderByDesc('p.id');
         }
         $data = $query->paginate($perpage);
         return response()->json(['success' => true,'data' => $data], 200);
    }
    public function productsbycategory(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        
         $category_slug = $request->category_slug;
         $show_all_flowers = empty($category_slug) || in_array($category_slug, ['all-flowers', 'flowers', 'all']);
         $category = null;
         if(!$show_all_flowers){
             $category = DB::table('categories')->where('slug',$category_slug)->where('status',1)->first();
         }
         if(!$show_all_flowers && !$category){
             return response()->json(['success' => false,'message' => 'Page not found.'], 404);
         }
         
         $perpage = config('PER_PAGE');
         $order = $request->orderby;
         $query = DB::table('products','p');
         if(!$show_all_flowers){
             $query->join('category_product', 'p.id', '=', 'category_product.product_id');
             $query->join('categories', 'categories.id', '=', 'category_product.category_id');
         }
 
        $query->select(
            'p.id',
            'p.id as product_id',
            'p.title',
            'p.sku',
            'p.slug',
            'product_weights.id as weight_id',
            'product_weights.name as weight_name',
            'product_weights.sell_price',
            'product_weights.list_price',
            'product_images.name as image_name',
            DB::raw('(SELECT categories.slug FROM categories INNER JOIN category_product ON category_product.category_id = categories.id WHERE category_product.product_id = p.id AND categories.status = 1 ORDER BY categories.priority LIMIT 1) as category_slug'),
            DB::raw('(SELECT categories.title FROM categories INNER JOIN category_product ON category_product.category_id = categories.id WHERE category_product.product_id = p.id AND categories.status = 1 ORDER BY categories.priority LIMIT 1) as category_title')
        );
        if($user_id){
            $query->addSelect(DB::raw("(SELECT id FROM wishlist WHERE wishlist.product_id  = p.id and user_id = $user_id) as wishlist_id"));
        }
        $query->leftJoin('product_images', function ($imgjoin) {
                $imgjoin->on('product_images.id', '=', DB::raw('(SELECT id FROM product_images WHERE product_images.product_id = p.id LIMIT 1)'));
            });
        $query->leftJoin('product_weights', function ($weightsjoin) {
                $weightsjoin->on('product_weights.id', '=', DB::raw('(SELECT id FROM product_weights WHERE product_weights.product_id = p.id LIMIT 1)'));
            });
         $query->where('p.status', 1);
         if(!$show_all_flowers){
             $query->where('categories.slug', $category_slug);
         }
         if($request->filled('q')){
             $query->where(function($searchQuery) use ($request) {
                 $searchQuery->where('p.title', 'like', $request->q .'%')
                     ->orWhere('p.sku', 'like', $request->q .'%');
             });
         }
 
         if($order == 'price-asc'){
             $query->orderBy(function ($obq) {
                 $obq->selectRaw('MIN(sell_price) as min_sell_price')->from('product_weights')->whereColumn('product_id', 'p.id');
             }, 'asc');
         }else if($order == 'price-desc'){
             $query->orderBy(function ($obq) {
                 $obq->selectRaw('MIN(sell_price) as min_sell_price')->from('product_weights')->whereColumn('product_id', 'p.id');
             }, 'desc');
         }else{
             $query->orderByDesc('p.id');
         }
         $data = $query->paginate($perpage);
         return response()->json(['success' => true,'data' => $data], 200);
    }
     
     
    public function categories1()
    {
        $categories = Cache::rememberForever('api_categories', function () {
            // return DB::select('select `id`, `name`, `title`, `image`, `short_description`, `description`, `home_category`, `parent_id` from `categories` where status = 1 order by `priority` asc');
            return DB::table('categories')->select(['id', 'name', 'title',DB::raw('CONCAT("' . config('app.url') . '/storage/categories/", image) AS image_url')])->where('status',1)->orderBy('priority')->get();
        });
        $response = [
            'success' => true,
            'data'    => $categories
        ];
        return response()->json($response, 200);
    }
    public function productsbycategory1(Request $request)
    {
        $id = $request->id;
        $page = !empty($request->page) ? $request->page : '1';
        $search = DB::table('products');
        $search->select('id','title','sku');
        $search->addSelect(DB::raw("(SELECT pi.name FROM products as p inner join product_images as pi on p.id=pi.product_id limit 1) as product_image"));
        if ($request->filled('title')) {
            $search->where('products.title', 'like','%' .$request->input('title'). '%');
        }

        if ($request->filled('from_price')) {
            $search->where('products.list_price', '>=' .$request->from_price);
        }
        
        if ($request->filled('to_price')) {
            $search->where('products.list_price', '<=' .$request->to_price);
        }
        $search->where('products.status',1);
        $sort = $request->input('sort');
        if ($sort == 'hightolow') {
            $search->orderByDesc('products.sell_price');
        }else if ($sort == 'lowtohigh') {
            $search->orderBy('products.sell_price');
        }else if ($sort == 'newest') {
            $search->orderByDesc('products.id');
        }
        $products = $search->paginate(config('PER_PAGE'))->withQueryString();
        
        $totalPages = (int) ceil($products->total() / $products->perPage());
        
        $response = [
            'success' => true,
            'products'    => $products,
            'next' => $page + 1,
            'totalPages' => $totalPages
        ];
        return response()->json($response, 200);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::all();
    
        return $this->sendResponse(ProductResource::collection($products), 'Products retrieved successfully.');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'name' => 'required',
            'detail' => 'required'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $product = Product::create($input);
   
        return $this->sendResponse(new ProductResource($product), 'Product created successfully.');
    } 
   
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $user_id = null;
        if(auth('sanctum')->check()){
            $user = auth('sanctum')->user();
            $user_id = $user->id;
        }
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_slug' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        #$product = DB::table('products')->where('id',$request->product_id)->first();
        $search = DB::table('products');
        #$search->with(['sizes','images']);
        $search->select('id','title','sku','description');
        if($user_id){
            $search->addSelect(DB::raw("(SELECT id FROM wishlist WHERE wishlist.product_id  = products.id and user_id = $user_id) as wishlist_id"));
        }
        $search->where('slug',$request->product_slug);
        $product = $search->first();
        if($product == null){
            return response()->json(['success'=>false,'message'=>'Page not found.'],404);
        }
        $images = DB::table('product_images')->select('name')->where('product_id',$product->id)->get();
        #$sizes = DB::table('product_sizes')->select('id','name','sell_price','list_price')->where('product_id',$product->id)->get();DB::raw('CONCAT("' . config('app.url') . '/storage/products/", name) AS url')
        $weights = DB::table('product_weights')->select('id','name','sell_price','list_price')->where('product_id',$product->id)->get();
        return response()->json(['success'=>true,'data'=>$product,'images'=>$images,'weights'=>$weights],200);
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'name' => 'required',
            'detail' => 'required'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
   
        $product->name = $input['name'];
        $product->detail = $input['detail'];
        $product->save();
   
        return $this->sendResponse(new ProductResource($product), 'Product updated successfully.');
    }
   
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();
   
        return $this->sendResponse([], 'Product deleted successfully.');
    }
    
    public function reviews(Request $request)
    {
        $data = DB::table('review_ratings')->select('name','comment','star_rating')->where(['product_id'=>$request->product_id,'status'=>1])->get();
        return response()->json(['success'=>true,'data'=>$data],200);
    }
    
    public function addReview(Request $request)
    {
        $input = $request->all();
   
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'name' => 'required',
            'email' => 'required',
            'comment' => 'required',
            'star_rating' => 'required',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        if(DB::table('review_ratings')->where(['product_id'=>$request->product_id,'email'=>$request->email])->exists()){
            return response()->json(['success'=>false,'message'=>'You have already submitted a review.'],500);
        }
        
        if(DB::table('review_ratings')->insert($input)){
            return response()->json(['success'=>true,'message'=>'Review added successfully.'],200);
        }else{
            return response()->json(['success'=>false,'message'=>'Server Error.'],500);
        }
    }
}
