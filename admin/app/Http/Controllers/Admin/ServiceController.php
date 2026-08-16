<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Service;
use Illuminate\Http\Request;
use App\Http\Requests\StoreServiceRequest;
use App\Traits\RedirectTrait;
use App\Traits\StoreImageTrait;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,DB;

class ServiceController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct(Service $model)
    {
        $this->model = $model;
        $this->module = 'services';
        View::share ( 'module', $this->module );
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $perPage = $request->input('per_page') ?: config('PER_PAGE');
        $question = $request->input('question');
        $answer = $request->input('answer');
        $status = $request->input('status');
        $query = $this->model::query();
        if ($request->filled('question')) {
            $query->where('question', 'like', '%' . $question . '%');
        }
        if ($request->filled('answer')) {
            $query->where('answer', 'like', '%' . $answer . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $status);
        }
        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
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
        $categories = DB::table('categories')->where('status',1)->orderBy('title')->get()->pluck('title','id');
        return view('admin.'.$this->module.'.create', ['row' => [],'categories'=>$categories]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreServiceRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->request->add(['slug'=>'']);
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        $service = $this->model::create($formInput);
        return $this->redirectAfterSave($request->FormButton, $service->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function show(Service $service)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function edit(Service $service)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $categories = DB::table('categories')->where('status',1)->orderBy('title')->get()->pluck('title','id');
        return view('admin.'.$this->module.'.edit', ['row' => $service,'categories'=>$categories]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function update(StoreServiceRequest $request, Service $service)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->request->add(['slug'=>'']);
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        if($service->update($formInput) === true)
            return $this->redirectAfterSave($request->FormButton, $service->id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $service = $this->model::findOrFail($id);
            if($service->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Service  $service
     * @return \Illuminate\Http\Response
     */
    public function destroy(Service $service)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($service->delete() == 1)
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
            $result = $model->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
