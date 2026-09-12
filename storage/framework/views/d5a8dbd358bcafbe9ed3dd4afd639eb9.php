<?php
use Illuminate\Support\Facades\Vite;

$menuCollapsed = ($configData['menuCollapsed'] === 'layout-menu-collapsed') ? json_encode(true) : false;
?>
<!-- laravel style -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/js/helpers.js']); ?>
<!-- beautify ignore:start -->
<?php if($configData['hasCustomizer']): ?>
  <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
  <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/js/template-customizer.js']); ?>
<?php endif; ?>

  <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
  <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/config.js']); ?>

<?php if($configData['hasCustomizer']): ?>
<script type="module">
  window.templateCustomizer = new TemplateCustomizer({
    cssPath: '',
    themesPath: '',
    defaultStyle: "<?php echo e($configData['styleOpt']); ?>",
    defaultShowDropdownOnHover: "<?php echo e($configData['showDropdownOnHover']); ?>", // true/false (for horizontal layout only)
    displayCustomizer: "<?php echo e($configData['displayCustomizer']); ?>",
    lang: '<?php echo e(app()->getLocale()); ?>',
    pathResolver: function(path) {
      var resolvedPaths = {
        // Core stylesheets
        <?php $__currentLoopData = ['core']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          '<?php echo e($name); ?>.scss': '<?php echo e(Vite::asset('resources/assets/vendor/scss'.$configData["rtlSupport"].'/'.$name.'.scss')); ?>',
          '<?php echo e($name); ?>-dark.scss': '<?php echo e(Vite::asset('resources/assets/vendor/scss'.$configData["rtlSupport"].'/'.$name.'-dark.scss')); ?>',
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        // Themes
        <?php $__currentLoopData = ['default', 'bordered', 'semi-dark']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          'theme-<?php echo e($name); ?>.scss': '<?php echo e(Vite::asset('resources/assets/vendor/scss'.$configData["rtlSupport"].'/theme-'.$name.'.scss')); ?>',
          'theme-<?php echo e($name); ?>-dark.scss': '<?php echo e(Vite::asset('resources/assets/vendor/scss'.$configData["rtlSupport"].'/theme-'.$name.'-dark.scss')); ?>',
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      }
      return resolvedPaths[path] || path;
    },
    'controls': <?php echo json_encode($configData['customizerControls']); ?>,
  });
</script>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/sections/scriptsIncludes.blade.php ENDPATH**/ ?>