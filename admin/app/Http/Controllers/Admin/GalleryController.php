<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Gallery;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use Storage,View,Gate;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

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
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
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
        abort_if(!Gate::any([$this->module.'_create', $this->module.'_edit']), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $request->validate([
            'Gallery' => ['nullable', 'array'],
            'Gallery.*.id' => ['nullable', 'integer', 'exists:galleries,id'],
            'Gallery.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'Gallery.*.priority' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'Gallery.*.status' => ['nullable', 'in:0,1'],
        ]);

        foreach((array) $request->input('Gallery', []) as $key=>$row)
        {
            $gallery = !empty($row['id']) ? Gallery::findOrFail($row['id']) : null;
            abort_if($gallery && Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
            abort_if(!$gallery && Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

            $file = $request->file("Gallery.$key.image");
            if(!$gallery && !$file){
                throw ValidationException::withMessages([
                    "Gallery.$key.image" => 'The image field is required.',
                ]);
            }

            $payload = [];
            if(array_key_exists('priority', $row)){
                $payload['priority'] = (int) $row['priority'];
            }
            if(array_key_exists('status', $row)){
                $payload['status'] = (int) $row['status'];
            }
            if($file){
                $payload['image'] = $this->verifyAndStoreMultipleImage($file, "Gallery.$key.image", 'gallery');
                if($gallery && $gallery->image){
                    Storage::delete('public/gallery/'.$gallery->image);
                }
            }

            if($gallery){
                $gallery->update($payload);
            }else{
                Gallery::create($payload);
            }
        }
        return redirect()->route('admin.gallery.index')->with('success','Saved successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $request->validate([
                'status' => ['required', 'in:0,1'],
            ]);

            $gallery = Gallery::findOrFail($id);
            if($gallery->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }

    public function isBeforeAfterStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $request->validate([
                'status' => ['required', 'in:0,1'],
            ]);

            $gallery = Gallery::findOrFail($id);  //dd(['type'=>$request->status]);
            if($gallery->update(['type'=>$request->status])){
                $status=$request->status==1?'Added to':'Removed from';
                return response()->json(['status'=>'success','message'=>" $status Before & After gallery successfully."]);
            }
        }
    }

    public function updateSort(Request $request)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->validate([
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'integer', 'exists:galleries,id'],
            'order.*.position' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        $result = true;
        foreach ($request->order as $order) {
            $result = $this->model::find($order['id'])->update(['priority' => $order['position']]) && $result;
        }
		if($result)
        return response()->json(['success'=>true, 'message' => 'Update successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    public function destroy(Gallery $gallery)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        Storage::delete('public/'.$this->module.'/'.$gallery->image);
        if($gallery->delete() == 1){
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        } else{
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = array_filter(explode(',',$request->ids));
        $result = 0;
        foreach($ids as $id) :
            $model = $this->model::find($id);
            if(!$model){
                continue;
            }
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
