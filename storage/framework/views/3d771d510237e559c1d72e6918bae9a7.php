<!-- BEGIN: Vendor JS-->



<?php echo app('Illuminate\Foundation\Vite')(['resources/js/translator.js', 'resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/popper/popper.js', 'resources/assets/vendor/js/bootstrap.js', 'resources/assets/vendor/libs/node-waves/node-waves.js', 'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'resources/assets/vendor/libs/hammer/hammer.js', 'resources/assets/vendor/libs/typeahead-js/typeahead.js', 'resources/assets/vendor/js/menu.js', 'resources/assets/vendor/libs/block-ui/block-ui.js', 'resources/assets/vendor/libs/toastr/toastr.js']); ?>

<!-- Notes JS -->

<?php echo app('Illuminate\Foundation\Vite')(['resources/js/notes.js']); ?>


<?php echo $__env->yieldContent('vendor-script'); ?>
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/main.js']); ?>

<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
<?php echo $__env->yieldPushContent('pricing-script'); ?>
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
<?php echo $__env->yieldContent('page-script'); ?>
<!-- END: Page JS-->

<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view_notifications')): ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/notifications.js']); ?>
<?php endif; ?>

<script>
    async function subscribeForPush() {
        const register = await navigator.serviceWorker.register("<?php echo e(url('/sw.js')); ?>");
        const vapidKey = "<?php echo e(env('VAPID_PUBLIC_KEY')); ?>";
        const convertedVapidKey = urlBase64ToUint8Array(vapidKey);

        const subscription = await register.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: convertedVapidKey
        });

        await fetch("<?php echo e(route('notifications.subscribe')); ?>", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
            },
            body: JSON.stringify(subscription)
        });

        console.log('تم الاشتراك في الإشعارات بنجاح');
        // alert('تم الاشتراك في الإشعارات بنجاح');
    }

    if ('serviceWorker' in navigator && 'PushManager' in window) {
        subscribeForPush();
    } else {
        console.warn('Push messaging غير مدعوم');
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');

        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
    }
</script>

<?php echo $__env->yieldPushContent('modals'); ?>
<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/sections/scripts.blade.php ENDPATH**/ ?>