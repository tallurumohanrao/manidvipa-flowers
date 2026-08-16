<?php $__env->startSection('content'); ?>
<section class="content">
	<div class="container-fluid">
		<ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                    <a href="<?php echo e(route('admin.'.$module.'.index')); ?>" class="btn btn-danger">Cancel</a>
				</div>
			</li>
		</ul>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
                <h5 class="text-capitalize">Edit <?php echo e(Str::singular($module)); ?></h5>
                <hr>
                <?php echo e(html()->model($row)->form('PATCH')->route('admin.'.$module.'.update', $row)->class('')->id('form')->attributes(['enctype'=>'multipart/form-data'])->open()); ?>

                <?php echo $__env->make('admin.'.$module.'.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('admin.partials.save', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(html()->form()->close()); ?>

            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/banners/edit.blade.php ENDPATH**/ ?>