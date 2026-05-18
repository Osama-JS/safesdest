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
                        <form id="investorForm" class="row g-3 form_submit" onsubmit="return false" method="POST" action="{{ route('admin.investors.store') }}" enctype="multipart/form-data">
                            <input type="hidden" name="id" id="investor_id">
                            
                            <div class="nav-align-top mb-6">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-main" aria-controls="navs-main"
                                            aria-selected="true">
                                            <i class="tf-icons ti ti-grid-dots ti-sm me-1"></i> الأساسية
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-bank" aria-controls="navs-bank"
                                            aria-selected="false">
                                            <i class="tf-icons ti ti-building-bank ti-sm me-1"></i> البنكية
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-additional" aria-controls="navs-additional"
                                            aria-selected="false">
                                            <i class="tf-icons ti ti-file-plus ti-sm me-1"></i> إضافية
                                        </button>
                                    </li>
                                </ul>
                                <div class="tab-content border-0 p-0 pt-4">
                                    <div class="tab-pane fade show active" id="navs-main" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="border-bottom pb-2">البيانات الأساسية</h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label">الاسم الكامل</label>
                                                <input type="text" name="name" class="form-control" placeholder="أدخل الاسم">
                                                <span class="name-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">البريد الإلكتروني</label>
                                                <input type="email" name="email" class="form-control" placeholder="example@mail.com">
                                                <span class="email-error text-danger text-error"></span>
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
                                                <span class="phone-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">كلمة المرور</label>
                                                <input type="password" name="password" class="form-control" placeholder="••••••••">
                                                <span class="password-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">تأكيد كلمة المرور</label>
                                                <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••">
                                                <span class="password_confirmation-error text-danger text-error"></span>
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
                                                <span class="status-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-12 mt-4"><h5 class="border-bottom pb-2">إعدادات العقد الاستثماري</h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label">نوع الاستثمار</label>
                                                <select name="contract_type" class="form-select" id="contract_type">
                                                    <option value="task_investment">استثمار بالمهام (دفع يدوي لكل مهمة)</option>
                                                    <option value="general_investment">استثمار عام (عمولات تراكمية دورية)</option>
                                                </select>
                                                <span class="contract_type-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">نوع العمولة</label>
                                                <select name="commission_type" class="form-select">
                                                    <option value="percentage">نسبة مئوية (%)</option>
                                                    <option value="fixed">مبلغ ثابت (ر.س)</option>
                                                </select>
                                                <span class="commission_type-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">قيمة العمولة</label>
                                                <input type="number" step="0.01" name="commission_value" class="form-control" placeholder="0.00">
                                                <span class="commission_value-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">تاريخ بدء العقد</label>
                                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                                                <span class="start_date-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">تاريخ نهاية العقد (اختياري)</label>
                                                <input type="date" name="end_date" class="form-control">
                                                <span class="end_date-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">تصفية حسب العملاء (اختياري)</label>
                                                <select name="customer_ids[]" class="form-select select2" multiple id="customer_ids">
                                                    @foreach($customers as $customer)
                                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">اتركه فارغاً لجميع العملاء</small>
                                                <span class="customer_ids-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">الحد الأدنى لعمولة المنصة (اختياري)</label>
                                                <input type="number" step="0.01" name="min_commission_threshold" class="form-control" placeholder="0.00">
                                                <small class="text-muted">لا يُسمح بالاستثمار إذا كانت عمولة المنصة أقل من هذا الرقم</small>
                                                <span class="min_commission_threshold-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-12 mt-4"><h5 class="border-bottom pb-2">إعدادات عمولة الوسيط (اختياري)</h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label">الوسيط (Broker)</label>
                                                <select name="broker_id" class="form-select select2" id="broker_id">
                                                    <option value="">-- بدون وسيط --</option>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                                    @endforeach
                                                </select>
                                                <span class="broker_id-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">مصدر خصم عمولة الوسيط</label>
                                                <select name="broker_commission_source" class="form-select">
                                                    <option value="investor_commission">من عمولة أرباح المستثمر نفسها</option>
                                                    <option value="task_commission">من إجمالي عمولة المهمة (المنصة تتحملها)</option>
                                                </select>
                                                <span class="broker_commission_source-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">طريقة احتساب عمولة الوسيط</label>
                                                <select name="broker_commission_type" class="form-select">
                                                    <option value="percentage">نسبة مئوية (%)</option>
                                                    <option value="fixed">مبلغ ثابت (ر.س)</option>
                                                </select>
                                                <span class="broker_commission_type-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">قيمة عمولة الوسيط</label>
                                                <input type="number" step="0.01" name="broker_commission_value" class="form-control" placeholder="0.00">
                                                <span class="broker_commission_value-error text-danger text-error"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="navs-bank" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="border-bottom pb-2">البيانات البنكية</h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-name">اسم البنك</label>
                                                <select id="user-bank-name" name="bank_name" class="form-select">
                                                    <option value="">إختر البنك</option>
                                                    <option value="البنك الأهلي السعودي">البنك الأهلي السعودي (SNB)</option>
                                                    <option value="مصرف الراجحي">مصرف الراجحي (Al Rajhi)</option>
                                                    <option value="بنك الرياض">بنك الرياض (Riyad Bank)</option>
                                                    <option value="البنك السعودي الأول">البنك السعودي الأول (SAB)</option>
                                                    <option value="بنك البلاد">بنك البلاد (Albilad)</option>
                                                    <option value="مصرف الإنماء">مصرف الإنماء (Alinma)</option>
                                                    <option value="البنك السعودي للاستثمار">البنك السعودي للاستثمار (SAIB)</option>
                                                    <option value="البنك العربي الوطني">البنك العربي الوطني (ANB)</option>
                                                    <option value="بنك الجزيرة">بنك الجزيرة (Aljazira)</option>
                                                    <option value="البنك السعودي الفرنسي">البنك السعودي الفرنسي (BSF)</option>
                                                    <option value="other">أخرى</option>
                                                </select>
                                                <span class="bank_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6" id="user-custom-bank-field" style="display: none;">
                                                <label class="form-label" for="user-custom-bank-name">اسم البنك المخصص</label>
                                                <input type="text" id="user-custom-bank-name" name="custom_bank_name" class="form-control" placeholder="أدخل اسم البنك">
                                                <span class="custom_bank_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-account-number">رقم الحساب</label>
                                                <input type="text" id="user-account-number" name="account_number" class="form-control" placeholder="أدخل رقم الحساب">
                                                <span class="account_number-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-iban-number">رقم الآيبان (IBAN)</label>
                                                <input type="text" id="user-iban-number" name="iban_number" class="form-control" placeholder="SA00 0000 0000 0000 0000 0000" dir="ltr">
                                                <span class="iban_number-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bic-code">رمز السويفت (BIC/SWIFT Code)</label>
                                                <input type="text" id="user-bic-code" name="bic_code" class="form-control" placeholder="أدخل رمز BIC">
                                                <span class="bic_code-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-beneficiary-name">اسم المستفيد (الرسمي)</label>
                                                <input type="text" id="user-beneficiary-name" name="beneficiary_name" class="form-control" placeholder="أدخل اسم المستفيد كما في البنك">
                                                <span class="beneficiary_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-address1">عنوان البنك 1</label>
                                                <input type="text" id="user-bank-address1" name="bank_address1" class="form-control" placeholder="العنوان السطر 1">
                                                <span class="bank_address1-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-address2">عنوان البنك 2</label>
                                                <input type="text" id="user-bank-address2" name="bank_address2" class="form-control" placeholder="العنوان السطر 2">
                                                <span class="bank_address2-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-city">المدينة</label>
                                                <input type="text" id="user-bank-city" name="bank_city" class="form-control" placeholder="أدخل المدينة">
                                                <span class="bank_city-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-country">الدولة</label>
                                                <select id="user-bank-country" name="bank_country" class="form-select">
                                                    <option value="السعودية">🇸🇦 المملكة العربية السعودية</option>
                                                    <option value="الإمارات">🇦🇪 الإمارات العربية المتحدة</option>
                                                    <option value="الكويت">🇰🇼 الكويت</option>
                                                    <option value="عمان">🇴🇲 عمان</option>
                                                    <option value="البحرين">🇧🇭 البحرين</option>
                                                    <option value="قطر">🇶🇦 قطر</option>
                                                    <option value="مصر">🇪🇬 مصر</option>
                                                    <option value="الأردن">🇯🇴 الأردن</option>
                                                    <option value="أخرى">🌐 أخرى</option>
                                                </select>
                                                <span class="bank_country-error text-danger text-error"></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="tab-pane fade" id="navs-additional" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">اختر القالب</label>
                                                <select name="template" id="select-template" class="form-select">
                                                    <option value="">-- اختر القالب --</option>
                                                    @foreach ($templates as $template)
                                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div id="additional-form" class="row mt-4">
                                                <!-- سيتم تعبئتها ديناميكياً -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">حفظ البيانات</button>
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

    {{-- Modal لربط المهام التاريخية --}}
    <div class="modal fade" id="linkTasksModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-5 pb-5">
                    <div class="text-center mb-4">
                        <h3 class="mb-2">ربط المهام التاريخية</h3>
                        <p class="text-muted">اختر المهام التي قام المستثمر بتمويلها سابقاً لربطها بنظام الاستثمار</p>
                        <h5 id="investor-name-modal" class="text-primary mt-2"></h5>
                    </div>
                    
                    <div class="alert alert-warning mb-4">
                        <div class="d-flex">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <div>
                                <strong>ملاحظة:</strong> سيتم خصم قيمة المهام المحددة من محفظة الاستثمار وتسجيل عمولة المستثمر (إذا كان العقد بالمهام).
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover" id="historicalTasksTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th><input type="checkbox" class="form-check-input" id="selectAllTasks"></th>
                                    <th>رقم المهمة</th>
                                    <th>العميل</th>
                                    <th>السائق</th>
                                    <th>الشاحنة</th>
                                    <th>المسار (من - إلى)</th>
                                    <th>المبلغ الإجمالي</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody id="historicalTasksBody">
                                <!-- سيتم تعبئتها عبر AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <div class="col-12 text-center mt-4">
                        <button type="button" class="btn btn-primary me-sm-3 me-1" id="btnLinkTasks">ربط المهام المختارة</button>
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
