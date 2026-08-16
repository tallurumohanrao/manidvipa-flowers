<?php $__env->startSection('content'); ?>
<section class="content">
	<div class="container-fluid">		
		<div class="card shadow mb-4">
            <div class="card-body">
                <ul class="list-inline mb-3 text-right">
                    <li class="list-inline-item float-left">
                        <h4 class="heading text-capitalize">
                            <a href="<?php echo e(route('admin.'.$module.'.index')); ?>">Featured Products</a>
                        </h4>
                    </li>
                    <li class="list-inline-item">
                        <div class="btn-group">
                          <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="<?php echo e(route('admin.'.$module.'.massdestroy')); ?>" role="button">Delete</a>
                        </div>
                    </li>
                </ul>
                <hr>
            	<div class="row">
                    <div class="col-md-2">
                        <?php echo e(html()->form('POST')->route('admin.'.$module.'.store')->class('form-horizontal')->id('form')->open()); ?>

                        <label for="products" class="col-form-label">Add Featured Products</label>
                    </div>
                    <div class="col-md-5">
                        <?php echo html()->multiselect('products[]',$products)->id('products')->class('select2 form-control'); ?>

                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <?php echo e(html()->form()->close()); ?>

                    </div>
                </div>
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
                                    <th>Product Title</th>
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
                                    <td><?php echo e($loop->iteration); ?></td>
                                    <td><?php echo e($row->title); ?></td>
                                    <td><?php echo e($row->created_at); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="javascript:;" class="delete" data-id="<?php echo e($row->id); ?>" data-url="<?php echo e(route('admin.'.$module.'.destroy',['featuredproduct'=>$row->id])); ?>"><i class="fas fa-trash text-danger p-1"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <div class="row">
                	<div class="col-sm-12 col-md-5">
                		<div class="dataTables_info" id="dataTable_info" role="status" aria-live="polite">Showing <?php echo e($data->firstItem()); ?> to <?php echo e($data->lastItem()); ?> of <?php echo e($data->total()); ?> entries</div>
                	</div>
                	<div class="col-sm-12 col-md-7">
                		<?php echo e($data->links()); ?>

                	</div>
                </div>
            </div>
        </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/featuredproducts/index.blade.php ENDPATH**/ ?>