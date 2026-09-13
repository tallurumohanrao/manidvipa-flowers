<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRoleRequest;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\RedirectTrait;
use Gate,View,DB;
class RoleController extends Controller
{
    use RedirectTrait;
    public function __construct(Role $model)
    {
        $this->model = $model;
        $this->module = 'roles';
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
        $roles = Role::all();
        return view('admin.'.$this->module.'.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $role = [];
        $permissions = Permission::whereStatus(1)->get();
        return view('admin.'.$this->module.'.create', compact('permissions','role'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoleRequest $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $role = Role::create($request->only('name', 'status'));
        $this->syncPermissions($role, $request->input('Permissions', []));
        return $this->redirectAfterSave($request->FormButton,$role->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Admin\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Admin\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $permissions = Permission::whereStatus(1)->get();
        return view('admin.'.$this->module.'.edit', compact('role','permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Admin\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRoleRequest $request, Role $role)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $role->update($request->only('name', 'status'));
        $this->syncPermissions($role, $request->input('Permissions', []));
        return $this->redirectAfterSave($request->FormButton,$role->id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->validate(['status' => ['required', 'boolean']]);
        if($request->ajax() && $request->isMethod('PATCH')){
            $role = Role::findOrFail($id);
            if ((int) $request->status !== 1 && $this->isCurrentAdminsRole($role)) {
                return response()->json(['status' => 'error', 'message' => 'You cannot disable a role assigned to your own account.'], 422);
            }
            if($role->update(['status'=>$request->status])){
                $status=$request->status==1?'enabled':'disabled';
                return response()->json(['status'=>'success','message'=>"Status $status successfully."]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if ($this->isAssignedRole($role)) {
            return response()->json(['success' => false, 'message' => 'This role is assigned to an administrator and cannot be deleted.'], 422);
        }
        if($permissions = $role->permissions()){
            $permissions->delete();
        }
        $result = $role->delete();
        if($result == 1)
        return response()->json(['success'=>true, 'message' => 'Deleted successfully.']);
        else
        return response()->json(['success'=>false, 'message' => 'An unexpected error has occurred.']);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = array_values(array_filter(array_map('intval', explode(',', (string) $request->ids))));
        $assignedRoleIds = DB::table('admin_role')->whereIn('role_id', $ids)->pluck('role_id')->all();

        if ($assignedRoleIds) {
            return response()->json(['success' => false, 'message' => 'Assigned roles were not deleted. Remove their administrator assignments first.'], 422);
        }

        DB::transaction(function () use ($ids) {
            DB::table('role_permissions')->whereIn('role_id', $ids)->delete();
            Role::whereIn('id', $ids)->delete();
        });

        return response()->json(['success' => true, 'message' => 'Selected roles deleted.']);
    }

    private function syncPermissions(Role $role, array $requestedPermissions): void
    {
        $validAbilities = Permission::where('status', 1)
            ->get(['view', 'create', 'edit', 'delete'])
            ->flatMap(fn ($permission) => [$permission->view, $permission->create, $permission->edit, $permission->delete])
            ->filter()
            ->unique();

        $permissions = collect($requestedPermissions)
            ->filter(fn ($ability) => $validAbilities->contains($ability))
            ->unique()
            ->values();

        DB::transaction(function () use ($role, $permissions) {
            $role->permissions()->delete();
            foreach ($permissions as $permission) {
                $role->permissions()->create(['permission' => $permission]);
            }
        });
    }

    private function isCurrentAdminsRole(Role $role): bool
    {
        return DB::table('admin_role')
            ->where('admin_id', auth('admin')->id())
            ->where('role_id', $role->id)
            ->exists();
    }

    private function isAssignedRole(Role $role): bool
    {
        return DB::table('admin_role')->where('role_id', $role->id)->exists();
    }

}
