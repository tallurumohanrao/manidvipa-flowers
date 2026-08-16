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
                <a href="<?php echo e(route('admin.'.$module.'.create')); ?>" class="btn btn-primary"><?php echo e($module); ?></a>
                  <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="<?php echo e(route('admin.'.$module.'.massdestroy')); ?>" role="button">Delete</a>
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
                <?php echo e(html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open()); ?>

            	<div class="row mb-3">
            		<div class="col-sm-12 d-flex align-items-center justify-content-between">
            			<ul class="list-inline">
            				<li class="list-inline-item">Show</li>
            				<li class="list-inline-item">
                                <?php echo html()->select('perPage',pageNumbers())->value(request('perPage'))->id('perPage')->class('custom-select custom-select-sm form-control form-control-sm w-80')->attributes(['onchange'=>'$("#search").submit();']); ?>

                            </li>
                			<li class="list-inline-item">entries</li>
            			</ul>

            			<ul class="list-inline">
            				<li class="list-inline-item">
                                <?php echo e(html()->text('url',request('url'))->class('form-control form-control-sm')->placeholder('URL')); ?>

                            </li>
            				
                            <li class="list-inline-item">
                            <?php echo html()->select('status', array('' => 'Status', '1' => 'Enable', '2' => 'Disable'))->value(request('status'))->id('status')->class('custom-select custom-select-sm form-control form-control-sm w-80'); ?>

                            </li>
                            <li class="list-inline-item">
                            <?php echo html()->button('Search','submit')->class('form-control form-control-sm'); ?>

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
                                    <th>S.No.</th>
                                    <th>URL</th>
                                    
                                    <th>Page Title</th>
                                    <th>Meta Keywords</th>
                                    <th>Meta Description</th>
                                    <th>Robots</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $seo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr id="row-<?php echo e($seo->id); ?>">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                        <?php echo html()->checkbox('id[]')->value($seo->id)->id($seo->id)->class('custom-control-input'); ?>

                                        <label class="custom-control-label" for="<?php echo e($seo->id); ?>"></label>
                                        </div>
                                    </td>
                                    <td><?php echo e($loop->iteration); ?></td>
                                    <td><?php echo e($seo->url); ?></td>
                                    
                                    <td><?php echo e($seo->page_title); ?></td>
                                    <td><?php echo e($seo->meta_keywords); ?></td>
                                    <td><?php echo e($seo->meta_description); ?></td>
                                    <td><?php echo e($seo->robots); ?></td>
                                    <td>
                                        <label class="switch">
                                        <?php echo e(html()->checkbox('status', $seo->status, null)->class('status')->id('status_'.$seo->id)->attributes(['data-id'=>$seo->id,'data-url'=>route('admin.'.$module.'.update.status',$seo)])); ?>

                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td><?php echo e($seo->created_at); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo e(route('admin.'.$module.'.edit',$seo)); ?>"><i class="fas fa-edit p-1"></i></a>
                                            <a href="javascript:;" class="delete" data-id="<?php echo e($seo->id); ?>" data-url="<?php echo e(route('admin.'.$module.'.destroy',$seo)); ?>"><i class="fas fa-trash text-danger p-1"></i></a>
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
                    <?php echo e($data->onEachSide(config('onEachSide'))->links()); ?>

                	</div>
                </div>
            </div>
        </div>
	</div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/seo/index.blade.php ENDPATH**/ ?>