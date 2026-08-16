<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreContactRequest;
use DB,Str,Cache,Mail;
use Illuminate\Validation\ValidationException;

class HomeController extends Controller
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
    public function index()
    {
        $page = Cache::rememberForever('home', function () {
            return DB::table('pages')->where(['id'=>3,'status'=>1])->first();
        });
        abort_if(!$page, 404);

        $banners = Cache::rememberForever('banners', function () {
            return DB::table('banners')->where(['status'=>1,'page'=>'home'])->orderBy('priority')->get();
        });

        $testimonials = Cache::rememberForever('testimonials', function () {
            return DB::table('testimonials')->where('status',1)->get();
        });

        $featuredProducts = DB::table('featured_products')->orderBy('priority')->get();
        $query =DB::table('categories');
        $query->selectRaw('categories.title as category_title,products.title as product_title,products.id as product_id,products.slug as product_slug')
                ->join('category_product', 'category_product.category_id', '=', 'categories.id')
                ->join('featured_products', 'category_product.product_id', '=', 'featured_products.product_id')
                ->join('products', 'featured_products.product_id', '=', 'products.id');
        $query->selectSub(function ($squery) {
            $squery->selectRaw('CONCAT(MIN(sell_price),",",MAX(sell_price))')->from('product_sizes')->whereColumn('product_id', 'products.id');
        },'size_prices');

        $query->selectSub(function ($iquery) {
            $iquery->selectRaw('name')->from('product_images')->whereColumn('product_id', 'products.id')->orderBy('priority')->limit(1);
        },'image');
                $categories_products = $query->get();
        $pricerange = null;
        foreach($categories_products as $categories_product){
            if($size_prices = $categories_product->size_prices){
                $price = explode(',',$size_prices);
                $pricerange = currency($price[0]) .' - '. currency($price[1]);
            }
            $homeCategories[$categories_product->category_title][] = [
                                                                        'category_title'=>$categories_product->category_title,
                                                                        'product_title'=>$categories_product->product_title,
                                                                        'product_id'=>$categories_product->product_id,
                                                                        'product_slug'=>$categories_product->product_slug,
                                                                        'product_image'=>$categories_product->image,
                                                                        'sell_price'=>$pricerange];
        }

        return view('home',compact('page','banners','testimonials','homeCategories'));
    }

    public function categories()
    {
        $categories = Cache::rememberForever('categories', function () {
            return DB::table('categories')->where('status',1)->get();
        });
        return view('categories',compact('categories'));
    }

    public function contact()
    {
        $page = Cache::rememberForever('contact_page', function () {
            return DB::table('pages')->where(['id'=>5,'status'=>1])->first();
        });

        $banner = Cache::rememberForever('contact_banner', function () {
            return DB::table('banners')->where(['status'=>1,'page'=>'contact-us'])->orderBy('priority')->first();
        });
        $products = ['Tanjore Painitngs','3d Embossed Tanjore Painitng','Customized Tanjore painitngs','Free Pooja Room Design','Corporate Gifting'];
        return view('contact',compact('products','banner','page'));
    }

    public function posts()
    {
        $banner = Cache::rememberForever('posts_banner', function () {
            return DB::table('banners')->where(['status'=>1,'page'=>'blog'])->orderBy('priority')->first();
        });
        $data = DB::table('posts')->where('status',1)->orderByDesc('id')->paginate(config('PER_PAGE'));
        return view('posts.index',compact('data','banner'));
    }

    public function postView($slug)
    {
        $banner = Cache::rememberForever('posts_details_banner', function () {
            return DB::table('banners')->where(['status'=>1,'page'=>'blog-details'])->orderBy('priority')->first();
        });
        $data = DB::table('posts')->where(['slug'=>$slug,'status'=>1])->first();
        return view('posts.show',compact('data','banner'));
    }

    public function gallery()
    {
        // $page = Cache::rememberForever('gallery', function () {
        //     return DB::table('pages')->where(['id'=>2,'status'=>1])->first();
        // });
        // abort_if(!$page, 404);
        $banner = Cache::rememberForever('gallery_banner', function () {
            return DB::table('banners')->where(['status'=>1,'page'=>'gallery'])->orderBy('priority')->first();
        });
        $images = DB::table('galleries')->where('status',1)->orderBy('priority')->get();
        return view('gallery',compact('images','banner'));
    }

    public function services()
    {
        $services = Cache::rememberForever('services', function () {
            $data = DB::table('services as s')->selectRaw('c.title as category_title,s.name,s.title,s.price,s.type,s.image,s.description,s.helpline_1,s.helpline_2')->join('categories as c','s.category_id','=','c.id')->where('s.status',1)->orderByDesc('s.priority')->get();
            #dd($data);
            $result = [];
            foreach($data as $v){
                $result[$v->category_title][$v->type][] = $v;
            }
            return $result;
        });
        #dd($services);
        $testimonials = Cache::rememberForever('testimonials', function () {
            return DB::table('testimonials')->where('status',1)->get();
        });

        return view('services.index',compact('services','testimonials'));
    }

    public function contactStore(StoreContactRequest $request)
    {
        $create = $request->except('_token');
        if(Str::contains($create['message'],miscWords())){
            if($request->type == 1){
            return response()->json(['status' => 'fail','message' => 'You have entered miscellaneous data or special charecters not allowed.']);
            }else{
                throw ValidationException::withMessages(['message' => 'You have entered miscellaneous data or special charecters not allowed.']);
                return redirect()->route('contact')->withInput();
            }
        }
        $create['subject'] = $request->filled('subject') ? implode(',',$create['subject']) : null;
        $create['created_at'] = date('Y-m-d H:i:s');
        DB::table('contacts')->insert($create);
        $adminBodyHtml = (string)view('emails.admin-contact',compact('create'));
        $customerBodyHtml = (string)view('emails.contact',compact('create'));

        Mail::html($customerBodyHtml, function($message) use($create){
            $message->to($create['email'], $create['name'])->subject('Thank you for contacting '.config('SITE_NAME'));
            $message->from(config('SITE_EMAIL'), config('SITE_NAME'));
        });
        Mail::html($adminBodyHtml, function($message) use($create){
            $message->to(config('SITE_EMAIL'), config('SITE_NAME'))->subject('New contact request recived from '.$create['name']);
            $message->from($create['email'], $create['name']);
        });
        if(DB::table('contacts')->insertGetId($create)){
            return response()->json(['status' => 'success','message' => '<strong>Thank you for contacting us!</strong> We`ll get back to you as soon as possible.']);
        }else{
            return response()->json(['status' => 'error','message' => '<strong>Error!</strong> Unexpected error occured.']);
        }
    }

    public function about()
    {
        $banner = Cache::rememberForever('about_banner', function () {
            return DB::table('banners')->where(['status'=>1,'page'=>'about-us'])->orderBy('priority')->first();
        });
        $page = Cache::rememberForever('about', function () {
            return DB::table('pages')->where(['id'=>1,'status'=>1])->first();
        });
        abort_if(!$page, 404);
        return view('page',compact('page','banner'));
    }

    public function terms()
    {
        $page = Cache::rememberForever('terms', function () {
            return DB::table('pages')->where(['id'=>4,'status'=>1])->first();
        });
        abort_if(!$page, 404);
        return view('page',compact('page'));
    }

    public function privacy()
    {
        $page = Cache::rememberForever('privacy', function () {
            return DB::table('pages')->where(['id'=>3,'status'=>1])->first();
        });
        abort_if(!$page, 404);
        return view('page',compact('page'));
    }

    public function refund()
    {
        $page = Cache::rememberForever('refund', function () {
            return DB::table('pages')->where(['id'=>2,'status'=>1])->first();
        });
        abort_if(!$page, 404);
        return view('page',compact('page'));
    }
    
    public function sitemap()
    {
        $categories = DB::table('categories')->whereStatus(1)->get();
        $products = DB::table('products')->whereStatus(1)->get();
        $posts = DB::table('posts')->whereStatus(1)->get();
        return response()->view('sitemap',compact('categories','products','posts'))->header('Content-Type', 'text/xml');
    }
}
