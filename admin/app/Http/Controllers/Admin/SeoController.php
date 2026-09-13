<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SeoUrl;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSeoRequest;
use App\Traits\RedirectTrait;
use Symfony\Component\HttpFoundation\Response;
use Gate,View,Cache;

class SeoController extends Controller
{
    use RedirectTrait;

    public function __construct(SeoUrl $model)
    {
        $this->model = $model;
        $this->module = 'seo';
        View::share ( 'module', $this->module );
    }

    private function clearStorefrontCache(): void
    {
        Cache::flush();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies($this->module.'_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $url = $request->input('url');
        $alias = $request->input('alias');
        $status = $request->input('status');
        $perPage = $request->input('perPage') ?: config('ADMIN_PER_PAGE');
        $query = $this->model::query();
        if ($request->filled('url')) {
            $query->where('url', 'like', '%' . $url . '%');
        }
        if ($request->filled('alias')) {
            $query->where('alias','like', '%' . $alias. '%');
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
        return view('admin.'.$this->module.'.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSeoRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->all();
        $model = $this->model::create($formInput);
        $this->clearStorefrontCache();
        return $this->redirectAfterSave($request->FormButton, $model->id,$model->type);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\SeoUrl  $seo
     * @return \Illuminate\Http\Response
     */
    public function edit(SeoUrl $seo)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', compact('seo'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\SeoUrl  $seo
     * @return \Illuminate\Http\Response
     */
    public function update(StoreSeoRequest $request, SeoUrl $seo)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $seo->update($request->all());
        $this->clearStorefrontCache();
        return $this->redirectAfterSave($request->FormButton, $seo->id,$seo->type);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $seo = $this->model::find($id);
            $value = $request->boolean('status') ? 1 : 0;
            if($seo->update(['status'=> $value])){
                $this->clearStorefrontCache();
                $status= $value == 1 ?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\SeoUrl  $seo
     * @return \Illuminate\Http\Response
     */
    public function destroy(SeoUrl $seo)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $result = $seo->delete();
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
            $seo = $this->model::find($id);
            if($seo) {
                $result = $seo->delete();
            }
        endforeach;
        if($result == 1) {
            $this->clearStorefrontCache();
            return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        }
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

}
