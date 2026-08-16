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
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_delete')): ?>
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="<?php echo e(route('admin.'.$module.'.massdestroy')); ?>" role="button">Delete</a>
                <?php endif; ?>
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
                <?php echo e(html()->form('GET')->route('admin.'.$module.'.index',['type'=>request('type')])->id('search')->open()); ?>

                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        <?php echo $__env->make('admin.includes.items', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <ul class="list-inline d-flex">
                        
                        <li class="list-inline-item">
                        <?php echo e(html()->hidden('type',request('type'))); ?>

                        <?php echo e(html()->text('email',request('email'))->class('form-control')->placeholder('Email')); ?>

                        <li class="list-inline-item">
                        <?php echo e(html()->text('mobile',request('mobile'))->class('form-control')->placeholder('Mobile')); ?>

                        </li>
                        <li class="list-inline-item">
                        <?php echo html()->button('Search','submit')->class('btn btn-primary'); ?>

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
                                    <th scope="col">ID</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Mobile</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Products</th>
                                    <th scope="col">Message</th>
                                    <th scope="col">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr id="row-<?php echo e($row->id); ?>">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                        <?php echo html()->checkbox('id[]')->value($row->id)->id($row->id)->class('custom-control-input'); ?>

                                        <label class="custom-control-label" for="<?php echo e($row->id); ?>"></label>
                                        </div>
                                    </td>
                                    <td><?php echo e($loop->iteration); ?></td>
                                    <td><?php echo e($row->name); ?></td>
                                    <td><?php echo e($row->mobile); ?></td>
                                    <td><?php echo e($row->email); ?></td>
                                    <td><?php echo e($row->subject); ?></td>
                                    <td><?php echo e($row->message); ?></td>
                                    <td><?php echo e($row->created_at); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <div class="row">
                	<div class="col-sm-12 col-md-5">
                		<p>Showing <?php echo e($data->firstItem()); ?> to <?php echo e($data->lastItem()); ?> of <?php echo e($data->total()); ?> entries</p>
                	</div>
                	<div class="col-sm-12 col-md-7">
                        <?php echo e($data->onEachSide(config('onEachSide'))->links()); ?>

                	</div>
                </div>
            </div>
        </div>
	</div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/contacts/index.blade.php ENDPATH**/ ?>