@extends('layouts/layoutMaster')

@section('title', 'إدارة المستثمرين')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    <script>
        window.customers = @json($customers);
    </script>
    @vite(['resources/js/admin/investors.js'])
    @vite(['resources/js/ajax.js'])
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>إجمالي المستثمرين</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2" id="total-investors">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-users ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>المستثمرون النشطون</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2" id="active-investors">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>استثمار بالمهام</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2" id="task-based-investors">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-settings ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>استثمار عام</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2" id="general-based-investors">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-chart-bar ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">قائمة المستثمرين</h5>
            @can('save_investors')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#investorModal" id="btn-add-investor">
                    <i class="ti ti-plus me-1"></i> إضافة مستثمر جديد
                </button>
            @endcan
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-investors table border-top">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المستثمر</th>
                        <th>البريد الإلكتروني</th>
                        <th>رصيد المحفظة</th>
                        <th>نوع العقد</th>
                        <th>العمولة</th>
                        <th>الحالة</th>
                        <th>إعادة تعيين كلمة المرور</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal لإضافة/تعديل مستثمر --}}
    @can('save_investors')
        <div class="modal fade" id="investorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-transparent">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-sm-5 pb-5">
                        <div class="text-center mb-4">
                            <h3 class="mb-2" id="modalTitle">إضافة مستثمر جديد</h3>
                            <p class="text-muted">إدارة بيانات المستثمر وإعدادات العقد النشط</p>
                        </div>
                        <form id="investorForm" class="row g-3" onsubmit="return false">
                            <input type="hidden" name="id" id="investor_id">
                            
                            <div class="col-12"><h5 class="border-bottom pb-2">البيانات الأساسية</h5></div>
                            <div class="col-md-6">
                                <label class="form-label">الاسم الكامل</label>
                                <input type="text" name="name" class="form-control" placeholder="أدخل الاسم">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control" placeholder="example@mail.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رقم الجوال</label>
                                <div class="input-group">
                                    <select name="phone_code" class="form-select" style="max-width: 100px;">
                                        <option value="+966">🇸🇦 +966</option>
                                        <option value="+971">🇦🇪 +971</option>
                                        <option value="+20">🇪🇬 +20</option>
                                        <option value="+1">🇺🇸 +1</option>
                                    </select>
                                    <input type="text" name="phone" class="form-control" placeholder="5xxxxxxxx">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">كلمة المرور</label>
                                <input type="password" name="password" class="form-control" placeholder="••••••••">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">تأكيد كلمة المرور</label>
                                <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••">
                            </div>
                            <div class="col-12">
                                <small class="text-muted" id="pass-hint">اتركه فارغاً عند التعديل لعدم التغيير</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الحالة</label>
                                <select name="status" class="form-select">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                    <option value="pending">قيد المراجعة</option>
                                </select>
                            </div>

                            <div class="col-12 mt-4"><h5 class="border-bottom pb-2">إعدادات العقد الاستثماري</h5></div>
                            <div class="col-md-6">
                                <label class="form-label">نوع الاستثمار</label>
                                <select name="contract_type" class="form-select" id="contract_type">
                                    <option value="task_investment">استثمار بالمهام (دفع يدوي لكل مهمة)</option>
                                    <option value="general_investment">استثمار عام (عمولات تراكمية دورية)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">نوع العمولة</label>
                                <select name="commission_type" class="form-select">
                                    <option value="percentage">نسبة مئوية (%)</option>
                                    <option value="fixed">مبلغ ثابت (ر.س)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">قيمة العمولة</label>
                                <input type="number" step="0.01" name="commission_value" class="form-control" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تاريخ بدء العقد</label>
                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تاريخ نهاية العقد (اختياري)</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تصفية حسب العملاء (اختياري)</label>
                                <select name="customer_ids[]" class="form-select select2" multiple id="customer_ids">
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">اتركه فارغاً لجميع العملاء</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الحد الأدنى لعمولة المنصة (اختياري)</label>
                                <input type="number" step="0.01" name="min_commission_threshold" class="form-control" placeholder="0.00">
                                <small class="text-muted">لا يُسمح بالاستثمار إذا كانت عمولة المنصة أقل من هذا الرقم</small>
                            </div>

                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">حفظ البيانات</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">إلغاء</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- Modal لعرض تفاصيل المستثمر --}}
    <div class="modal fade" id="viewInvestorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-5 pb-5">
                    <div class="text-center mb-4">
                        <h3 class="mb-2">تفاصيل المستثمر</h3>
                        <p class="text-muted">البيانات الشخصية والمالية الكاملة</p>
                    </div>
                    
                    <div class="row g-4">
                        <!-- Personal Info -->
                        <div class="col-md-6">
                            <div class="info-container">
                                <h5 class="border-bottom pb-2">البيانات الشخصية</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><span class="fw-medium me-1">الاسم:</span> <span id="view-name"></span></li>
                                    <li class="mb-2"><span class="fw-medium me-1">البريد:</span> <span id="view-email"></span></li>
                                    <li class="mb-2"><span class="fw-medium me-1">الجوال:</span> <span id="view-phone"></span></li>
                                    <li class="mb-2"><span class="fw-medium me-1">الحالة:</span> <span id="view-status"></span></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Wallets Info -->
                        <div class="col-md-6">
                            <div class="info-container">
                                <h5 class="border-bottom pb-2">المحافظ المالية</h5>
                                <div class="d-flex align-items-center mb-3 p-2 border rounded bg-light">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-wallet"></i></span>
                                    </div>
                                    <div>
                                        <small class="d-block text-muted">محفظة الاستثمار (الرصيد المتاح)</small>
                                        <h5 class="mb-0 text-primary" id="view-invest-balance">0.00 ر.س</h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center p-2 border rounded bg-light">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-label-success"><i class="ti ti-coins"></i></span>
                                    </div>
                                    <div>
                                        <small class="d-block text-muted">محفظة العمولات (الرصيد الشخصي)</small>
                                        <h5 class="mb-0 text-success" id="view-commission-balance">0.00 ر.س</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contract Info -->
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mt-2">العقد النشط حالياً</h5>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>النوع</th>
                                            <th>العمولة</th>
                                            <th>تاريخ البدء</th>
                                            <th>تاريخ الانتهاء</th>
                                            <th>الحد الأدنى</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td id="view-contract-type"></td>
                                            <td id="view-contract-commission"></td>
                                            <td id="view-contract-start"></td>
                                            <td id="view-contract-end"></td>
                                            <td id="view-contract-min"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
