<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\User;
use App\Http\Requests\StoreUserRequest;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\RedirectTrait;
use Gate,Hash,View;

class UserController extends Controller
{
    use RedirectTrait;
    public function __construct(User $model)
    {
        $this->model = $model;
        $this->module = 'users';
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
        $query = User::query();
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $name . '%');
        }
        if ($request->filled('email')) {
            $query->where('email','like', '%' . $email . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $status);
        }
        $data = $query->orderByDesc('id')->paginate($perPage)->withQueryString();
        return view('admin.'.$this->module.'.index', ['users' => $data]);
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
    public function store(StoreUserRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $create = $request->all();

        $create['password'] = Hash::make($request->password);
        $create['alerts'] = implode(',',$request->alerts);
        $user = User::create($create);
        return $this->redirectAfterSave($request->FormButton, $user->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        return view('admin.'.$this->module.'.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreUserRequest $request, User $user)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $formInput = $request->filled('password') ? $request->all() : $request->except(['password','password_confirmation']);

        if($request->password){
            $formInput['password'] = Hash::make($request->password);
        }
        if($user->update($formInput) === true){
            return $this->redirectAfterSave($request->FormButton, $user->id);
        }
        return $this->redirectAfterSave($request->FormButton, $user->id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        if($request->ajax() && $request->isMethod('PATCH')){
            $role = User::findOrFail($id);
            if($role->update(['status'=>$request->status])){
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
    public function destroy(User $user)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $result = $user->delete();
        if($result == 1)
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
