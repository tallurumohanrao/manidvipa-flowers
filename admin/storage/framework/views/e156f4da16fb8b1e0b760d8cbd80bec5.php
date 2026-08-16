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
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_create')): ?>
                <a class="btn btn-primary" href="javascript:document.getElementById('FormButton').click();"><i class="fa fa-save mr-1"></i>Save</a>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_delete')): ?>
                    <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="<?php echo e(route('admin.'.$module.'.massdestroy')); ?>" role="button">Delete</a>
                <?php endif; ?>
				</div>
			</li>
		</ul>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
                    <?php echo e(html()->form('POST')->route('admin.'.$module.'.store')->class('search')->id('search')->attributes(['enctype'=>'multipart/form-data'])->open()); ?>

            			<table class="table table-bordered table-hover">
                            <thead>
                                <tr role="row">
                                    <th>
                                        <div class="custom-control custom-checkbox">
                                            <?php echo html()->checkbox('selectAll')->id('selectAll')->class('custom-control-input'); ?>

                                            <label class="custom-control-label" for="selectAll"></label>
                                        </div>
                                    </th>
                                    <th>S.No.</th>
                                    <th>Upload</th>
                                    <th>Image</th>
                                    
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th><a href="javascript:;" onclick="addGallery()"><i class="fa fa-plus-circle"></i></a></th>
                                </tr>
                            </thead>

                            <tbody id="tablecontents">
                            <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr id="row-<?php echo e($row->id); ?>">
                                    <td>
                                        <div class="custom-control custom-checkbox sub_chk">
                                            <?php echo html()->checkbox('id[]', false, $row->id)->id($row->id)->class('custom-control-input'); ?>

                                            <label class="custom-control-label" for="<?php echo e($row->id); ?>"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo e($loop->iteration); ?>

                                        <?php echo html()->hidden('Gallery['.$loop->index.'][id]', $row->id ); ?>

                                        <?php echo html()->hidden('Gallery['.$loop->index.'][old_file]', $row->image ); ?>

                                    </td>

                                    <td><?php echo html()->file('Gallery['.$loop->index.'][image]'); ?></td>
                                    <td><?php echo e(html()->img(asset('storage/'.$module.'/'. @$row->image ), null)->attributes(array('title' => @$row->image ,'width' => '70px'))); ?></td>
                                    
                                    <td>
                                        <?php echo html()->text('Gallery['.$loop->index.'][priority]', $row->priority)->class('form-control'); ?>

                                    </td>
                                    <td>
                                        <label class="switch">
                                        <?php echo html()->checkbox('status', $row->status ==1?true:false)->id($row->id)->class('status')->id('status_'.$row->id)->attributes(['data-url'=>route('admin.'.$module.'.update.status',$row)]); ?>

                                        <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td><?php echo e($row->created_at); ?></td>
                                    <td>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_delete')): ?>
                                            <a href="javascript:;" class="delete btn btn-danger" data-id="<?php echo e($row->id); ?>" data-url="<?php echo e(route('admin.'.$module.'.destroy',$row)); ?>"><i class="fa fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                        <?php echo html()->submit('FormButton','SAVE')->class('btn btn-primary d-none')->id('FormButton'); ?>

                    <?php echo e(html()->form()->close()); ?>

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
<script>
    var row_no =<?php echo e($data->count() + 1); ?>;
</script>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('script'); ?>
<script>
function addGallery()
{
    row = '<tr id="row-'+ row_no +'">';
    row += '<td></td>';
    row += '<td>'+row_no+'</td>';
    row += '<td><input name="Gallery[' + row_no + '][image]" type="file" required></td>';
    row += '<td></td>';
    //row += '<td><select class="form-control" autocomplete="off" name="Gallery[' + row_no + '][type]"><option value="1">Yes</option><option value="0">No</option></select></td>';
    row += '<td><input name="Gallery[' + row_no + '][priority]" class="form-control" type="text"></td>';
    row += '<td><select class="form-control" autocomplete="off" name="Gallery[' + row_no + '][status]"><option value="1">Enable</option><option value="2">Disable</option></select></td>';
    row += '<td></td>';
    row += '<td> <a onclick="$(\'#row-' + row_no + '\').remove();"  class="btn btn-danger" ><i class="fa fa-trash"></i></a> </td>';
    row += '</tr>';
    $('#tablecontents').prepend(row);
    row_no++;
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/gallery/index.blade.php ENDPATH**/ ?>