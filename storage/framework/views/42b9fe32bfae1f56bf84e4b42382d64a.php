<?php
    $containerFooter =
        isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
            ? 'container-xxl'
            : 'container-fluid';
?>

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
    <div class="<?php echo e($containerFooter); ?>">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="text-body">
                ©
                <script>
                    document.write(new Date().getFullYear())
                </script>, made by <a
                    href="<?php echo e(!empty(config('variables.creatorUrl')) ? config('variables.creatorUrl') : ''); ?>"
                    target="_blank"
                    class="footer-link"><?php echo e(!empty(config('variables.creatorName')) ? config('variables.creatorName') : ''); ?></a>
            </div>
            <div class="d-none d-lg-inline-block">

            </div>
        </div>
    </div>
</footer>
<!--/ Footer-->
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/sections/footer/footer.blade.php ENDPATH**/ ?>