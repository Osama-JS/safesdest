<?php if(isset($pageConfigs)): ?>
<?php echo Helper::updatePageConfig($pageConfigs); ?>

<?php endif; ?>
<?php
$configData = Helper::appClasses();

/* Display elements */
$customizerHidden = ($customizerHidden ?? '');

?>



<?php $__env->startSection('layoutContent'); ?>

<!-- Content -->
<?php echo $__env->yieldContent('content'); ?>
<!--/ Content -->

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/commonMaster' , array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/blankLayout.blade.php ENDPATH**/ ?>