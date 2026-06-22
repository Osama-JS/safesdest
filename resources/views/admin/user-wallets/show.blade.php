@extends('layouts/layoutMaster')

@section('title', __('User Wallet') . ' - ' . $user->name)

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
    @vite(['resources/js/admin/user-wallets.js'])
@endsection

@section('content')
    <!-- User Info -->
    <div class="row g-6 mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-4">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-user ti-lg"></i>
                            </span>
                        </div>
                        <div>
                            <h4 class="mb-1">{{ $user->name }}</h4>
                            <p class="mb-0 text-muted">{{ $user->email }}</p>
                            <small class="text-muted">{{ __('User ID') }}: #{{ $user->id }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('admin.user-wallets.show', $user->id) }}" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="from_date" class="form-label">من تاريخ</label>
                            <input type="date" class="form-control" id="from_date" name="from_date" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="to_date" class="form-label">إلى تاريخ</label>
                            <input type="date" class="form-control" id="to_date" name="to_date" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-filter me-1"></i> تصفية
                            </button>
                            <a href="{{ route('admin.user-wallets.show', $user->id) }}" class="btn btn-outline-secondary">
                                إعادة ضبط
                            </a>
                            <a href="{{ route('admin.user-wallets.export', ['userId' => $user->id, 'from_date' => request('from_date'), 'to_date' => request('to_date')]) }}" class="btn btn-success ms-auto">
                                <i class="ti ti-file-spreadsheet me-1"></i> تصدير Excel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Statistics -->
    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Current Balance') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($balance, 2) }}</h4>
                                <small class="text-muted">{{ __('SAR') }}</small>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-{{ $balance >= 0 ? 'success' : 'danger' }}">
                                <i class="ti ti-wallet ti-26px"></i>
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
                            <span class="text-heading">{{ __('Total Credit') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($credit, 2) }}</h4>
                                <small class="text-muted">{{ __('SAR') }}</small>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-trending-up ti-26px"></i>
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
                            <span class="text-heading">{{ __('Total Debit') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($debit, 2) }}</h4>
                                <small class="text-muted">{{ __('SAR') }}</small>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-trending-down ti-26px"></i>
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
                            <span class="text-heading">{{ __('Debt Ceiling') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($wallet->debt_ceiling, 2) }}</h4>
                                <small class="text-muted">{{ __('SAR') }}</small>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-shield-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($isInvestor)
    <!-- Investor-specific wallet stats -->
    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-xl-4">
            <div class="card border border-success">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading fw-medium">{{ __('Withdrawable Balance') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-success" id="withdrawableBalanceDisplay">{{ number_format($withdrawableBalance ?? 0, 2) }}</h4>
                                <small class="text-muted">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-muted">
                                @if($activeContract && $activeContract->contract_type === 'task_investment')
                                    {{ __('Settled task commissions + manual deposits − withdrawals and reinvestment') }}
                                @elseif($activeContract && $activeContract->contract_type === 'general_investment')
                                    {{ __('Total profits available for withdrawal or reinvestment') }}
                                @else
                                    {{ __('Profits available for withdrawal or reinvestment') }}
                                @endif
                            </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-cash ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card border border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading fw-medium">{{ __('Investment Wallet Balance') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-warning" id="investmentWalletBalanceDisplay">{{ number_format($investmentWalletBalance, 2) }}</h4>
                                <small class="text-muted">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-muted">
                                @if($hasInvestmentWallet)
                                    <a href="{{ route('admin.investors.invest-wallet', $user->id) }}" class="text-primary">{{ __('View Investment Wallet') }}</a>
                                @else
                                    <span class="text-danger">{{ __('Investment wallet not created yet') }}</span>
                                @endif
                            </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-wallet ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if($activeContract)
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading fw-medium">{{ __('Investment Contract Type') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2">
                                    {{ $activeContract->contract_type === 'task_investment' ? __('Task-based investment') : __('General investment') }}
                                </h5>
                            </div>
                            <small class="text-muted">
                                {{ __('Commission') }}: {{ $activeContract->commission_value }}{{ $activeContract->commission_type === 'percentage' ? '%' : ' ' . __('SAR') }}
                            </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-file-invoice ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Wallet Transactions -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">
                <i class="tf-icons ti ti-list me-2 fs-3 text-white bg-primary rounded p-1"></i>
                {{ __('Wallet Transactions') }}
            </h5>
            <button class="add-transaction btn btn-primary waves-effect waves-light mt-5 mx-2" data-bs-toggle="modal"
                data-bs-target="#transactionModal">
                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block"> {{ __('Add Transaction') }}</span>
            </button>
            <button class="clear-wallet btn btn-danger waves-effect waves-light mt-5 mx-2" id="clearWalletBtn">
                <i class="ti ti-trash me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block"> {{ __('Clear Wallet') }}</span>
            </button>
            @if($isInvestor)
                <button class="btn btn-info waves-effect waves-light mt-5 mx-2" data-bs-toggle="modal"
                    data-bs-target="#manualCommissionModal">
                    <i class="ti ti-settings me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> {{ __('Manual Calculation') }}</span>
                </button>
                @if($activeContract && $activeContract->contract_type === 'general_investment')
                <button class="btn btn-success waves-effect waves-light mt-5 mx-2" id="calculateGeneralBtn">
                    <i class="ti ti-calculator me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> {{ __('Calculate General Commissions') }}</span>
                </button>
                @elseif($activeContract && $activeContract->contract_type === 'task_investment')
                <button class="btn btn-success waves-effect waves-light mt-5 mx-2" id="calculateTasksBtn">
                    <i class="ti ti-calculator me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> احتساب عمولات المهام الممولة</span>
                </button>
                @endif
                @if(($withdrawableBalance ?? 0) > 0)
                <button class="btn btn-primary waves-effect waves-light mt-5 mx-2" data-bs-toggle="modal"
                    data-bs-target="#reinvestProfitsModal">
                    <i class="ti ti-refresh me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> {{ __('Reinvest Investor Profits') }}</span>
                </button>
                @endif
                @if($activeContract)
                <a href="{{ route('admin.user-wallets.tasks-funding', $user->id) }}" class="btn btn-secondary waves-effect waves-light mt-5 mx-2">
                    <i class="ti ti-cash me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> تمويل المهام (للمستثمر)</span>
                </a>
                @endif
                @if((isset($duplicateCommissions) && $duplicateCommissions->isNotEmpty()) || (isset($negativeCommissions) && $negativeCommissions->isNotEmpty()))
                    <button class="btn btn-danger waves-effect waves-light mt-5 mx-2" data-bs-toggle="modal" data-bs-target="#checkErrorsModal">
                        <i class="ti ti-alert-triangle me-0 me-sm-1 ti-xs"></i>
                        <span class="d-none d-sm-inline-block"> {{ __('Check for Errors') }}</span>
                    </button>
                @else
                    <button class="btn btn-outline-danger waves-effect waves-light mt-5 mx-2" onclick="Swal.fire('{{ __('No Errors') }}', 'لا توجد عمولات مكررة أو أخطاء في الخصم العكسي.', 'success')">
                        <i class="ti ti-shield-check me-0 me-sm-1 ti-xs"></i>
                        <span class="d-none d-sm-inline-block"> {{ __('Check for Errors') }}</span>
                    </button>
                @endif
            @endif
            @if($isBroker)
                <button class="btn btn-warning waves-effect waves-light mt-5 mx-2" id="calculateBrokerBtn">
                    <i class="ti ti-user-check me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> احتساب عمولات الوسيط (مضارب) </span>
                </button>
            @endif
            @if(!$isInvestor)
                <button class="btn btn-dark waves-effect waves-light mt-5 mx-2" id="calculateTruckBrokerBtn">
                    <i class="ti ti-truck me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> احتساب عمولات وسيط الشاحنات </span>
                </button>
            @endif
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-transactions table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>{{ __('#') }}</th>
                        <th>{{ __('amount') }}</th>
                        <th>{{ __('description') }}</th>
                        <th>{{ __('task id') }}</th>
                        <th>{{ __('user') }}</th>
                        <th>{{ __('created at') }}</th>
                        <th>{{ __('action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div class="modal fade " id="transactionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add New Transaction') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"></button>
                </div>
                <form class="add-new-transaction pt-0 form_submit" method="POST"
                    action="{{ route('admin.user-wallets.addTransaction') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top mb-6">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <!-- Hidden wallet_id -->
                                        <input type="hidden" name="user" id="wallet_id" value="{{ $user->id }}">
                                        <span class="user-error text-danger text-error"></span>

                                        <input type="hidden" name="id" id="trans_id">

                                        <!-- Amount -->
                                        <div class="mb-4">
                                            <label class="form-label" for="amount">* {{ __('Amount') }}</label>
                                            <input type="number" name="amount" class="form-control" id="trans_amount"
                                                placeholder="{{ __('Enter the amount') }}" step="0.01"
                                                min="0">
                                            <span class="amount-error text-danger text-error"></span>
                                        </div>

                                        <!-- Transaction Type -->
                                        <div class="mb-4">
                                            <label class="form-label d-block">* {{ __('Transaction Type') }}</label>
                                            <div class="row">

                                                <div class="col-12">
                                                    <input type="radio" class="btn-check" name="type"
                                                        id="debit" value="debit" autocomplete="off" required
                                                        readonly checked>
                                                    <label class="btn btn-warning w-100 py-2 btn-debit" for="debit">
                                                        <i class="ti ti-circle-minus me-1"></i> {{ __('Debit') }}
                                                    </label>
                                                </div>
                                            </div>
                                            <span class="type-error text-danger text-error"></span>
                                        </div>

                                        <!-- Payment Method -->
                                        <div class="mb-4" id="paymentMethodSection">
                                            <label class="form-label" for="payment_method">* {{ __('Payment Method') }}</label>
                                            <select name="payment_method" id="payment_method" class="form-select">
                                                <option value="manual">يدوي (تسوية خارج النظام)</option>
                                                <option value="hyperpay">تحويل بنكي آلي (HyperPay Payout)</option>
                                            </select>
                                            <div class="form-text mt-2 text-warning d-none" id="hyperPayWarning">
                                                <i class="ti ti-alert-triangle me-1"></i> سيتم تحويل المبلغ بشكل فوري إلى الحساب البنكي الخاص بهذا المستخدم باستخدام بوابة HyperPay.
                                                <div class="mt-3 p-3 bg-light border rounded text-dark">
                                                    <strong>البيانات البنكية المسجلة للمضارب:</strong>
                                                    <ul class="list-unstyled mt-2 mb-0">
                                                        <li><strong>اسم البنك:</strong> {{ $user->bank_name ?? 'غير محدد' }}</li>
                                                        <li><strong>رقم الآيبان:</strong> <span dir="ltr">{{ $user->iban_number ?? 'غير محدد' }}</span></li>
                                                        <li><strong>رمز السويفت:</strong> {{ $user->bic_code ?? 'غير محدد' }}</li>
                                                        <li><strong>اسم المستفيد:</strong> {{ $user->beneficiary_name ?? 'غير محدد' }}</li>
                                                    </ul>
                                                    @if(!$user->iban_number || !$user->bic_code || !$user->beneficiary_name)
                                                        <div class="alert alert-danger mt-2 mb-0 py-2">
                                                            <i class="ti ti-ban me-1"></i> لا يمكن إتمام التحويل الآلي لعدم اكتمال البيانات البنكية الأساسية (الآيبان، رمز السويفت، أو اسم المستفيد).
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>




                                        <!-- Maturity Time (Hidden by default) -->
                                        <div class="mb-4" id="maturity-time-group" style="display: none;">
                                            <label class="form-label" for="maturity">{{ __('Maturity Time') }}</label>
                                            <input type="datetime-local" name="maturity" class="form-control"
                                                id="trans_maturity">
                                            <span class="maturity-error text-danger text-error"></span>
                                        </div>

                                        <!-- Description -->
                                        <div class="mb-4">
                                            <label class="form-label" for="description">* {{ __('Description') }}</label>
                                            <textarea name="description" class="form-control" id="trans_description" rows="3"
                                                placeholder="{{ __('Optional notes...') }}"></textarea>
                                            <span class="description-error text-danger text-error"></span>
                                        </div>
                                        <div class="mb-6">

                                            <div class="form-group mb-3">
                                                <label for="image" class="form-label">
                                                    <i class="fas fa-file-upload me-1"></i>
                                                    {{ __('Upload File') }}
                                                </label>
                                                <input type="file" name="image" class="form-control" id="image"
                                                    accept=".jpeg,.jpg,.png,.webp,.pdf,.doc,.docx,.txt,.csv">
                                                <div class="form-text text-muted mt-1">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        {{ __('Supported formats: Images (JPEG, PNG, WebP), Documents (PDF). Max size: 10MB') }}
                                                    </small>
                                                </div>
                                                <span class="image-error text-danger text-error"></span>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    @if($isInvestor && ($withdrawableBalance ?? 0) > 0)
    <div class="modal fade" id="reinvestProfitsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-refresh text-primary me-2"></i>
                        {{ __('Reinvest Investor Profits') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="reinvestProfitsForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info small">
                            <i class="ti ti-info-circle me-1"></i>
                            {{ __('Amount will be deducted from withdrawable balance in commission wallet and added to investment wallet as new capital.') }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium" for="reinvest_amount">{{ __('Top up amount (SAR)') }}</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="reinvest_amount" name="amount"
                                    step="0.01" min="0.01"
                                    max="{{ number_format($withdrawableBalance, 2, '.', '') }}"
                                    value="{{ number_format($withdrawableBalance, 2, '.', '') }}" required>
                                <button type="button" class="btn btn-label-secondary" id="reinvestMaxBtn">{{ __('Maximum') }}</button>
                            </div>
                            <small class="text-muted">{{ __('Withdrawable balance') }}: {{ number_format($withdrawableBalance, 2) }} {{ __('SAR') }}</small>
                            <div class="text-danger small mt-1 d-none" id="reinvest_amount_error"></div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-medium" for="reinvest_notes">{{ __('Notes (optional)') }}</label>
                            <textarea class="form-control" id="reinvest_notes" name="notes" rows="2"
                                maxlength="255" placeholder="{{ __('Reason or reference for this operation...') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="reinvestSubmitBtn">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                            {{ __('Confirm Reinvestment Admin') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
    @endif

    {{-- Check for errors modal --}}
    @if((isset($duplicateCommissions) && $duplicateCommissions->isNotEmpty()) || (isset($negativeCommissions) && $negativeCommissions->isNotEmpty()))
    <div class="modal fade" id="checkErrorsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center text-danger">
                        <i class="ti ti-alert-triangle me-2 ti-md"></i>
                        {{ __('Commission Errors Found') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    @if(isset($duplicateCommissions) && $duplicateCommissions->isNotEmpty())
                    <h6 class="text-danger fw-bold mb-2">العمولات المكررة (Duplicate Commissions)</h6>
                    <div class="alert alert-warning mb-4">
                        <i class="ti ti-info-circle me-2"></i>
                        {{ __('The following tasks have multiple commissions recorded in your wallet. Please review and delete the duplicates. You can only keep one commission per task.') }}
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Task #') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($duplicateCommissions as $taskId => $transactionsGroup)
                                    @foreach($transactionsGroup as $index => $transaction)
                                        <tr class="duplicate-row" data-task-id="{{ $taskId }}">
                                            <td class="align-middle text-center fw-bold text-primary">
                                                #{{ $taskId }}
                                                @if($transaction->task)
                                                    @if($transaction->task->investor_id == $user->id)
                                                        <br><span class="badge bg-label-info mt-1" style="font-size: 0.7rem;">ممولة منه</span>
                                                    @else
                                                        <br><span class="badge bg-label-secondary mt-1" style="font-size: 0.7rem;">مستثمر عام</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>{{ number_format($transaction->amount, 2) }} {{ __('SAR') }}</td>
                                            <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <form action="{{ route('admin.user-wallets.destroyDuplicateCommission', $transaction->id) }}" method="POST" class="d-inline delete-duplicate-form" data-task-id="{{ $taskId }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-danger delete-duplicate-btn">
                                                        <i class="ti ti-trash"></i> {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @if(isset($negativeCommissions) && $negativeCommissions->isNotEmpty())
                    <h6 class="text-danger fw-bold mb-2 mt-4">أخطاء الخصم العكسي (Negative / Mismatched Commissions)</h6>
                    <div class="alert alert-danger mb-4">
                        <i class="ti ti-info-circle me-2"></i>
                        يوجد خلل في المهام التالية حيث أن إجمالي الخصم (Debit) يفوق الإيداع (Credit) مما سبب خللاً في الرصيد. يرجى مراجعتها وحذف الخصم الزائد لتصحيح الرصيد.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Task #') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>النوع</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($negativeCommissions as $taskId => $transactionsGroup)
                                    @foreach($transactionsGroup as $index => $transaction)
                                        <tr class="duplicate-row" data-task-id="{{ $taskId }}">
                                            <td class="align-middle text-center fw-bold text-primary">
                                                #{{ $taskId }}
                                                @if($transaction->task)
                                                    @if($transaction->task->investor_id == $user->id)
                                                        <br><span class="badge bg-label-info mt-1" style="font-size: 0.7rem;">ممولة منه</span>
                                                    @else
                                                        <br><span class="badge bg-label-secondary mt-1" style="font-size: 0.7rem;">مستثمر عام</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="{{ $transaction->transaction_type == 'credit' ? 'text-success' : 'text-danger' }} fw-bold">
                                                {{ $transaction->transaction_type == 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} {{ __('SAR') }}
                                            </td>
                                            <td>
                                                <span class="badge {{ $transaction->transaction_type == 'credit' ? 'bg-label-success' : 'bg-label-danger' }}">
                                                    {{ $transaction->transaction_type == 'credit' ? 'إيداع' : 'خصم' }}
                                                </span>
                                            </td>
                                            <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                @if($transaction->transaction_type == 'debit')
                                                <form action="{{ route('admin.user-wallets.destroyDuplicateCommission', $transaction->id) }}" method="POST" class="d-inline delete-duplicate-form" data-task-id="{{ $taskId }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-danger delete-duplicate-btn">
                                                        <i class="ti ti-trash"></i> {{ __('Delete') }}
                                                    </button>
                                                </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">{{ __('View the File') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('close') }}"></button>
                </div>
                <div class="modal-body text-center" id="modalContent">
                    <img id="modalImage" src="" class="img-fluid rounded shadow" alt="{{ __('image') }}" />
                </div>
            </div>
        </div>
    </div>


    <script>
        const baseUrl = '{{ url('/') }}/';
        const userId = {{ $user->id }};
        const transactionsDataUrl = '{!! route('admin.user-wallets.getTransactions', ['userId' => $user->id, 'from_date' => request('from_date'), 'to_date' => request('to_date')]) !!}';
        const addTransactionUrl = '{{ route('admin.user-wallets.addTransaction') }}';
        const withdrawalUrl = '{{ route('admin.user-wallets.withdrawal') }}';
        const clearWalletUrl = '{{ route('admin.user-wallets.clear', $user->id) }}';
        const searchTaskUrl = '{{ route('admin.user-wallets.search-task', $user->id) }}';
        const calculateManualUrl = '{{ route('admin.user-wallets.calculate-manual', $user->id) }}';
        const calculateGeneralUrl = '{{ route('admin.user-wallets.calculate-general', $user->id) }}';
        const calculateTasksUrl = '{{ route('admin.user-wallets.calculate-tasks', $user->id) }}';
        const calculateBrokerUrl = '{{ route('admin.user-wallets.calculate-broker', $user->id) }}';
        const calculateTruckBrokerUrl = '{{ route('admin.user-wallets.calculate-truck-broker', $user->id) }}';
        const reinvestProfitsUrl = '{{ route('admin.user-wallets.reinvest-profits', $user->id) }}';
        const isInvestor = {{ $isInvestor ? 'true' : 'false' }};
        const withdrawableBalance = {{ $isInvestor ? ($withdrawableBalance ?? 0) : 0 }};
        const currentBalance = {{ $wallet->balance }};
        const debtCeiling = {{ $wallet->debt_ceiling }};
        const maxWithdrawal = {{ $wallet->balance + $wallet->debt_ceiling }};

        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethod = document.getElementById('payment_method');
            const hyperPayWarning = document.getElementById('hyperPayWarning');

            if (paymentMethod) {
                paymentMethod.addEventListener('change', function() {
                    if (this.value === 'hyperpay') {
                        hyperPayWarning.classList.remove('d-none');
                    } else {
                        hyperPayWarning.classList.add('d-none');
                    }
                });
            }

            // AJAX Delete for Duplicate Commissions
            const deleteDuplicateBtns = document.querySelectorAll('.delete-duplicate-btn');
            deleteDuplicateBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const formElement = this.closest('form');
                    const actionUrl = formElement.getAttribute('action');
                    const taskId = formElement.getAttribute('data-task-id');
                    const tr = formElement.closest('tr');

                    Swal.fire({
                        title: '{{ __("Are you sure?") }}',
                        text: '{{ __("You will not be able to revert this!") }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '{{ __("Yes, delete it!") }}',
                        cancelButtonText: '{{ __("Cancel") }}',
                        customClass: {
                            confirmButton: 'btn btn-primary me-1',
                            cancelButton: 'btn btn-label-secondary'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: actionUrl,
                                type: 'DELETE',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(response) {
                                    if (response.status === 1) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '{{ __("Deleted!") }}',
                                            text: response.success,
                                            customClass: {
                                                confirmButton: 'btn btn-success'
                                            }
                                        }).then(() => {
                                            // Remove the row from the table
                                            tr.remove();

                                            // Check remaining rows for this task
                                            const remainingRows = document.querySelectorAll('.duplicate-row[data-task-id="' + taskId + '"]');
                                            if (remainingRows.length === 1) {
                                                // If only 1 remains, it is no longer a duplicate
                                                remainingRows[0].remove();
                                            }

                                            // If no duplicates left in the table, close modal and reload to refresh UI
                                            if (document.querySelectorAll('.duplicate-row').length === 0) {
                                                $('#checkErrorsModal').modal('hide');
                                                location.reload();
                                            }
                                        });
                                    } else {
                                        Swal.fire({
                                            title: '{{ __("Error!") }}',
                                            text: response.error,
                                            icon: 'error',
                                            customClass: {
                                                confirmButton: 'btn btn-primary'
                                            }
                                        });
                                    }
                                },
                                error: function(xhr) {
                                    Swal.fire({
                                        title: '{{ __("Error!") }}',
                                        text: '{{ __("An error occurred while deleting the duplicate commission.") }}',
                                        icon: 'error',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        }
                                    });
                                }
                            });
                        }
                    });
                });
            });
        });
    </script>
    @include('admin.user-wallets.manual-commission-modal')
@endsection
