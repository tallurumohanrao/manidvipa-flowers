<?php $__env->startSection('content'); ?>
<section class="content">
	<div class="container-fluid">
        <h4 class="heading text-capitalize">
            <a href="<?php echo e(route('admin.'.$module.'.index')); ?>">Order Statuses</a>
        </h4>
		<hr>
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                    <a href="<?php echo e(route('admin.'.$module.'.create')); ?>" class="btn btn-primary">Create</a>
                  
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
            			<table class="table table-bordered  table-hover tablegrid">
                            <thead>
                                <tr role="row">
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            <?php echo html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input'); ?>

                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr id="row-<?php echo e($row->id); ?>">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            <?php echo html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input'); ?>

                                            <label class="custom-control-label" for="<?php echo e($row->id); ?>"></label>
                                        </div>
                                    </td>
                                    <td><?php echo e($row->id); ?></td>
                                    <td><?php echo e($row->name); ?></td>
                                    <td><?php echo e($row->subject); ?></td>
                                    <td><?php echo $row->body_html; ?></td>
                                    <td><?php echo e($row->created_at); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo e(route('admin.'.$module.'.edit',['orderstatus'=>$row->id])); ?>"><i class="fas fa-edit p-1"></i></a>
                                            
                                        </div>
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

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/orderstatuses/index.blade.php ENDPATH**/ ?>