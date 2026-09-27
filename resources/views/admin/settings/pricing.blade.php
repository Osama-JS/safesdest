@extends('layouts/layoutMaster')

@section('title', __('Pricing'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])

@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js'])
@endsection

@section('page-script')
    @vite(['resources/js/admin/pricing.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection

@section('content')
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item">
                <a href="{{ url('admin') }}"><i class="ti ti-home-2 me-1"></i>{{ __('الرئيسية') }}</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">{{ __('الإعدادات') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('طرق التسعير') }}</li>
        </ol>
    </nav>

    <!-- Hero Header Banner -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.02) 100%);">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center p-2">
                        <i class="ti ti-calculator fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h4 class="fw-bold mb-0 text-heading">{{ __('طرق وسياسات التسعير') }}</h4>
                            <span class="badge bg-label-primary rounded-pill px-3 py-1 fs-tiny fw-semibold">
                                <i class="ti ti-coin me-1"></i> {{ __('تسعير الرحلات والمهام') }}
                            </span>
                        </div>
                        <p class="text-muted mb-0">
                            {{ __('تحديد واحتساب تكاليف الرحلات والخدمات (حسب المسافة، الوزن، المناطق، أو التسعير الثابت) وإدارتها.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom py-3">
            <h5 class="card-title mb-0 fw-bold">
                <i class="ti ti-list-details text-primary me-2"></i>{{ __('طرق التسعير المتاحة بالنظام') }}
            </h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-users table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add New Method') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('settings.pricing.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">

                            <div class="nav-align-top  mb-6">

                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <input type="hidden" name="id" id="pricing_id">
                                        <span class="id-error text-danger text-error"></span>

                                        <div class="mb-4">
                                            <label class="form-label" for="pricing-name">* {{ __('Method Name') }}</label>
                                            <input type="text" name="name" class="form-control" id="pricing-name"
                                                placeholder="{{ __('enter the Method name') }}" />
                                            <span class="name-error text-danger text-error"></span>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label" for="pricing-description">
                                                {{ __('Description') }} </label>
                                            <textarea name="description" id="pricing-description" class="form-control" cols="30" rows="3"></textarea>
                                            <span class="description-error text-danger text-error"></span>
                                        </div>
                                    </div>
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
@endsection
