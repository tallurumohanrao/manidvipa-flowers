<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Category;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCategoryRequest;
use App\Traits\RedirectTrait;
use App\Traits\StoreImageTrait;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,Str,DB;

class CategoryController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct(Category $model)
    {
        $this->model = $model;
        $this->module = 'categories';
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
        $perPage = $request->input('per_page') ?: config('ADMIN_PER_PAGE');
        $query = DB::table('categories');
        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
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
        return view('admin.'.$this->module.'.create', ['row' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCategoryRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $slug = Str::slug($formInput['title']);
        $formInput['name'] = $slug;
        $formInput['slug'] = $slug;
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        $category = $this->model::create($formInput);
        return $this->redirectAfterSave($request->FormButton, $category->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', ['row' => $category]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(StoreCategoryRequest $request, Category $category)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $slug = Str::slug($formInput['title']);
        $formInput['name'] = $slug;
        $formInput['slug'] = $slug;
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);
        if($category->update($formInput) === true)
            return $this->redirectAfterSave($request->FormButton, $category->id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $category = $this->model::findOrFail($id);
            if($category->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($category->delete() == 1)
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
