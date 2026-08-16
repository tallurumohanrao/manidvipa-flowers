<?php $__env->startSection('content'); ?>
<section class="content">
	<div class="container-fluid">
        <div class="row">
            <ul class="list-inline">
                <li class="list-inline-item">
                    <div class="btn-group">
                        <p><a target="_blank" href="<?php echo e(route('admin.'.$module.'.images',['id'=>$row->id])); ?>" class="btn btn-primary text-right">Images</a></p>
                        <p><a target="_blank" href="<?php echo e(route('admin.'.$module.'.weights',['id'=>$row->id])); ?>" class="btn btn-primary text-right">Weights</a></p>
                        
                        <p><a target="_blank" href="<?php echo e(route('admin.'.$module.'.reviews',['id'=>$row->id])); ?>" class="btn btn-primary text-right">Reviews</a></p>
                    </div>
                </li>
            </ul>
            <ul class="list-inline">
                <li class="list-inline-item">
                    <div class="btn-group">
                        <a href="<?php echo e(route('admin.'.$module.'.index')); ?>" class="btn btn-danger">Cancel</a>
                    </div>
                </li>
            </ul>
        </div>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
                <h5 class="text-capitalize">Edit <?php echo e(Str::singular($module)); ?> - <?php echo e($row->title); ?></h5>
                <hr>
                <?php echo e(html()->model($row)->form('PATCH')->route('admin.'.$module.'.update', ['product'=>$row->id])->class('')->id('form')->attributes(['enctype'=>'multipart/form-data'])->open()); ?>

                <?php echo $__env->make('admin.'.$module.'.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('admin.partials.save', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(html()->form()->close()); ?>

            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/products/edit.blade.php ENDPATH**/ ?>