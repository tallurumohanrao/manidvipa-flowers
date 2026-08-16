<?php $__env->startSection('content'); ?>
<section class="content">
	<div class="container-fluid">
		<div class="card shadow mb-4">
            <div class="card-body">
                <ul class="list-inline mb-3 text-right">
                    <li class="list-inline-item float-left">
                        <h4 class="heading text-capitalize">
                            <a href="<?php echo e(route('admin.'.$module.'.index')); ?>"><?php echo e($module); ?></a>
                        </h4>
                    </li>
                    <li class="list-inline-item">
                        <div class="btn-group">
                          <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="<?php echo e(route('admin.'.$module.'.massdestroy')); ?>" role="button">Delete</a>
                        </div>
                    </li>
                </ul><hr>
                <?php echo e(html()->form('GET')->route('admin.'.$module.'.index')->id('search')->open()); ?>

                <div class="row">
                    <div class="col-md-12 d-flex align-items-center justify-content-between">
                        <?php echo $__env->make('admin.includes.items', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <ul class="list-inline">
                            <li class="list-inline-item">
                            <?php echo e(html()->text('orderId',request('orderId'))->class('form-control form-control-sm')->placeholder('Order Id')); ?>

                            </li>
                            
                            <li class="list-inline-item">
                                <?php echo html()->select('orderStatus', $orderStatuses)->placeholder('-- Order Status --')->value(request('orderStatus'))->id('orderStatus')->class('custom-select custom-select-sm form-control form-control-sm w-80'); ?>

                            </li>
                            
                            <li class="list-inline-item">
                                <?php echo html()->select('shippingStatus', $shippingStatuses)->placeholder('-- Shipping Status --')->value(request('shippingStatus'))->id('shippingStatus')->class('custom-select custom-select-sm form-control form-control-sm w-80'); ?>

                            </li>
                            
                            <li class="list-inline-item">
                                <?php echo html()->select('paymentStatus', paymentStatuses())->placeholder('-- Payment Status --')->value(request('paymentStatus'))->id('paymentStatus')->class('custom-select custom-select-sm form-control form-control-sm w-80'); ?>

                            </li>
                            
                            <li class="list-inline-item">
                                <label for="deliveryDate">Delivery Date</label>
                                <?php echo html()->date('deliveryDate')->placeholder('-- Delivery Date --')->value(request('deliveryDate'))->id('deliveryDate')->class('form-control form-control-sm w-80'); ?>

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
                                    <th>Order ID</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>User Id</th>
                                    <th>Amount</th>
                                    <th>Order Status</th>
                                    <th>Payment Status</th>
                                    <th>Shipping Status</th>
                                    <th>Delivery Date</th>
                                    <th>Created Date</th>
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
                                    <td><a target="_blank" href="<?php echo e(route('admin.'.$module.'.show',['order'=>$row->id])); ?>"><?php echo e($row->id); ?></a></td>
                                    <td><?php echo e($row->name); ?></td>
                                    <td><?php echo $row->email .'</br>'. $row->contact_number; ?></td>
                                    <td>
                                        <?php if($row->user_id): ?>
                                        <a target="_blank" href="<?php echo e(route('admin.users.show',$row->user_id)); ?>"><?php echo e($row->user_id); ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo currency($row->amount); ?></td>
                                    <td><?php echo e($row->order_status); ?></td>
                                    <td><?php echo e($row->payment_status); ?></td>
                                    <td><?php echo e($row->shipping_status); ?></td>
                                    <td><?php echo e($row->serve_date ? date('d/m/Y',strtotime($row->serve_date)) : null); ?></td>
                                    <td><?php echo e(date('d/m/Y h:i A',strtotime($row->created_at))); ?></td>
                                    
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

<?php echo $__env->make('admin.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/orders/index.blade.php ENDPATH**/ ?>