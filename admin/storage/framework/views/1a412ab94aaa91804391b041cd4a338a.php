<?php $__env->startSection('content'); ?>
<section class="content">
	<div class="container-fluid">
		<h4 class="heading text-capitalize">
            <a href="<?php echo e(route('admin.'.$module.'.weights',['id'=>$product->id])); ?>">Add/Remove Product Weights  - <?php echo e($product->title); ?></a>
        </h4>
		<hr>
		<div class="card shadow mb-4">
            <div class="card-body">
            	<div class="row">
            		<div class="col-sm-12">
            		<div class="table-responsive">
                    <?php echo e(html()->form('POST')->route('admin.'.$module.'.weightsstore',['id'=>$product->id])->class('form-horizontal')->id('form')->open()); ?>

            			<table class="table table-bordered table-hover">
                            <thead>
                                <tr role="row">
                                    
                                    <th scope="col">S.No</th>
                                    <th scope="col">Weight</th>
                                    <th scope="col">Sell Price</th>
                                    <th scope="col">List Price</th>
                                    <th scope="col">Cost Price</th>
                                    
                                    <th scope="col">Stock Qty</th>
                                    <th scope="col">Enable Stock</th>
                                    <th scope="col">Status</th>
                                    
                                    <th><a href="javascript:;" onclick="addWeights()"><i class="fa fa-plus-circle"></i></a></th>
                                </tr>
                            </thead>

                            <tbody id="tablecontents">
                            <?php $__currentLoopData = $weights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr id="row-<?php echo e($row->id); ?>">
                                    
                                    <td>
                                        <?php echo e($loop->iteration); ?>

                                        <?php echo e(html()->hidden('Weight['.$loop->index.'][id]', $row->id)); ?>

                                    </td>
                                    <td>
                                        <?php echo html()->select('Weight['.$loop->index.'][name]',$selectboxweights,$row->name)->class('form-control')->placeholder('-- Select --','')->required(); ?>

                                    </td>
                                    <td>
                                        <?php echo e(html()->text('Weight['.$loop->index.'][sell_price]', $row->sell_price)->class('form-control')->required()); ?>

                                    </td>
                                    <td>
                                        <?php echo e(html()->text('Weight['.$loop->index.'][list_price]', $row->list_price)->class('form-control')->required()); ?>

                                    </td>
                                    <td>
                                        <?php echo e(html()->text('Weight['.$loop->index.'][cost_price]', $row->cost_price)->class('form-control')); ?>

                                    </td>
                                    <td><?php echo e(html()->text('Weight['.$loop->index.'][qty]', $row->qty)->class('form-control')); ?></td>
                                    
                                    <td>
                                    <?php echo e(html()->checkbox('Weight['.$loop->index.'][stock]', $row->stock == 1, 1)->class('form-control')); ?>

                                    </td>
                                    <td>
                                        <?php echo html()->select('Weight['.$loop->index.'][status]',[''=>'-- Select --','1'=>'Enable','0'=>'Disable'],$row->status)->class('form-control')->required(); ?>

                                        
                                    </td>
                                    
                                    <td>
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($module.'_delete')): ?>
                                            <a href="javascript:;" class="delete btn btn-danger" data-id="<?php echo e($row->id); ?>" data-url="<?php echo e(route('admin.'.$module.'.weight.destroy',['id'=>$row->id])); ?>"><i class="fa fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">Save</button>
                    <?php echo e(html()->form()->close()); ?>

                    </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</section>
<script>
    var row_no =<?php echo e($weights->count() + 1); ?>;
</script>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('script'); ?>
<script>
function addWeights()
{
    row = '<tr id="row-'+ row_no +'">';
    // row += '<td></td>';
    row += '<td>'+row_no+'<input name="Weight[' + row_no + '][id]" type="hidden" value=""></td>';
    row += '<td><select class="select2 form-control" autocomplete="off" name="Weight[' + row_no + '][name]" required><?php echo $shtml; ?></select></td>';
    row += '<td><input name="Weight[' + row_no + '][sell_price]" class="form-control" type="text" required></td>';
    row += '<td><input name="Weight[' + row_no + '][list_price]" class="form-control" type="text" required></td>';
    row += '<td><input name="Weight[' + row_no + '][cost_price]" class="form-control" type="text"></td>';
    row += '<td><input class="form-control" autocomplete="off" name="Weight[' + row_no + '][qty]" type="number" step="any"></td>';
    //row += '<td><input class="form-control" autocomplete="off" name="Weight[' + row_no + '][vat_price]" type="number" step="any"></td>';
    //row += '<td><input autocomplete="off" name="Weight[' + row_no + '][vat_enable]" type="checkbox" value="1"></td>';
    row += '<td><input autocomplete="off" name="Weight[' + row_no + '][stock]" class="form-control" type="checkbox" value="1" checked></td>';
    row += '<td><select class="form-control" autocomplete="off" name="Weight[' + row_no + '][status]"><option value="1">Enable</option><option value="2">Disable</option></select></td>';
    row += '<td> <a onclick="$(\'#row-' + row_no + '\').remove();"  class="btn btn-danger" ><i class="fa fa-trash"></i></a> </td>';
    row += '</tr>';
    $('#tablecontents').append(row);
    row_no++;
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/products/product_weights.blade.php ENDPATH**/ ?>