<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Addon;
use Illuminate\Http\Request;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use App\Http\Requests\StoreAddonRequest;
use Symfony\Component\HttpFoundation\Response;
use Storage,Gate,View,Str,File;

class AddonController extends Controller
{
    use StoreImageTrait,RedirectTrait;

    public function __construct(Addon $model)
    {
        $this->model = $model;
        $this->module = 'addons';
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
        return view('admin.'.$this->module.'.index', ['data' => $this->model::all() ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.create', [ 'row' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAddonRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        $model = $this->model::create($formInput);
        return $this->redirectAfterSave($request->FormButton, $model->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Addon  $addon
     * @return \Illuminate\Http\Response
     */
    public function show(Addon $addon)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Addon  $addon
     * @return \Illuminate\Http\Response
     */
    public function edit(Addon $addon)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', ['row' => $addon]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Addon  $addon
     * @return \Illuminate\Http\Response
     */
    public function update(StoreAddonRequest $request, Addon $addon)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        if($addon->update($formInput))
        return $this->redirectAfterSave($request->FormButton, $addon->id);
    }
    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $addon = $this->model::find($id);
            if($addon->update(['status'=> $request->status])){
                $status= $request->status == 1 ?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    public function updateSort(Request $request)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        foreach ($request->order as $order) {
            $result = $this->model::find($order['id'])->update(['priority' => $order['position']]);
        }
		if($result)
        return response()->json(['success'=>true, 'message' => 'Update successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Addon  $addon
     * @return \Illuminate\Http\Response
     */
    public function destroy(Addon $addon)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($addon->image && File::exists('storage/'.$this->module.'/'. $addon->image)){
            Storage::delete('public/'.$this->module.'/'.$addon->image);
        }
        if($addon->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $model = $this->model::find($id);
            if(@$model->image && File::exists('storage/'.$this->module.'/'. @$model->image)){
                $images = 'public/'.$this->module.'/'.$model->image;
                Storage::delete($images);
            }
            $result = $model->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
}
