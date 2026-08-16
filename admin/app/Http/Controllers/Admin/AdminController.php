<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use App\Models\Admin\Role;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAdminRequest;
use App\Traits\StoreImageTrait;
use App\Traits\RedirectTrait;
use Symfony\Component\HttpFoundation\Response;
use Hash,Gate,View,DB;

class AdminController extends Controller
{
    use StoreImageTrait,RedirectTrait;
    public function __construct(Admin $model)
    {
        $this->model = $model;
        $this->module = 'admins';
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
        $name = $request->input('name');
        $email = $request->input('email');
        $status = $request->input('status');
        $search = $this->model::query();
        if ($request->filled('name')) {
            $search->where('name', 'like', '%' . $name . '%');
        }
        if ($request->filled('email')) {
            $search->where('email',$email);
        }
        if ($request->filled('status')) {
            $search->where('status', $status);
        }
        if ($request->filled('role')) {
            $search->whereHas('roles', function ($query) use ($request) {
                $query->where('roles.name', $request->input('role'));
            });
        }
        $data = $search->orderByDesc('id')->paginate($perPage)->withQueryString();
        $roles = Role::pluck('name', 'name')->prepend('--Select Role--', '');
        return view('admin.'.$this->module.'.index', compact('data','roles'));
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
    public function store(StoreAdminRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $create = $request->all();
        $create['password'] = Hash::make($request->password);
        $admin = Admin::create($create);
        if($request->filled('roles')) {
            $admin->roles()->sync($request->input('roles'));
        }
        return $this->redirectAfterSave($request->FormButton, $admin->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Admin $admin)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $selected = DB::table('admin_role')->where('admin_id',$admin->id)->get()->pluck('role_id')->toArray();
        return view('admin.'.$this->module.'.edit', compact('admin','selected'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreAdminRequest $request, Admin $admin)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->filled('password') ? $request->all() : $request->except(['password','password_confirmation']);

        $formInput['image'] = $this->verifyAndStoreImage($request, 'image', 'admins');
        if($request->password){
            $formInput['password'] = Hash::make($request->password);
        }
        if($admin->update($formInput) === true){
            //if($request->filled('roles')) {
                $admin->roles()->sync($request->input('roles'));
            //}
            return $this->redirectAfterSave($request->FormButton, $admin->id);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($request->ajax() && $request->isMethod('PATCH')){
            $admin = Admin::findOrFail($id);
            if($admin->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $admin)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if($admin->delete() == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }
}
