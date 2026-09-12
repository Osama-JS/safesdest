<?php
    use Illuminate\Support\Facades\Route;
    $configData = Helper::appClasses();

    // استخدام البيانات المرسلة من MenuServiceProvider
    $menuSet = isset($menuData[0]) && isset($menuData[0]->menu) ? $menuData[0]->menu : [];

?>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- ! Hide app brand if navbar-full -->
    <?php if(!isset($navbarFull)): ?>
        <div class="app-brand demo">
            <a href="<?php echo e(url('/')); ?>" class="app-brand-link">
                <span class="app-brand-logo demo"><?php echo $__env->make('_partials.macros', ['height' => 20], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
                <span class="app-brand-text demo menu-text fw-bold"><?php echo e(config('variables.templateName')); ?></span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="ti menu-toggle-icon d-none d-xl-block align-middle"></i>
                <i class="ti ti-x d-block d-xl-none ti-md align-middle"></i>
            </a>
        </div>
    <?php endif; ?>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <?php $__currentLoopData = $menuSet; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            

            <?php if(isset($menu->broker)): ?>
                <?php if(!auth()->user()->is_customs_clearance_agent): ?>
                    <?php continue; ?>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if(isset($menu->menuHeader)): ?>
                <li class="menu-header small">
                    <span class="menu-header-text"><?php echo e(__($menu->menuHeader)); ?>

                    </span>
                </li>
            <?php else: ?>
                
                <?php
                    $activeClass = null;
                    $currentRouteName = Route::currentRouteName();

                    if ($currentRouteName === $menu->slug) {
                        $activeClass = 'active';
                    } elseif (isset($menu->submenu)) {
                        if (gettype($menu->slug) === 'array') {
                            foreach ($menu->slug as $slug) {
                                if (str_contains($currentRouteName, $slug) and strpos($currentRouteName, $slug) === 0) {
                                    $activeClass = 'active open';
                                }
                            }
                        } else {
                            if (
                                str_contains($currentRouteName, $menu->slug) and
                                strpos($currentRouteName, $menu->slug) === 0
                            ) {
                                $activeClass = 'active open';
                            }
                        }
                    }
                ?>

                
                <?php
                    if (auth()->guard('web')->check() && auth()->user()->investor) {
                        if (isset($menu->slug) && $menu->slug === 'investor.task-payment') {
                            $contract = auth()->user()->activeInvestmentContract;
                            if (!$contract || $contract->contract_type !== 'task_investment') {
                                continue;
                            }
                        }
                    }
                ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($menu->permission ?? null)): ?>
                    <li class="menu-item <?php echo e($activeClass); ?> <?php echo $__env->yieldContent($menu->isactive ?? ''); ?>">
                        <a href="<?php echo e(isset($menu->url) ? url($menu->url) : 'javascript:void(0);'); ?>"
                            class="<?php echo e(isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link'); ?>"
                            <?php if(isset($menu->target) and !empty($menu->target)): ?> target="_blank" <?php endif; ?>>
                            <?php if(isset($menu->icon)): ?>
                                <i class="<?php echo e($menu->icon); ?>"></i>
                            <?php endif; ?>
                            <div><?php echo e(isset($menu->name) ? __($menu->name) : ''); ?></div>
                            <?php if(isset($menu->badge)): ?>
                                <div class="badge bg-<?php echo e($menu->badge[0]); ?> rounded-pill ms-auto"><?php echo e($menu->badge[1]); ?></div>
                            <?php endif; ?>
                        </a>

                        
                        <?php if(isset($menu->submenu)): ?>
                            <?php echo $__env->make('layouts.sections.menu.submenu', ['menu' => $menu->submenu], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>

</aside>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/sections/menu/verticalMenu.blade.php ENDPATH**/ ?>