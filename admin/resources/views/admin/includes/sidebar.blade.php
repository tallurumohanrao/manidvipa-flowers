@php
$routeName = request()->route()?->getName() ?? 'admin.index';
$route = explode('.', $routeName)[1] ?? 'index';
$result = DB::table('permissions')->where('menu_status', 1)->where('status', 1)->orderBy('group_sort_order')->get();
$all = [];
$modules = [];
$icon_class = '';
foreach($result->sortBy('module_sort_order') as $row){
    if (!$row->view || !\Illuminate\Support\Facades\Gate::allows($row->view)) {
        continue;
    }
    $all[$row->group_name][] = $row->route_name;
    // if($row->icon_class == ''){
    //     $icon_class == '';
    // }
    $modules[$row->group_name][] = ['module'=>$row->module,'route'=>$row->route_name,'icon_class'=>$row->icon_class,'all'=>$all[$row->group_name]];
}  #dd($modules);
@endphp
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
<!-- Sidebar - Brand -->
<a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('admin.index') }}">
    <div class="sidebar-brand-text mx-3">{{ config('SITE_NAME') }}</div>
</a>
<!-- Divider -->
<hr class="sidebar-divider my-0">
<!-- Nav Item - Dashboard -->
<li class="nav-item @if($route === 'index') active @endif">
    <a class="nav-link" href="{{ route('admin.index') }}">
        <i class="fas fa-fw fa-tachometer-alt"></i>
        <span>Dashboard</span></a>
</li>
<hr class="sidebar-divider">
@if(!empty($modules))
<li class="nav-item px-3 pb-2">
    <label for="admin-menu-search" class="sr-only">Filter admin menu</label>
    <input id="admin-menu-search" type="search" class="form-control form-control-sm" placeholder="Find a section…" autocomplete="off">
</li>
@foreach($modules as $group=>$menus)
@php $all_modules = end($menus)['all']; @endphp
@if(count($all_modules) > 1)
@php $groupId = 'collapse-'.Str::slug($group); $groupActive = in_array($route, $all_modules); @endphp
<li class="nav-item admin-menu-entry admin-menu-group @if($groupActive) active @endif">
    <a class="nav-link @if(!$groupActive) collapsed @endif" href="#" data-toggle="collapse" data-target="#{{ $groupId }}" aria-expanded="{{ $groupActive ? 'true' : 'false' }}" aria-controls="{{ $groupId }}">
    <i class="{{ $menus[0]['icon_class'] }}"></i><span>{{ ucwords($group) }}</span></a>
        <div id="{{ $groupId }}" class="collapse @if($groupActive) show @endif" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                @foreach($menus as $module=>$menu)
                @if(Route::has('admin.'.$menu['route'].'.index'))
                <a class="collapse-item @if($route == strtolower($menu['route'])) active @endif" href="{{ route('admin.'.$menu['route'].'.index') }}">{{ ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $menu['module'])) }}</a>
                @endif
                @endforeach
            </div>
        </div>
    </li>
@else
@if(Route::has('admin.'.$menus[0]['route'].'.index'))
    <li class="nav-item admin-menu-entry @if(in_array($route,$all_modules)) active @endif">
        <a class="nav-link" href="{{ route('admin.'.$menus[0]['route'].'.index') }}">
        <i class="{{ $menus[0]['icon_class'] }}"></i>
            <span>{{ ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $menus[0]['module'])) }}</span></a>
    </li>
    @endif
@endif
@endforeach
@endif
<hr class="sidebar-divider">
<!-- Divider -->
<hr class="sidebar-divider d-none d-md-block">
<!-- Sidebar Toggler (Sidebar) -->
<div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
</div>
</ul>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('admin-menu-search');
    if (!search) return;

    search.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        document.querySelectorAll('.admin-menu-entry').forEach(function (entry) {
            const matches = !term || entry.textContent.toLowerCase().includes(term);
            entry.classList.toggle('d-none', !matches);
            if (term && matches) {
                const submenu = entry.querySelector('.collapse');
                if (submenu) submenu.classList.add('show');
            }
        });
    });
});
</script>
<!-- End of Sidebar -->
