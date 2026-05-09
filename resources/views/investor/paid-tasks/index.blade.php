@extends('layouts/layoutMaster')

@section('title', 'سجل المهام الممولة')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-list-check me-2 text-primary"></i>سجل المهام الممولة</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('investor.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item active">المهام المدفوعة</li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- إحصائيات سريعة --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>إجمالي التمويل</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($tasks->sum('total_price'), 2) }}</h4>
                                <small class="text-muted">ر.س</small>
                            </div>
                            <small class="text-muted small">لهذه الصفحة</small>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="ti ti-currency-dollar ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>إجمالي عمولاتك</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">
                                    @php
                                        $totalComm = 0;
                                        foreach($tasks as $t) {
                                            $totalComm += $t->userWalletTransactions->first()?->amount ?? 0;
                                        }
                                    @endphp
                                    {{ number_format($totalComm, 2) }}
                                </h4>
                                <small class="text-muted">ر.س</small>
                            </div>
                            <small class="text-muted small">لهذه الصفحة</small>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="ti ti-chart-pie-2 ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>عدد المهام</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $tasks->total() }}</h4>
                            </div>
                            <small class="text-muted small">إجمالي السجل</small>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="ti ti-list-check ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- فلاتر البحث --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">بحث (رقم المهمة، اسم العميل)</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="ابحث هنا..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>تصفية</button>
                    <a href="{{ route('investor.paid-tasks') }}" class="btn btn-label-secondary w-100">تصفير</a>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول المهام الممولة --}}
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-hover border-top">
                <thead class="table-light">
                    <tr>
                        <th>رقم المهمة</th>
                        <th>تاريخ التمويل</th>
                        <th>العميل</th>
                        <th>مسار الرحلة</th>
                        <th>المبلغ المدفوع</th>
                        <th>عمولتك</th>
                        <th>الحالة</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                    <tr>
                        <td>
                            <span class="fw-bold text-primary">#{{ $task->id }}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-nowrap">{{ $task->updated_at->format('Y-m-d') }}</span>
                                <small class="text-muted">{{ $task->updated_at->format('H:i A') }}</small>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-xs me-2">
                                    <span class="avatar-initial rounded-circle bg-label-info"><i class="ti ti-user ti-xs"></i></span>
                                </div>
                                <span class="text-truncate" style="max-width: 150px;">{{ $task->customer?->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center small">
                                <span class="text-truncate" title="{{ $task->pickup->address ?? '' }}">{{ $task->pickup->address ?? '—' }}</span>
                                <i class="ti ti-arrow-narrow-left mx-1 text-muted"></i>
                                <span class="text-truncate" title="{{ $task->delivery->address ?? '' }}">{{ $task->delivery->address ?? '—' }}</span>
                            </div>
                            @if($task->vehicle_size)
                                <div class="mt-1">
                                    <span class="badge bg-label-secondary p-1 px-2 small" style="font-size: 10px;">
                                        <i class="ti ti-truck me-1"></i>{{ $task->vehicle_size->type->name ?? '' }} ({{ $task->vehicle_size->name ?? '' }})
                                    </span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="fw-semibold text-danger">{{ number_format($task->total_price, 2) }}</span>
                            <small class="text-muted">ر.س</small>
                        </td>
                        <td>
                            @php
                                $commissionTrans = $task->userWalletTransactions->first();
                            @endphp
                            @if($commissionTrans)
                                <span class="fw-bold text-success">{{ number_format($commissionTrans->amount, 2) }}</span>
                                <small class="text-muted">ر.س</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($task->closed)
                                <span class="badge bg-label-secondary">مغلقة</span>
                            @else
                                <span class="badge bg-label-success">تم التمويل</span>
                            @endif
                            <div class="mt-1 small text-muted">{{ $task->status }}</div>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-block text-nowrap">
                                <a href="{{ route('investor.paid-tasks.report', $task) }}" target="_blank" class="btn btn-sm btn-icon btn-label-secondary rounded-pill me-1" title="طباعة التقرير">
                                    <i class="ti ti-printer"></i>
                                </a>
                                <button class="btn btn-sm btn-icon btn-label-primary rounded-pill" title="عرض التفاصيل (قريباً)">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ti ti-archive ti-lg d-block mb-2"></i>
                                لا توجد سجلات مطابقة للبحث
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($tasks->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $tasks->appends(request()->input())->links() }}
    </div>
    @endif

</div>
@endsection
