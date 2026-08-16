@php
$route = explode('.',request()->route()->getName())[1];

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

@canany(['admins_view','users_view','roles_view'])
<li class="nav-item">
    <a class="nav-link @if(in_array($route,['admins','users','roles'])) collapsed @endif" href="#" data-toggle="collapse" data-target="#collapseUsers"
        aria-expanded="true" aria-controls="collapseUsers">
        <i class="fas fa-fw fa-user"></i>
        <span>Users</span>
    </a>
    <div id="collapseUsers" class="collapse @if(in_array($route,['admins','users','roles'])) show @endif" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            @can('admins_view')
            <a class="collapse-item @if($route == 'admins') active @endif" href="{{ route('admin.admins.index') }}">Admins</a>
            @endcan
            @endcan
            @endcan
            @can('users_view')
            <a class="collapse-item @if($route == 'users') active @endif" href="{{ route('admin.users.index') }}">Users</a>
            @endcan
            @can('roles_view')
            <a class="collapse-item @if($route == 'roles') active @endif" href="{{ route('admin.roles.index') }}">Roles</a>
            @endcan
            @can('permissions_view')
            <a class="collapse-item @if($route == 'roles') active @endif" href="{{ route('admin.permissions.index') }}">Permissions</a>
            @endcan
        </div>
    </div>
</li>
@endcan

@canany(['seo_view'])
<li class="nav-item @if(in_array($route,['seo'])) active @endif">
    <a class="nav-link" href="{{ route('admin.seo.index') }}">
    <i class="fas fa-fw fa-globe"></i>
        <span>Seo</span></a>
</li>
@endcan
<!-- Divider -->
<hr class="sidebar-divider d-none d-md-block">

<!-- Sidebar Toggler (Sidebar) -->
<div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
</div>
</ul>
<!-- End of Sidebar -->
