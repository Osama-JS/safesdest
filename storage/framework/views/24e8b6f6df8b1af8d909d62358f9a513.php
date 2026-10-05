<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo $__env->yieldContent('title'); ?></title>


    <link type="text/css" rel="stylesheet" href="<?php echo e(url(asset('/assets/errors/css/style.css'))); ?>" />


</head>

<body>

    <div id="notfound">
        <div class="notfound">
            <div class="notfound-404"></div>
            <h1><?php echo $__env->yieldContent('code'); ?></h1>
            <h2> <?php echo $__env->yieldContent('message'); ?></h2>
            <p><?php echo $__env->yieldContent('desc'); ?></p>
            <?php echo $__env->yieldContent('content'); ?>
            <a href="<?php echo e(url('/')); ?>">Go To Home Page</a>
        </div>
    </div>

</body>

</html>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/errors/minimal.blade.php ENDPATH**/ ?>