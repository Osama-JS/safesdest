@extends('layouts/layoutMaster')

@section('title', __('Teams'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/teams/teams.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-users-group me-2 fs-3 text-white bg-primary rounded p-1"></i>
                {{ __('Teams') }}
            </h5>
            <p>Organize your Manager into logical groups to efficiently manage your field operations. You may group them on
                the basis of location, geography, type of service and so on and so forth.</p>

        </div>
        <div class="row mb-3 p-3">
            <div class="col-md-12">
                @can('save_teams')
                    <button class="add-new btn btn-primary waves-effect waves-light mb-5 mx-4" data-bs-toggle="modal"
                        data-bs-target="#submitModal">
                        <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                        <span class="d-none d-sm-inline-block"> Add New Team</span>
                    </button>
                @endcan

                <input type="text" id="search-team" class="form-control " placeholder="🔍 Search Team">

            </div>

        </div>

    </div>

    <div class="container mt-5">
        <div id="teams-container" class="row ">

        </div>

        <div class="d-flex justify-content-center">
            <ul class="pagination" id="pagination">

            </ul>
        </div>
    </div>

    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add new Team') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('teams.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">

                            <div class="nav-align-top  mb-6">

                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <input type="hidden" name="id" id="team_id">
                                        <span class="id-error text-danger text-error"></span>

                                        <div class="mb-4">
                                            <label class="form-label" for="team-name">* {{ 'Team Name' }}</label>
                                            <input type="text" name="name" class="form-control" id="team-name"
                                                placeholder="{{ __('enter the team name') }}" />
                                            <span class="name-error text-danger text-error"></span>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label" for="team-address">* {{ 'Team Address' }}</label>
                                            <input type="text" name="address" class="form-control" id="team-address"
                                                placeholder="{{ __('enter the team address') }}" />
                                            <span class="address-error text-danger text-error"></span>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label" for="team-location_update">
                                                {{ 'Drivers Location Updated every' }} :</label>
                                            <input type="number" name="location_update" class="form-control" step="1"
                                                min="30" id="team-location_update"
                                                placeholder="{{ __('min time is 30 secund') }}" />
                                            <span class="location_update-error text-danger text-error"></span>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label" for="user-phone"> {{ __('Commission') }}</label>
                                            <div class="input-group">

                                                <select name="commission_type" id="team-commission-type"
                                                    class="form-select">
                                                    <option value="">{{ __('Select Commission Type') }}</option>
                                                    <option value="rate">{{ __('ٌRate') }}</option>
                                                    <option value="fixed">{{ __('Fixed Amount') }}</option>
                                                    <option value="subscription">{{ __('Subscription Monthly') }}</option>
                                                </select>
                                                <input type="number" name="commission" class="form-control" step="1"
                                                    id="team-commission" placeholder="{{ __('Commission Amount') }}" />
                                            </div>
                                            <span class="commission_type-error text-danger text-error"></span>
                                            <span class="commission-error text-danger text-error"></span>


                                        </div>



                                        <div class="mb-4">
                                            <label class="form-label" for="team-location_update">
                                                {{ 'Note' }} </label>
                                            <textarea name="note" id="team-note" class="form-control" cols="30" rows="3"></textarea>

                                            <span class="note-error text-danger text-error"></span>
                                        </div>

                                        <div class="mb-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_public"
                                                    id="team-is-public" value="1" checked>
                                                <label class="form-check-label" for="team-is-public">
                                                    <i class="ti ti-eye me-1"></i>{{ __('Public Team') }}
                                                </label>
                                                <small class="form-text text-muted d-block">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    {{ __('Public teams appear in driver registration form. Private teams are only visible to admins.') }}
                                                </small>
                                            </div>
                                            <span class="is_public-error text-danger text-error"></span>
                                        </div>

                                        <hr class="my-4">
                                        <h6 class="mb-4">{{ __('Bank Details') }}</h6>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label" for="team-beneficiary-name">{{ __('Beneficiary Name') }}</label>
                                                <input type="text" name="beneficiary_name" class="form-control" id="team-beneficiary-name" placeholder="{{ __('Enter beneficiary name') }}" />
                                                <span class="beneficiary_name-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label" for="team-bank-name">{{ __('Bank Name') }}</label>
                                                <select name="bank_name" id="team-bank-name" class="form-select">
                                                    <option value="">{{ __('Select Bank') }}</option>
                                                    <option value="البنك الأهلي السعودي">البنك الأهلي السعودي</option>
                                                    <option value="بنك الراجحي">بنك الراجحي</option>
                                                    <option value="بنك الرياض">بنك الرياض</option>
                                                    <option value="البنك السعودي للاستثمار">البنك السعودي للاستثمار</option>
                                                    <option value="البنك السعودي الفرنسي">البنك السعودي الفرنسي</option>
                                                    <option value="البنك السعودي البريطاني">البنك السعودي البريطاني (ساب)</option>
                                                    <option value="بنك العربي الوطني">بنك العربي الوطني</option>
                                                    <option value="بنك سامبا">بنك سامبا</option>
                                                    <option value="البنك الأول">البنك الأول</option>
                                                    <option value="بنك الجزيرة">بنك الجزيرة</option>
                                                    <option value="بنك الإنماء">بنك الإنماء</option>
                                                    <option value="البنك العربي">البنك العربي</option>
                                                    <option value="other">{{ __('Other') }}</option>
                                                </select>
                                                <span class="bank_name-error text-danger text-error"></span>
                                            </div>
                                        </div>

                                        <div class="row" id="team-custom-bank-field" style="display: none;">
                                            <div class="col-md-12 mb-4">
                                                <label class="form-label" for="team-custom-bank-name">{{ __('Custom Bank Name') }}</label>
                                                <input type="text" name="custom_bank_name" id="team-custom-bank-name" class="form-control" placeholder="{{ __('Enter bank name') }}">
                                                <span class="custom_bank_name-error text-danger text-error"></span>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label" for="team-account-number">{{ __('Account Number') }}</label>
                                                <input type="text" name="account_number" class="form-control" id="team-account-number" placeholder="{{ __('Enter account number') }}" />
                                                <span class="account_number-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label" for="team-iban-number">{{ __('IBAN Number') }}</label>
                                                <input type="text" name="iban_number" class="form-control" id="team-iban-number" placeholder="{{ __('Enter IBAN number') }}" />
                                                <span class="iban_number-error text-danger text-error"></span>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label" for="team-bic-code">{{ __('BIC Code (Bank Identifier)') }}</label>
                                                <input type="text" name="bic_code" class="form-control" id="team-bic-code" placeholder="{{ __('Enter BIC code') }}" />
                                                <span class="bic_code-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label" for="team-bank-city">{{ __('Bank City') }}</label>
                                                <input type="text" name="bank_city" class="form-control" id="team-bank-city" placeholder="{{ __('Enter bank city') }}" />
                                                <span class="bank_city-error text-danger text-error"></span>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label" for="team-bank-country">{{ __('Bank Country') }}</label>
                                                <select name="bank_country" id="team-bank-country" class="form-select">
                                                    <option value="SA" selected>Saudi Arabia (SA)</option>
                                                    <option value="AE">United Arab Emirates (AE)</option>
                                                    <option value="EG">Egypt (EG)</option>
                                                </select>
                                                <span class="bank_country-error text-danger text-error"></span>
                                            </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label" for="team-bank-address1">{{ __('Bank Address 1') }}</label>
                                                <input type="text" name="bank_address1" class="form-control" id="team-bank-address1" placeholder="{{ __('Enter bank address 1') }}" />
                                                <span class="bank_address1-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label" for="team-bank-address2">{{ __('Bank Address 2') }}</label>
                                                <input type="text" name="bank_address2" class="form-control" id="team-bank-address2" placeholder="{{ __('Enter bank address 2') }}" />
                                                <span class="bank_address2-error text-danger text-error"></span>
                                            </div>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>



                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>

                    </div>
                </form>

            </div>
        </div>
    </div>

@endsection
