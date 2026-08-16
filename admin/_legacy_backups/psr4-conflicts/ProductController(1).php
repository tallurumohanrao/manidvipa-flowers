<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB,Auth,Cache;

class ProductController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

     public function categories(Request $request,$slug)
     {
         $user_id = Auth::id();
         $perpage = config('PER_PAGE');
         $order = $request->orderby;
         $query = DB::table('products','p');
         $query->join('category_product', 'p.id', '=', 'category_product.product_id');
         $query->join('categories', 'categories.id', '=', 'category_product.category_id');
 
         $query->select('p.id','p.title','p.sku','p.slug');
         if($user_id){
             $query->addSelect(DB::raw("(SELECT count(*) FROM wishlist WHERE wishlist.product_id = p.id and user_id = $user_id) as is_wishlist"));
         }
 
         $query->selectSub(function ($squery) {
             $squery->selectRaw('CONCAT(MIN(sell_price),",",MAX(sell_price))')->from('product_sizes')->whereColumn('product_id', 'p.id');
         },'size_prices');
 
         $query->selectSub(function ($iquery) {
             $iquery->selectRaw('name')->from('product_images')->whereColumn('product_id', 'p.id')->orderBy('priority')->limit(1);
         },'image');
 
         $query->where('p.status', 1);
         $query->where('categories.slug', $slug);
         if($request->filled('q')){
             $query->where('p.title', 'like', $request->q .'%');
             $query->orWhere('p.sku', 'like', $request->q .'%');
         }
 
         if($order == 'price-asc'){
             $query->orderBy(function ($obq) {
                 $obq->selectRaw('MIN(sell_price) as min_sell_price')->from('product_sizes')->whereColumn('product_id', 'p.id');
             }, 'asc');
         }else if($order == 'price-desc'){
             $query->orderBy(function ($obq) {
                 $obq->selectRaw('MIN(sell_price) as min_sell_price')->from('product_sizes')->whereColumn('product_id', 'p.id');
             }, 'desc');
         }else{
             $query->orderByDesc('p.id');
         }
         $data = $query->paginate($perpage);
         $categories = DB::table('categories')->select('categories.*')->addSelect(DB::raw("(SELECT count(*) FROM category_product WHERE category_product.category_id = categories.id ) as total_products"))->where('status',1)->get();
         return view('products.index',compact('data','categories'));
     }

    public function store(Request $request){
        $user_id = Auth::id();
         $perpage = config('PER_PAGE');
         $order = $request->orderby;
         $query = DB::table('products','p');
         $query->join('category_product', 'p.id', '=', 'category_product.product_id');
         $query->join('categories', 'categories.id', '=', 'category_product.category_id');
 
         $query->select('p.id','p.title','p.sku','p.slug');
         if($user_id){
             $query->addSelect(DB::raw("(SELECT count(*) FROM wishlist WHERE wishlist.product_id = p.id and user_id = $user_id) as is_wishlist"));
         }
 
         $query->selectSub(function ($squery) {
             $squery->selectRaw('CONCAT(MIN(sell_price),",",MAX(sell_price))')->from('product_sizes')->whereColumn('product_id', 'p.id');
         },'size_prices');
 
         $query->selectSub(function ($iquery) {
             $iquery->selectRaw('name')->from('product_images')->whereColumn('product_id', 'p.id')->orderBy('priority')->limit(1);
         },'image');
 
         $query->where('p.status', 1);
         if($request->filled('q')){
             $query->where('p.title', 'like', $request->q .'%');
             $query->orWhere('p.sku', 'like', $request->q .'%');
         }
 
         if($order == 'price-asc'){
             $query->orderBy(function ($obq) {
                 $obq->selectRaw('MIN(sell_price) as min_sell_price')->from('product_sizes')->whereColumn('product_id', 'p.id');
             }, 'asc');
         }else if($order == 'price-desc'){
             $query->orderBy(function ($obq) {
                 $obq->selectRaw('MIN(sell_price) as min_sell_price')->from('product_sizes')->whereColumn('product_id', 'p.id');
             }, 'desc');
         }else{
             $query->orderByDesc('p.id');
         }
         $data = $query->paginate($perpage);
        $categories = DB::table('categories')->select('categories.*')->addSelect(DB::raw("(SELECT count(*) FROM category_product WHERE category_product.category_id = categories.id ) as total_products"))->where('status',1)->get();
        
        $banner = Cache::rememberForever('store_banner', function () {
            return DB::table('banners')->where(['status'=>1,'page'=>'store'])->orderBy('priority')->first();
        });
        return view('products.store',compact('data','categories','banner'));
    }

    public function show($slug)
    {
        $user_id = Auth::id();
        $query = DB::table('products');
        $query->select('products.*');
        if($user_id){
            $query->addSelect(DB::raw("(SELECT count(*) FROM wishlist WHERE wishlist.product_id = products.id and user_id = $user_id) as is_wishlist"));
        }
        $query->where('slug',$slug);
        $product = $query->first();

        // $review_ratings = DB::table('review_ratings')->select('*')->addSelect(DB::raw('(SELECT SUM(star_rating) as total_rating FROM review_ratings GROUP BY product_id)'))->where('product_id',$product->id)->get(); dd($review_ratings)


        $review_rating_total = DB::table('review_ratings')->selectRaw('SUM(star_rating) as total_rating')->groupBy('product_id')->where(['product_id'=>$product->id,'status'=>1])->first();
        $review_ratings = DB::table('review_ratings')->where(['product_id'=>$product->id,'status'=>1])->orderByDesc('id')->get();
        $productImages = DB::table('product_images')->where(['product_id'=>$product->id,'status'=>1])->get();
        $sizes = DB::table('product_sizes')->where(['product_id'=>$product->id,'status'=>1])->get();
        $productCategory = DB::table('category_product')->where('product_id',$product->id)->get()->pluck('category_id')->toArray();
        $categories = DB::table('categories')->whereIn('id',$productCategory)->get();
        $size = DB::table('product_sizes')->selectRaw('MIN(sell_price) as min_sell_price,MAX(sell_price) as max_sell_price')->where(['product_id'=>$product->id,'status'=>1])->first();
        return view('products.show',compact('product','productImages','sizes','categories','size','review_ratings','review_rating_total'));
    }

    public function addRemoveWishlist(Request $request){
        if(!Auth::check()){
            return response()->json(['status'=>'fail','message'=>"login_error"]);
        }
        $userId = Auth::id();
        $arr = ['product_id'=> $request->id,'user_id'=>$userId];
        if (DB::table('wishlist')->where($arr)->doesntExist()) {
            $arr['created_at'] = date('Y-m-d H:i:s');
            $id = DB::table('wishlist')->insertGetId($arr);
            return response()->json(['status'=>'success','action'=>'remove']);
        }else{
            DB::table('wishlist')->where($arr)->delete();
            return response()->json(['status'=>'success','action'=>'add']);
        }
    }

    public function reviewStore(Request $request){
        $insert = $request->all();
        $insert['product_id'] = $request->product_id;
        $insert['created_at'] = date('Y-m-d H:i:s'); 
        if (DB::table('review_ratings')->insert($insert)) {
            return response()->json(['status'=>'success','message'=>'Review added successfully.']);
        }else{
            return response()->json(['status'=>'success','message'=>'Unexpected error occured!.']);
        }
    }
}
