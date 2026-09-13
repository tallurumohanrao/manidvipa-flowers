<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Artisan,Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{

    public function index(){
        $stats = [
            'products' => Schema::hasTable('products') ? DB::table('products')->count() : 0,
            'active_products' => Schema::hasTable('products') ? DB::table('products')->where('status', 1)->count() : 0,
            'orders' => Schema::hasTable('orders') ? DB::table('orders')->count() : 0,
            'customers' => Schema::hasTable('users') ? DB::table('users')->count() : 0,
            'low_stock' => Schema::hasTable('product_weights')
                ? DB::table('product_weights')->where('stock', 1)->where('qty', '<=', 5)->count()
                : 0,
            'subscription_enquiries' => Schema::hasTable('subscription_enquiries')
                ? DB::table('subscription_enquiries')->count()
                : 0,
        ];

        $recentOrders = Schema::hasTable('orders')
            ? DB::table('orders')->select('id', 'name', 'amount', 'created_at')->orderByDesc('id')->limit(5)->get()
            : collect();

        return view('admin.home', compact('stats', 'recentOrders'));
    }

    public function clear(){
        abort_if(Gate::denies('settings_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        Artisan::call('route:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        //create cache
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        return back()->with('success','All cache cleared.');
    }

    public function down(){
        abort_if(Gate::denies('settings_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        Artisan::call('down');
        return back()->with('success','Site is under maintenance.');
    }

    public function up(){
        abort_if(Gate::denies('settings_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        Artisan::call('up');
        return back()->with('success','Site is on live.');
    }

    public function upload(Request $request)
    {
        abort_if(!Gate::any([
            'pages_create', 'pages_edit',
            'posts_create', 'posts_edit',
            'contentblocks_create', 'contentblocks_edit',
            'services_create', 'services_edit',
        ]), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $request->validate([
            'upload' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,bmp,webp,pdf', 'max:8192'],
        ]);

        if($request->hasFile('upload')) {
            //get filename with extension
            $filenamewithextension = $request->file('upload')->getClientOriginalName();

            //get filename without extension
            $filename = \Illuminate\Support\Str::slug(pathinfo($filenamewithextension, PATHINFO_FILENAME)) ?: 'upload';

            //get file extension
            $extension = $request->file('upload')->getClientOriginalExtension();
            $file = $request->file('upload');
            //filename to store
            $filenametostore = $filename.'_'.time().'.'.$extension;

            //Upload File
            if(in_array($extension,['JPG','jpg','jpeg','JPEG','PNG','png','GIF','gif','BMP','bmp','WebP','webp','WEBP'])){
                Image::make($file->getRealPath())->save(storage_path('app/public/ckeditor/'.$filenametostore), 60);
            }else{
                $request->file('upload')->storeAs('public/ckeditor', $filenametostore);
            }

            $CKEditorFuncNum = $request->input('CKEditorFuncNum');
            $url = '/storage/ckeditor/'.$filenametostore;
            $msg = 'Uploaded successfully.';
            $re = "<script>window.parent.CKEDITOR.tools.callFunction($CKEditorFuncNum, '$url', '$msg')</script>";

            // Render HTML output
            @header('Content-type: text/html; charset=utf-8');
            echo $re;
        }
    }

}
