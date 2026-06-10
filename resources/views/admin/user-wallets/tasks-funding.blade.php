@extends('layouts/layoutMaster')

@section('title', __('Task Funding') . ' - ' . $investor->name)

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-credit-card me-2 text-primary"></i>{{ __('Tasks Available for Funding') }}</h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.user-wallets.show', $investor->id) }}">{{ __('Investor Wallet') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Task Funding') }}</li>
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
              <small class="d-block text-muted">{{ __('Available Investment Balance Label') }}</small>
              <h5 class="mb-0 fw-bold text-warning">{{ number_format($walletBalance, 2) }} <small>{{ __('SAR') }}</small></h5>
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

    {{-- معلومات المستثمر والعقد والفلترة --}}
    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="card bg-label-primary border-0 h-100">
          <div class="card-body py-3 d-flex align-items-center">
            <div class="avatar avatar-md me-3">
              <span class="avatar-initial rounded-circle bg-primary"><i class="ti ti-user"></i></span>
            </div>
            <div>
              <h6 class="mb-1 text-primary">{{ __('Investor') }}: <strong>{{ $investor->name }}</strong> (ID: {{ $investor->id }})</h6>
              <p class="mb-0 small">
                {{ __('Your commission') }}:
                <strong>{{ $contract->commission_value }}{{ $contract->commission_type === 'percentage' ? '%' : ' ' . __('SAR') }}</strong>
                @if($contract->min_commission_threshold)
                  &nbsp;|&nbsp; {{ __('Minimum platform commission') }}:
                  <strong>{{ number_format($contract->min_commission_threshold, 2) }} {{ __('SAR') }}</strong>
                @endif
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100 shadow-none border">
          <div class="card-body py-2 d-flex align-items-center">
            <form method="GET" class="d-flex align-items-center h-100 flex-grow-1">
              <div class="input-group input-group-merge border-0">
                <span class="input-group-text border-0 ps-0"><i class="ti ti-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-0 shadow-none"
                  placeholder="{{ __('Search by task number') }}" value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-primary rounded ms-2 px-3">{{ __('Search') }}</button>
              </div>
            </form>
            <button class="btn btn-sm btn-icon btn-label-secondary ms-2 flex-shrink-0 d-none" id="restoreHiddenTasksBtn" title="{{ __('Restore Hidden Tasks') }}" data-bs-toggle="tooltip">
              <i class="ti ti-reload"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- عرض المهام على شكل بطاقات رحلات --}}
    <div class="row g-4">
      @forelse($tasks as $task)
        <div class="col-md-6 col-xl-4 task-card-wrapper" data-task-id="{{ $task->id }}">
          <div class="card h-100 card-action shadow-sm border-0 position-relative">
            <button type="button" class="btn btn-sm btn-icon btn-text-secondary hide-task-btn position-absolute top-0 end-0 m-2" data-task-id="{{ $task->id }}" title="{{ __('Hide Task') }}" data-bs-toggle="tooltip" style="z-index: 10;">
                <i class="ti ti-x text-muted fs-5"></i>
            </button>
            <div class="card-header pb-2 mt-2">
              <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded bg-label-info"><i class="ti ti-truck"></i></span>
                  </div>
                  <div>
                    <h6 class="mb-0">{{ __('Task #') }}{{ $task->id }}</h6>
                    <small class="text-muted">{{ $task->created_at->format('M d, Y') }}</small>
                  </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                  <span class="badge bg-label-primary rounded-pill">{{ number_format($task->total_price, 2) }} {{ __('SAR') }}</span>
                  <span class="badge bg-label-info rounded-pill">{{ __('Status') }}: {{ __($task->status) }}</span>
                  <span class="badge bg-label-success rounded-pill">{{ __('Payment Status') }}: {{ __($task->payment_status) }}</span>
                </div>
              </div>

            </div>
            <div class="card-body">
              <div>
                <span class="badge bg-label-info mb-3  rounded-pill">{{ __('Vehicle Size') }}: {{ $task->vehicle_size?->VehicleName ?? __('Not specified') }}
                </span>
              </div>
              {{-- مسار الرحلة --}}
              <div class="trip-path mb-3 p-2 bg-light rounded">
                <div class="d-flex align-items-center mb-2">
                  <i class="ti ti-circle-filled text-primary me-2" style="font-size: 10px;"></i>
                  <span class="text-truncate small fw-medium text-dark">{{ __('from') }} :
                    {{ $task->pickup->address ?? __('Pickup point') }}</span>
                </div>
                <div class="ms-1 ps-2 border-start border-primary border-2 mb-2" style="height: 15px;"></div>
                <div class="d-flex align-items-center">
                  <i class="ti ti-map-pin-filled text-danger me-2" style="font-size: 10px;"></i>
                  <span class="text-truncate small fw-medium text-dark">{{ __('to') }}
                    :{{ $task->delivery->address ?? __('Delivery point') }}</span>
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <div class="border rounded p-2 text-center">
                    <small class="text-muted d-block mb-1">{{ __('Customer') }}</small>
                    <span class="fw-semibold small text-truncate d-block">{{ $task->customer?->name ?? '—' }}</span>
                  </div>
                </div>
                <div class="col-6">
                  <div class="border rounded p-2 text-center bg-label-success">
                    <small class="text-success d-block mb-1">{{ __('Your Expected Commission') }}</small>
                    @php
                      // جلب عمولة المنصة من الإعلان أو المهمة مباشرة
                      $platformComm = (float) ($task->ad->service_commission ?? $task->commission ?? 0);

                      $myComm = $contract->commission_type === 'percentage'
                        ? min(($platformComm * $contract->commission_value / 100), $platformComm)
                        : min((float) $contract->commission_value, $platformComm);
                    @endphp
                    <span class="fw-bold text-success">{{ number_format($myComm, 2) }} <small>{{ __('SAR') }}</small></span>
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
                  data-url="{{ route('admin.user-wallets.pay-task', ['userId' => $investor->id, 'task' => $task->id]) }}">
                  <i class="ti ti-credit-card me-1"></i> {{ __('Pay Task Value') }}
                </button>
              @else
                <button class="btn btn-label-danger w-100" disabled>
                  <i class="ti ti-alert-triangle me-1"></i> {{ __('Insufficient balance') }}
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
              <h5 class="text-muted">{{ __('No tasks available for funding now') }}</h5>
              <p class="text-muted px-md-5">{{ __('Tasks matching contract will appear') }}</p>
            </div>
          </div>
        </div>
      @endforelse
    </div>

    {{-- Pagination --}}
    @if($tasks->hasPages())
      <div class="card mt-3">
        <div class="card-body px-4 py-2">
            {{ $tasks->appends(request()->input())->links('investor.partials.pagination') }}
        </div>
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
            <div class="alert alert-warning d-flex align-items-start mb-0">
              <i class="ti ti-alert-circle me-2 mt-1"></i>
              <div>
                <h6 class="alert-heading mb-1 fw-bold">{{ __('Funding terms and conditions') }}</h6>
                <ul class="mb-0 ps-3 small">
                  <li>سيتم خصم مبلغ <strong id="displayTotalPrice"></strong> {{ __('SAR') }} من محفظة المستثمر <strong>{{ $investor->name }}</strong> الاستثمارية.</li>
                  <li>{{ __('Funding cannot be reversed') }}</li>
                  <li>{{ __('Expected profits recorded on task completion') }}</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-submit">
              <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
              {{ __('Confirm Payment') }}
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

        if (displayTaskId) displayTaskId.textContent = '#' + taskId;
        if (displayTotalPrice) displayTotalPrice.textContent = totalPrice;
        if (form) form.setAttribute('action', actionUrl);
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

    // إخفاء المهام بناءً على LocalStorage
    const investorId = {{ $investor->id }};
    const storageKey = `hidden_funding_tasks_${investorId}`;
    
    // جلب المهام المخفية من الذاكرة
    let hiddenTasks = JSON.parse(localStorage.getItem(storageKey)) || [];
    
    const restoreBtn = document.getElementById('restoreHiddenTasksBtn');
    if (hiddenTasks.length > 0 && restoreBtn) {
      restoreBtn.classList.remove('d-none');
    }

    if (restoreBtn) {
      restoreBtn.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
          title: '{{ __("Are you sure?") }}',
          text: '{{ __("Do you want to restore all hidden tasks?") }}',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: '{{ __("Yes, restore them") }}',
          cancelButtonText: '{{ __("Cancel") }}',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-label-secondary'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.isConfirmed) {
            localStorage.removeItem(storageKey);
            window.location.reload();
          }
        });
      });
    }

    // إخفاء المهام فور تحميل الصفحة
    document.querySelectorAll('.task-card-wrapper').forEach(card => {
      const taskId = card.getAttribute('data-task-id');
      if (hiddenTasks.includes(taskId)) {
        card.style.display = 'none';
        card.classList.add('d-none'); // For extra safety against layout issues
      }
    });

    // معالجة ضغطة زر الإخفاء (X)
    document.querySelectorAll('.hide-task-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // لمنع تفعيل أي أحداث أخرى في البطاقة
        
        const taskId = this.getAttribute('data-task-id');
        const cardWrapper = document.querySelector(`.task-card-wrapper[data-task-id="${taskId}"]`);
        
        Swal.fire({
          title: '{{ __("Are you sure?") }}',
          text: '{{ __("Do you really want to hide this task?") }}',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: '{{ __("Yes, hide it!") }}',
          cancelButtonText: '{{ __("Cancel") }}',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-label-secondary'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.isConfirmed) {
            if (!hiddenTasks.includes(taskId)) {
              hiddenTasks.push(taskId);
              localStorage.setItem(storageKey, JSON.stringify(hiddenTasks));
              if (restoreBtn) restoreBtn.classList.remove('d-none');
            }

            // إخفاء البطاقة بتأثير حركي بسيط
            if (cardWrapper) {
              cardWrapper.style.transition = "opacity 0.3s ease, transform 0.3s ease";
              cardWrapper.style.opacity = "0";
              cardWrapper.style.transform = "scale(0.9)";
              setTimeout(() => {
                cardWrapper.style.display = 'none';
                cardWrapper.classList.add('d-none');
              }, 300);
            }
          }
        });
      });
    });

    // تفعيل الـ Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

  });
</script>
@endsection
