@extends('layouts/layoutMaster')

@section('title', 'محفظة المضاربة - ' . $user->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    <script>
        const investorId = {{ $user->id }};
        const transactionsDataUrl = '{{ route('admin.investors.invest-wallet.getTransactions', $user->id) }}';
        const addTransactionUrl = '{{ route('admin.investors.invest-wallet.addTransaction') }}';
    </script>
    @vite(['resources/js/admin/investor-wallets.js'])
    @vite(['resources/js/admin/investor-wallets.js'])
@endsection

@section('content')
    <!-- User Info -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar avatar-xl me-4 shadow-lg border border-2 border-white rounded-circle bg-white p-1">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            <i class="ti ti-user ti-lg"></i>
                        </span>
                    </div>
                    <div>
                        <h4 id="investorName" class="mb-1 text-white fw-bold">{{ $user->name }}</h4>
                        <p class="mb-0 opacity-75">{{ $user->email }}</p>
                        <span class="badge bg-white text-primary mt-2">رقم المضارب: #{{ $user->id }}</span>
                    </div>
                    <div class="ms-auto d-none d-md-block">
                        <a href="{{ route('admin.investors.index') }}" class="btn btn-outline-white btn-sm">
                            <i class="ti ti-arrow-right me-1"></i> العودة لقائمة المضاربين
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-body position-relative">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-success rounded me-3">
                            <i class="ti ti-wallet ti-md"></i>
                        </div>
                        <span class="text-muted fw-medium">الرصيد الحالي المتاح</span>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="mb-0 fw-bold text-success">{{ number_format($balance, 2) }}</h3>
                        <span class="ms-2 text-muted">ر.س</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-info rounded me-3">
                            <i class="ti ti-trending-up ti-md"></i>
                        </div>
                        <span class="text-muted fw-medium">إجمالي رأس المال</span>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="mb-0 fw-bold text-info">{{ number_format($credit, 2) }}</h3>
                        <span class="ms-2 text-muted">ر.س</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-warning rounded me-3">
                            <i class="ti ti-arrow-back-up ti-md"></i>
                        </div>
                        <span class="text-muted fw-medium">إجمالي استعادة الاستثمار</span>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="mb-0 fw-bold text-warning">{{ number_format($returned_capital, 2) }}</h3>
                        <span class="ms-2 text-muted">ر.س</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-danger rounded me-3">
                            <i class="ti ti-trending-down ti-md"></i>
                        </div>
                        <span class="text-muted fw-medium">إجمالي السحوبات / التمويل</span>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="mb-0 fw-bold text-danger">{{ number_format($debit, 2) }}</h3>
                        <span class="ms-2 text-muted">ر.س</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Transactions -->
    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title mb-0 fw-bold">سجل العمليات المالية (محفظة المضاربة)</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
                <i class="ti ti-plus me-1"></i> إضافة عملية جديدة
            </button>
        </div>
        <div class="card-datatable table-responsive">
            <table id="investorTransactionsTable" class="datatables-transactions table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المبلغ</th>
                        <th>النوع</th>
                        <th>البيان / الوصف</th>
                        <th>رقم المهمة</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Transaction Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">{{ __('Add New Transaction') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="transactionForm" class="pt-0" onsubmit="return false" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="transaction_id">
                        <input type="hidden" name="user" value="{{ $user->id }}">
                        
                        <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
                            <span class="alert-icon text-primary me-2">
                                <i class="ti ti-info-circle ti-xs"></i>
                            </span>
                            <span>{{ __('This transaction will increase the investor available balance.') }}</span>
                        </div>

                        <!-- Amount -->
                        <div class="mb-4">
                            <label class="form-label" for="amount">* {{ __('Amount') }}</label>
                            <input type="number" name="amount" class="form-control" placeholder="{{ __('Enter the amount') }}" step="0.01" min="0.01" required>
                        </div>

                        <!-- Transaction Type -->
                        <div class="mb-4">
                            <label class="form-label d-block">* {{ __('Transaction Type') }}</label>
                            <div class="row">
                                <div class="col-12">
                                    <div class="btn btn-success w-100 py-2">
                                        <i class="ti ti-circle-plus me-1"></i> {{ __('Credit / Charging') }}
                                    </div>
                                    <input type="hidden" name="type" value="credit">
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label" for="description">* {{ __('Description / Notes') }}</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="{{ __('Enter transaction details...') }}" required></textarea>
                        </div>

                        <!-- Attachment -->
                        <div class="mb-0">
                            <label class="form-label" for="attachment">{{ __('Upload Receipt / Attachment') }}</label>
                            <input type="file" name="attachment" id="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text text-muted mt-1">
                                <small>{{ __('Supported formats: JPEG, PNG, PDF. Max size: 2MB') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغاء</button>
                        <button type="submit" class="btn btn-primary btn-submit">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                            {{ __('Submit Transaction') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image/File Modal -->
    <div class="modal fade" id="fileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('View Attachment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="modalFileContent">
                    <!-- Dynamic content will be injected here -->
                </div>
            </div>
        </div>
    </div>
@endsection
