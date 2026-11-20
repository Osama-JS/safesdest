<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="border: 1px solid #e0e7ff;">
        <thead style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff;">
            <tr>
                <th style="color: #fff;" class="py-3">{{ __('رقم المرجع') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('المبلغ المطلوب') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('الحساب البنكي المحول إليه') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('تاريخ الطلب') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('الحالة') }}</th>
                <th style="color: #fff;" class="py-3">{{ __('ملاحظات / سبب الرفض') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payoutRequests as $payout)
                @php
                    $details = $payout->transaction_details ?? [];
                    $iban = $details['iban'] ?? ($user->iban_number ?? '—');
                    $bankName = $details['bank_name'] ?? ($user->bank_name ?? '—');
                    $beneficiary = $details['beneficiary_name'] ?? ($user->beneficiary_name ?? '—');
                @endphp
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td>
                        <span class="font-monospace fw-bold text-primary">#{{ $payout->reference_id }}</span>
                    </td>
                    <td>
                        <span class="fw-bold text-success fs-6">{{ number_format($payout->amount, 2) }}</span>
                        <small class="text-dark fw-bold">ر.س</small>
                    </td>
                    <td>
                        <div class="d-flex flex-column small">
                            <span class="fw-bold text-dark">{{ $beneficiary }}</span>
                            <span class="font-monospace text-primary fw-semibold" dir="ltr">{{ $iban }}</span>
                            <span class="text-info fw-semibold">{{ $bankName }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-dark">{{ $payout->created_at->format('Y-m-d') }}</span>
                            <small class="text-primary fw-semibold">{{ $payout->created_at->format('H:i') }}</small>
                        </div>
                    </td>
                    <td>
                        {!! $payout->status_badge !!}
                    </td>
                    <td>
                        @if($payout->status === 'rejected' && $payout->rejection_reason)
                            <span class="badge bg-label-danger text-wrap" style="max-width: 250px;">
                                <i class="ti ti-alert-circle me-1"></i>{{ $payout->rejection_reason }}
                            </span>
                        @elseif($payout->failure_reason)
                            <span class="badge bg-label-danger text-wrap" style="max-width: 250px;">
                                <i class="ti ti-alert-triangle me-1"></i>{{ $payout->failure_reason }}
                            </span>
                        @elseif(!empty($details['description']))
                            <span class="text-dark small fw-semibold">{{ $details['description'] }}</span>
                        @else
                            <span class="text-dark small">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="ti ti-receipt text-primary mb-2" style="font-size: 3.2rem;"></i>
                            <h6 class="text-dark fw-bold mb-1">{{ __('لا توجد طلبات سحب سابقة') }}</h6>
                            <p class="small text-dark mb-0">{{ __('عند تقديم طلب سحب عمولة سيظهر سجل الطلب وحالته المباشرة هنا.') }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($payoutRequests->hasPages())
    <div class="d-flex justify-content-between align-items-center p-3 border-top flex-wrap gap-2" style="border-color: #e0e7ff !important; background: #fafafa;">
        <small class="text-dark fw-bold">
            {{ __('عرض :from إلى :to من أصل :total طلب', [
                'from' => $payoutRequests->firstItem(),
                'to' => $payoutRequests->lastItem(),
                'total' => $payoutRequests->total()
            ]) }}
        </small>
        <div class="payouts-pagination">
            {{ $payoutRequests->links() }}
        </div>
    </div>
@endif
