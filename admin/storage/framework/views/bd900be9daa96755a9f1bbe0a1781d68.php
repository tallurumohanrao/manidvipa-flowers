<ul class="list-inline mb-3 text-right">
    <li class="list-inline-item float-left">
        <h4 class="heading text-capitalize">
            <a href="<?php echo e(route('admin.'.$module.'.index')); ?>"><?php echo e($module); ?></a>
        </h4>
    </li>
    <li class="list-inline-item">
        <div class="btn-group">
        <a href="<?php echo e(route('admin.'.$module.'.create')); ?>" class="btn btn-primary">Create</a>
          <a class="btn btn-danger" href="javascript:;" id="delete_all" data-url="<?php echo e(route('admin.'.$module.'.massdestroy')); ?>" role="button">Delete</a>
        </div>
    </li>
</ul><hr>
<?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/includes/index.blade.php ENDPATH**/ ?>