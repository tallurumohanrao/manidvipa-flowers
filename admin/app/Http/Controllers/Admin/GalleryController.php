<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Gallery;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use Storage,View,Gate,Response;

class GalleryController extends Controller
{
    use StoreImageTrait,RedirectTrait;

    public function __construct(Gallery $model)
    {
        $this->model = $model;
        $this->module = 'gallery';
        View::share('module',$this->module);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = $this->model::orderBy('priority')->paginate(20)->withQueryString();
        return view('admin.'.$this->module.'.index', ['data' => $data]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->Gallery){
            foreach($request->Gallery as $k=>$row)
            {
                $gallery = Gallery::find($row['id'] ?? '');
                if($row['image'] ?? ''){
                    $image = $this->verifyAndStoreMultipleImage($row['image'], 'image', 'gallery');
                    $row['image'] = $image;
                }
                if($gallery){
                    $gallery->update($row);
                }else{
                    Gallery::create($row);
                }
            }
        }
        return redirect()->route('admin.gallery.index')->with('success','Saved successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        //abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $gallery = Gallery::findOrFail($id);
            if($gallery->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }

    public function isBeforeAfterStatus(Request $request, $id)
    {
        //abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $gallery = Gallery::findOrFail($id);  //dd(['type'=>$request->status]);
            if($gallery->update(['type'=>$request->status])){
                $status=$request->status==1?'Added to':'Removed from';
                return response()->json(['status'=>'success','message'=>" $status Before & After gallery successfully."]);
            }
        }
    }

    public function updateSort(Request $request)
    {
        foreach ($request->order as $order) {
            $result = $this->model::find($order['id'])->update(['priority' => $order['position']]);
        }
		if($result)
        return response()->json(['success'=>true, 'message' => 'Update successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    public function destroy(Gallery $gallery)
    {
        Storage::delete('public/'.$this->module.'/'.$gallery->image);
        if($gallery->delete() == 1){
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        } else{
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }

    public function massDestroy(Request $request)
    {
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $model = $this->model::find($id);
            $images = 'public/gallery/'.$model->image;
            Storage::delete($images);
            $result = $model->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
}
