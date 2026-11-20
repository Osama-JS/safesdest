<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="border: 1px solid #e0e7ff;">
        <thead style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff;">
            <tr>
                <th style="width: 70px; color: #fff;" class="py-3">#</th>
                <th style="color: #fff;" class="py-3">{{ __('التاريخ والوقت') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('النوع') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('المبلغ (ر.س)') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('الوصف والملاحظات') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('رقم المهمة') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('الرصيد بعد العملية') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $trx)
                @php
                    $balanceAfter = $runningBalances[$trx->id] ?? (float) ($trx->balance_after ?? 0);
                @endphp
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td>
                        <span class="fw-bold text-primary font-monospace small">#{{ $trx->id }}</span>
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-dark">{{ $trx->created_at->format('Y-m-d') }}</span>
                            <small class="text-primary fw-semibold">{{ $trx->created_at->format('H:i') }}</small>
                        </div>
                    </td>
                    <td>
                        @if($trx->transaction_type === 'credit')
                            <span class="badge bg-label-success">
                                <i class="ti ti-arrow-down-left me-1"></i> {{ __('إحتساب') }}
                            </span>
                        @else
                            <span class="badge bg-label-danger">
                                <i class="ti ti-arrow-up-right me-1"></i> {{ __('سحب') }}
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($trx->transaction_type === 'credit')
                            <span class="fw-bold text-success fs-6">+{{ number_format($trx->amount, 2) }}</span>
                        @else
                            <span class="fw-bold text-danger fs-6">-{{ number_format($trx->amount, 2) }}</span>
                        @endif
                        <small class="text-dark fw-bold">ر.س</small>
                    </td>
                    <td>
                        <span class="small fw-semibold text-dark">{{ $trx->description ?: '—' }}</span>
                    </td>
                    <td>
                        @if($trx->task_id)
                            @if(auth()->user()->can('show_tasks'))
                                <a href="{{ route('tasks.list.show', $trx->task_id) }}" class="badge bg-label-info font-monospace text-decoration-none" target="_blank" title="{{ __('عرض تفاصيل المهمة') }}">
                                    <i class="ti ti-truck-delivery me-1"></i>#{{ $trx->task_id }}
                                </a>
                            @else
                                <span class="badge bg-label-info font-monospace">
                                    <i class="ti ti-truck-delivery me-1"></i>#{{ $trx->task_id }}
                                </span>
                            @endif
                        @else
                            <span class="text-dark small">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="fw-bold text-primary font-monospace fs-6">{{ number_format($balanceAfter, 2) }}</span>
                        <small class="text-dark fw-bold">ر.س</small>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="ti ti-receipt-off text-primary mb-2" style="font-size: 3.2rem;"></i>
                            <h6 class="text-dark fw-bold mb-1">{{ __('لا توجد حركات مالية مسجلة مطابقة للبحث') }}</h6>
                            <p class="small text-dark mb-0">{{ __('جرّب تعديل خيارات التصفية أو البحث لعرض النتائج.') }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- الترقيم --}}
@if($transactions->hasPages())
    <div class="d-flex justify-content-between align-items-center p-3 border-top flex-wrap gap-2" style="border-color: #e0e7ff !important; background: #fafafa;">
        <small class="text-dark fw-bold">
            {{ __('عرض :from إلى :to من أصل :total حركة مالية', [
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
                'total' => $transactions->total()
            ]) }}
        </small>
        <div class="transactions-pagination">
            {{ $transactions->links() }}
        </div>
    </div>
@endif
