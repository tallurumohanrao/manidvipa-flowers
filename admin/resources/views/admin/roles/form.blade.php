<div class="form-group row">
    <label for="name" class="col-sm-3 col-form-label"></label>
    <div class="col-sm-9">
    {{ html()->text('name')->class('form-control')->placeholder('Name')->required() }}
    </div>
</div>

<div class="form-group row">
    <label for="status" class="col-sm-3 col-form-label"></label>
    <div class="col-sm-9">
    {!! html()->radio('status', $role ? $role->status : false, 1)->id('enable_status') !!}
    <label for="enable_status" class="control-label">Enable</label>

    {!! html()->radio('status', $role ? ($role->status == 0 ? true : false) : false, 0)->id('disable_status') !!}
    <label for="disable_status" class="control-label">Disable</label>
    </div>
</div>

<div class="custom-control custom-checkbox">
    {!! html()->checkbox('rolesAll')->id('rolesAll')->class('custom-control-input') !!}
    <label class="custom-control-label" for="rolesAll">Select All</label>
</div>
<table class="table table-striped rolesCheck">
    <tbody>
    @if(!$permissions->isEmpty())
    @foreach($permissions as $permission)
    <tr>
        <td>{{ ucwords(str_replace('_',' ',$permission->module)) }}</td>
        <td>
            <div class="custom-control custom-checkbox">
            {!! html()->checkbox("Permissions[]", $role ? in_array($permission->view, $role->permissions->pluck('permission')->toArray()) : false, $permission->view)->id('Permissions_'.$permission->view)->class('custom-control-input') !!}
            {!! html()->label('View')->class('custom-control-label')->for('Permissions_'.$permission->view) !!}
            </div>
        </td>

        <td>
            <div class="custom-control custom-checkbox">
            {!! html()->checkbox("Permissions[]", $role ? in_array($permission->create, $role->permissions->pluck('permission')->toArray()) : false, $permission->create)->id('Permissions_'.$permission->create)->class('custom-control-input') !!}
            {!! html()->label('Create')->class('custom-control-label')->for('Permissions_'.$permission->create) !!}
            </div>
        </td>

        <td>
            <div class="custom-control custom-checkbox">
            {!! html()->checkbox("Permissions[]", $role ? in_array($permission->edit, $role->permissions->pluck('permission')->toArray()) : false, $permission->edit)->id('Permissions_'.$permission->edit)->class('custom-control-input') !!}
            {!! html()->label('Edit')->class('custom-control-label')->for('Permissions_'.$permission->edit) !!}
            </div>
        </td>

        <td>
            <div class="custom-control custom-checkbox">
            {!! html()->checkbox("Permissions[]", $role ? in_array($permission->delete, $role->permissions->pluck('permission')->toArray()) : false, $permission->delete)->id('Permissions_'.$permission->delete)->class('custom-control-input') !!}
            {!! html()->label('Delete')->class('custom-control-label')->for('Permissions_'.$permission->delete) !!}
            </div>
        </td>
    </tr>
    @endforeach
    @endif
    </tbody>
</table>

@php
//in_array($permission->id, $role->permissions->pluck('pivot.permission_id')->toArray())
//($role ?$role->permissions->count():null)
@endphp
