@extends('layouts/layoutMaster')

@section('title', __('Investment Wallet - :name', ['name' => $user->name]))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    <script>
        const investorId = {{ $user->id }};
        const transactionsDataUrl = '{!! route('admin.investors.invest-wallet.getTransactions', ['userId' => $user->id, 'from_date' => request('from_date'), 'to_date' => request('to_date')]) !!}';
        const addTransactionUrl = '{{ route('admin.investors.invest-wallet.addTransaction') }}';
        const convertTransactionUrl = '{{ url('admin/investors/invest-wallet/transaction/convert') }}';
        const cancelInvestmentUrl = '{{ url('admin/investors/invest-wallet/transaction/cancel-investment') }}';
        const isOsama = {{ auth()->user()->email === 'osama.samomy@gmail.com' ? 'true' : 'false' }};
    </script>
    @vite(['resources/js/admin/investor-wallets.js'])
    <script>
        // ===== الدوال التي تُستدعى من onclick في HTML - يجب أن تكون عالمية =====

        window.checkFunding = function() {
            $('#fundingCheckModal').modal('show');
            $('#fundingCheckLoading').removeClass('d-none');
            $('#fundingCheckResults').addClass('d-none').html('');
            $.ajax({
                url: baseUrl + 'admin/investors/{{ $user->id }}/invest-wallet/check-funding',
                type: 'GET',
                success: function(response) {
                    $('#fundingCheckLoading').addClass('d-none');
                    $('#fundingCheckResults').removeClass('d-none');
                    if (response.status === 1) {
                        let html = '';
                        if (response.anomalies.length === 0) {
                            html = '<div class="alert alert-success"><i class="ti ti-circle-check me-2"></i>لا توجد أي تعارضات.</div>';
                        } else {
                            html = '<div class="alert alert-danger"><i class="ti ti-alert-triangle me-2"></i>تم العثور على ' + response.anomalies.length + ' تعارض(ات).</div><div class="list-group">';
                            response.anomalies.forEach(function(anomaly, index) {
                                html += '<div class="list-group-item border-start border-4 border-danger mb-2 rounded">';
                                html += '<div class="d-flex w-100 justify-content-between mb-2"><h6 class="mb-0 text-danger fw-bold">تعارض #' + (index + 1) + '</h6>';
                                html += anomaly.type === 'task_without_transaction'
                                    ? '<span class="badge bg-label-warning">مهمة #' + anomaly.task_id + '</span>'
                                    : '<span class="badge bg-label-info">عملية #' + anomaly.transaction_id + '</span>';
                                html += '</div><p class="mb-3">' + anomaly.message + '</p><div class="d-flex gap-2">';
                                if (anomaly.type === 'task_without_transaction') {
                                    html += '<button class="btn btn-sm btn-outline-danger" onclick="fixAnomaly(\'' + anomaly.type + '\',\'unlink_task\',' + anomaly.task_id + ',null)"><i class="ti ti-unlink me-1"></i>فصل المهمة</button>';
                                    html += '<button class="btn btn-sm btn-primary" onclick="fixAnomaly(\'' + anomaly.type + '\',\'create_funding\',' + anomaly.task_id + ',null)"><i class="ti ti-plus me-1"></i>إنشاء تمويل</button>';
                                } else if (anomaly.type === 'transaction_without_task') {
                                    html += '<button class="btn btn-sm btn-outline-danger" onclick="fixAnomaly(\'' + anomaly.type + '\',\'delete_transaction\',' + anomaly.task_id + ',' + anomaly.transaction_id + ')"><i class="ti ti-trash me-1"></i>حذف العملية</button>';
                                    html += '<button class="btn btn-sm btn-primary" onclick="fixAnomaly(\'' + anomaly.type + '\',\'link_task\',' + anomaly.task_id + ',' + anomaly.transaction_id + ')"><i class="ti ti-link me-1"></i>ربط المهمة</button>';
                                }
                                html += '</div></div>';
                            });
                            html += '</div>';
                        }
                        $('#fundingCheckResults').html(html);
                    } else {
                        $('#fundingCheckResults').html('<div class="alert alert-danger">حدث خطأ: ' + response.error + '</div>');
                    }
                },
                error: function() {
                    $('#fundingCheckLoading').addClass('d-none');
                    $('#fundingCheckResults').removeClass('d-none').html('<div class="alert alert-danger">حدث خطأ بالاتصال.</div>');
                }
            });
        };

        window.fixAnomaly = function(anomalyType, fixAction, taskId, transactionId) {
            if (!confirm('هل أنت متأكد؟')) return;
            $('#fundingCheckLoading').removeClass('d-none');
            $('#fundingCheckResults').addClass('d-none');
            $.ajax({
                url: baseUrl + 'admin/investors/{{ $user->id }}/invest-wallet/fix-funding',
                type: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content'), anomaly_type: anomalyType, fix_action: fixAction, task_id: taskId, transaction_id: transactionId },
                success: function(response) {
                    if (response.status === 1) { alert(response.success); window.checkFunding(); }
                    else { alert('خطأ: ' + response.error); $('#fundingCheckLoading').addClass('d-none'); $('#fundingCheckResults').removeClass('d-none'); }
                },
                error: function() { alert('حدث خطأ بالاتصال.'); $('#fundingCheckLoading').addClass('d-none'); $('#fundingCheckResults').removeClass('d-none'); }
            });
        };

        window.openManualSettlementModal = function() {
            $('#manualSettlementAmount').val('');
            $('#manualSettlementNote').val('');
            $('#unsettledTasksTableBody').html('<tr><td colspan="5" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> جاري التحميل...</td></tr>');
            $('#manualSettlementTotal').text('0.00 ر.س');
            $('#manualSettlementError').addClass('d-none');
            $('#submitManualSettlementBtn').prop('disabled', true);
            $('#selectAllUnsettledTasks').prop('checked', false);
            $('#manualSettlementModal').modal('show');
            $.ajax({
                url: baseUrl + 'admin/investors/{{ $user->id }}/invest-wallet/unsettled-tasks',
                type: 'GET',
                success: function(response) {
                    if (response.status === 1) {
                        let html = '';
                        if (response.data.length === 0) {
                            html = '<tr><td colspan="5" class="text-center py-3 text-muted">لا توجد مهام معلقة للتسوية</td></tr>';
                        } else {
                            response.data.forEach(function(task) {
                                html += '<tr><td><input class="form-check-input task-checkbox" type="checkbox" value="' + task.id + '" data-price="' + task.total_price + '"></td>';
                                html += '<td>#' + task.id + '</td><td>' + task.customer_name + '</td><td>' + task.created_at + '</td>';
                                html += '<td class="fw-bold">' + parseFloat(task.total_price).toFixed(2) + '</td></tr>';
                            });
                        }
                        $('#unsettledTasksTableBody').html(html);
                        bindSettlementEvents();
                    } else {
                        $('#unsettledTasksTableBody').html('<tr><td colspan="5" class="text-center text-danger py-3">حدث خطأ: ' + response.error + '</td></tr>');
                    }
                },
                error: function() {
                    $('#unsettledTasksTableBody').html('<tr><td colspan="5" class="text-center text-danger py-3">حدث خطأ بالاتصال</td></tr>');
                }
            });
        };

        // ===== دوال داخلية + event listeners =====
        function validateManualSettlement() {
            let total = 0;
            $('.task-checkbox:checked').each(function() { total += parseFloat($(this).data('price')); });
            $('#manualSettlementTotal').text(total.toFixed(2) + ' ر.س');
            let entered = parseFloat($('#manualSettlementAmount').val()) || 0;
            if (entered > 0 && Math.abs(total - entered) < 0.01) {
                $('#submitManualSettlementBtn').prop('disabled', false);
                $('#manualSettlementError').addClass('d-none');
            } else {
                $('#submitManualSettlementBtn').prop('disabled', true);
                entered > 0 ? $('#manualSettlementError').removeClass('d-none') : $('#manualSettlementError').addClass('d-none');
            }
        }

        function bindSettlementEvents() {
            $(document).off('change keyup', '.task-checkbox, #manualSettlementAmount')
                       .on('change keyup',  '.task-checkbox, #manualSettlementAmount', validateManualSettlement);
            $('#selectAllUnsettledTasks').off('change').on('change', function() {
                $('.task-checkbox').prop('checked', this.checked);
                validateManualSettlement();
            });
        }

        window.addEventListener('load', function () {
            $(document).on('click', '#submitManualSettlementBtn', function() {
                let selectedTasks = [];
                $('.task-checkbox:checked').each(function() { selectedTasks.push($(this).val()); });
                if (selectedTasks.length === 0) { toastr.warning('يرجى تحديد مهمة واحدة على الأقل'); return; }
                let amount = parseFloat($('#manualSettlementAmount').val()) || 0;
                if (amount <= 0) { toastr.warning('يرجى إدخال مبلغ صحيح'); return; }
                let adminNote = $('#manualSettlementNote').val();
                let btn = $(this);
                btn.prop('disabled', true);
                btn.find('.spinner-border').removeClass('d-none');
                $.ajax({
                    url: baseUrl + 'admin/investors/{{ $user->id }}/invest-wallet/manual-settlement',
                    type: 'POST', dataType: 'json',
                    data: { _token: $('meta[name="csrf-token"]').attr('content'), amount: amount, task_ids: selectedTasks, admin_note: adminNote },
                    success: function(response) {
                        btn.find('.spinner-border').addClass('d-none');
                        if (response.status === 1) {
                            $('#manualSettlementModal').modal('hide');
                            toastr.options = { closeButton: true, progressBar: true, timeOut: 4000, positionClass: 'toast-top-center', preventDuplicates: true };
                            toastr.success(response.success || 'تمت التسوية بنجاح');
                            setTimeout(() => { window.location.reload(); }, 1800);
                        } else {
                            btn.prop('disabled', false); validateManualSettlement();
                            toastr.options = { closeButton: true, progressBar: true, timeOut: 7000, positionClass: 'toast-top-center', preventDuplicates: true };
                            toastr.error(response.error || 'حدث خطأ أثناء التسوية');
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false); btn.find('.spinner-border').addClass('d-none'); validateManualSettlement();
                        let errMsg = xhr.responseJSON ? (xhr.responseJSON.message || xhr.responseJSON.error || 'حدث خطأ') : 'حدث خطأ بالاتصال';
                        toastr.options = { closeButton: true, progressBar: true, timeOut: 7000, positionClass: 'toast-top-center', preventDuplicates: true };
                        toastr.error(errMsg);
                    }
                });
            });
        }); // end window.load
    </script>
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
                        <span class="badge bg-white text-primary mt-2">{{ __('Investor #:id', ['id' => $user->id]) }}</span>
                    </div>
                    <div class="ms-auto d-none d-md-block">
                        <a href="{{ route('admin.investors.index') }}" class="btn btn-outline-white btn-sm">
                            <i class="ti ti-arrow-right me-1"></i> {{ __('Back to Investors List') }}
                        </a>
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
                    <form action="{{ route('admin.investors.invest-wallet', $user->id) }}" method="GET" class="row g-3 align-items-end">
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
                            <a href="{{ route('admin.investors.invest-wallet', $user->id) }}" class="btn btn-outline-secondary">
                                إعادة ضبط
                            </a>
                            <a href="{{ route('admin.investors.invest-wallet.export', ['userId' => $user->id, 'from_date' => request('from_date'), 'to_date' => request('to_date')]) }}" class="btn btn-success ms-auto">
                                <i class="ti ti-file-spreadsheet me-1"></i> تصدير Excel
                            </a>
                        </div>
                    </form>
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
                        <span class="text-muted fw-medium">{{ __('Current Available Balance Label') }}</span>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="mb-0 fw-bold text-success">{{ number_format($balance, 2) }}</h3>
                        <span class="ms-2 text-muted">{{ __('SAR') }}</span>
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
                        <span class="text-muted fw-medium">{{ __('Total Capital Label') }}</span>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="mb-0 fw-bold text-info">{{ number_format($credit, 2) }}</h3>
                        <span class="ms-2 text-muted">{{ __('SAR') }}</span>
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
                        <span class="text-muted fw-medium">{{ __('Total Investment Returns') }}</span>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="mb-0 fw-bold text-warning">{{ number_format($returned_capital, 2) }}</h3>
                        <span class="ms-2 text-muted">{{ __('SAR') }}</span>
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
                        <span class="text-muted fw-medium">{{ __('Total withdrawals funding') }}</span>
                    </div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="mb-0 fw-bold text-danger">{{ number_format($debit, 2) }}</h3>
                        <span class="ms-2 text-muted">{{ __('SAR') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Transactions -->
    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title mb-0 fw-bold">{{ __('Wallet transactions log investor') }}</h5>
            <div>
                @if($user->activeInvestmentContract)
                <a href="{{ route('admin.user-wallets.tasks-funding', $user->id) }}" class="btn btn-secondary me-2">
                    <i class="ti ti-cash me-1"></i> تمويل المهام
                </a>
                @endif
                @if(auth()->user()->email === 'osama.samomy@gmail.com')
                <button class="btn btn-dark me-2" data-bs-toggle="modal" data-bs-target="#restorePaymentsModal" onclick="fetchMissingPayments()">
                    <i class="ti ti-history me-1"></i> استعادة الدفع
                </button>
                @endif
                <button class="btn btn-warning me-2" onclick="checkFunding()">
                    <i class="ti ti-search me-1"></i> فحص التمويل
                </button>
                @can('manual_investment_settlement')
                <button class="btn btn-success me-2" onclick="openManualSettlementModal()">
                    <i class="ti ti-cash-banknote me-1"></i> تسوية يدوية
                </button>
                @endcan
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
                    <i class="ti ti-plus me-1"></i> {{ __('Add new operation') }}
                </button>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table id="investorTransactionsTable" class="datatables-transactions table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Source Type') }}</th>
                        <th>{{ __('Statement description') }}</th>
                        <th>{{ __('Task ID') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Actions') }}</th>
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
                            <input type="number" name="amount" class="form-control"
                                placeholder="{{ __('Enter the amount') }}" step="0.01" min="0.01" required>
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
                            <textarea name="description" class="form-control" rows="3"
                                placeholder="{{ __('Enter transaction details...') }}" required></textarea>
                        </div>

                        <!-- Attachment -->
                        <div class="mb-0">
                            <label class="form-label" for="attachment">{{ __('Upload Receipt / Attachment') }}</label>
                            <input type="file" name="attachment" id="attachment" class="form-control"
                                accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text text-muted mt-1">
                                <small>{{ __('Supported formats: JPEG, PNG, PDF. Max size: 2MB') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary btn-submit">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"
                                aria-hidden="true"></span>
                            {{ __('Submit Transaction') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Convert Deposit To Refund Modal -->
    <div class="modal fade" id="convertTransactionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">{{ __('Convert deposit modal title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">{!! __('Convert deposit confirm') !!}</p>
                    <p class="text-muted">{{ __('Convert deposit note') }}</p>
                    <input type="hidden" id="convert_transaction_id">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Transaction reference') }}</label>
                        <div id="convertTransactionReference" class="fw-bold">-</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Amount') }}</label>
                        <div id="convertTransactionAmount" class="fw-bold text-success">-</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="convertTransactionPassword">{{ __('Password required for conversion') }}</label>
                        <input type="text" name="fakeusernameremembered" style="display:none" autocomplete="username"><input type="password" id="convertTransactionPassword" class="form-control"
                            placeholder="{{ __('Enter password') }}" autocomplete="new-password" required>
                        <div class="form-text text-muted">{{ __('Password required for conversion') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" id="confirmConvertTransaction" class="btn btn-warning">{{ __('Yes convert now') }}</button>
                </div>
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
    <!-- Funding Check Modal -->
    <div class="modal fade" id="fundingCheckModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">نتائج فحص التمويل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="fundingCheckLoading" class="text-center py-4">
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">جاري الفحص...</span>
                        </div>
                        <p class="mt-2">جاري فحص تعارضات التمويل...</p>
                    </div>
                    <div id="fundingCheckResults" class="d-none">
                        <!-- Dynamic Results -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Settlement Modal -->
    <div class="modal fade" id="manualSettlementModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title text-dark fw-bold">
                        <i class="ti ti-cash-banknote me-2 text-success"></i> تسوية استثمار يدوية من قبل الإدارة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body mt-2">
                    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                        <span class="alert-icon text-info me-2">
                            <i class="ti ti-info-circle ti-xs"></i>
                        </span>
                        <span>تستخدم هذه الميزة لإرجاع رأس مال المهام للمستثمر في حال تأخر العميل بالسداد. يجب أن يطابق المبلغ المدخل مجموع تكلفة المهام المحددة.</span>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="manualSettlementAmount">* المبلغ المراد تسويته (ر.س)</label>
                            <input type="number" id="manualSettlementAmount" class="form-control" placeholder="أدخل المبلغ..." step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="manualSettlementNote">ملاحظة الإدارة</label>
                            <input type="text" id="manualSettlementNote" class="form-control" placeholder="مثال: شكراً لك لقد تم تسوية مبلغ الاستثمار من قبل الإدارة...">
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">المهام الممولة غير المسواة:</h6>
                    <div class="table-responsive border rounded" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-hover table-sm">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 50px;">
                                        <input class="form-check-input" type="checkbox" id="selectAllUnsettledTasks">
                                    </th>
                                    <th>رقم المهمة</th>
                                    <th>صاحب المهمة (العميل)</th>
                                    <th>تاريخ التمويل</th>
                                    <th>التكلفة (ر.س)</th>
                                </tr>
                            </thead>
                            <tbody id="unsettledTasksTableBody">
                                <!-- Tasks will be loaded here via AJAX -->
                                <tr>
                                    <td colspan="5" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div> جاري التحميل...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3 p-3 bg-light rounded">
                        <span class="fw-bold">المجموع المحدد:</span>
                        <span id="manualSettlementTotal" class="fw-bold text-success fs-5">0.00 ر.س</span>
                    </div>
                    <div id="manualSettlementError" class="text-danger mt-2 d-none fw-bold small">
                        المبلغ المحدد لا يطابق المبلغ المدخل!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" id="submitManualSettlementBtn" class="btn btn-success" disabled>
                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                        حفظ التسوية
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Settlement Transaction Modal -->
    <div class="modal fade" id="deleteSettlementModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="ti ti-trash-off me-2"></i>
                        حذف معاملة تسوية
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-flex align-items-start mb-3">
                        <i class="ti ti-alert-triangle ti-lg me-2 mt-1 flex-shrink-0"></i>
                        <div>
                            <strong>تحذير:</strong> أنت على وشك حذف معاملة تسوية مرتبطة بمهمة من محفظة الاستثمار.
                            هذا الإجراء <strong>لا يمكن التراجع عنه</strong> وسيؤثر على رصيد المحفظة.
                        </div>
                    </div>
                    <input type="hidden" id="deleteSettlementId">
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="deleteSettlementPassword">
                            <i class="ti ti-lock me-1"></i>
                            كلمة المرور للتأكيد
                        </label>
                        <input type="text" name="fakeusernameremembered" style="display:none" autocomplete="username"><input type="password" id="deleteSettlementPassword" class="form-control"
                            placeholder="أدخل كلمة المرور" autocomplete="new-password">
                        <div class="form-text text-muted">مطلوبة لحماية العملية من التنفيذ غير المقصود.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" id="confirmDeleteSettlement" class="btn btn-danger">
                        <i class="ti ti-trash me-1"></i>
                        تأكيد الحذف
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Payments Modal -->
    @if(auth()->user()->email === 'osama.samomy@gmail.com')
    <div class="modal fade" id="restorePaymentsModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white"><i class="ti ti-history me-2"></i> استعادة مدفوعات هايبر باي المفقودة</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">بحث بالمبلغ</label>
                            <div class="input-group">
                                <input type="number" id="restorePaymentAmountFilter" class="form-control" placeholder="أدخل المبلغ للبحث...">
                                <button class="btn btn-outline-primary" type="button" onclick="fetchMissingPayments()">بحث</button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th># ID</th>
                                    <th>المبلغ</th>
                                    <th>تاريخ العملية</th>
                                    <th>المرجع (Checkout ID)</th>
                                    <th>الحالة</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody id="restorePaymentsTableBody">
                                <tr>
                                    <td colspan="6" class="text-center">جاري التحميل...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Restore Payment Password Modal -->
    <div class="modal fade" id="confirmRestorePaymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="ti ti-restore me-2"></i>
                        استعادة عملية الشحن
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center mt-3">
                    <p>أدخل كلمة المرور لتأكيد إضافة هذه العملية إلى المحفظة:</p>
                    <input type="hidden" id="restorePaymentIdInput">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium" for="restorePaymentPasswordInput">كلمة المرور</label>
                        <input type="text" name="fakeusernameremembered" style="display:none" autocomplete="username"><input type="password" id="restorePaymentPasswordInput" class="form-control" placeholder="كلمة المرور" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" id="submitRestorePaymentBtn" class="btn btn-primary">تأكيد الاستعادة</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Confirm Cancel Investment Modal -->
    <div class="modal fade" id="cancelInvestmentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="ti ti-trash-off me-2"></i>
                        إلغاء الاستثمار
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center mt-3">
                    <p>أدخل كلمة المرور لتأكيد الإلغاء. سيتم حذف عملية التمويل، فصل المهمة، وحذف جميع عمولات المستثمر والوسطاء المتعلقة بها.</p>
                    <input type="hidden" id="cancelInvestmentTransactionIdInput">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-medium" for="cancelInvestmentPasswordInput">كلمة المرور</label>
                        <input type="text" name="fakeusernameremembered" style="display:none" autocomplete="username"><input type="password" id="cancelInvestmentPasswordInput" class="form-control" placeholder="كلمة المرور" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">تراجع</button>
                    <button type="button" id="submitCancelInvestmentBtn" class="btn btn-danger">تأكيد الإلغاء</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection
