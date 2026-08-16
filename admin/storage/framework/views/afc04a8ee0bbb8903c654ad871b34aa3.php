<?php $__env->startSection('content'); ?>
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize"><a href="<?php echo e(route('admin.'.$module.'.index')); ?>"><?php echo e($module); ?></a></h4>
		<hr>
		<ul class="list-inline mb-3 text-right">
			<li class="list-inline-item">
				<div class="btn-group">
                  <a href="<?php echo e(route('admin.'.$module.'.create')); ?>" class="btn btn-primary">Create</a>
                  <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="<?php echo e(route('admin.'.$module.'.massdestroy')); ?>" role="button">Delete</a>
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
                                    <th>S.No</th>
                                    <th>Title</th>
                                    <th>Image</th>
                                    <th>Status</th>
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
                                    <td>
                                        <?php echo e(html()->img(asset('storage/'.$module.'/'. @$row->image ), null)->attributes(array('title' => @$row->image ,'width' => '70px'))); ?>

                                    </td>
                                    <td>
                                        <label class="switch">
                                        <?php echo e(html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',['id'=>$row->id])])); ?>

                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td><?php echo e($row->created_at); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo e(route('admin.'.$module.'.edit',$row)); ?>" class="text-primary fas fa-edit p-1 "><i class="ti-pencil-alt"></i></a>
                                            <a href="javascript:;" class="delete text-danger fas fa-trash text-danger p-1" data-id="<?php echo e($row->id); ?>" data-url="<?php echo e(route('admin.'.$module.'.destroy',$row)); ?>"><i class="ti-trash"></i></a>
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
	</div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/posts/index.blade.php ENDPATH**/ ?>