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
    @vite(['resources/js/admin/teams.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">{{ __('Teams') }}</h5>
            <p>Organize your Manager into logical groups to efficiently manage your field operations. You may group them on
                the basis of location, geography, type of service and so on and so forth.</p>

        </div>
        <div class="row mb-3 p-3">
            <div class="col-md-12">
                <button class="add-new btn btn-primary waves-effect waves-light mb-5 mx-4" data-bs-toggle="modal"
                    data-bs-target="#submitModal">
                    <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> Add New Team</span>
                </button>
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
