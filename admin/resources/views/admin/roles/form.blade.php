<div class="form-group row">
    <label for="name" class="col-sm-3 col-form-label">Role name</label>
    <div class="col-sm-9">
    {{ html()->text('name')->class('form-control')->placeholder('Name')->required() }}
    </div>
</div>

<div class="form-group row">
    <label for="status" class="col-sm-3 col-form-label">Role status</label>
    <div class="col-sm-9">
    {!! html()->radio('status', $role ? $role->status : false, 1)->id('enable_status') !!}
    <label for="enable_status" class="control-label">Enable</label>

    {!! html()->radio('status', $role ? ($role->status == 0 ? true : false) : false, 0)->id('disable_status') !!}
    <label for="disable_status" class="control-label">Disable</label>
    </div>
</div>

@php
    $selectedAbilities = is_object($role) ? $role->permissions->pluck('permission')->all() : [];
    $permissionGroups = $permissions->groupBy(fn ($permission) => $permission->group_name ?: 'Other');
@endphp

<div class="alert alert-info mt-4 mb-3">
    <strong>How access works</strong>
    <ol class="mb-0 pl-3">
        <li>Select the actions this role may perform for each module.</li>
        <li>Assign this role to an administrator in the Administrators screen.</li>
        <li>The administrator then sees only allowed menu items and actions.</li>
    </ol>
    <small class="d-block mt-2">View opens the module. Create adds records. Edit changes records. Delete removes records. Selecting Create, Edit, or Delete automatically includes View.</small>
</div>

<div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
    <h6 class="mb-2">Module permissions</h6>
    <div class="custom-control custom-checkbox mb-2">
        {!! html()->checkbox('rolesAll')->id('rolesAll')->class('custom-control-input') !!}
        <label class="custom-control-label" for="rolesAll">Grant all permissions</label>
    </div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped rolesCheck">
    <thead class="thead-light">
        <tr>
            <th>Module</th>
            <th class="text-center">View</th>
            <th class="text-center">Create</th>
            <th class="text-center">Edit</th>
            <th class="text-center">Delete</th>
        </tr>
    </thead>
    <tbody>
    @forelse($permissionGroups as $groupName => $groupPermissions)
    <tr class="table-secondary">
        <th colspan="5">{{ $groupName }}</th>
    </tr>
    @foreach($groupPermissions as $permission)
    @php $moduleKey = 'permission-module-'.$permission->id; @endphp
    <tr>
        <td>
            <strong>{{ ucwords(str_replace('_',' ',$permission->module)) }}</strong>
            @if($permission->route_name)
                <small class="d-block text-muted">/{{ $permission->route_name }}</small>
            @endif
        </td>
        <td>
            @if($permission->view)
            <div class="custom-control custom-checkbox">
            {!! html()->checkbox("Permissions[]", in_array($permission->view, $selectedAbilities, true), $permission->view)->id('Permissions_'.$permission->view)->class('custom-control-input role-permission-checkbox')->attributes(['data-permission-action' => 'view', 'data-permission-module' => $moduleKey]) !!}
            {!! html()->label('View')->class('custom-control-label')->for('Permissions_'.$permission->view) !!}
            </div>
            @endif
        </td>

        <td>
            @if($permission->create)
            <div class="custom-control custom-checkbox">
            {!! html()->checkbox("Permissions[]", in_array($permission->create, $selectedAbilities, true), $permission->create)->id('Permissions_'.$permission->create)->class('custom-control-input role-permission-checkbox')->attributes(['data-permission-action' => 'create', 'data-permission-module' => $moduleKey]) !!}
            {!! html()->label('Create')->class('custom-control-label')->for('Permissions_'.$permission->create) !!}
            </div>
            @endif
        </td>

        <td>
            @if($permission->edit)
            <div class="custom-control custom-checkbox">
            {!! html()->checkbox("Permissions[]", in_array($permission->edit, $selectedAbilities, true), $permission->edit)->id('Permissions_'.$permission->edit)->class('custom-control-input role-permission-checkbox')->attributes(['data-permission-action' => 'edit', 'data-permission-module' => $moduleKey]) !!}
            {!! html()->label('Edit')->class('custom-control-label')->for('Permissions_'.$permission->edit) !!}
            </div>
            @endif
        </td>

        <td>
            @if($permission->delete)
            <div class="custom-control custom-checkbox">
            {!! html()->checkbox("Permissions[]", in_array($permission->delete, $selectedAbilities, true), $permission->delete)->id('Permissions_'.$permission->delete)->class('custom-control-input role-permission-checkbox')->attributes(['data-permission-action' => 'delete', 'data-permission-module' => $moduleKey]) !!}
            {!! html()->label('Delete')->class('custom-control-label')->for('Permissions_'.$permission->delete) !!}
            </div>
            @endif
        </td>
    </tr>
    @endforeach
    @empty
        <tr><td colspan="5" class="text-center text-muted">No active permissions are configured.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<small class="form-text text-muted mb-3">Changes take effect after saving the role. If the administrator already has an active session, ask them to sign in again.</small>

@push('styles')
<style>
    .rolesCheck th,
    .rolesCheck td { vertical-align: middle; }
    .rolesCheck th:not(:first-child),
    .rolesCheck td:not(:first-child) { text-align: center; min-width: 92px; }
    .rolesCheck .table-secondary th { color: #4e566b; text-align: left !important; }
</style>
@endpush

@push('script')
<script>
    (function () {
        const permissionBoxes = () => Array.from(document.querySelectorAll('.role-permission-checkbox'));

        function setModuleView(moduleKey, checked) {
            const view = document.querySelector('.role-permission-checkbox[data-permission-module="' + moduleKey + '"][data-permission-action="view"]');
            if (view && checked) view.checked = true;
        }

        document.querySelectorAll('.role-permission-checkbox').forEach(function (box) {
            box.addEventListener('change', function () {
                const moduleKey = this.dataset.permissionModule;
                if (this.dataset.permissionAction !== 'view' && this.checked) {
                    setModuleView(moduleKey, true);
                }
                if (this.dataset.permissionAction === 'view' && !this.checked) {
                    document.querySelectorAll('.role-permission-checkbox[data-permission-module="' + moduleKey + '"]:not([data-permission-action="view"])').forEach(function (action) {
                        action.checked = false;
                    });
                }
            });
        });

        const grantAll = document.getElementById('rolesAll');
        if (grantAll) {
            grantAll.addEventListener('change', function () {
                permissionBoxes().forEach(function (box) {
                    box.checked = grantAll.checked;
                });
            });
        }
    })();
</script>
@endpush

@php
//in_array($permission->id, $role->permissions->pluck('pivot.permission_id')->toArray())
//($role ?$role->permissions->count():null)
@endphp
