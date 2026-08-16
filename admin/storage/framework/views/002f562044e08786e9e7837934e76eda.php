<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $__env->yieldContent('title'); ?></title>
	<!-- Meta -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(asset('storage/website/'.config('SITE_FAVICON'))); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/sb-admin-2.min.css')); ?>" />
    <link rel="stylesheet" href="<?php echo e(asset('assets/admin/css/auth/style.css')); ?>" />
</head>
<!-- [ auth-signin ] start -->
<div class="auth-wrapper">
    <div class="col-md-4">
        <div class="auth-content container">
            <div class="card">
                <div class="row align-items-center">
					<div class="card-body">
                        <?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                        <?php endif; ?>
						<?php echo $__env->yieldContent('content'); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
<?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/layouts/auth.blade.php ENDPATH**/ ?>