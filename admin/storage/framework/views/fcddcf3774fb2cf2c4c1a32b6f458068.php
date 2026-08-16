<?php $__currentLoopData = ['Site','Contact','Social Media','Developer']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 pb-3">
        <div class="form-boarder">
            <div class="form-row">
                <h3 class="text-uppercase w-100"><?php echo e($v); ?></h3>
                <?php $__currentLoopData = $settings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $setting): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($setting->type==$v): ?>
                <div class="form-group col-md-4 col-lg-4 col-sm-12 col-xs-12">
                    <label class="w-100 control-label" for="[<?php echo e($setting->key); ?>]"><?php echo e($setting->label); ?></label>
                        <?php if($setting->input=='text'): ?>
                            <?php echo e(html()->text("Site[$setting->key]")->value($setting->value)->class('form-control')); ?>

                        <?php endif; ?>
                        <?php if($setting->input=='textarea'): ?>
                            <?php echo e(html()->textarea("Site[$setting->key]")->value($setting->value)->class('form-control')->attributes(['rows'=>2])); ?>

                        <?php endif; ?>
                        <?php if($setting->input=='file'): ?>
                            <?php echo html()->file('Files['.$setting->key.']'); ?>

                            <input type="hidden" name="Files[old_<?php echo e($setting->key); ?>]" value="<?php echo e($setting->value); ?>">
                            <?php if(@$setting->value): ?>
                            <div class="image float-right">
                                <?php echo e(html()->img(asset('storage/website/'. @$setting->value ), $setting->value)->attributes(['title' => @$setting->value ,'class' => 'w-50'])); ?>

                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\Projects\manidvipa-flowers\admin\resources\views/admin/settings/fields.blade.php ENDPATH**/ ?>