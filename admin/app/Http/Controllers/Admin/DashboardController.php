<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Artisan,Image;

class DashboardController extends Controller
{

    public function index(){

        return view('admin.home');
    }

    public function clear(){
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
        Artisan::call('down');
        return back()->with('success','Site is under maintenance.');
    }

    public function up(){
        Artisan::call('up');
        return back()->with('success','Site is on live.');
    }

    public function upload(Request $request)
    {
        if($request->hasFile('upload')) {
            //get filename with extension
            $filenamewithextension = $request->file('upload')->getClientOriginalName();

            //get filename without extension
            $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);

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
