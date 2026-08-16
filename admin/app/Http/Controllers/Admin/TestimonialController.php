<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Testimonial;
use Illuminate\Http\Request;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use App\Http\Requests\StoreTestimonialRequest;
use Symfony\Component\HttpFoundation\Response;
use Storage,Gate,View,File,DB;

class TestimonialController extends Controller
{
    use StoreImageTrait,RedirectTrait;

    public function __construct(Testimonial $model)
    {
        $this->model = $model;
        $this->module = 'testimonials';
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
        $data = DB::table('testimonials')->get();
        return view('admin.'.$this->module.'.index', ['data' => $data ]);
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
    public function store(StoreTestimonialRequest $request)
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
     * @param  \App\Models\Admin\Testimonial  $testimonial
     * @return \Illuminate\Http\Response
     */
    public function show(Testimonial $testimonial)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Testimonial  $testimonial
     * @return \Illuminate\Http\Response
     */
    public function edit(Testimonial $testimonial)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', ['row' => $testimonial]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Testimonial  $testimonial
     * @return \Illuminate\Http\Response
     */
    public function update(StoreTestimonialRequest $request, Testimonial $testimonial)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        if($testimonial->update($formInput))
        return $this->redirectAfterSave($request->FormButton, $testimonial->id);
    }
    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $testimonial = $this->model::find($id);
            if($testimonial->update(['status'=> $request->status])){
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
     * @param  \App\Models\Admin\Testimonial  $testimonial
     * @return \Illuminate\Http\Response
     */
    public function destroy(Testimonial $testimonial)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($testimonial->image && File::exists('storage/'.$this->module.'/'. $testimonial->image)){
            Storage::delete('public/'.$this->module.'/'.$testimonial->image);
        }
        if($testimonial->delete() == 1)
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
