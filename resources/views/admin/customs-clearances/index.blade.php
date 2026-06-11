@extends('layouts/layoutMaster')

@section('title', __('Customs Clearance'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss')
    @vite(['resources/css/app.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')

    <script>
        const templateId = {{ $customs_clearance->value ?? 0 }}
    </script>
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/admin/customs-clearances/index.js'])
    @vite(['resources/js/spical.js'])
    <script>
        const navContent = document.querySelector('#navbar-custom-nav-container');
        const mobileContainer = document.querySelector('#mobile-custom-nav');
        const originalContent = navContent?.innerHTML;

        function moveCustomNav() {
            if (window.innerWidth < 1124) {
                // شاشة صغيرة، انقل المحتوى إلى الأسفل
                if (originalContent && mobileContainer && mobileContainer.innerHTML.trim() === '') {
                    mobileContainer.innerHTML = originalContent;
                    navContent.innerHTML = '';
                }
            } else {
                // شاشة كبيرة، أعد المحتوى إلى مكانه الأصلي
                if (originalContent && navContent && navContent.innerHTML.trim() === '') {
                    navContent.innerHTML = originalContent;
                    mobileContainer.innerHTML = '';
                }
            }
        }

        moveCustomNav(); // تنفيذ أولي
        window.addEventListener('resize', moveCustomNav); // تنفيذ عند تغيير حجم الشاشة
    </script>
@endsection
@section('customes-isactive')
    active
@endsection
@section('navbar-custom-nav')
    <!-- Filters Section -->
    <div class="d-flex flex-wrap align-items-center gap-2 my-2">
        <!-- Status Filter -->


        <!-- Owner Type Filter -->
        <div>
            <select class="form-select" id="ownerTypeFilter">
                <option value="">{{ __('All Owners') }}</option>
                <option value="admin">{{ __('Admin') }}</option>
                <option value="customer">{{ __('Customer') }}</option>
            </select>
        </div>

        <!-- Date Range -->
        <div>
            <input type="text" id="dateRange" class="form-control" placeholder="{{ __('Select Date Range') }}">
        </div>

        <!-- Action Buttons -->
        <div>

            <button type="button" class="btn btn-icon" id="refreshTable" data-tooltip="refresh">
                <i class="ti ti-refresh me-1"></i>
            </button>
            <button type="button" class="btn btn-icon " id="clearFilters" title="clear">
                <i class="ti ti-x me-1"></i>
            </button>
        </div>
    </div>
@endsection
@section('content')
    <!-- خارج الـ navbar (أسفلها مباشرة) -->
    <div id="mobile-custom-nav" class="d-lg-none  z-1 card shadow mb-3 p-2" style="white-space: nowrap;">
    </div>
    <!-- /Search -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">
                <i class="tf-icons ti ti-clipboard-check me-2 fs-3 text-white bg-primary rounded p-1"></i>

                {{ __('Customs Clearance') }}
            </h5>
            <button class="add-new btn btn-primary waves-effect waves-light mt-5 mx-4" data-bs-toggle="modal"
                data-bs-target="#submitModal">
                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block"> {{ __('Add New Customs Clearance') }}</span>
            </button>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-customs-clearances table">
                <thead class="border">
                    <tr>
                        <th></th>
                        <th>{{ __('ID') }}</th>
                        <th>{{ __('Price') }}</th>
                        <th>{{ __('Owner') }}</th>
                        <th>{{ __('Clearance Agent') }}</th>
                        <th>{{ __('Offers') }}</th>
                        <th>{{ __('Public') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Closed') }}</th>
                        <th>{{ __('Payment') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>


    <div class="modal fade" id="submitModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add New Customs Clearance') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"></button>
                </div>
                <form class="pt-0 form_submit" method="POST" action="{{ route('admin.customs-clearances.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-7">
                                <input type="hidden" name="id" id="customs_clearance_id">

                                <div class="row">

                                    <!-- Owner Selection -->
                                    <div class="col-md-6 mb-3">
                                        <label for="owner_type" class="form-label">* {{ __('Owner Type') }}</label>
                                        <select name="owner_type" id="owner_type" class="form-select">
                                            <option value="admin">{{ __('Administrator') }}</option>
                                            <option value="customer">{{ __('Customer') }}</option>
                                        </select>
                                        <span class="owner_type-error text-danger text-error"></span>
                                    </div>

                                    <!-- Customer Selection (Hidden initially) -->
                                    <div class="col-md-6 mb-3" id="customer_select_div" style="display: none;">
                                        <label for="customer_id" class="form-label">* {{ __('Select Customer') }}</label>
                                        <select name="customer_id" id="customer_id" class="form-select select2">
                                            <option value="">{{ __('Select Customer') }}</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="customer_id-error text-danger text-error"></span>
                                    </div>
                                </div>


                                <div class="divider text-start">
                                    <div class="divider-text"><strong>{{ __('Customs Clearance Information') }}</strong></div>
                                </div>
                                <!-- Template Selection -->
                                <div class="col-12 mb-3">
                                    <label for="select-template" class="form-label">* {{ __('Select Template') }}</label>
                                    <select name="template" id="select-template" class="form-select">
                                        <option value="">{{ __('-- Select Template --') }}</option>
                                        @foreach ($templates as $template)
                                            <option value="{{ $template->id }}"
                                                {{ isset($customs_clearance) && $customs_clearance->value == $template->id ? 'selected' : '' }}>
                                                {{ $template->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="template-error text-danger text-error"></span>
                                </div>

                                <!-- Dynamic Form Fields -->
                                <div class="col-12">
                                    <div id="additional-form" class="row">
                                        <!-- Dynamic fields will be loaded here -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5  ">
                                <div class="divider text-start">
                                    <div class="divider-text"><strong>{{ __('Customize your order') }}</strong></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="customs-price"
                                        class="form-label">{{ __('What price would you like to offer') }}</label>
                                    <input type="number" name="price" id="customs-price" class="form-control"
                                        placeholder="0.00" min="0" step="any">
                                    <span class="price-error text-danger text-error"></span>
                                </div>
                                <div id="price-includes-info" class="alert alert-info mb-3 " role="alert"
                                    style="max-width: 600px; display: none;">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="included" id="customs-included"
                                            class="form-check-input" value="1">
                                        <label class="form-check-label fw-bold" for="customs-included">
                                            {{ __('Including VAT and service charge') }}
                                        </label>
                                    </div>

                                    <p class="small text-muted">
                                        {{ __('If you do not select this option, both the VAT and the service commission will be calculated on top of the amount you display.') }}
                                    </p>
                                    <span class="included-error text-danger mt-2"></span>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_public"
                                            id="customs-is-public" value="1" checked>
                                        <label class="form-check-label" for="customs-is-public">
                                            <i class="ti ti-eye me-1"></i>{{ __('Set it Public') }}
                                        </label>
                                        <small class="form-text text-muted d-block">
                                            <i class="ti ti-info-circle me-1"></i>
                                            {{ __('Set its visibility to Public to allow the broker to view it.') }}
                                        </small>
                                    </div>
                                    <span class="is_public-error text-danger text-error"></span>
                                </div>

                                <div class="mb-3">
                                    <label for="customs-notes" class="form-label">{{ __('Notes') }}</label>
                                    <textarea name="notes" id="customs-notes" class="form-control" placeholder="{{ __('Enter Your Notes Or Description') }}"
                                        cols="30" rows="5"></textarea>
                                    <span class="notes-error text-danger text-error"></span>

                                </div>


                            </div>



                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade " id="assignModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignTitle">{{ __('Assign Customs Clearance') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST"
                    action="{{ route('customs-clearances.assign') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <input type="hidden" name="id" id="task-assign-id">
                                        <span class="id-error text-danger text-error"></span>
                                        <div class="mb-4">
                                            <label class="form-label" for="broker">* {{ __('Broker') }}</label>
                                            <select name="broker" id="clearance-broker"
                                                class="broker-driver form-select"></select>
                                            <span class="broker-error text-danger text-error"></span>
                                        </div>
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <label for="price" class="form-label">* {{ __('Price') }}
                                                    (SAR)</label>
                                                <input type="number" name="price" id="clearance-price"
                                                    class="form-control" step="any" min="0"
                                                    placeholder="0.00">
                                                <span class="price-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="commission" class="form-label">*
                                                    {{ __('Service Commission') }}
                                                    (SAR)</label>
                                                <input type="number" name="commission" id="clearance-commission"
                                                    class="form-control" step="any" min="0"
                                                    placeholder="0.00">
                                                <span class="commission-error text-danger text-error"></span>
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

    <div class="modal fade " id="paymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentTitle">{{ __('Payment for Customs Clearance') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 payment_submit payment_form" method="POST"
                    action="{{ route('payment.initiate') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <input type="hidden" name="id" id="task-payment-id">
                                        <input type="hidden" name="total" id="task-payment-total">
                                        <p>{{ __('You Need to Pay: ') }}</p>
                                        <h4 id="pay-price"> </h4>
                                        <span class="id-error text-danger text-error"></span>
                                        <div class="mb-4">
                                            <label class="form-label" for="task-payment-method">*
                                                {{ __('Payment Method') }}</label>
                                            <select name="payment_method" id="task-payment-method" class="form-select">
                                                <option value="hyperpay_mada">{{ __('Mada') }}</option>
                                                <option value="credit">{{ __('Credit Card (Visa/Mastercard)') }}</option>
                                                <option value="banking">{{ __('Bank transfer') }}</option>
                                                <option value="wallet" id="wallet-option">{{ __('Use your Wallet') }}
                                                </option>
                                            </select>
                                            <span class="payment_method-error text-danger text-error"></span>
                                        </div>
                                        <div class="mb-4" id="receipt-section" style="display: none">
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="receipt_number">*
                                                    {{ __('Receipt Number') }}</label>
                                                <input type="text" name="receipt_number" id="receipt_number"
                                                    class="form-control" placeholder="{{ __('Receipt Number') }}">
                                                <span class="receipt_number-error text-danger text-error"></span>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="receipt_image">*
                                                    {{ __('Receipt Image') }}</label>
                                                <input type="file" name="receipt_image" id="receipt_image"
                                                    class="form-control">
                                                <span class="receipt_image-error text-danger text-error"></span>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="receipt_image">*
                                                    {{ __('Receipt Note') }}</label>
                                                <textarea name="note" id="receipt_note" cols="30" rows="5" class="form-control"></textarea>

                                                <span class="receipt_image-error text-danger text-error"></span>
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

    <div class="modal fade " id="checkPaymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignTitle">{{ __('Check Payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 payment_submit payment_form" method="POST"
                    action="{{ route('payment.initiate') }}">
                    @csrf
                    <div class="modal-body">
                        <div id="checkPaymentContainer">

                        </div>

                    </div>

                </form>

            </div>
        </div>
    </div>

    @include('admin.customs-clearances.form-modal')

@endsection
