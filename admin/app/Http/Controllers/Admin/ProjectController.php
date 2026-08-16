<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Project;
use App\Models\Admin\ProjectImage;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProjectRequest;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use Symfony\Component\HttpFoundation\Response;
use Storage,Gate,View,Str;

class ProjectController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct(Project $model)
    {
        $this->model = $model;
        $this->module = 'projects';
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
        $data = $this->model::paginate(config('PER_PAGE'));
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
    public function store(StoreProjectRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['slug'] = Str::slug($formInput['title']);
        $project = $this->model::create($formInput);

        return $this->redirectAfterSave($request->FormButton, $project->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project)
    {
        return view('admin.'.$this->module.'.show',compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function edit(Project $project)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', ['row' => $project]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function update(StoreProjectRequest $request, Project $project)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $formInput['slug'] = Str::slug($formInput['title']);
        if($project->update($formInput) === true){
            return $this->redirectAfterSave($request->FormButton, $project->id);
        }
    }

    public function storeImage(Request $request,$id)
    {
        if($request->isMethod('POST')){
            $project = $this->model::find($id);
            $input = $request->all();
            $input['image'] = $this->verifyAndStoreImage($request, 'file', 'projects');
            $input['status'] = 1;
            $project->images()->create($input);
            return response()->json(['success'=>'File Uploaded Successfully']);
        }
    }

    public function projectImageUpdateStatus(Request $request, $id)
    {
        if($request->ajax() && $request->isMethod('PATCH')){
            $image = ProjectImage::find($id);
            $value = $request->status == 1 ?:0;
            if($image->update(['status'=> $value])){
                $status= $value == 1 ?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }

    public function projectImageDestroy($id)
    {
        $image = ProjectImage::find($id);
        Storage::delete('public/projects/'.$image->image);
        if($image->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    public function projectImageUpdateSort(Request $request)
    {
        foreach ($request->order as $order) {
            $result = ProjectImage::find($order['id'])->update(['priority' => $order['position']]);
        }
		if($result)
        return response()->json(['success'=>true, 'message' => 'Update successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $project = $this->model::findOrFail($id);
            if($project->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        foreach($project->images as $image){
            Storage::delete('public/'.$this->module.'/'.$image->image);
            $image->delete();
        }
        if($project->delete() == 1){
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }else{
            return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
        }
    }
    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        foreach($ids as $id) :
            $model = $this->model::find($id);
            foreach($model->images as $image){
                $images = 'public/'.$this->module.'/'.$model->image;
                Storage::delete($images);
                $image->delete();
            }
            $result = $model->delete();
        endforeach;

        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
