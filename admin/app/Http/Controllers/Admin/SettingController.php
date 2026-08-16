<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,Image,Str,Storage;

class SettingController extends Controller
{
    public function __construct(Setting $model)
    {
        $this->model = $model;
        $this->module = 'settings';
        View::share ( 'module', $this->module );
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $settings = \DB::table('settings')->whereStatus(1)->get();
        return view('admin.settings.index', ['settings' => $settings]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->hasFile('Files')){
            $directory = 'website';
            foreach($request->file('Files') as $key=>$image){
                $exp = explode('.',$image->getClientOriginalName());
                $extension = $image->getClientOriginalExtension();
                $old_file = $request->Files['old_'.$key];
                if($extension == 'png'){
                    $name = preg_replace("/[^a-zA-Z0-9]+/", "-",$exp['0']).time().'.jpg';
                }else{
                    $name = preg_replace("/[^a-zA-Z0-9]+/", "-",$exp['0']) . time() .'.'. $extension;
                }
                if($image->storeAs( $directory , $name , 'public' )){
                    Storage::delete('public/'.$directory.'/'.$old_file);
                }
            
                Setting::where('key' , '=', $key)->update(array('value' => $name));
            }
        }
        foreach($request->Site as $key=>$value){
            Setting::where('key' , '=', $key)->update(array('value' => $value));
        }
        return redirect()->route('admin.settings.index')->with('success', 'Saved successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function show(Setting $setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function edit(Setting $setting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Setting $setting)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Setting  $setting
     * @return \Illuminate\Http\Response
     */
    public function destroy(Setting $setting)
    {
        //
    }
}
