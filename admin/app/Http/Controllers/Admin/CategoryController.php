<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Category;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCategoryRequest;
use App\Traits\RedirectTrait;
use App\Traits\StoreImageTrait;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,Str,DB,Cache;

class CategoryController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct(Category $model)
    {
        $this->model = $model;
        $this->module = 'categories';
        View::share ( 'module', $this->module );
    }

    private function clearStorefrontCache(): void
    {
        Cache::flush();
    }

    private function getParentCategories($excludeId = null)
    {
        return DB::table('categories')
            ->where('status', 1)
            ->whereNull('parent_id')
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->orderByRaw('COALESCE(priority, 999999) ASC')
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    private function prepareCategoryInput(StoreCategoryRequest $request): array
    {
        $formInput = $request->all();
        $slug = Str::slug($formInput['title']);

        $formInput['name'] = $slug;
        $formInput['slug'] = $slug;
        $formInput['parent_id'] = $request->filled('parent_id') ? $request->parent_id : null;
        $formInput['home_category'] = $request->boolean('home_category') ? 1 : 0;
        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', $this->module);

        return $formInput;
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
        $query = DB::table('categories as c')
            ->leftJoin('categories as parent', 'parent.id', '=', 'c.parent_id')
            ->select(
                'c.*',
                'parent.title as parent_title',
                DB::raw('(select count(*) from categories as child where child.parent_id = c.id) as children_count')
            );
        if ($request->filled('title')) {
            $query->where('c.title', 'like', '%' . $request->title . '%');
        }
        if ($request->filled('status')) {
            $query->where('c.status', $request->status);
        }
        if ($request->filled('category_type')) {
            if ($request->category_type === 'main') {
                $query->whereNull('c.parent_id');
            }
            if ($request->category_type === 'child') {
                $query->whereNotNull('c.parent_id');
            }
            if ($request->category_type === 'home') {
                $query->whereNull('c.parent_id')->where('c.home_category', 1);
            }
        }

        $data = $query
            ->orderByRaw('COALESCE(parent.priority, c.priority, 999999) ASC')
            ->orderByRaw('COALESCE(parent.title, c.title) ASC')
            ->orderByRaw('CASE WHEN c.parent_id IS NULL THEN 0 ELSE 1 END ASC')
            ->orderByRaw('COALESCE(c.priority, 999999) ASC')
            ->orderBy('c.title')
            ->paginate($perPage)
            ->withQueryString();
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
        return view('admin.'.$this->module.'.create', [
            'row' => [],
            'parentCategories' => $this->getParentCategories(),
        ]);
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
        $formInput = $this->prepareCategoryInput($request);
        $category = $this->model::create($formInput);
        $this->clearStorefrontCache();
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
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', [
            'row' => $category,
            'parentCategories' => $this->getParentCategories($category->id),
        ]);
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
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $this->prepareCategoryInput($request);
        if ((int) $formInput['parent_id'] === (int) $category->id) {
            $formInput['parent_id'] = null;
        }
        if($category->update($formInput) === true) {
            $this->clearStorefrontCache();
            return $this->redirectAfterSave($request->FormButton, $category->id);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $category = $this->model::findOrFail($id);
            if($category->update(['status'=>$request->status])){
                $this->clearStorefrontCache();
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
        $result = $category->delete();
        if($result == 1) {
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = explode(',',$request->ids);
        $result = 0;
        foreach($ids as $id) :
            $model = $this->model::find($id);
            if($model) {
                $result = $model->delete();
            }
        endforeach;

        if($result == 1) {
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
