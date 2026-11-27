<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap"
    rel="stylesheet">


<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/fonts/tabler-icons.scss', 'resources/assets/vendor/fonts/fontawesome.scss', 'resources/assets/vendor/fonts/flag-icons.scss', 'resources/assets/vendor/libs/node-waves/node-waves.scss']); ?>
<!-- Core CSS -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/scss' . $configData['rtlSupport'] . '/core' . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss', 'resources/assets/vendor/scss' . $configData['rtlSupport'] . '/' . $configData['theme'] . ($configData['style'] !== 'light' ? '-' . $configData['style'] : '') . '.scss', 'resources/assets/css/demo.css']); ?>


<!-- Vendor Styles -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss', 'resources/assets/vendor/libs/typeahead-js/typeahead.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss', 'resources/assets/vendor/libs/toastr/toastr.scss']); ?>
<?php echo $__env->yieldContent('vendor-style'); ?>

<!-- Page Styles -->
<?php echo $__env->yieldContent('page-style'); ?>

<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/sections/styles.blade.php ENDPATH**/ ?>