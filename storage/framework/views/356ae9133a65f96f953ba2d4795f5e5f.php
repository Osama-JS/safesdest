<!DOCTYPE html>
<?php
    use Illuminate\Support\Facades\App;
    use Illuminate\Support\Facades\File;

    $menuFixed =
        $configData['layout'] === 'vertical'
            ? $menuFixed ?? ''
            : ($configData['layout'] === 'front'
                ? ''
                : $configData['headerType']);
    $navbarType =
        $configData['layout'] === 'vertical'
            ? $configData['navbarType'] ?? ''
            : ($configData['layout'] === 'front'
                ? 'layout-navbar-fixed'
                : '');
    $isFront = ($isFront ?? '') == true ? 'Front' : '';
    $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';

    $locale = App::getLocale();
    $langFile = base_path("lang/{$locale}.json");
    $translationsA = [];

    if (File::exists($langFile)) {
        $translationsA = json_decode(File::get($langFile), true);
    }
    $translationsB = json_encode($translationsA);

?>

<html lang="<?php echo e(session()->get('locale') ?? app()->getLocale()); ?>"
    class="<?php echo e($configData['style']); ?>-style <?php echo e($contentLayout ?? ''); ?> <?php echo e($navbarType ?? ''); ?> <?php echo e($menuFixed ?? ''); ?> <?php echo e($menuCollapsed ?? ''); ?> <?php echo e($menuFlipped ?? ''); ?> <?php echo e($menuOffcanvas ?? ''); ?> <?php echo e($footerFixed ?? ''); ?> <?php echo e($customizerHidden ?? ''); ?>"
    dir="<?php echo e($configData['textDirection']); ?>" data-theme="<?php echo e($configData['theme']); ?>"
    data-assets-path="<?php echo e(asset('/assets') . '/'); ?>" data-base-url="<?php echo e(url('/')); ?>" data-framework="laravel"
    data-template="<?php echo e($configData['layout'] . '-menu-' . $configData['themeOpt'] . '-' . $configData['styleOpt']); ?>"
    data-style="<?php echo e($configData['styleOptVal']); ?>">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title><?php echo $__env->yieldContent('title'); ?> |
        <?php echo e(config('variables.templateName') ? config('variables.templateName') : 'TemplateName'); ?> -
        <?php echo e(config('variables.templateSuffix') ? config('variables.templateSuffix') : 'TemplateSuffix'); ?>

    </title>
    <meta name="description"
        content="<?php echo e(config('variables.templateDescription') ? config('variables.templateDescription') : ''); ?>" />
    <meta name="keywords"
        content="<?php echo e(config('variables.templateKeyword') ? config('variables.templateKeyword') : ''); ?>">
    <!-- laravel CRUD token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="user-name" content="<?php echo e(auth()->check() ? auth()->user()->name : ''); ?>">
    <!-- Canonical SEO -->
    <link rel="canonical" href="<?php echo e(config('variables.productPage') ? config('variables.productPage') : ''); ?>">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('assets/img/favicon/favicon.ico')); ?>" />

    <script>
        window.translations = <?php echo $translationsB; ?>;
    </script>

    <!-- Include Styles -->
    <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
    <?php echo $__env->make('layouts/sections/styles' . $isFront, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Include Scripts for customizer, helper, analytics, config -->
    <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
    <?php echo $__env->make('layouts/sections/scriptsIncludes' . $isFront, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</head>

<body>

    <!-- Layout Content -->
    <?php echo $__env->yieldContent('layoutContent'); ?>
    <!--/ Layout Content -->



    <!-- Include Scripts -->
    <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
    <?php echo $__env->make('layouts/sections/scripts' . $isFront, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</body>

</html>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/commonMaster.blade.php ENDPATH**/ ?>