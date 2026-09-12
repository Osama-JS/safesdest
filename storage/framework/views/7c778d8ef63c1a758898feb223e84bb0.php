<?php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;
    $containerNav = $configData['contentLayout'] === 'compact' ? 'container-xxl' : 'container-fluid';
    $navbarDetached = $navbarDetached ?? '';
?>

<!-- Navbar -->
<?php if(isset($navbarDetached) && $navbarDetached == 'navbar-detached'): ?>
    <nav class="layout-navbar <?php echo e($containerNav); ?> navbar navbar-expand-xl <?php echo e($navbarDetached); ?> align-items-center bg-navbar-theme"
        id="layout-navbar">
<?php endif; ?>
<?php if(isset($navbarDetached) && $navbarDetached == ''): ?>
    <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
        <div class="<?php echo e($containerNav); ?>">
<?php endif; ?>

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
<?php if(isset($navbarFull)): ?>
    <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
        <a href="<?php echo e(url('/')); ?>" class="app-brand-link">
            <span class="app-brand-loo demo" style="height: auto; width:auto"><?php echo $__env->make('_partials.macros', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
            <span class="app-brand-text demo menu-text fw-bold"><?php echo e(config('variables.templateName')); ?></span>
        </a>
        <?php if(isset($menuHorizontal)): ?>
            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
                <i class="ti ti-x ti-md align-middle"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- ! Not required for layout-without-menu -->
<?php if(!isset($navbarHideToggle)): ?>
    <div
        class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0<?php echo e(isset($menuHorizontal) ? ' d-xl-none ' : ''); ?> <?php echo e(isset($contentNavbar) ? ' d-xl-none ' : ''); ?>">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="ti ti-menu-2 ti-md"></i>
        </a>
    </div>
<?php endif; ?>

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">

    <?php if(!isset($menuHorizontal)): ?>
        <!-- Search -->
        
        <div id="navbar-custom-nav-container" class="d-none d-lg-flex align-items-center justify-content-between ">
            <?php echo $__env->yieldContent('navbar-custom-nav'); ?>
        </div>
    <?php endif; ?>

    <ul class="navbar-nav flex-row align-items-center ms-auto">
        <?php if(isset($menuHorizontal)): ?>
            <!-- Search -->
            <li class="nav-item navbar-search-wrapper">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill search-toggler"
                    href="javascript:void(0);">
                    <i class="ti ti-search ti-md"></i>
                </a>
            </li>
            <!-- /Search -->
        <?php endif; ?>

        <!-- Language -->
        <li class="nav-item dropdown-language dropdown">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class='ti ti-language rounded-circle ti-md'></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item <?php echo e(app()->getLocale() === 'en' ? 'active' : ''); ?>"
                        href="<?php echo e(url('lang/en')); ?>" data-language="en" data-text-direction="ltr">
                        <span>English</span>
                    </a>
                </li>
                
                <li>
                    <a class="dropdown-item <?php echo e(app()->getLocale() === 'ar' ? 'active' : ''); ?>"
                        href="<?php echo e(url('lang/ar')); ?>" data-language="ar" data-text-direction="rtl">
                        <span>Arabic</span>
                    </a>
                </li>
                
            </ul>
        </li>
        <!--/ Language -->

        <?php if($configData['hasCustomizer'] == true): ?>
            <!-- Style Switcher -->
            <li class="nav-item dropdown-style-switcher dropdown">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class='ti ti-md'></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                            <span class="align-middle"><i class='ti ti-sun ti-md me-3'></i>Light</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                            <span class="align-middle"><i class="ti ti-moon-stars ti-md me-3"></i>Dark</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                            <span class="align-middle"><i
                                    class="ti ti-device-desktop-analytics ti-md me-3"></i>System</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- / Style Switcher -->
        <?php endif; ?>

        <!-- Quick links  -->
        
        <!-- Quick links -->

        <!-- Notification -->
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view_notifications')): ?>
        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <span class="position-relative">
                    <i class="ti ti-bell ti-md"></i>
                    <span class="badge rounded-pill bg-danger badge-dot badge-notifications border" style="display: none;"></span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-0">
                <li class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <h6 class="mb-0 me-auto">Notification</h6>
                        <div class="d-flex align-items-center h6 mb-0">
                            <span class="badge bg-label-primary me-2">0 New</span>
                            <a href="javascript:void(0)"
                                class="btn btn-text-secondary rounded-pill btn-icon dropdown-notifications-all"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read"><i
                                    class="ti ti-mail-opened text-heading"></i></a>
                        </div>
                    </div>
                </li>
                <li class="dropdown-notifications-list scrollable-container">
                    <ul class="list-group list-group-flush">
                        <!-- Notifications will be loaded here via JS -->
                        <li class="list-group-item text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </li>
                    </ul>
                </li>
                
            </ul>
        </li>
        <?php endif; ?>
        <!--/ Notification -->

        <!-- Notes -->
        <?php if(Auth::check() && Auth::guard('web')->check()): ?>
            <li class="nav-item me-3 me-xl-2">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill" href="javascript:void(0);"
                    id="notes-toggle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="My Notes">
                    <i class="ti ti-notes ti-md"></i>
                </a>
            </li>
        <?php endif; ?>
        <!--/ Notes -->

        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                    <img src="<?php echo e(Auth::user()->image ? url(asset(Auth::user()->image)) : asset('assets/img/person.png')); ?>"
                        alt class="rounded-circle">
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item mt-0" href="<?php echo e(route('profile.show')); ?>">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-2">
                                <div class="avatar avatar-online">
                                    <img src="<?php echo e(Auth::user()->image ? url(asset(Auth::user()->image)) : asset('assets/img/person.png')); ?>"
                                        alt class="rounded-circle">
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">
                                    <?php if(Auth::check()): ?>
                                        <?php echo e(Auth::user()->name); ?>

                                    <?php else: ?>
                                        John Doe
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted">
                                    <?php if(Auth::check()): ?>
                                        <?php echo e(Auth::user()->role?->name); ?>

                                    <?php else: ?>
                                        John Doe
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo e(url('/profile')); ?>">
                        <i class="ti ti-user me-3 ti-md"></i><span class="align-middle">My Profile</span>
                    </a>
                </li>

                <?php if(Auth::check() && Laravel\Jetstream\Jetstream::hasApiFeatures()): ?>
                    <li>
                        <a class="dropdown-item" href="<?php echo e(route('api-tokens.index')); ?>">
                            <i class="ti ti-key ti-md me-3"></i><span class="align-middle">API Tokens</span>
                        </a>
                    </li>
                <?php endif; ?>
                

                <?php if(Auth::User() && Laravel\Jetstream\Jetstream::hasTeamFeatures()): ?>
                    <li>
                        <div class="dropdown-divider my-1 mx-n2"></div>
                    </li>
                    <li>
                        <h6 class="dropdown-header">Manage Team</h6>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1 mx-n2"></div>
                    </li>
                    <li>
                        <a class="dropdown-item"
                            href="<?php echo e(Auth::user() ? route('teams.show', Auth::user()->currentTeam->id) : 'javascript:void(0)'); ?>">
                            <i class="ti ti-settings ti-md me-3"></i><span class="align-middle">Team Settings</span>
                        </a>
                    </li>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', Laravel\Jetstream\Jetstream::newTeamModel())): ?>
                        <li>
                            <a class="dropdown-item" href="<?php echo e(route('teams.create')); ?>">
                                <i class="ti ti-user ti-md me-3"></i><span class="align-middle">Create New Team</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if(Auth::user()->allTeams()->count() > 1): ?>
                        <li>
                            <div class="dropdown-divider my-1 mx-n2"></div>
                        </li>
                        <li>
                            <h6 class="dropdown-header">Switch Teams</h6>
                        </li>
                        <li>
                            <div class="dropdown-divider my-1 mx-n2"></div>
                        </li>
                    <?php endif; ?>

                    <?php if(Auth::user()): ?>
                        <?php $__currentLoopData = Auth::user()->allTeams(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            

                            <?php if (isset($component)) { $__componentOriginal12b9baaa9d085739b53a541d2c8778fa = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal12b9baaa9d085739b53a541d2c8778fa = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.switchable-team','data' => ['team' => $team]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('switchable-team'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['team' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($team)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal12b9baaa9d085739b53a541d2c8778fa)): ?>
<?php $attributes = $__attributesOriginal12b9baaa9d085739b53a541d2c8778fa; ?>
<?php unset($__attributesOriginal12b9baaa9d085739b53a541d2c8778fa); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal12b9baaa9d085739b53a541d2c8778fa)): ?>
<?php $component = $__componentOriginal12b9baaa9d085739b53a541d2c8778fa; ?>
<?php unset($__componentOriginal12b9baaa9d085739b53a541d2c8778fa); ?>
<?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                <?php endif; ?>
                <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <?php if(Auth::check()): ?>
                    <li>
                        <div class="d-grid px-2 pt-2 pb-1">
                            <a class="btn btn-sm btn-danger d-flex" href="<?php echo e(route('custom.logout')); ?>"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <small class="align-middle">Logout</small>
                                <i class="ti ti-logout ms-2 ti-14px"></i>
                            </a>
                        </div>
                    </li>
                    <form method="POST" id="logout-form" action="<?php echo e(route('custom.logout')); ?>">
                        <?php echo csrf_field(); ?>
                    </form>
                <?php else: ?>
                    <li>
                        <div class="d-grid px-2 pt-2 pb-1">
                            <a class="btn btn-sm btn-danger d-flex"
                                href="<?php echo e(Route::has('login') ? route('login') : url('auth/login-basic')); ?>">
                                <small class="align-middle">Login</small>
                                <i class="ti ti-login ms-2 ti-14px"></i>
                            </a>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </li>
        <!--/ User -->
    </ul>
</div>

<!-- Search Small Screens -->

<!--/ Search Small Screens -->
<?php if(isset($navbarDetached) && $navbarDetached == ''): ?>
    </div>
<?php endif; ?>
</nav>
<!-- / Navbar -->
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/sections/navbar/navbar.blade.php ENDPATH**/ ?>