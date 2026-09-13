<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use App\Cart\CartData;
use Cache,Config,View,Validator,DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind('cartdata',function($app){
            return new CartData;
        });

        if(session('session_cart') == null){
            session()->put('session_cart',uniqid());
        }
        //Validator::extend('recaptcha', 'App\\Validators\\ReCaptcha@validate');

        Paginator::useBootstrap();
        Schema::defaultStringLength(191);
        Config::set('PER_PAGE', (int) config('PER_PAGE') ?: 10);
        Config::set('ADMIN_PER_PAGE', (int) config('ADMIN_PER_PAGE') ?: 10);

        if ($this->app->runningInConsole()) {
            return;
        }

        $settings = Cache::rememberForever('configurations', function () {
             return DB::table('settings')->where('status',1)->get();
        });
        foreach($settings as $setting) :
            Config::set($setting->key,$setting->value);
        endforeach;

        View::composer('includes.seo', function($view)
        {
            $path = request()->path();
            $pre="/";
            $url = $path=="/" ? $path : $pre.$path;
            $seo = Cache::rememberForever('seo-'.$path, function () use ($url){
                $result = DB::table('seo_urls')->where(['url'=>$url,'status'=>1])->first();
                if($result){
                    return
                        ['page_title'=>$result->page_title,
                        'meta_description'=>$result->meta_description,
                        'meta_keywords'=>$result->meta_keywords,
                        'robots'=>$result->robots
                    ];
                }
                return;
            });
            $view->with('seo', $seo);
        });

        View::composer(['layouts.app','home'], function($view)
        {
            $primarymenu = Cache::rememberForever('primarymenu', function (){
                return DB::table('categories')->whereStatus(1)->get();
            });
            $clients = Cache::rememberForever('clients', function (){
                return DB::table('clients')->whereStatus(1)->get();
            });
            $categories = DB::table('categories')->select('categories.*')->addSelect(DB::raw("(SELECT count(*) FROM category_product WHERE category_product.category_id = categories.id ) as total_products"))->where('status',1)->get();
            $view->with(['clients'=>$clients,'primarymenu'=>$primarymenu,'categories'=>$categories]);
        });
    }
}
