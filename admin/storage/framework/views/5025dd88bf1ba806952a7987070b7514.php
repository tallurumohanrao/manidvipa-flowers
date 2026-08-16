<?php $__env->startSection('content'); ?>
<section class="content">
	<div class="container-fluid">
		<ul class="list-inline mb-3 text-right">
            <li class="list-inline-item">
                <div class="btn-group">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_edit')): ?>
                    <a href="javascript:$('#form').submit();" class="btn btn-primary">Save</a>
                <?php endif; ?>
                    <a href="<?php echo e(route('admin.'.$module.'.index')); ?>" class="btn btn-danger">Cancel</a>
				</div>
			</li>
		</ul>
        <hr>
		<div class="card shadow mb-4">
            <div class="card-body">
            <?php echo e(html()->form('POST')->route('admin.'.$module.'.store')->id('form')->attributes(['enctype'=>'multipart/form-data'])->open()); ?>

            <?php echo $__env->make('admin.'.$module.'.fields', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo html()->button('Save','submit')->class('btn btn-primary d-none')->id('save'); ?>

            <?php echo e(html()->form()->close()); ?>

            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/settings/index.blade.php ENDPATH**/ ?>