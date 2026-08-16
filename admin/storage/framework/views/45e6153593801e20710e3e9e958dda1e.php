<?php $__env->startSection('title','Admin Login'); ?>
<?php $__env->startSection('content'); ?>
<h4 class="mb-3 f-w-400">Login into your account</h4>
<?php echo e(html()->form('POST')->route('admin.login')->open()); ?>

<div class="input-group mb-2">
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="feather icon-mail"></i></span>
    </div>
    <?php echo e(html()->email('email')->class('form-control')->placeholder('Email')); ?>

</div>
<div class="input-group mb-3">
    <div class="input-group-prepend">
        <span class="input-group-text"><i class="feather icon-lock"></i></span>
    </div>
    <?php echo e(html()->password('password')->class('form-control')->placeholder('Password')); ?>

</div>

<div class="form-group text-left mt-2">
    <div class="checkbox checkbox-primary d-inline">
        <?php echo html()->checkbox('remember')->checked()->class('custom-control-input'); ?>

        <label for="remember" class="cr">Remember me</label>
    </div>
</div>

<?php echo e(html()->submit('Login')->class('btn btn-primary mb-4')); ?>

<?php echo e(html()->form()->close()); ?>

<p class="mb-2 text-muted">Forgot password? <?php echo e(html()->a(route('admin.password.request'),'Reset')->class('f-w-400')); ?></p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/auth/login.blade.php ENDPATH**/ ?>