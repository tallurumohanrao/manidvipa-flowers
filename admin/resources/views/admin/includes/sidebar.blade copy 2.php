@php
$route = explode('.',request()->route()->getName())[1];
$result = \App\Models\Admin\Permission::whereMenuStatus(1)->orderBy('group_sort_order')->get();  #dd($result);
$all = [];
$icon_class = '';
foreach($result->sortBy('module_sort_order') as $row){
    $all[$row->group_name][] = $row->module;
    // if($row->icon_class == ''){
    //     $icon_class == '';
    // }
    $modules[$row->group_name][] = ['module'=>$row->module,'icon_class'=>$row->icon_class,'all'=>$all[$row->group_name]];
}  //dd($modules);
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

@canany(['bookings_view','offlinebookings_view'])
<li class="nav-item">
    <a class="nav-link @if(in_array($route,['bookings','offlinebookings'])) collapsed @endif" href="#" data-toggle="collapse" data-target="#collapseTwo"
        aria-expanded="true" aria-controls="collapseTwo">
        <i class="fas fa-fw fa-folder"></i>
        <span>Bookings</span>
    </a>
    <div id="collapseTwo" class="collapse @if(in_array($route,['bookings','offlinebookings'])) show @endif" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            @can('bookings_view')
            <a class="collapse-item @if(request('booking_type') == 0) active @endif" href="{{ route('admin.bookings.index',['booking_type'=>0]) }}">Bookings</a>
            @endcan
            @can('offlinebookings_view')
            <a class="collapse-item @if(request('booking_type') == 1) active @endif" href="{{ route('admin.bookings.index',['booking_type'=>1]) }}">Offline Bookings</a>
            @endcan
        </div>
    </div>
</li>
@endcan
<hr class="sidebar-divider">
@foreach($modules as $group=>$menus)
@php $all_modules = end($menus)['all']; @endphp
@if(count($all_modules) > 1)
<li class="nav-item">
    <a class="nav-link @if(in_array($route,$all_modules)) collapsed @endif" href="#" data-toggle="collapse" data-target="#collapse{{ $group }}"
        aria-expanded="true" aria-controls="collapse{{ $group }}">
        <i class="{{ $menus[0]['icon_class'] }}"></i>
        <span>{{ ucwords($group) }}</span>
    </a>
    <div id="collapse{{ $group }}" class="collapse @if(in_array($route,$all_modules)) show @endif" aria-labelledby="heading{{ $loop->iteration }}" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            @foreach($menus as $module=>$menu)
            <a class="collapse-item @if($route == $menu['module']) active @endif" href="{{ route('admin.'.str_replace(' ','',$menu['module']).'.index') }}">{{ ucwords($menu['module']) }}</a>
            @endforeach
        </div>
    </div>
</li>
@else
<li class="nav-item @if(in_array($route,$all_modules)) active @endif">
    <a class="nav-link" href="{{ route('admin.'.$menus[0]['module'].'.index') }}">
    <i class="{{ $menus[0]['icon_class'] }}"></i>
        <span>{{ ucwords($menus[0]['module']) }}</span></a>
</li>
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
