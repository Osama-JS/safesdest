@extends('layouts/layoutMaster')

@section('title', 'تمويل المهام')

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-credit-card me-2 text-primary"></i>تمويل المهام المتاحة</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('investor.dashboard') }}">الرئيسية</a></li>
            <li class="breadcrumb-item active">تمويل المهام</li>
          </ol>
        </nav>
      </div>
      <div class="col-auto">
        <div class="card card-border-shadow-warning p-2">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-sm me-2">
              <span class="avatar-initial rounded bg-label-warning"><i class="ti ti-wallet"></i></span>
            </div>
            <div>
              <small class="d-block text-muted">رصيد المضاربة المتاح</small>
              <h5 class="mb-0 fw-bold text-warning">{{ number_format($walletBalance, 2) }} <small>ر.س</small></h5>
            </div>
          </div>
        </div>
      </div>
    </div>

    @foreach(['success', 'error'] as $msg)
      @if(session($msg))
        <div class="alert alert-{{ $msg === 'error' ? 'danger' : $msg }} alert-dismissible mb-4" role="alert">
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          {{ session($msg) }}
        </div>
      @endif
    @endforeach

    {{-- معلومات العقد والفلترة --}}
    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="card bg-label-primary border-0 h-100">
          <div class="card-body py-3 d-flex align-items-center">
            <div class="avatar avatar-md me-3">
              <span class="avatar-initial rounded-circle bg-primary"><i class="ti ti-file-invoice"></i></span>
            </div>
            <div>
              <h6 class="mb-1 text-primary">إعدادات عقدك الحالي</h6>
              <p class="mb-0 small">
                عمولتك:
                <strong>{{ $contract->commission_value }}{{ $contract->commission_type === 'percentage' ? '%' : ' ر.س' }}</strong>
                @if($contract->min_commission_threshold)
                  &nbsp;|&nbsp; الحد الأدنى لعمولة المنصة:
                  <strong>{{ number_format($contract->min_commission_threshold, 2) }} ر.س</strong>
                @endif
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100 shadow-none border">
          <div class="card-body py-2">
            <form method="GET" class="d-flex align-items-center h-100">
              <div class="input-group input-group-merge border-0">
                <span class="input-group-text border-0 ps-0"><i class="ti ti-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-0 shadow-none"
                  placeholder="ابحث برقم المهمة..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-primary rounded ms-2 px-3">بحث</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    {{-- عرض المهام على شكل بطاقات رحلات --}}
    <div class="row g-4">
      @forelse($tasks as $task)
        <div class="col-md-6 col-xl-4">
          <div class="card h-100 card-action shadow-sm border-0">
            <div class="card-header pb-2">
              <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded bg-label-info"><i class="ti ti-truck"></i></span>
                  </div>
                  <div>
                    <h6 class="mb-0">مهمة #{{ $task->id }}</h6>
                    <small class="text-muted">{{ $task->created_at->format('M d, Y') }}</small>
                  </div>
                </div>
                <div class="dropdown">
                  <span class="badge bg-label-primary rounded-pill">{{ number_format($task->total_price, 2) }} ر.س</span>
                  <span class="badge bg-label-info rounded-pill">الحالة: {{ $task->status }}</span>
                  <span class="badge bg-label-success rounded-pill">الدفع: {{ $task->payment_status }}</span>
                </div>
              </div>

            </div>
            <div class="card-body">
              <div>
                <span class="badge bg-label-info mb-3  rounded-pill">الشاحنة : {{ $task->vehicle_size?->VehicleName }}
                </span>
              </div>
              {{-- مسار الرحلة --}}
              <div class="trip-path mb-3 p-2 bg-light rounded">
                <div class="d-flex align-items-center mb-2">
                  <i class="ti ti-circle-filled text-primary me-2" style="font-size: 10px;"></i>
                  <span class="text-truncate small fw-medium text-dark">{{ __('from') }} :
                    {{ $task->pickup->address ?? 'نقطة الانطلاق' }}</span>
                </div>
                <div class="ms-1 ps-2 border-start border-primary border-2 mb-2" style="height: 15px;"></div>
                <div class="d-flex align-items-center">
                  <i class="ti ti-map-pin-filled text-danger me-2" style="font-size: 10px;"></i>
                  <span class="text-truncate small fw-medium text-dark">{{ __('to') }}
                    :{{ $task->delivery->address ?? 'نقطة الوصول' }}</span>
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <div class="border rounded p-2 text-center">
                    <small class="text-muted d-block mb-1">العميل</small>
                    <span class="fw-semibold small text-truncate d-block">{{ $task->customer?->name ?? '—' }}</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="border rounded p-2 text-center bg-label-success">
                    <small class="text-success d-block mb-1">عمولتك المتوقعة</small>
                    @php
                      // جلب عمولة المنصة من الإعلان أو المهمة مباشرة
                      $platformComm = (float) ($task->ad->service_commission ?? $task->commission ?? 0);

                      $myComm = $contract->commission_type === 'percentage'
                        ? min(($platformComm * $contract->commission_value / 100), $platformComm)
                        : min((float) $contract->commission_value, $platformComm);
                    @endphp
                    <span class="fw-bold text-success">{{ number_format($myComm, 2) }} <small>ر.س</small></span>
                  </div>
                </div>
              </div>



            </div>
            <div class="card-footer pt-0">
              <hr class="mt-0">
              @if($walletBalance >= $task->total_price)
                <button type="button" class="btn btn-primary w-100 shadow-sm fund-task-btn"
                  data-bs-toggle="modal" 
                  data-bs-target="#fundTaskModal"
                  data-task-id="{{ $task->id }}"
                  data-total-price="{{ number_format($task->total_price, 2) }}"
                  data-url="{{ route('investor.task-payment.pay', $task) }}">
                  <i class="ti ti-credit-card me-1"></i> تمويل المهمة الآن
                </button>
              @else
                <button class="btn btn-label-danger w-100" disabled>
                  <i class="ti ti-alert-triangle me-1"></i> رصيد المضاربة لا يكفي
                </button>
              @endif
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="card shadow-none border-2 border-dashed text-center py-5">
            <div class="card-body">
              <img src="{{ asset('assets/img/illustrations/page-misc-under-maintenance.png') }}" alt="No tasks" width="150"
                class="mb-4">
              <h5 class="text-muted">لا توجد مهام متاحة للتمويل حالياً</h5>
              <p class="text-muted px-md-5">بناءً على إعدادات عقدك، ستظهر هنا المهام التي تتوافق مع شروط المضاربة الخاصة بك
                فور توفرها.</p>
            </div>
          </div>
        </div>
      @endforelse
    </div>

    {{-- Pagination --}}
    @if($tasks->hasPages())
      <div class="d-flex justify-content-center mt-5">
        {{ $tasks->appends(request()->input())->links() }}
      </div>
    @endif

  </div>

  <!-- Modal تأكيد التمويل -->
  <div class="modal fade" id="fundTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <h5 class="modal-title d-flex align-items-center">
            <i class="ti ti-shield-check text-primary me-2 ti-md"></i>
            تأكيد تمويل المهمة <span id="displayTaskId" class="ms-1 fw-bold"></span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="fundTaskForm" method="POST">
          @csrf
          <div class="modal-body">
            <!-- شروط التمويل -->
            <div class="alert alert-warning d-flex align-items-start mb-4">
              <i class="ti ti-alert-circle me-2 mt-1"></i>
              <div>
                <h6 class="alert-heading mb-1 fw-bold">شروط وأحكام التمويل:</h6>
                <ul class="mb-0 ps-3 small">
                  <li>سيتم خصم مبلغ <strong id="displayTotalPrice"></strong> ر.س من رصيدك الحالي فور الموافقة.</li>
                  <li>لا يمكن التراجع عن عملية التمويل بعد تأكيدها.</li>
                  <li>سيتم إضافة الأرباح المتوقعة إلى سجلك المالي عند اكتمال المهمة بنجاح.</li>
                </ul>
              </div>
            </div>

            <!-- طلب كلمة المرور -->
            <div class="mb-0">
              <label class="form-label fw-bold mb-1" for="password">أدخل كلمة المرور للتأكيد:</label>
              <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" 
                  placeholder="············" required autocomplete="current-password">
              </div>
              <small class="text-danger mt-1 d-block">
                * يرجى التأكد من كلمة المرور قبل الضغط على تأكيد.
              </small>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="submit" class="btn btn-primary btn-submit">
              <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
              تأكيد التمويل الآن
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const fundTaskModal = document.getElementById('fundTaskModal');
    if (fundTaskModal) {
      fundTaskModal.addEventListener('show.bs.modal', function (event) {
        // الزر الذي فتح المودال
        const button = event.relatedTarget;
        
        // استخراج البيانات من سمات الزر
        const taskId = button.getAttribute('data-task-id');
        const totalPrice = button.getAttribute('data-total-price');
        const actionUrl = button.getAttribute('data-url');

        // تحديث محتوى المودال
        const displayTaskId = fundTaskModal.querySelector('#displayTaskId');
        const displayTotalPrice = fundTaskModal.querySelector('#displayTotalPrice');
        const form = fundTaskModal.querySelector('#fundTaskForm');
        const passwordInput = fundTaskModal.querySelector('#password');

        if (displayTaskId) displayTaskId.textContent = '#' + taskId;
        if (displayTotalPrice) displayTotalPrice.textContent = totalPrice;
        if (form) form.setAttribute('action', actionUrl);
        if (passwordInput) passwordInput.value = '';
      });
    }

    // معالجة حالة التحميل عند الإرسال
    const fundTaskForm = document.getElementById('fundTaskForm');
    if (fundTaskForm) {
      fundTaskForm.addEventListener('submit', function() {
        const submitBtn = this.querySelector('.btn-submit');
        if (submitBtn) {
          submitBtn.disabled = true;
          const spinner = submitBtn.querySelector('.spinner-border');
          if (spinner) spinner.classList.remove('d-none');
        }
      });
    }
  });
</script>
@endsection
