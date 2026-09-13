<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use App\Traits\RedirectTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use View,Str,Gate,DB;
use Symfony\Component\HttpFoundation\Response;

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
    public function store(Request $request)
    {
        abort_if(Gate::denies($this->module.'_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $input = $this->validatedInput($request);
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
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
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
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $input = $this->validatedInput($request, $permission);
        $module = Str::slug($input['module'],'_');
        $input['view'] = $module.'_view';
        $input['create'] = $module.'_create';
        $input['edit'] = $module.'_edit';
        $input['delete'] = $module.'_delete';
        if ($permission->module === 'Permissions' && $input['module'] !== 'Permissions') {
            throw ValidationException::withMessages([
                'module' => 'The core Permissions module name cannot be changed.',
            ]);
        }
        $oldAbilities = collect(['view', 'create', 'edit', 'delete'])
            ->mapWithKeys(fn ($field) => [$field => $permission->{$field}]);
        $newAbilities = collect(['view', 'create', 'edit', 'delete'])
            ->mapWithKeys(fn ($field) => [$field => $input[$field]]);

        DB::transaction(function () use ($permission, $input, $oldAbilities, $newAbilities) {
            $permission->update($input);
            foreach ($oldAbilities as $field => $oldAbility) {
                $newAbility = $newAbilities[$field];
                if ($oldAbility && $newAbility && $oldAbility !== $newAbility) {
                    DB::table('role_permissions')
                        ->where('permission', $oldAbility)
                        ->update(['permission' => $newAbility, 'updated_at' => now()]);
                }
            }
        });

        return $this->redirectAfterSave($request->FormButton, $permission->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Admin\Permission  $permission
     * @return \Illuminate\Http\Response
     */
    public function destroy(Permission $permission)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $this->deletePermission($permission);

        return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies($this->module.'_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $request->validate(['status' => ['required', 'boolean']]);
        $permission = $this->model::findOrFail($id);
        if ($permission->module === 'Permissions' && ! $request->boolean('status')) {
            return response()->json(['status' => 'error', 'message' => 'The core Permissions module cannot be disabled.'], 422);
        }
        $permission->update(['status' => $request->boolean('status')]);

        return response()->json(['status' => 'success', 'message' => 'Permission status updated.']);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies($this->module.'_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = array_filter(explode(',', (string) $request->ids));
        $permissions = $this->model::whereIn('id', $ids)->get();
        if ($permissions->contains('module', 'Permissions')) {
            return response()->json(['success' => false, 'message' => 'The core Permissions module cannot be deleted.'], 422);
        }
        $permissions->each(fn ($permission) => $this->deletePermission($permission));

        return response()->json(['success' => true, 'message' => 'Selected permissions deleted.']);
    }

    private function validatedInput(Request $request, ?Permission $permission = null): array
    {
        $id = $permission?->id;

        return $request->validate([
            'group_name' => ['required', 'string', 'max:50'],
            'module' => ['required', 'string', 'max:50', 'unique:permissions,module,'.$id],
            'route_name' => ['required', 'string', 'max:50', 'unique:permissions,route_name,'.$id],
            'group_sort_order' => ['required', 'integer', 'min:0'],
            'module_sort_order' => ['required', 'integer', 'min:0'],
            'icon_class' => ['nullable', 'string', 'max:100'],
            'menu_status' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
        ]);
    }

    private function deletePermission(Permission $permission): void
    {
        abort_if($permission->module === 'Permissions', Response::HTTP_UNPROCESSABLE_ENTITY, 'The core Permissions module cannot be deleted.');
        $abilities = array_filter([$permission->view, $permission->create, $permission->edit, $permission->delete]);

        DB::transaction(function () use ($permission, $abilities) {
            DB::table('role_permissions')->whereIn('permission', $abilities)->delete();
            $permission->delete();
        });
    }
}
