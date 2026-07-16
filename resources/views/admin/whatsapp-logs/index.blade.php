@extends('layouts/layoutMaster')

@section('title', 'سجلات رسائل الواتساب')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">الواتساب /</span> السجلات
</h4>

<div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">إجمالي الرسائل</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['total_messages'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-message ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">تم التسليم</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['delivered_messages'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-checks ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">تمت القراءة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['read_messages'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="ti ti-eye ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">رسائل فشلت</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $stats['failed_messages'] }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-alert-circle ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">سجلات رسائل الواتساب</h5>
            </div>
            <div class="card-body mt-3">
                
                <div class="row mb-4 align-items-end">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="form-label">الحالة</label>
                        <select id="filter_status" class="form-select">
                            <option value="">جميع الحالات</option>
                            <option value="pending">قيد الانتظار</option>
                            <option value="sent">تم الإرسال</option>
                            <option value="delivered">تم التسليم</option>
                            <option value="read">مقروءة</option>
                            <option value="failed">فشلت</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="form-label">الاتجاه</label>
                        <select id="filter_direction" class="form-select">
                            <option value="">جميع الاتجاهات</option>
                            <option value="outbound">صادرة</option>
                            <option value="inbound">واردة</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button id="btn_filter" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i> فلترة</button>
                    </div>
                    <div class="col-md-2">
                        <button id="btn_reset" class="btn btn-label-secondary w-100"><i class="ti ti-refresh me-1"></i> إعادة ضبط</button>
                    </div>
                </div>

                <div class="table-responsive text-nowrap">
                    <table class="table table-bordered table-striped" id="logs-table">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>الرقم</th>
                                <th>الاتجاه</th>
                                <th>النوع</th>
                                <th>المحتوى</th>
                                <th>الحالة</th>
                                <th>تاريخ الإرسال</th>
                                <th>سجل الأخطاء</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script type="module">
$(document).ready(function() {
    var emptyTableHtml = `
        <div class="text-center p-4">
            <i class="ti ti-notes text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
            <h5>لا توجد سجلات لعرضها</h5>
            <p class="text-muted">لم يتم العثور على أي رسائل مطابقة لبحثك، أو لم يتم إرسال أي رسائل حتى الآن.</p>
        </div>
    `;

    var dt_logs = $('#logs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.whatsapp-logs.index') }}",
            data: function (d) {
                d.status = $('#filter_status').val();
                d.direction = $('#filter_direction').val();
            }
        },
        columns: [
            {data: 'id', name: 'id'},
            {data: 'phone_number', name: 'phone_number', orderable: false, searchable: true},
            {data: 'direction_badge', name: 'direction', orderable: false, searchable: false},
            {data: 'message_type', name: 'message_type'},
            {data: 'content', name: 'content', orderable: false, searchable: false},
            {data: 'status_badge', name: 'status', orderable: false, searchable: false},
            {data: 'created_at', name: 'created_at'},
            {data: 'error_log', name: 'error_log', orderable: false, searchable: false},
        ],
        order: [[0, 'desc']],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json',
            emptyTable: emptyTableHtml,
            zeroRecords: emptyTableHtml
        }
    });

    $('#btn_filter').click(function() {
        dt_logs.draw();
    });

    $('#btn_reset').click(function() {
        $('#filter_status').val('');
        $('#filter_direction').val('');
        dt_logs.draw();
    });
});
</script>
@endsection
