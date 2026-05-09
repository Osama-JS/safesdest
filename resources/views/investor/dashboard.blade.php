@extends('layouts/layoutMaster')

@section('title', 'لوحة تحكم المستثمر')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
<script>
    'use strict';
    (function () {
        document.addEventListener('DOMContentLoaded', function () {
            let cardColor, headingColor, labelColor, shadeColor, borderColor;

        cardColor = config.colors.cardColor;
        headingColor = config.colors.headingColor;
        labelColor = config.colors.textMuted;
        borderColor = config.colors.borderColor;

        // Profit Chart
        const profitChartEl = document.querySelector('#profitChart'),
            profitChartConfig = {
                series: [{
                    name: 'الأرباح (ر.س)',
                    data: @json($chartData['data'])
                }],
                chart: {
                    height: 250,
                    type: 'area',
                    toolbar: { show: false }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                grid: {
                    borderColor: borderColor,
                    padding: { top: 0, bottom: -10, left: 20, right: 20 }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.5,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: @json($chartData['labels']),
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { colors: labelColor, fontSize: '13px' } }
                },
                yaxis: { labels: { show: false } },
                colors: [config.colors.success]
            };
        if (typeof profitChartEl !== undefined && profitChartEl !== null) {
            const profitChart = new ApexCharts(profitChartEl, profitChartConfig);
            profitChart.render();
        }

        // Investment Radial Chart
        const investmentChartEl = document.querySelector('#investmentRadialChart'),
            investmentChartConfig = {
                chart: {
                    height: 250,
                    type: 'radialBar'
                },
                colors: [config.colors.primary, config.colors.info],
                series: [75], // Example percentage
                plotOptions: {
                    radialBar: {
                        hollow: { size: '65%' },
                        dataLabels: {
                            name: { show: false },
                            value: {
                                fontWeight: '600',
                                color: headingColor,
                                fontSize: '22px',
                                offsetY: 10
                            }
                        }
                    }
                },
                labels: ['الاستثمار']
            };
            if (typeof investmentChartEl !== undefined && investmentChartEl !== null) {
                const investmentChart = new ApexCharts(investmentChartEl, investmentChartConfig);
                investmentChart.render();
            }
        });
    })();
</script>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- رسائل النجاح / الخطأ --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            {{ session('success') }}
        </div>
    @endif

    {{-- ترحيب وبطاقة الحالة --}}
    <div class="row mb-4">
        <div class="col-lg-8 col-12">
            <div class="card bg-primary text-white h-100">
                <div class="card-body d-flex justify-content-between flex-wrap gap-3">
                    <div class="d-flex flex-column justify-content-center">
                        <h4 class="text-white mb-1">مرحباً، {{ auth()->user()->name }} 👋</h4>
                        <p class="mb-3">نحن سعداء برؤية استثماراتك تنمو اليوم.</p>
                        <div class="d-flex gap-2 flex-wrap">
                            @if($contract)
                                <span class="badge bg-white text-primary px-3">
                                    <i class="ti ti-certificate me-1"></i>
                                    عقد {{ $contract->contract_type === 'task_investment' ? 'بالمهام' : 'عام' }}
                                </span>
                                <span class="badge bg-white text-primary px-3">
                                    <i class="ti ti-percentage me-1"></i>
                                    عمولة {{ $contract->commission_value }}{{ $contract->commission_type === 'percentage' ? '%' : ' ر.س' }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="d-none d-md-block">
                        <img src="{{ asset('assets/img/illustrations/page-pricing-enterprise.png') }}" alt="Dashboard Illustration" width="140">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-12 mt-4 mt-lg-0">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="avatar avatar-md mx-auto mb-3">
                        <span class="avatar-initial rounded-circle bg-label-success"><i class="ti ti-coins ti-md"></i></span>
                    </div>
                    <p class="mb-1 text-muted">إجمالي العمولات المكتسبة</p>
                    <h3 class="mb-0 fw-bold">{{ number_format($stats['total_commissions'], 2) }} <small class="fs-6">ر.س</small></h3>
                </div>
            </div>
        </div>
    </div>

    {{-- إحصائيات سريعة --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="ti ti-wallet ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($stats['investment_balance'], 2) }}</h4>
                    </div>
                    <p class="mb-1">رصيد الاستثمار المتاح</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-success me-1">جاهز للتمويل</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="ti ti-cash ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($stats['personal_balance'], 2) }}</h4>
                    </div>
                    <p class="mb-1">رصيد محفظتي الشخصية</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-info me-1">قابل للسحب</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-checklist ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($stats['paid_tasks_count']) }}</h4>
                    </div>
                    <p class="mb-1">إجمالي المهام الممولة</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-primary me-1">عمليات ناجحة</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="ti ti-calendar ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ $contract ? $contract->start_date->format('Y-m-d') : '—' }}</h4>
                    </div>
                    <p class="mb-1">تاريخ بداية الاستثمار</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-muted">العقد الحالي</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- رسوم بيانية --}}
    <div class="row mb-4">
        {{-- نمو الأرباح --}}
        <div class="col-lg-8 col-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2 text-muted">نمو الأرباح (آخر 6 أشهر)</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div id="profitChart"></div>
                </div>
            </div>
        </div>
        {{-- نظرة عامة --}}
        <div class="col-lg-4 col-12 mt-4 mt-lg-0">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2 text-muted">نظرة عامة على المحفظة</h5>
                </div>
                <div class="card-body">
                    <div id="investmentRadialChart"></div>
                    <div class="d-flex justify-content-around align-items-center mt-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge badge-dot bg-primary"></span>
                            <small>المستثمر</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge badge-dot bg-info"></span>
                            <small>المتاح</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- آخر العمليات والروابط --}}
    <div class="row">
        <div class="col-md-8 col-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">آخر العمليات على محفظة الاستثمار</h5>
                    <a href="{{ route('investor.investment-wallet') }}" class="btn btn-sm btn-label-primary">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>نوع العملية</th>
                                <th>المبلغ</th>
                                <th>المهمة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivity as $tx)
                            <tr>
                                <td>
                                    <span class="badge bg-label-{{ $tx->transaction_type === 'credit' ? 'success' : 'danger' }} rounded-pill">
                                        {{ $tx->transaction_type === 'credit' ? 'إيداع' : 'تمويل مهمة' }}
                                    </span>
                                </td>
                                <td class="fw-bold {{ $tx->transaction_type === 'credit' ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->transaction_type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount, 2) }}
                                </td>
                                <td>
                                    @if($tx->task_id)
                                        <span class="text-primary">#{{ $tx->task_id }}</span>
                                    @else <small class="text-muted">نظام</small> @endif
                                </td>
                                <td class="small">{{ $tx->created_at->format('M d, H:i') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4">لا توجد حركات مؤخراً</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12 mt-4 mt-md-0">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">روابط الوصول السريع</h5></div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('investor.task-payment') }}" class="list-group-item list-group-item-action d-flex align-items-center px-0">
                            <i class="ti ti-credit-card me-3 text-primary"></i> تمويل مهام جديدة
                        </a>
                        <a href="{{ route('investor.paid-tasks') }}" class="list-group-item list-group-item-action d-flex align-items-center px-0">
                            <i class="ti ti-list-check me-3 text-success"></i> سجل المهام الممولة
                        </a>
                        <a href="{{ route('investor.investment-wallet') }}" class="list-group-item list-group-item-action d-flex align-items-center px-0">
                            <i class="ti ti-wallet me-3 text-warning"></i> كشف حساب الاستثمار
                        </a>
                        <a href="{{ route('investor.profile') }}" class="list-group-item list-group-item-action d-flex align-items-center px-0">
                            <i class="ti ti-settings me-3 text-info"></i> إعدادات الحساب
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
