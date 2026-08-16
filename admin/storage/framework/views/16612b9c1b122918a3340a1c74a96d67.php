<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="title">Title</label>
        <?php echo e(html()->text('title')->class('form-control')); ?>

    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="sku">SKU</label>
        <?php echo e(html()->text('sku')->class('form-control')); ?>

    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="qty">Qty</label>
        <?php echo e(html()->text('qty')->class('form-control')); ?>

    </div>
    
    <div class="col-md-4">
        <label class="col-form-label" for="categories">Categories</label>
        <?php echo html()->multiselect('product_category[]',$categories,$selected)->id('product_category')->class('select2 form-control'); ?>

    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="priority">Priority</label>
        <?php echo e(html()->text('priority')->class('form-control')); ?>

    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        <?php echo html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control'); ?>

    </div>
    <div class="col-md-12">
        <label class="col-form-label" for="short_description">Short Description</label>
        <?php echo e(html()->textarea('short_description')->class('form-control editor')); ?>

    </div>
    <div class="col-md-12">
        <label class="col-form-label" for="description">Description</label>
        <?php echo e(html()->textarea('description')->class('form-control editor')); ?>

    </div>
</div>
<?php echo $__env->make('admin.partials.seo', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/products/form.blade.php ENDPATH**/ ?>