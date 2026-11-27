<?php
    use Illuminate\Support\Facades\Route;
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
    $configData = Helper::appClasses();
?>



<?php $__env->startSection('title', 'Login'); ?>

<?php $__env->startSection('page-style'); ?>
    <!-- Page -->
    <?php echo app('Illuminate\Foundation\Vite')('resources/assets/vendor/scss/pages/page-auth.scss'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Language -->
    <div class="nav-item dropdown-language dropdown " style="position: fixed; z-index: 1000;bottom: 0;">

        <a class="nav-link btn btn-text-secondary   dropdown-toggle hide-arrow "
            style="margin: 20px;
                      background: white;
                      padding: 10px 20px;
                      border-radius: 10px;"
            href="javascript:void(0);" data-bs-toggle="dropdown">
            <i class='ti ti-language rounded-circle ti-md'></i>
            <?php echo e(app()->getLocale() === 'en' ? 'English' : 'عربي'); ?>

        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item <?php echo e(app()->getLocale() === 'en' ? 'active' : ''); ?>" href="<?php echo e(url('lang/en')); ?>"
                    data-language="en" data-text-direction="ltr">
                    <span>English</span>
                </a>
            </li>

            <li>
                <a class="dropdown-item <?php echo e(app()->getLocale() === 'ar' ? 'active' : ''); ?>" href="<?php echo e(url('lang/ar')); ?>"
                    data-language="ar" data-text-direction="rtl">
                    <span>Arabic</span>
                </a>
            </li>

        </ul>

    </div>
    <!--/ Language -->
    <div class="authentication-wrapper authentication-cover">
        <!-- Logo -->
        <a href="<?php echo e(url('/')); ?>" class="app-brand auth-cover-brand">
            <span class="app-brand-logo demo"><?php echo $__env->make('_partials.macros', ['height' => 20, 'withbg' => 'fill: #fff;'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
            <span class="app-brand-text demo text-heading fw-bold"><?php echo e(config('variables.templateName')); ?></span>
        </a>
        <!-- /Logo -->
        <div class="authentication-inner row m-0">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-8 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
                    <img src="<?php echo e(asset('assets/img/illustrations/auth-login-illustration-' . $configData['style'] . '.png')); ?>"
                        alt="auth-login-cover" class="my-5 auth-illustration"
                        data-app-light-img="illustrations/auth-login-illustration-light.png"
                        data-app-dark-img="illustrations/auth-login-illustration-dark.png">

                    <img src="<?php echo e(asset('assets/img/illustrations/bg-shape-image-' . $configData['style'] . '.png')); ?>"
                        alt="auth-login-cover" class="platform-bg"
                        data-app-light-img="illustrations/bg-shape-image-light.png"
                        data-app-dark-img="illustrations/bg-shape-image-dark.png">
                </div>
            </div>
            <!-- /Left Text -->

            <!-- Login -->
            <div class="d-flex col-12 col-lg-4 align-items-center authentication-bg p-sm-12 p-6">
                <div class="w-px-400 mx-auto mt-12 pt-5">
                    <h4 class="mb-1"><?php echo e(__('Welcome to')); ?> <?php echo e(config('variables.templateName')); ?>! 👋</h4>
                    <p class="mb-6"><?php echo e(__('sign in to start manage the tasks')); ?></p>

                    <?php if(session('status')): ?>
                        <div class="alert alert-success mb-1 rounded-0" role="alert">
                            <div class="alert-body">
                                <?php echo e(session('status')); ?>

                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="formAuthentication" class="mb-6" action="<?php echo e(route('login')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-6">
                            <label for="login-email" class="form-label"><?php echo e(__('Email')); ?></label>
                            <input type="text" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="login-email"
                                name="email" placeholder="john@example.com" autofocus>
                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <span class="fw-medium"><?php echo e($message); ?></span>
                                </span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="mb-6 form-password-toggle">
                            <label class="form-label" for="login-password"><?php echo e(__('Password')); ?></label>
                            <div class="input-group input-group-merge <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <input type="password" id="login-password"
                                    class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password" />
                                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                            </div>
                            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <span class="fw-medium"><?php echo e($message); ?></span>
                                </span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        

                        <div class="mb-4">
                            <?php $__errorArgs = ['captcha'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    <span><?php echo e($message); ?></span>
                                </div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <div class="captcha-section">
                                <label class="form-label mb-3">
                                    <i class="ti ti-shield-check me-2"></i><?php echo e(__('Enter the code in the image')); ?>

                                </label>
                                <div class="captcha-container d-flex align-items-center gap-3 mb-3">
                                    <img src="<?php echo e(captcha_src()); ?>" alt="captcha" id="captcha-image"
                                        style="height: 60px; border-radius: 8px; border: 2px solid #e9ecef;">
                                    <button type="button" class="btn btn-outline-secondary btn-refresh"
                                        onclick="refreshCaptcha()">
                                        <i class="ti ti-refresh"></i>
                                    </button>
                                </div>
                                <input type="text" class="form-control <?php $__errorArgs = ['captcha'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    name="captcha" placeholder="Enter captcha code" required>
                                <?php $__errorArgs = ['captcha'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback">
                                        <i class="ti ti-alert-circle me-1"></i><?php echo e($message); ?>

                                    </div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <button class="btn btn-primary d-grid w-100" type="submit"><?php echo e(__('Sign In')); ?></button>
                    </form>

                </div>
            </div>
            <!-- /Login -->
        </div>
    </div>

    <?php echo htmlScriptTagJsApi(); ?>


    <script>
        function refreshCaptcha() {
            const captchaImage = document.getElementById('captcha-image');
            if (captchaImage) {
                captchaImage.src = '<?php echo e(captcha_src()); ?>?' + Math.random();
            }
        }
    </script>

    



<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/blankLayout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/auth/login.blade.php ENDPATH**/ ?>