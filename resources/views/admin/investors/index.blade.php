@extends('layouts/layoutMaster')

@section('title', __('Investor Management'))

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
                            <span>{{ __('Total Investors') }}</span>
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
                            <span>{{ __('Active Investors') }}</span>
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
                            <span>{{ __('Task-based Investment') }}</span>
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
                            <span>{{ __('General Investment') }}</span>
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
            <h5 class="card-title mb-0">{{ __('Investors List') }}</h5>
            @can('save_investors')
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#investorModal" id="btn-add-investor">
                    <i class="ti ti-plus me-1"></i> {{ __('Add New Investor') }}
                </button>
            @endcan
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-investors table border-top">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Investor') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Wallet Balance') }}</th>
                        <th>{{ __('Contract Type') }}</th>
                        <th>{{ __('Commission') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Reset Password') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal لإضافة/تعديل مضارب --}}
    @can('save_investors')
        <div class="modal fade" id="investorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-transparent">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-sm-5 pb-5">
                        <div class="text-center mb-4">
                            <h3 class="mb-2" id="modalTitle">{{ __('Add New Investor') }}</h3>
                            <p class="text-muted">{{ __('Manage investor data and active contract settings') }}</p>
                        </div>
                        <form id="investorForm" class="row g-3 form_submit" onsubmit="return false" method="POST" action="{{ route('admin.investors.store') }}" enctype="multipart/form-data">
                            <input type="hidden" name="id" id="investor_id">
                            
                            <div class="nav-align-top mb-6">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-main" aria-controls="navs-main"
                                            aria-selected="true">
                                            <i class="tf-icons ti ti-grid-dots ti-sm me-1"></i> {{ __('Main') }}
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-bank" aria-controls="navs-bank"
                                            aria-selected="false">
                                            <i class="tf-icons ti ti-building-bank ti-sm me-1"></i> {{ __('Banking') }}
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-additional" aria-controls="navs-additional"
                                            aria-selected="false">
                                            <i class="tf-icons ti ti-file-plus ti-sm me-1"></i> {{ __('Additional') }}
                                        </button>
                                    </li>
                                </ul>
                                <div class="tab-content border-0 p-0 pt-4">
                                    <div class="tab-pane fade show active" id="navs-main" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="border-bottom pb-2">{{ __('Basic Information') }}</h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Full Name') }}</label>
                                                <input type="text" name="name" class="form-control" placeholder="{{ __('Enter name') }}">
                                                <span class="name-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Email') }}</label>
                                                <input type="email" name="email" class="form-control" placeholder="example@mail.com">
                                                <span class="email-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Phone Number') }}</label>
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
                                                <label class="form-label">{{ __('Password') }}</label>
                                                <input type="password" name="password" class="form-control" placeholder="••••••••">
                                                <span class="password-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">{{ __('Confirm Password') }}</label>
                                                <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••">
                                                <span class="password_confirmation-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-12">
                                                <small class="text-muted" id="pass-hint">{{ __('Leave blank when editing to keep unchanged') }}</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Status') }}</label>
                                                <select name="status" class="form-select">
                                                    <option value="active">{{ __('Active') }}</option>
                                                    <option value="inactive">{{ __('Inactive') }}</option>
                                                    <option value="pending">{{ __('Pending Review') }}</option>
                                                </select>
                                                <span class="status-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-12 mt-4"><h5 class="border-bottom pb-2">{{ __('Investment Contract Settings') }}</h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Investment Type') }}</label>
                                                <select name="contract_type" class="form-select" id="contract_type">
                                                    <option value="task_investment">{{ __('Task investment (manual payment per task)') }}</option>
                                                    <option value="general_investment">{{ __('General investment (periodic cumulative commissions)') }}</option>
                                                </select>
                                                <span class="contract_type-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">{{ __('Commission Type') }}</label>
                                                <select name="commission_type" class="form-select">
                                                    <option value="percentage">{{ __('Percentage (%)') }}</option>
                                                    <option value="fixed">{{ __('Fixed Amount (SAR)') }}</option>
                                                </select>
                                                <span class="commission_type-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">{{ __('Commission Value') }}</label>
                                                <input type="number" step="0.01" name="commission_value" class="form-control" placeholder="0.00">
                                                <span class="commission_value-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Contract Start Date') }}</label>
                                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                                                <span class="start_date-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Contract End Date (optional)') }}</label>
                                                <input type="date" name="end_date" class="form-control">
                                                <span class="end_date-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Filter by Customers (optional)') }}</label>
                                                <select name="customer_ids[]" class="form-select select2" multiple id="customer_ids">
                                                    @foreach($customers as $customer)
                                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">{{ __('Leave empty for all customers') }}</small>
                                                <span class="customer_ids-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Minimum Platform Commission (optional)') }}</label>
                                                <input type="number" step="0.01" name="min_commission_threshold" class="form-control" placeholder="0.00">
                                                <small class="text-muted">{{ __('Tasks below this platform commission are not eligible') }}</small>
                                                <span class="min_commission_threshold-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-12 mt-4"><h5 class="border-bottom pb-2">{{ __('Broker Settings (optional)') }}</h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Broker') }}</label>
                                                <select name="broker_id" class="form-select select2" id="broker_id">
                                                    <option value="">{{ __('No Broker') }}</option>
                                                    @foreach($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                                    @endforeach
                                                </select>
                                                <span class="broker_id-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Broker Commission Source') }}</label>
                                                <select name="broker_commission_source" class="form-select">
                                                    <option value="investor_commission">{{ __('From investor profit commission') }}</option>
                                                    <option value="task_commission">{{ __('From total task commission (platform bears)') }}</option>
                                                </select>
                                                <span class="broker_commission_source-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Broker Commission Calculation') }}</label>
                                                <select name="broker_commission_type" class="form-select">
                                                    <option value="percentage">{{ __('Percentage (%)') }}</option>
                                                    <option value="fixed">{{ __('Fixed Amount (SAR)') }}</option>
                                                </select>
                                                <span class="broker_commission_type-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">{{ __('Broker Commission Value') }}</label>
                                                <input type="number" step="0.01" name="broker_commission_value" class="form-control" placeholder="0.00">
                                                <span class="broker_commission_value-error text-danger text-error"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="navs-bank" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="border-bottom pb-2">{{ __('Banking Information') }}</h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-name">{{ __('Bank Name') }}</label>
                                                <select id="user-bank-name" name="bank_name" class="form-select">
                                                    <option value="">{{ __('Select Bank') }}</option>
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
                                                    <option value="other">{{ __('Other') }}</option>
                                                </select>
                                                <span class="bank_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6" id="user-custom-bank-field" style="display: none;">
                                                <label class="form-label" for="user-custom-bank-name">{{ __('Custom Bank Name') }}</label>
                                                <input type="text" id="user-custom-bank-name" name="custom_bank_name" class="form-control" placeholder="{{ __('Enter bank name') }}">
                                                <span class="custom_bank_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-account-number">{{ __('Account Number') }}</label>
                                                <input type="text" id="user-account-number" name="account_number" class="form-control" placeholder="{{ __('Enter account number') }}">
                                                <span class="account_number-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-iban-number">{{ __('IBAN Number') }}</label>
                                                <input type="text" id="user-iban-number" name="iban_number" class="form-control" placeholder="SA00 0000 0000 0000 0000 0000" dir="ltr">
                                                <span class="iban_number-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bic-code">{{ __('BIC/SWIFT Code') }}</label>
                                                <input type="text" id="user-bic-code" name="bic_code" class="form-control" placeholder="{{ __('Enter BIC code') }}">
                                                <span class="bic_code-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-beneficiary-name">{{ __('Beneficiary Name (official)') }}</label>
                                                <input type="text" id="user-beneficiary-name" name="beneficiary_name" class="form-control" placeholder="{{ __('Enter beneficiary name as in bank') }}">
                                                <span class="beneficiary_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-address1">{{ __('Bank Address 1') }}</label>
                                                <input type="text" id="user-bank-address1" name="bank_address1" class="form-control" placeholder="{{ __('Bank Address 1') }}">
                                                <span class="bank_address1-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-address2">{{ __('Bank Address 2') }}</label>
                                                <input type="text" id="user-bank-address2" name="bank_address2" class="form-control" placeholder="{{ __('Bank Address 2') }}">
                                                <span class="bank_address2-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-city">{{ __('City') }}</label>
                                                <input type="text" id="user-bank-city" name="bank_city" class="form-control" placeholder="{{ __('Enter city') }}">
                                                <span class="bank_city-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-country">{{ __('Country') }}</label>
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
                                                <label class="form-label">{{ __('Select Template') }}</label>
                                                <select name="template" id="select-template" class="form-select">
                                                    <option value="">{{ __('Choose template') }}</option>
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
                                <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">{{ __('Save Data') }}</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">{{ __('Cancel') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- Modal لعرض تفاصيل المضارب --}}
    <div class="modal fade" id="viewInvestorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-5 pb-5">
                    <div class="text-center mb-4">
                        <h3 class="mb-2">{{ __('Investor Details') }}</h3>
                        <p class="text-muted">{{ __('Full personal and financial data') }}</p>
                    </div>
                    
                    <div class="row g-4">
                        <!-- Personal Info -->
                        <div class="col-md-6">
                            <div class="info-container">
                                <h5 class="border-bottom pb-2">{{ __('Personal Information') }}</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><span class="fw-medium me-1">{{ __('Name') }}:</span> <span id="view-name"></span></li>
                                    <li class="mb-2"><span class="fw-medium me-1">{{ __('Email') }}:</span> <span id="view-email"></span></li>
                                    <li class="mb-2"><span class="fw-medium me-1">{{ __('Mobile') }}:</span> <span id="view-phone"></span></li>
                                    <li class="mb-2"><span class="fw-medium me-1">{{ __('Status') }}:</span> <span id="view-status"></span></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Wallets Info -->
                        <div class="col-md-6">
                            <div class="info-container">
                                <h5 class="border-bottom pb-2">{{ __('Financial Wallets') }}</h5>
                                <div class="d-flex align-items-center mb-3 p-2 border rounded bg-light">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-wallet"></i></span>
                                    </div>
                                    <div>
                                        <small class="d-block text-muted">{{ __('Investment Wallet (available balance)') }}</small>
                                        <h5 class="mb-0 text-primary" id="view-invest-balance">0.00 {{ __('SAR') }}</h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center p-2 border rounded bg-light">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-label-success"><i class="ti ti-coins"></i></span>
                                    </div>
                                    <div>
                                        <small class="d-block text-muted">{{ __('Commission Wallet (personal balance)') }}</small>
                                        <h5 class="mb-0 text-success" id="view-commission-balance">0.00 {{ __('SAR') }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contract Info -->
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mt-2">{{ __('Active Contract') }}</h5>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Type') }}</th>
                                            <th>{{ __('Commission') }}</th>
                                            <th>{{ __('Start Date') }}</th>
                                            <th>{{ __('End Date') }}</th>
                                            <th>{{ __('Minimum') }}</th>
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
                        <h3 class="mb-2">{{ __('Link Historical Tasks') }}</h3>
                        <p class="text-muted">{{ __('Select tasks previously funded by the investor') }}</p>
                        <h5 id="investor-name-modal" class="text-primary mt-2"></h5>
                    </div>
                    
                    <div class="alert alert-warning mb-4">
                        <div class="d-flex">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <div>
                                {{ __('Note: selected task amounts will be debited from the investment wallet and investor commission recorded (for task-based contracts).') }}
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover" id="historicalTasksTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th><input type="checkbox" class="form-check-input" id="selectAllTasks"></th>
                                    <th>{{ __('Task #') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Driver') }}</th>
                                    <th>{{ __('Truck') }}</th>
                                    <th>{{ __('Route (from - to)') }}</th>
                                    <th>{{ __('Total Amount') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                            </thead>
                            <tbody id="historicalTasksBody">
                                <!-- سيتم تعبئتها عبر AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <div class="col-12 text-center mt-4">
                        <button type="button" class="btn btn-primary me-sm-3 me-1" id="btnLinkTasks">{{ __('Link Selected Tasks') }}</button>
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
