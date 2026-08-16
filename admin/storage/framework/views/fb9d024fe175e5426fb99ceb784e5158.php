<div class="row">
    <div class="col-md-6">
        <label class="col-form-label" for="title">Title</label>
        <?php echo e(html()->text('title')->class('form-control')); ?>

    </div>
    <div class="col-md-6">
        <label class="col-form-label" for="alt">Alt</label>
        <?php echo e(html()->text('alt')->class('form-control')); ?>

    </div>
    <div class="col-md-6">
        <label class="col-form-label" for="image">Image</label>
        <div class="row">
            <div class="col-md-9">
            <?php echo html()->file('image'); ?>

            <?php echo html()->hidden('old_image', @$row->image); ?>

            </div>
            <div class="col-md-3">
                <?php if(@$row->image && File::exists('storage/'.$module.'/'. @$row->image)): ?>
                <?php echo e(html()->img(asset('storage/'.$module.'/'. @$row->image ))->attributes(['title' => @$row->image ,'width' => '100%'])); ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <label class="col-form-label" for="url">URL</label>
        <?php echo e(html()->text('url')->class('form-control')); ?>

    </div>
    <div class="col-md-6">
        <label class="col-form-label" for="button_text">Button Text</label>
        <?php echo e(html()->text('button_text')->class('form-control')->placeholder('SHOP FRESH FLOWERS')); ?>

    </div>
    <div class="col-md-12">
        <label class="col-form-label" for="banner_text">Banner Text</label>
        <?php echo e(html()->textarea('banner_text')->class('form-control')->rows(3)->placeholder('Short slider description')); ?>

        <small class="form-text text-muted">Tip: use a vertical bar in Title to split lines, for example: Fresh Flowers. | Delivered With Devotion.</small>
    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="priority">Priority</label>
        <?php echo e(html()->text('priority')->class('form-control')); ?>

    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="page">Page</label>
        <?php echo html()->select('page',config('app.pages'))->placeholder('-- Page --')->id('page')->class('form-control'); ?>

    </div>
    <div class="col-md-3">
        <label class="col-form-label" for="parent_div_class">Parent Div Class</label>
        <?php echo e(html()->text('parent_div_class')->class('form-control')); ?>

    </div>
    <div class="col-md-2">
        <label class="col-form-label" for="status">Status</label>
        <?php echo html()->select('status',array('1' => 'Enable', '0' => 'Disable'))->id('status')->class('form-control'); ?>

    </div>
    
</div>
<?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/banners/form.blade.php ENDPATH**/ ?>