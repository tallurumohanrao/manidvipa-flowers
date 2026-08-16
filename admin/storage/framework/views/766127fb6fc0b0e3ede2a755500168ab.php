<?php $__env->startSection('content'); ?>
<section class="content">
	<div class="container-fluid">
        <h4 class="heading text-capitalize">
            <a href="<?php echo e(route('admin.'.$module.'.index')); ?>"><?php echo e($module); ?></a>
        </h4>
		<hr>
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_create1')): ?>
                <a href="<?php echo e(route('admin.'.$module.'.create')); ?>" class="btn btn-primary">Create</a>
                <?php endif; ?>
                </div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
            			<table class="table table-bordered  table-hover">
                            <thead>
                                <tr role="row">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr id="row-<?php echo e($row->id); ?>">
                                    <td><?php echo e($row->id); ?></td>
                                    <td><?php echo e($row->name); ?></td>
                                    <td>
                                        <label class="switch">
                                        <?php echo e(html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',$row)])); ?>

                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td><?php echo e($row->created_at); ?></td>
                                    <td>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_edit')): ?>
                                            <a href="<?php echo e(route('admin.'.$module.'.edit',$row)); ?>"><i class="fas fa-edit p-1"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/pages/index.blade.php ENDPATH**/ ?>