<?php if($errors ?? ''): ?>
    <?php if($errors->any()): ?>
        Message.add('<?php echo collect($errors->all())->implode('</br>'); ?>', {type: 'error',sticky: true});
    <?php endif; ?>
<?php endif; ?>

<?php if($message = Session::get('success')): ?>
Message.add('<?php echo e($message); ?>', {type: 'success'});
<?php endif; ?>

<?php if($message = Session::get('error')): ?>
<div class="alert alert-danger alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <strong>Error:</strong> <?php echo e($message); ?>

</div>
<?php endif; ?>

<?php if($message = Session::get('warning')): ?>
<div class="alert alert-warning alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <strong>Warning:</strong> <?php echo e($message); ?>

</div>
<?php endif; ?>

<?php if($message = Session::get('info')): ?>
<div class="alert alert-info alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <strong>Info:</strong> <?php echo e($message); ?>

</div>
<?php endif; ?>
<?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/includes/flash-message.blade.php ENDPATH**/ ?>