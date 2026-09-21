<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Setting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,Image,Str,Storage,Cache;
use Illuminate\Validation\ValidationException;
use App\Support\PriceVisibility;

class SettingController extends Controller
{
    public function __construct(Setting $model)
    {
        $this->model = $model;
        $this->module = 'settings';
        View::share ( 'module', $this->module );
    }

    private function validateSettingImage($image, string $key): void
    {
        $extension = strtolower($image->getClientOriginalExtension());
        $mimeType = $image->getMimeType();

        if (!$image->isValid() || !Str::startsWith($mimeType, 'image/') || !in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || $image->getSize() > 4096 * 1024) {
            throw ValidationException::withMessages([
                'Files.'.$key => 'Only JPG, JPEG, PNG and WEBP images up to 4MB are allowed.',
            ]);
        }
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
        $request->validate([
            'Site.GOOGLE_ANALYTICS_ID' => ['nullable', 'regex:/^G-[A-Z0-9]+$/i'],
            'Site.GOOGLE_TAG_MANAGER_ID' => ['nullable', 'regex:/^GTM-[A-Z0-9]+$/i'],
            'Site.META_PIXEL_ID' => ['nullable', 'regex:/^[0-9]{5,25}$/'],
            'Site.GOOGLE_SEARCH_CONSOLE_VERIFICATION' => ['nullable', 'string', 'max:1000'],
            'Site.PRODUCT_PRICE_VISIBILITY_DEFAULT' => ['nullable', 'in:show_everywhere,details_only,show_after_selection,enquiry_only,coming_soon'],
            'Site.SUBSCRIPTION_PRICE_VISIBILITY_DEFAULT' => ['nullable', 'in:show_everywhere,enquiry_only,coming_soon'],
            'Site.PRICE_ENQUIRY_LABEL' => ['nullable', 'string', 'max:80'],
            'Site.PRICE_ENQUIRY_BUTTON_LABEL' => ['nullable', 'string', 'max:40'],
            'Site.PRICE_COMING_SOON_LABEL' => ['nullable', 'string', 'max:80'],
        ], [
            'Site.GOOGLE_ANALYTICS_ID.regex' => 'Enter a valid GA4 Measurement ID such as G-XXXXXXXXXX.',
            'Site.GOOGLE_TAG_MANAGER_ID.regex' => 'Enter a valid Google Tag Manager Container ID such as GTM-XXXXXXX.',
            'Site.META_PIXEL_ID.regex' => 'Enter a valid numeric Meta Pixel ID.',
        ]);
        if($request->hasFile('Files')){
            $directory = 'website';
            foreach($request->file('Files') as $key=>$image){
                $this->validateSettingImage($image, $key);
                $exp = explode('.',$image->getClientOriginalName());
                $extension = strtolower($image->getClientOriginalExtension());
                $old_file = $request->input('Files.old_'.$key);
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
        foreach((array) $request->input('Site', []) as $key=>$value){
            Setting::where('key' , '=', $key)->update(array('value' => $value));
        }
        Cache::forget('settings');
        Cache::forget('configurations');
        Cache::forget('api_subscription_plans');
        Cache::forget('api_subscription_plans_featured');
        Cache::forget('api_featured_products');
        Cache::forget('api_home_sections');
        Cache::forget('home');
        PriceVisibility::clearSettingsCache();
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
