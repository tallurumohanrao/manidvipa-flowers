<?php
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
?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
<!-- Sidebar - Brand -->
<a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo e(route('admin.index')); ?>">
    <div class="sidebar-brand-text mx-3"><?php echo e(config('SITE_NAME')); ?></div>
</a>
<!-- Divider -->
<hr class="sidebar-divider my-0">
<!-- Nav Item - Dashboard -->
<li class="nav-item active">
    <a class="nav-link" href="<?php echo e(route('admin.index')); ?>">
        <i class="fas fa-fw fa-tachometer-alt"></i>
        <span>Dashboard</span></a>
</li>
<hr class="sidebar-divider">
<?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group=>$menus): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php $all_modules = end($menus)['all']; ?>
<?php if(count($all_modules) > 1): ?>
<li class="nav-item">
    <a class="nav-link <?php if(in_array($route,$all_modules)): ?> collapsed <?php endif; ?>" href="#" data-toggle="collapse" data-target="#collapse<?php echo e($group); ?>" aria-expanded="true" aria-controls="collapse<?php echo e($group); ?>">
    <i class="<?php echo e($menus[0]['icon_class']); ?>"></i><span><?php echo e(ucwords($group)); ?></span></a>
        <div id="collapse<?php echo e($group); ?>" class="collapse <?php if(in_array($route,$all_modules)): ?> show <?php endif; ?>" aria-labelledby="heading<?php echo e($loop->iteration); ?>" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php $__currentLoopData = $menus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module=>$menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(Route::has('admin.'.$menu['route'].'.index')): ?>
                <a class="collapse-item <?php if($route == strtolower($menu['route'])): ?> active <?php endif; ?>" href="<?php echo e(route('admin.'.$menu['route'].'.index')); ?>"><?php echo e(ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $menu['module']))); ?></a>
                <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </li>
<?php else: ?>
<?php if(Route::has('admin.'.$menus[0]['route'].'.index')): ?>
    <li class="nav-item <?php if(in_array($route,$all_modules)): ?> active <?php endif; ?>">
        <a class="nav-link" href="<?php echo e(route('admin.'.$menus[0]['route'].'.index')); ?>">
        <i class="<?php echo e($menus[0]['icon_class']); ?>"></i>
            <span><?php echo e(ucwords(preg_replace("/[^a-zA-Z0-9]/", " ", $menus[0]['module']))); ?></span></a>
    </li>
    <?php endif; ?>
<?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<hr class="sidebar-divider">
<!-- Divider -->
<hr class="sidebar-divider d-none d-md-block">
<!-- Sidebar Toggler (Sidebar) -->
<div class="text-center d-none d-md-inline">
    <button class="rounded-circle border-0" id="sidebarToggle"></button>
</div>
</ul>
<!-- End of Sidebar -->
<?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/includes/sidebar.blade.php ENDPATH**/ ?>