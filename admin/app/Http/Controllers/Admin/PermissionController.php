<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Traits\RedirectTrait;
use Illuminate\Http\Request;
use View,Str;

class PermissionController extends Controller
{
    use RedirectTrait;
    public function __construct(Permission $model)
    {
        $this->model = $model;
        $this->module = 'permissions';
        View::share ( 'module', $this->module );
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
        return view('admin.'.$this->module.'.create', ['row' => []]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $module = Str::slug($input['module'],'_');
        $input['view'] = $module.'_view';
        $input['create'] = $module.'_create';
        $input['edit'] = $module.'_edit';
        $input['delete'] = $module.'_delete';
        $model = $this->model::create($input);
        return $this->redirectAfterSave($request->FormButton, $model->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function edit(Permission $permission)
    {
        return view('admin.'.$this->module.'.edit', ['row' => $permission]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Permission $permission)
    {
        $input = $request->all();
        $module = Str::slug($input['module'],'_');
        $input['view'] = $module.'_view';
        $input['create'] = $module.'_create';
        $input['edit'] = $module.'_edit';
        $input['delete'] = $module.'_delete';
        if($permission->update($input) === true){
            return $this->redirectAfterSave($request->FormButton, $permission->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        //
    }
}
