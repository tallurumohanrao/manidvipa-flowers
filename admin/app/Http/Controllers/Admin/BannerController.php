<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Banner;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBannerRequest;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use Symfony\Component\HttpFoundation\Response;
use Storage,Gate,View,Cache;

class BannerController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct(Banner $model)
    {
        $this->model = $model;
        $this->module = 'banners';
        View::share ( 'module', $this->module );
    }

    private function clearBannerCache(): void
    {
        Cache::forget('api_banners');
        Cache::forget('api_banners_all');

        foreach (array_keys(config('app.pages', [])) as $page) {
            Cache::forget('api_banners_' . $page);
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
        $data = $this->model::orderBy('priority')->paginate(config('PER_PAGE'));
        return view('admin.'.$this->module.'.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.create', ['row' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBannerRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        $banner = $this->model::create($formInput);
        $this->clearBannerCache();
        return $this->redirectAfterSave($request->FormButton, $banner->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function show(Banner $banner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function edit(Banner $banner)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', ['row' => $banner]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function update(StoreBannerRequest $request, Banner $banner)
    { 
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        if($banner->update($formInput) === true) {
            $this->clearBannerCache();
            return $this->redirectAfterSave($request->FormButton, $banner->id);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $banner = $this->model::findOrFail($id);
            if($banner->update(['status'=>$request->status])){
                $this->clearBannerCache();
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function destroy(Banner $banner)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        Storage::delete('public/'.$this->module.'/'.$banner->image);
        if($banner->delete() == 1) {
            $this->clearBannerCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        } else {
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }
    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $model = $this->model::find($id);
            $images = 'public/'.$this->module.'/'.$model->image;
            Storage::delete($images);
            $result = $model->delete();
        endforeach;
        
        if($result == 1) {
            $this->clearBannerCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        } else {
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }
    
}
