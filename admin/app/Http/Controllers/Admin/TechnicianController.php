<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Technician;
use App\Http\Requests\StoreTechnicianRequest;
use App\Traits\RedirectTrait;
use App\Traits\StoreImageTrait;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,DB;

class TechnicianController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct(Technician $model)
    {
        $this->model = $model;
        $this->module = 'technicians';
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
        $perPage = $request->per_page ?: config('PER_PAGE');
        $query = DB::table('technicians');
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
    public function store(StoreTechnicianRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->request->add(['slug'=>'']);
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        $technician = $this->model::create($formInput);
        return $this->redirectAfterSave($request->FormButton, $technician->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Technician  $technician
     * @return \Illuminate\Http\Response
     */
    public function show(Technician $technician)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Technician  $technician
     * @return \Illuminate\Http\Response
     */
    public function edit(Technician $technician)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $categories = DB::table('categories')->where('status',1)->orderBy('title')->get()->pluck('title','id');
        return view('admin.'.$this->module.'.edit', ['row' => $technician,'categories'=>$categories]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Technician  $technician
     * @return \Illuminate\Http\Response
     */
    public function update(StoreTechnicianRequest $request, Technician $technician)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->request->add(['slug'=>'']);
        $formInput = $request->all();
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        if($technician->update($formInput) === true)
            return $this->redirectAfterSave($request->FormButton, $technician->id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $technician = $this->model::findOrFail($id);
            if($technician->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Technician  $technician
     * @return \Illuminate\Http\Response
     */
    public function destroy(Technician $technician)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($technician->delete() == 1)
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
