@php
$route = explode('.',request()->route()->getName())[1];
$result = DB::table('permissions')->whereMenuStatus(1)->orderBy('group_sort_order')->get();
$all = [];
$icon_class = '';
foreach($result->sortBy('module_sort_order') as $row){
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
<li class="nav-item active">
    <a class="nav-link" href="{{ route('admin.index') }}">
        <i class="fas fa-fw fa-tachometer-alt"></i>
        <span>Dashboard</span></a>
</li>
<hr class="sidebar-divider">
@foreach($modules as $group=>$menus)
@php $all_modules = end($menus)['all']; @endphp
@if(count($all_modules) > 1)
<li class="nav-item">
    <a class="nav-link @if(in_array($route,$all_modules)) collapsed @endif" href="#" data-toggle="collapse" data-target="#collapse{{ $group }}" aria-expanded="true" aria-controls="collapse{{ $group }}">
    <i class="{{ $menus[0]['icon_class'] }}"></i><span>{{ ucwords($group) }}</span></a>
        <div id="collapse{{ $group }}" class="collapse @if(in_array($route,$all_modules)) show @endif" aria-labelledby="heading{{ $loop->iteration }}" data-parent="#accordionSidebar">
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
    <li class="nav-item @if(in_array($route,$all_modules)) active @endif">
        <a class="nav-link" href="{{ route('admin.'.$menus[0]['route'].'.index') }}">
        <i class="{{ $menus[0]['icon_class'] }}"></i>
            <span>{{ ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $menus[0]['module'])) }}</span></a>
    </li>
    @endif
@endif
@endforeach
<hr class="sidebar-divider">
<!-- Divider -->
<hr class="sidebar-divider d-none d-md-block">
<!-- Sidebar Toggler (Sidebar) -->
<div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
</div>
</ul>
<!-- End of Sidebar -->
