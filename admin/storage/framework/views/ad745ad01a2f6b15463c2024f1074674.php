<?php $__env->startSection('content'); ?>
<section class="content">
    <div class="container-fluid">
		<div class="card shadow mb-4">
            <div class="card-body">
                <?php echo $__env->make('admin.includes.index', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo e(html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open()); ?>

                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        <?php echo $__env->make('admin.includes.items', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                            <?php echo e(html()->text('title',request('title'))->class('form-control')->placeholder('Title')); ?>

                            </li>
                            <li class="list-inline-item">
                                <?php echo html()->select('status', array('' => 'Status', '1' => 'Enable', '0' => 'Disable'))->value(request('status'))->id('status')->class('custom-select custom-select-sm form-control form-control-sm w-80'); ?>

                            </li>
                            <li class="list-inline-item">
                            <?php echo html()->button('Search','submit')->class('btn btn-primary form-control form-control-sm'); ?>

                            </li>
                        </ul>
                    </div>
                </div>
                <?php echo e(html()->form()->close()); ?>

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
                                    <td><?php echo e(html()->img(asset('storage/'.$module.'/'. @$row->image ), null)->attributes(array('title' => @$row->title ,'width' => '70px'))); ?></td>
                                    <td>
                                        <label class="switch">
                                        <?php echo e(html()->checkbox('status', $row->status, null)->class('status')->id('status_'.$row->id)->attributes(['data-id'=>$row->id,'data-url'=>route('admin.'.$module.'.update.status',['id'=>$row->id])])); ?>

                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td><?php echo e($row->created_at); ?></td>
                                    <td>
                                        <div class="btn-group">
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_edit')): ?>
                                            <a href="<?php echo e(route('admin.'.$module.'.edit',['category'=>$row->id])); ?>"><i class="fas fa-edit p-1"></i></a>
                                        <?php endif; ?>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_delete')): ?>
                                            <a href="javascript:;" class="delete" data-id="<?php echo e($row->id); ?>" data-url="<?php echo e(route('admin.'.$module.'.destroy',['category'=>$row->id])); ?>"><i class="fas fa-trash text-danger p-1"></i></a>
                                        <?php endif; ?>
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

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/categories/index.blade.php ENDPATH**/ ?>