@if ($paginator->hasPages())
@php
    $currentPage = $paginator->currentPage();
    $lastPage    = $paginator->lastPage();
    $perPage     = $paginator->perPage();
    $total       = $paginator->total();
    $from        = $paginator->firstItem() ?? 0;
    $to          = $paginator->lastItem() ?? 0;
@endphp

<div class="investor-pagination-wrapper d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 py-3 px-1">

    {{-- معلومات السجلات --}}
    <div class="pagination-info d-flex align-items-center gap-2">
        <div class="pagination-info-icon">
            <i class="ti ti-database text-muted"></i>
        </div>
        <span class="text-muted small">
            {{ __('Showing') }}
            <strong class="text-body">{{ number_format($from) }}</strong>
            {{ __('to') }}
            <strong class="text-body">{{ number_format($to) }}</strong>
            {{ __('of') }}
            <strong class="text-body">{{ number_format($total) }}</strong>
            {{ __('records') }}
        </span>
    </div>

    {{-- أزرار التنقل --}}
    <nav aria-label="{{ __('Pagination') }}">
        <ul class="investor-pagination mb-0 d-flex align-items-center gap-1">

            {{-- زر الصفحة الأولى --}}
            @if ($currentPage > 2)
                <li class="page-item-investor">
                    <a href="{{ $paginator->url(1) }}" class="page-btn page-btn-nav" title="{{ __('First page') }}">
                        <i class="ti ti-chevrons-right"></i>
                    </a>
                </li>
            @endif

            {{-- زر السابق --}}
            @if ($paginator->onFirstPage())
                <li class="page-item-investor disabled">
                    <span class="page-btn page-btn-nav">
                        <i class="ti ti-chevron-right"></i>
                    </span>
                </li>
            @else
                <li class="page-item-investor">
                    <a href="{{ $paginator->previousPageUrl() }}" class="page-btn page-btn-nav" rel="prev">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            @endif

            {{-- الصفحات --}}
            @php
                $start = max(1, $currentPage - 2);
                $end   = min($lastPage, $currentPage + 2);
                // ضبط النطاق ليكون دائماً 5 عناصر إن أمكن
                if ($end - $start < 4) {
                    if ($start === 1) $end = min($lastPage, $start + 4);
                    else $start = max(1, $end - 4);
                }
            @endphp

            @if ($start > 1)
                <li class="page-item-investor">
                    <a href="{{ $paginator->url(1) }}" class="page-btn">1</a>
                </li>
                @if ($start > 2)
                    <li class="page-item-investor disabled">
                        <span class="page-btn page-btn-dots">…</span>
                    </li>
                @endif
            @endif

            @for ($page = $start; $page <= $end; $page++)
                <li class="page-item-investor {{ $page == $currentPage ? 'active' : '' }}">
                    @if ($page == $currentPage)
                        <span class="page-btn page-btn-active">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="page-btn">{{ $page }}</a>
                    @endif
                </li>
            @endfor

            @if ($end < $lastPage)
                @if ($end < $lastPage - 1)
                    <li class="page-item-investor disabled">
                        <span class="page-btn page-btn-dots">…</span>
                    </li>
                @endif
                <li class="page-item-investor">
                    <a href="{{ $paginator->url($lastPage) }}" class="page-btn">{{ $lastPage }}</a>
                </li>
            @endif

            {{-- زر التالي --}}
            @if ($paginator->hasMorePages())
                <li class="page-item-investor">
                    <a href="{{ $paginator->nextPageUrl() }}" class="page-btn page-btn-nav" rel="next">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            @else
                <li class="page-item-investor disabled">
                    <span class="page-btn page-btn-nav">
                        <i class="ti ti-chevron-left"></i>
                    </span>
                </li>
            @endif

            {{-- زر الصفحة الأخيرة --}}
            @if ($currentPage < $lastPage - 1)
                <li class="page-item-investor">
                    <a href="{{ $paginator->url($lastPage) }}" class="page-btn page-btn-nav" title="{{ __('Last page') }}">
                        <i class="ti ti-chevrons-left"></i>
                    </a>
                </li>
            @endif
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
@endif
