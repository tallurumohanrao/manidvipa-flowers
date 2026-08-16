<div class="row pt-2">
    <div class="col-md-12">
        <h5 class="text-capitalize">SEO</h5><hr>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <label for="url" class="col-form-label">URL*</label>
        <?php echo e(html()->hidden('seo[old_url]',$seo->url ?? null)->class('form-control')); ?>

        <?php echo e(html()->text('seo[url]',$seo->url ?? null)->class('form-control')->required()); ?>

    </div>
    <div class="col-md-6">
        <label for="page_title" class="col-form-label">Page Title</label>
        <?php echo e(html()->textarea('seo[page_title]',$seo->page_title ?? null)->rows(3)->class('form-control')); ?>

    </div>
    <div class="col-md-6">
        <label for="meta_keywords" class="col-form-label">Meta Keywords</label>
        <?php echo e(html()->textarea('seo[meta_keywords]',$seo->meta_keywords ?? null)->rows(3)->class('form-control')); ?>

    </div>
    <div class="col-md-6">
        <label for="meta_description" class="col-form-label">Meta Description</label>
        <?php echo e(html()->textarea('seo[meta_description]',$seo->meta_description ?? null)->rows(3)->class('form-control')); ?>

    </div>
    <div class="col-md-4">
        <label for="robots" class="col-form-label">Robots</label>
        <?php echo e(html()->text('seo[robots]',$seo->robots ?? null)->class('form-control')); ?>

    </div>
</div>

<?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/partials/seo.blade.php ENDPATH**/ ?>