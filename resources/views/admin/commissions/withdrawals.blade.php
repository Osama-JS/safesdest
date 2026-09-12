@extends('layouts/layoutMaster')

@section('title', 'إدارة طلبات سحب العمولات للمستثمرين')

@section('vendor-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
@endsection

@section('vendor-script')
<script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-cash-banknote me-2 text-primary"></i>طلبات سحب العمولات للمستثمرين
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('Home') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.investors.index') }}">المستثمرون</a></li>
                    <li class="breadcrumb-item active">طلبات سحب العمولات</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- كروت الإحصائيات --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-files ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ $stats['total'] }}</h4>
                    </div>
                    <p class="mb-0 fw-medium text-muted">إجمالي الطلبات المسجلة</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm" style="border-top: 3px solid #ffab00 !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="ti ti-clock ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-warning">{{ $stats['pending'] }}</h4>
                    </div>
                    <p class="mb-0 fw-medium text-muted">بانتظار المراجعة والصرف</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm" style="border-top: 3px solid #28a745 !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="ti ti-check ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-success">{{ $stats['approved'] }}</h4>
                    </div>
                    <p class="mb-0 fw-medium text-muted">طلبات تم اعتمادها وصرفها</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm" style="border-top: 3px solid #7367f0 !important;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="ti ti-cash ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-info">{{ number_format($stats['total_approved_amount'], 2) }}</h4>
                    </div>
                    <p class="mb-0 fw-medium text-muted">إجمالي المبالغ المصروفة (ر.س)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- بطاقة التصفية والبحث --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.investors.commission-withdrawals.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">بحث بالمستثمر</label>
                    <input type="text" name="search" class="form-control" placeholder="اسم المستثمر، البريد، أو رقم الهاتف..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>مقبول ومصروف</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">من تاريخ</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">إلى تاريخ</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-filter me-1"></i> تصفية
                    </button>
                    @if(request()->anyFilled(['search', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('admin.investors.commission-withdrawals.index') }}" class="btn btn-label-secondary" title="إعادة تعيين">
                            <i class="ti ti-refresh"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- جدول الطلبات --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3"># الطلب</th>
                        <th class="py-3">المستثمر</th>
                        <th class="py-3">المبلغ المطلوب</th>
                        <th class="py-3">الرصيد المتاح</th>
                        <th class="py-3">بيانات التحويل البنكي</th>
                        <th class="py-3">تاريخ الطلب</th>
                        <th class="py-3 text-center">الحالة</th>
                        <th class="py-3">الإيصال / الملاحظات</th>
                        <th class="py-3 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $w)
                    <tr>
                        <td class="fw-bold">#{{ $w->id }}</td>
                        <td>
                            @if($w->user)
                                <div class="d-flex align-items-center">
                                    <div>
                                        <a href="{{ route('admin.user-wallets.show', $w->user->id) }}" class="fw-bold text-heading text-decoration-none">
                                            {{ $w->user->name }}
                                        </a>
                                        <div class="text-muted small">{{ $w->user->phone ?: $w->user->email }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <strong class="text-primary fs-6">{{ number_format($w->amount, 2) }}</strong>
                            <small class="text-muted">ر.س</small>
                        </td>
                        <td>
                            @php
                                $withdrawable = (float) ($w->wallet?->withdrawable_balance ?? 0);
                            @endphp
                            <span class="badge {{ $withdrawable >= $w->amount ? 'bg-label-success' : 'bg-label-danger' }}">
                                {{ number_format($withdrawable, 2) }} ر.س
                            </span>
                        </td>
                        <td>
                            @if($w->bank_name || $w->iban_number)
                                <div class="fw-medium small">{{ $w->bank_name ?: 'غير محدد' }}</div>
                                <div class="font-monospace small text-muted" style="font-size: 0.78rem;">{{ $w->iban_number ?: $w->account_number }}</div>
                                @if($w->account_holder)
                                    <small class="text-muted">{{ $w->account_holder }}</small>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <div>{{ $w->created_at->format('Y-m-d') }}</div>
                            <small class="text-muted">{{ $w->created_at->format('H:i') }}</small>
                        </td>
                        <td class="text-center">
                            @if($w->status === 'pending')
                                <span class="badge bg-label-warning px-2 py-1">
                                    <i class="ti ti-clock me-1"></i> قيد المراجعة
                                </span>
                            @elseif($w->status === 'approved')
                                <span class="badge bg-label-success px-2 py-1">
                                    <i class="ti ti-check me-1"></i> مقبول ومصروف
                                </span>
                            @elseif($w->status === 'rejected')
                                <span class="badge bg-label-danger px-2 py-1">
                                    <i class="ti ti-x me-1"></i> مرفوض
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($w->receipt_image)
                                <button type="button" class="btn btn-xs btn-label-info mb-1" onclick="previewReceipt('{{ $w->receipt_url }}')">
                                    <i class="ti ti-file-certificate me-1"></i> الإيصال
                                </button>
                            @endif

                            @if($w->status === 'rejected' && $w->rejection_reason)
                                <div class="text-danger small fw-medium" title="{{ $w->rejection_reason }}">
                                    <i class="ti ti-alert-circle me-1"></i>{{ \Illuminate\Support\Str::limit($w->rejection_reason, 35) }}
                                </div>
                            @endif

                            @if($w->admin_notes)
                                <div class="text-muted small" style="font-size: 0.75rem;">{{ \Illuminate\Support\Str::limit($w->admin_notes, 30) }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($w->status === 'pending')
                                <div class="d-flex justify-content-center gap-1">
                                    @canany(['approve_investor_withdrawals', 'manage_investor_withdrawals', 'save_investors'])
                                    <button type="button" class="btn btn-sm btn-success px-2"
                                            onclick="openApproveModal(
                                                {{ $w->id }},
                                                '{{ addslashes($w->user?->name ?? '—') }}',
                                                '{{ addslashes($w->user?->phone ?: ($w->user?->email ?? '—')) }}',
                                                {{ $w->amount }},
                                                {{ $withdrawable }},
                                                '{{ addslashes($w->bank_name ?: 'غير محدد') }}',
                                                '{{ addslashes($w->account_holder ?: ($w->user?->name ?? 'غير محدد')) }}',
                                                '{{ addslashes($w->iban_number ?: ($w->account_number ?: 'غير محدد')) }}',
                                                '{{ addslashes(str_replace(["\r", "\n"], ' ', $w->investor_notes ?? '')) }}'
                                            )"
                                            title="الموافقة ورفع الإيصال">
                                        <i class="ti ti-check me-1"></i> موافقة
                                    </button>
                                    @endcanany
                                    @canany(['reject_investor_withdrawals', 'manage_investor_withdrawals', 'save_investors'])
                                    <button type="button" class="btn btn-sm btn-danger px-2"
                                            onclick="openRejectModal(
                                                {{ $w->id }},
                                                '{{ addslashes($w->user?->name ?? '—') }}',
                                                {{ $w->amount }},
                                                '{{ addslashes($w->bank_name ?: 'غير محدد') }}',
                                                '{{ addslashes($w->iban_number ?: ($w->account_number ?: 'غير محدد')) }}'
                                            )"
                                            title="رفض الطلب مع ذكر السبب">
                                        <i class="ti ti-x me-1"></i> رفض
                                    </button>
                                    @endcanany
                                    @cannot('approve_investor_withdrawals')
                                        @cannot('manage_investor_withdrawals')
                                            @cannot('save_investors')
                                                <span class="text-muted small">عرض فقط</span>
                                            @endcannot
                                        @endcannot
                                    @endcannot
                                </div>
                            @else
                                <span class="text-muted small">
                                    {{ $w->processor?->name ? 'عبر: ' . $w->processor->name : 'مكتمل' }}
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ti ti-files-off display-4 text-light mb-3"></i>
                                <h5>لا توجد طلبات سحب عمولات مطابقة</h5>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
        <div class="card-footer border-top py-3">
            {{ $withdrawals->links() }}
        </div>
        @endif
    </div>

</div>

{{-- نافذة الموافقة على السحب ورفع الإيصال --}}
<div class="modal fade" id="approveWithdrawalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title d-flex align-items-center text-success">
                    <i class="ti ti-check me-2 ti-md"></i> الموافقة على طلب سحب العمولات
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveWithdrawalForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="approve_withdrawal_id" name="id">
                <div class="modal-body">
                    <div class="alert alert-success d-flex align-items-start mb-3">
                        <i class="ti ti-info-circle me-2 mt-1"></i>
                        <div class="small">
                            عند تأكيد الموافقة، سيتم تسجيل حركة خصم (Debit) تلقائياً في محفظة العمولات للمستثمر بمبلغ السحب، وربط الإيصال المرفوع بها وإرسال إشعار بريد إلكتروني للمستثمر.
                        </div>
                    </div>

                    <div class="card bg-label-secondary mb-3 border-0">
                        <div class="card-body p-3">
                            <div class="row g-3">
                                {{-- عمود بيانات المستثمر والطلب --}}
                                <div class="col-md-6 border-end-md">
                                    <h6 class="fw-bold mb-2 text-primary d-flex align-items-center">
                                        <i class="ti ti-user me-1"></i> بيانات المستثمر والطلب
                                    </h6>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">المستثمر:</span>
                                        <strong id="approve_investor_name" class="small text-heading"></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">الاتصال:</span>
                                        <span id="approve_investor_contact" class="small text-muted"></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">المبلغ المطلوب:</span>
                                        <strong id="approve_amount" class="text-primary small"></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted small">الرصيد المتاح للسحب:</span>
                                        <strong id="approve_available_balance" class="text-success small"></strong>
                                    </div>
                                </div>

                                {{-- عمود بيانات الحساب البنكي --}}
                                <div class="col-md-6 ps-md-3">
                                    <h6 class="fw-bold mb-2 text-info d-flex align-items-center">
                                        <i class="ti ti-building-bank me-1"></i> بيانات التحويل البنكي
                                    </h6>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">البنك:</span>
                                        <strong id="approve_bank_name" class="small text-heading"></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">اسم المستفيد:</span>
                                        <strong id="approve_account_holder" class="small text-heading"></strong>
                                    </div>
                                    <div class="mb-1">
                                        <span class="text-muted small d-block mb-1">رقم الآيبان / الحساب:</span>
                                        <div class="d-flex align-items-center justify-content-between bg-white rounded px-2 py-1 border">
                                            <span id="approve_iban_number" class="font-monospace small fw-bold text-dark text-break"></span>
                                            <button type="button" class="btn btn-xs btn-label-primary ms-1 px-1 py-0" onclick="copyApproveIban()" title="نسخ الآيبان">
                                                <i class="ti ti-copy" style="font-size: 0.85rem;"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="approve_notes_wrapper" class="d-none mt-2 pt-1 border-top">
                                        <span class="text-muted small d-block">ملاحظة المستثمر:</span>
                                        <span id="approve_investor_notes" class="small text-muted fst-italic"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">طريقة الصرف / السحب <span class="text-danger">*</span></label>
                        <select name="withdrawal_method" class="form-select" required>
                            <option value="bank_transfer" selected>تحويل بنكي</option>
                            <option value="cash">نقداً</option>
                            <option value="check">شيك مصدق</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">صورة / ملف إيصال التحويل <span class="text-danger">*</span></label>
                        <input type="file" name="receipt" class="form-control" accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                        <small class="text-muted">مسموح: JPG, PNG, PDF (الحد الأقصى 5 ميجابايت)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">رقم المرجع / الحوالة البنكية (اختياري)</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="مثال: REF-92834723">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small">ملاحظات إدارية (اختياري)</label>
                        <textarea name="admin_notes" class="form-control" rows="2" placeholder="أي ملاحظات تسجل على الحركة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success" id="btnConfirmApprove">
                        <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                        <i class="ti ti-check me-1"></i> اعتماد الصرف وخصم المحفظة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- نافذة رفض طلب السحب --}}
<div class="modal fade" id="rejectWithdrawalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title d-flex align-items-center text-danger">
                    <i class="ti ti-x me-2 ti-md"></i> رفض طلب سحب العمولات
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectWithdrawalForm">
                @csrf
                <input type="hidden" id="reject_withdrawal_id" name="id">
                <div class="modal-body">
                    <div class="alert alert-danger d-flex align-items-start mb-3">
                        <i class="ti ti-alert-triangle me-2 mt-1"></i>
                        <div class="small">
                            يجب تحديد سبب الرفض بوضوح ليظهر في لوحة تحكم المستثمر ويتم إرساله له عبر البريد الإلكتروني.
                        </div>
                    </div>

                    <div class="card bg-label-secondary mb-3 border-0">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">المستثمر:</span>
                                <strong id="reject_investor_name" class="small"></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">مبلغ الطلب:</span>
                                <strong id="reject_amount" class="text-danger small"></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">البنك / الآيبان:</span>
                                <span id="reject_bank_info" class="small font-monospace text-muted"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">سبب الرفض <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" required
                            placeholder="اكتب سبب رفض الطلب هنا ليظهر للمستثمر..."></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small">ملاحظات إدارية داخلية (اختياري)</label>
                        <textarea name="admin_notes" class="form-control" rows="2" placeholder="ملاحظات داخلية لا تظهر للعميل..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmReject">
                        <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                        <i class="ti ti-x me-1"></i> تأكيد رفض الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- نافذة معاينة الإيصال --}}
<div class="modal fade" id="adminReceiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="ti ti-file-certificate text-primary me-2 ti-md"></i> إيصال تحويل السحب
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3" id="adminReceiptBody"></div>
            <div class="modal-footer border-top">
                <a href="#" id="adminDownloadReceiptBtn" class="btn btn-primary" download>
                    <i class="ti ti-download me-1"></i> تحميل
                </a>
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    let approveModalInstance = null;
    let rejectModalInstance = null;
    let receiptModalInstance = null;

    document.addEventListener('DOMContentLoaded', function () {
        approveModalInstance = new bootstrap.Modal(document.getElementById('approveWithdrawalModal'));
        rejectModalInstance  = new bootstrap.Modal(document.getElementById('rejectWithdrawalModal'));
        receiptModalInstance = new bootstrap.Modal(document.getElementById('adminReceiptModal'));
    });

    let currentApproveIban = '';

    function openApproveModal(id, name, contact, amount, available, bankName, accountHolder, iban, notes) {
        document.getElementById('approve_withdrawal_id').value = id;
        document.getElementById('approve_investor_name').innerText = name || '—';
        document.getElementById('approve_investor_contact').innerText = contact || '—';
        document.getElementById('approve_amount').innerText = parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' ر.س';
        document.getElementById('approve_available_balance').innerText = parseFloat(available).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' ر.س';

        document.getElementById('approve_bank_name').innerText = bankName || 'غير محدد';
        document.getElementById('approve_account_holder').innerText = accountHolder || name || 'غير محدد';
        document.getElementById('approve_iban_number').innerText = iban || 'غير محدد';
        currentApproveIban = iban || '';

        const notesWrapper = document.getElementById('approve_notes_wrapper');
        const notesElem = document.getElementById('approve_investor_notes');
        if (notes && notes.trim() !== '') {
            notesElem.innerText = notes;
            notesWrapper.classList.remove('d-none');
        } else {
            notesElem.innerText = '';
            notesWrapper.classList.add('d-none');
        }

        document.getElementById('approveWithdrawalForm').reset();
        document.getElementById('approve_withdrawal_id').value = id;
        approveModalInstance.show();
    }

    function copyApproveIban() {
        if (!currentApproveIban || currentApproveIban === 'غير محدد') {
            Swal.fire({
                icon: 'info',
                title: 'تنبيه',
                text: 'لا يوجد رقم آيبان لنسخه.',
                timer: 1500,
                showConfirmButton: false
            });
            return;
        }
        navigator.clipboard.writeText(currentApproveIban).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'تم النسخ',
                text: 'تم نسخ رقم الآيبان إلى الحافظة بنجاح: ' + currentApproveIban,
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(() => {
            // fallback
            const tempInput = document.createElement('input');
            tempInput.value = currentApproveIban;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            Swal.fire({
                icon: 'success',
                title: 'تم النسخ',
                text: 'تم نسخ رقم الآيبان بنجاح.',
                timer: 1500,
                showConfirmButton: false
            });
        });
    }

    function openRejectModal(id, name, amount, bankName, iban) {
        document.getElementById('reject_withdrawal_id').value = id;
        if (document.getElementById('reject_investor_name')) {
            document.getElementById('reject_investor_name').innerText = name || '—';
            document.getElementById('reject_amount').innerText = parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' ر.س';
            document.getElementById('reject_bank_info').innerText = (bankName ? bankName + ' - ' : '') + (iban || '—');
        }
        document.getElementById('rejectWithdrawalForm').reset();
        document.getElementById('reject_withdrawal_id').value = id;
        rejectModalInstance.show();
    }

    function previewReceipt(url) {
        const body = document.getElementById('adminReceiptBody');
        const download = document.getElementById('adminDownloadReceiptBtn');
        const ext = url.split('.').pop().toLowerCase();

        download.href = url;
        if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
            body.innerHTML = `<img src="${url}" class="img-fluid rounded shadow-sm" style="max-height: 70vh;" alt="Receipt">`;
        } else if (ext === 'pdf') {
            body.innerHTML = `<iframe src="${url}" width="100%" height="520px" style="border: none;"></iframe>`;
        } else {
            body.innerHTML = `<p class="py-5 text-muted">يرجى تنزيل الملف للاطلاع عليه.</p>`;
        }
        receiptModalInstance.show();
    }

    // إرسال نموذج الموافقة
    document.getElementById('approveWithdrawalForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('approve_withdrawal_id').value;
        const btn = document.getElementById('btnConfirmApprove');
        const spinner = btn.querySelector('.spinner-border');

        btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');

        const formData = new FormData(this);

        fetch(`{{ url('admin/investors/commission-withdrawals') }}/${id}/approve`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');

            if (data.status === 1) {
                approveModalInstance.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح!',
                    text: data.success,
                    confirmButtonText: 'حسناً'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: data.error || 'حدث خطأ أثناء معالجة الطلب.'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            Swal.fire({
                icon: 'error',
                title: 'خطأ في الاتصال',
                text: 'تعذر الاتصال بالخادم، يرجى المحاولة لاحقاً.'
            });
        });
    });

    // إرسال نموذج الرفض
    document.getElementById('rejectWithdrawalForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('reject_withdrawal_id').value;
        const btn = document.getElementById('btnConfirmReject');
        const spinner = btn.querySelector('.spinner-border');

        btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');

        const formData = new FormData(this);

        fetch(`{{ url('admin/investors/commission-withdrawals') }}/${id}/reject`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');

            if (data.status === 1) {
                rejectModalInstance.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'تم الرفض بنجاح',
                    text: data.success,
                    confirmButtonText: 'حسناً'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: data.error || 'حدث خطأ أثناء رفض الطلب.'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            if (spinner) spinner.classList.add('d-none');
            Swal.fire({
                icon: 'error',
                title: 'خطأ في الاتصال',
                text: 'تعذر الاتصال بالخادم، يرجى المحاولة لاحقاً.'
            });
        });
    });
</script>
@endsection
