<?php if($paginator->hasPages()): ?>
<?php
    $currentPage = $paginator->currentPage();
    $lastPage    = $paginator->lastPage();
    $perPage     = $paginator->perPage();
    $total       = $paginator->total();
    $from        = $paginator->firstItem() ?? 0;
    $to          = $paginator->lastItem() ?? 0;
?>

<div class="investor-pagination-wrapper d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 py-3 px-1">

    
    <div class="pagination-info d-flex align-items-center gap-2">
        <div class="pagination-info-icon">
            <i class="ti ti-database text-muted"></i>
        </div>
        <span class="text-muted small">
            <?php echo e(__('Showing')); ?>

            <strong class="text-body"><?php echo e(number_format($from)); ?></strong>
            <?php echo e(__('to')); ?>

            <strong class="text-body"><?php echo e(number_format($to)); ?></strong>
            <?php echo e(__('of')); ?>

            <strong class="text-body"><?php echo e(number_format($total)); ?></strong>
            <?php echo e(__('records')); ?>

        </span>
    </div>

    
    <nav aria-label="<?php echo e(__('Pagination Navigation')); ?>">
        <ul class="investor-pagination mb-0 d-flex align-items-center gap-1">

            
            <?php if($currentPage > 2): ?>
                <li class="page-item-investor">
                    <a href="<?php echo e($paginator->url(1)); ?>" class="page-btn page-btn-nav" title="<?php echo e(__('First page')); ?>">
                        <i class="ti ti-chevrons-right"></i>
                    </a>
                </li>
            <?php endif; ?>

            
            <?php if($paginator->onFirstPage()): ?>
                <li class="page-item-investor disabled">
                    <span class="page-btn page-btn-nav">
                        <i class="ti ti-chevron-right"></i>
                    </span>
                </li>
            <?php else: ?>
                <li class="page-item-investor">
                    <a href="<?php echo e($paginator->previousPageUrl()); ?>" class="page-btn page-btn-nav" rel="prev">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            <?php endif; ?>

            
            <?php
                $start = max(1, $currentPage - 2);
                $end   = min($lastPage, $currentPage + 2);
                // ضبط النطاق ليكون دائماً 5 عناصر إن أمكن
                if ($end - $start < 4) {
                    if ($start === 1) $end = min($lastPage, $start + 4);
                    else $start = max(1, $end - 4);
                }
            ?>

            <?php if($start > 1): ?>
                <li class="page-item-investor">
                    <a href="<?php echo e($paginator->url(1)); ?>" class="page-btn">1</a>
                </li>
                <?php if($start > 2): ?>
                    <li class="page-item-investor disabled">
                        <span class="page-btn page-btn-dots">…</span>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for($page = $start; $page <= $end; $page++): ?>
                <li class="page-item-investor <?php echo e($page == $currentPage ? 'active' : ''); ?>">
                    <?php if($page == $currentPage): ?>
                        <span class="page-btn page-btn-active"><?php echo e($page); ?></span>
                    <?php else: ?>
                        <a href="<?php echo e($paginator->url($page)); ?>" class="page-btn"><?php echo e($page); ?></a>
                    <?php endif; ?>
                </li>
            <?php endfor; ?>

            <?php if($end < $lastPage): ?>
                <?php if($end < $lastPage - 1): ?>
                    <li class="page-item-investor disabled">
                        <span class="page-btn page-btn-dots">…</span>
                    </li>
                <?php endif; ?>
                <li class="page-item-investor">
                    <a href="<?php echo e($paginator->url($lastPage)); ?>" class="page-btn"><?php echo e($lastPage); ?></a>
                </li>
            <?php endif; ?>

            
            <?php if($paginator->hasMorePages()): ?>
                <li class="page-item-investor">
                    <a href="<?php echo e($paginator->nextPageUrl()); ?>" class="page-btn page-btn-nav" rel="next">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item-investor disabled">
                    <span class="page-btn page-btn-nav">
                        <i class="ti ti-chevron-left"></i>
                    </span>
                </li>
            <?php endif; ?>

            
            <?php if($currentPage < $lastPage - 1): ?>
                <li class="page-item-investor">
                    <a href="<?php echo e($paginator->url($lastPage)); ?>" class="page-btn page-btn-nav" title="<?php echo e(__('Last page')); ?>">
                        <i class="ti ti-chevrons-left"></i>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

</div>

<style>
/* ════════════════════════════════════════
   Investor Premium Pagination
════════════════════════════════════════ */
.investor-pagination-wrapper {
    border-top: 1px solid rgba(0,0,0,.06);
}

.investor-pagination {
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 4px !important;
}

.page-item-investor {
    display: inline-flex;
}

.page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 8px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6c757d;
    background: transparent;
    border: 1.5px solid transparent;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    user-select: none;
    line-height: 1;
}

.page-btn:hover {
    color: #696cff;
    background: rgba(105, 108, 255, 0.08);
    border-color: rgba(105, 108, 255, 0.2);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(105, 108, 255, 0.15);
}

.page-btn-active {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 8px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 700;
    color: #fff !important;
    background: linear-gradient(135deg, #696cff 0%, #8a8fff 100%);
    border: 1.5px solid transparent;
    box-shadow: 0 4px 12px rgba(105, 108, 255, 0.4);
    line-height: 1;
    cursor: default;
}

.page-btn-nav {
    color: #6c757d;
    background: rgba(0,0,0,.03);
    border-color: rgba(0,0,0,.08);
    font-size: 0.8rem;
}

.page-btn-nav:hover {
    color: #696cff;
    background: rgba(105, 108, 255, 0.08);
    border-color: rgba(105, 108, 255, 0.2);
}

.page-item-investor.disabled .page-btn,
.page-item-investor.disabled .page-btn-nav {
    color: #ccc !important;
    background: rgba(0,0,0,.02) !important;
    border-color: transparent !important;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

.page-btn-dots {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    color: #adb5bd;
    font-size: 0.875rem;
    letter-spacing: 2px;
    cursor: default;
}

.pagination-info {
    font-size: 0.82rem;
}

/* Dark mode */
[data-bs-theme="dark"] .page-btn {
    color: #adb5bd;
}
[data-bs-theme="dark"] .page-btn-nav {
    background: rgba(255,255,255,.05);
    border-color: rgba(255,255,255,.08);
}
[data-bs-theme="dark"] .page-btn:hover {
    background: rgba(105,108,255,.15);
}
[data-bs-theme="dark"] .investor-pagination-wrapper {
    border-top-color: rgba(255,255,255,.08);
}
[data-bs-theme="dark"] .page-item-investor.disabled .page-btn {
    background: rgba(255,255,255,.03) !important;
}

/* RTL support */
[dir="rtl"] .ti-chevron-right::before { content: "\ea64"; }
[dir="rtl"] .ti-chevron-left::before  { content: "\ea61"; }
[dir="rtl"] .ti-chevrons-right::before { content: "\ea66"; }
[dir="rtl"] .ti-chevrons-left::before  { content: "\ea63"; }
</style>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/investor/partials/pagination.blade.php ENDPATH**/ ?>