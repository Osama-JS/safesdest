<div class="modal fade" id="submitModal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-fullscreen" role="document">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modelTitle">{{ __('Add New Tasks') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form class="form_submit pt-0 " id="task-form" method="POST"
                    action="{{ route('customer.task.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-5">
                            <input type="hidden" name="id" id="task-id">

                            <ul class="nav nav-tabs" id="taskTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="tab-step1" data-bs-toggle="tab"
                                        data-bs-target="#step1" type="button" role="tab">Step 1</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link disabled" id="tab-step2" data-bs-toggle="tab"
                                        data-bs-target="#step2" type="button" role="tab">Step 2</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link disabled" id="tab-step3" data-bs-toggle="tab"
                                        data-bs-target="#step3" type="button" role="tab">Step 3</button>
                                </li>
                            </ul>

                            <div class="tab-content mt-3">
                                <!-- Step 1 -->
                                <div class="tab-pane fade show active" id="step1" role="tabpanel">



                                    <!-- Vehicle Selection -->
                                    <div class="mb-3">
                                        <div class="divider text-start">
                                            <div class="divider-text"><strong>Vehicle Selection</strong></div>
                                        </div>

                                        <div id="vehicle-selection-container">
                                            <!-- سيتم توليد السطور ديناميكيًا هنا -->
                                        </div>


                                    </div>

                                    <div class="mb-3">
                                        <div class="divider text-start">
                                            <div class="divider-text"><strong>Task Information</strong></div>
                                        </div>
                                        <div id="additional-form" class="row mt-4">

                                        </div>
                                    </div>


                                    <button type="button" id="go-to-step2"
                                        class="btn btn-primary mt-3">{{ __('Next') }}
                                        <i class="ti ti-arrow-right"></i></button>

                                </div>

                                <!-- Step 2 -->
                                <div class="tab-pane fade" id="step2" role="tabpanel">
                                    <!-- Pricing Method Selection -->
                                    <div class="mb-3">
                                        <div class="divider text-start">
                                            <div class="divider-text"><strong>{{ __('Pricing') }}</strong></div>
                                        </div>

                                        <select id="pricing-method-select" name="pricing_method" class="form-select">
                                            <!-- سيتم تعبئته عبر الجافاسكربت -->
                                        </select>

                                        <span class="pricing_method-error text-danger text-error"></span>
                                    </div>
                                    <div class="mb-3">
                                        <div class="divider text-start">
                                            <div class="divider-text"><strong>(Pickup / Delivery) Point</strong></div>
                                        </div>
                                        <div id="accordionCustomIcon" class="accordion mt-4 accordion-custom-button">
                                            <div class="accordion-item">
                                                <h4 class="accordion-header text-body d-flex justify-content-between"
                                                    id="accordionCustomIconOne">
                                                    <button type="button" class="accordion-button collapsed"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#accordionCustomIcon-1"
                                                        aria-controls="accordionCustomIcon-1">
                                                        <i class="ri-bar-chart-2-line me-2 ri-20px"></i>
                                                        <h5> Pickup Point</h5>
                                                    </button>
                                                </h4>



                                                <div id="accordionCustomIcon-1" class="accordion-collapse collapse show"
                                                    data-bs-parent="#accordionCustomIcon">
                                                    <div class="accordion-body">

                                                        <!-- Pickup Point -->
                                                        <div class="mb-3">

                                                            <!-- Name & Phone -->
                                                            <div class="mb-3">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <label for="pickup-contact-name">*
                                                                            {{ __('Name') }}</label>
                                                                        <input type="text" id="pickup-contact-name"
                                                                            name="pickup_name" class="form-control"
                                                                            placeholder="{{ __('Enter pickup address') }}"
                                                                            required />
                                                                        <span
                                                                            class="pickup_name-error text-danger text-error"></span>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="pickup-contact-phone">*
                                                                            {{ __('Phone') }}</label>
                                                                        <input type="number"
                                                                            id="pickup-contact-phone"
                                                                            name="pickup_phone" class="form-control"
                                                                            placeholder="{{ __('Enter pickup address') }}"
                                                                            required />
                                                                        <span
                                                                            class="pickup_phone-error text-danger text-error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Email & Pickup Before -->
                                                            <div class="mb-3">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <label for="pickup-email">*
                                                                            {{ __('Email') }}</label>
                                                                        <input type="email"
                                                                            id="pickup-contact-email"
                                                                            name="pickup_email" class="form-control"
                                                                            placeholder="{{ __('Email') }}"
                                                                            required />
                                                                        <span
                                                                            class="pickup_email-error text-danger text-error"></span>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="pickup-before">*
                                                                            {{ __('Pickup before') }}</label>
                                                                        <input type="datetime-local"
                                                                            id="pickup-before" name="pickup_before"
                                                                            class="form-control" required
                                                                            value="{{ now()->format('Y-m-d\TH:i') }}" />
                                                                        <span
                                                                            class="pickup_before-error text-danger text-error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Address -->
                                                            <div class="mb-3">
                                                                <label for="pickup-address">*
                                                                    {{ __('Address') }}</label>
                                                                <input type="text" id="pickup-address"
                                                                    name="pickup_address" class="form-control"
                                                                    placeholder="{{ __('Enter pickup address') }}" />
                                                                <span
                                                                    class="pickup_address-error text-danger text-error"></span>
                                                            </div>

                                                            <!-- Location Geocoder + Map -->
                                                            <div class="mb-3" id="pickup-map-section">


                                                                <label for="pickup-location">*
                                                                    {{ __('Location') }}</label>
                                                                <div class="input-group mb-2">
                                                                    <div class="form-control p-0"
                                                                        id="pickup-geocoder">
                                                                    </div>
                                                                    <button type="button" title="تحليل رابط موقع"
                                                                        id="pickup-toggle-link-input"
                                                                        class="input-group-text bg-white">
                                                                        <i class="fas fa-link text-secondary"></i>
                                                                    </button>
                                                                    <button type="button" title="إدخال يدوي"
                                                                        id="pickup-manual-btn"
                                                                        class="input-group-text bg-white">
                                                                        <i class="fas fa-globe text-secondary"></i>
                                                                    </button>
                                                                    <button type="button" title="موقعي الحالي"
                                                                        id="pickup-getCurrentLocation"
                                                                        class="input-group-text bg-white">
                                                                        <i
                                                                            class="fas fa-location-crosshairs text-secondary"></i>
                                                                    </button>
                                                                </div>
                                                                <div id="pickup-link-input-wrapper" class="mt-2"
                                                                    style="display: none;">
                                                                    <div class="input-group">
                                                                        <input type="text" id="pickup-map-link"
                                                                            class="form-control"
                                                                            placeholder="ألصق رابط الموقع هنا" />
                                                                        <button type="button" id="pickup-parse-link"
                                                                            class="btn btn-secondary">
                                                                            تحليل الرابط
                                                                        </button>
                                                                    </div>
                                                                </div>


                                                                <!-- Map Container -->
                                                                <div id="pickup-map-container"
                                                                    class="position-relative rounded overflow-hidden border"
                                                                    style="height: 200px; display: none;">
                                                                    <div
                                                                        class="row mb-2 position-absolute top-0 start-0 m-2 z-3">
                                                                        <div class="col">
                                                                            <input type="number"
                                                                                name="pickup_latitude" step="any"
                                                                                id="pickup-latitude"
                                                                                class="form-control"
                                                                                placeholder="(Latitude)">
                                                                        </div>
                                                                        <div class="col">
                                                                            <input type="number"
                                                                                name="pickup_longitude" step="any"
                                                                                id="pickup-longitude"
                                                                                class="form-control"
                                                                                placeholder="(Longitude)">
                                                                        </div>
                                                                    </div>
                                                                    <button id="pickup-confirm-location"
                                                                        type="button"
                                                                        class="btn btn-primary btn-sm position-absolute top-0 end-0 m-2 z-3"
                                                                        style="display: none;">
                                                                        {{ __('confirm location') }}
                                                                    </button>
                                                                    <div id="pickup-map" class="w-100 h-100"
                                                                        style="display: none;"></div>
                                                                </div>

                                                                <span
                                                                    class="pickup_latitude-error text-danger text-error"></span>
                                                                <span
                                                                    class="pickup_longitude-error text-danger text-error"></span>
                                                                <!-- Hidden Final Address -->

                                                            </div>

                                                            <!-- Note -->
                                                            <div class="mb-3">
                                                                <label for="pickup-note">{{ __('Note') }}</label>
                                                                <input type="text" id="pickup-note"
                                                                    name="pickup_note" class="form-control"
                                                                    placeholder="{{ __('Note') }}" />
                                                                <span
                                                                    class="pickup_note-error text-danger text-error"></span>
                                                            </div>

                                                            <div class="mb-6">
                                                                <label
                                                                    for="pickup-image">{{ __('Image for pickup address') }}</label>

                                                                <div class="form-group mt-2">
                                                                    <img src="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                                                        data-image="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                                                        alt="" id="image"
                                                                        style="width: 120px;    height: 100px;
                                                                object-fit: cover;"
                                                                        class="rounded preview-pickup-image image-input">

                                                                    <input type="file"
                                                                        class="form-control file-pickup-image"
                                                                        id="pickup-image" name="pickup_image"
                                                                        style="display: none" />
                                                                    <span
                                                                        class="pickup_image-error text-danger text-error"></span>

                                                                </div>

                                                            </div>

                                                        </div>

                                                    </div>
                                                </div>

                                                <h4 class="accordion-header text-body d-flex justify-content-between"
                                                    id="accordionCustomIconOne">
                                                    <button type="button" class="accordion-button collapsed"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#accordionCustomIcon-2"
                                                        aria-controls="accordionCustomIcon-2">
                                                        <i class="ri-bar-chart-2-line me-2 ri-20px"></i>
                                                        <h5> Delivery Point</h5>
                                                    </button>
                                                </h4>

                                                <div id="accordionCustomIcon-2" class="accordion-collapse collapse"
                                                    data-bs-parent="#accordionCustomIcon">
                                                    <div class="accordion-body">

                                                        <div class="mb-3">


                                                            <!-- Name & Phone -->
                                                            <div class="mb-3">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <label for="delivery-contact-name">*
                                                                            {{ __('Name') }}</label>
                                                                        <input type="text"
                                                                            id="delivery-contact-name"
                                                                            name="delivery_name" class="form-control"
                                                                            placeholder="{{ __('Enter delivery name') }}"
                                                                            required />
                                                                        <span
                                                                            class="delivery_name-error text-danger text-error"></span>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="delivery-contact-phone">*
                                                                            {{ __('Phone') }}</label>
                                                                        <input type="number"
                                                                            id="delivery-contact-phone"
                                                                            name="delivery_phone" class="form-control"
                                                                            placeholder="{{ __('Enter delivery phone') }}"
                                                                            required />
                                                                        <span
                                                                            class="delivery_phone-error text-danger text-error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Email & Delivery Before -->
                                                            <div class="mb-3">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <label for="delivery-email">*
                                                                            {{ __('Email') }}</label>
                                                                        <input type="email"
                                                                            id="delivery-contact-email"
                                                                            name="delivery_email" class="form-control"
                                                                            placeholder="{{ __('Email') }}"
                                                                            required />
                                                                        <span
                                                                            class="delivery_email-error text-danger text-error"></span>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label for="delivery-before">*
                                                                            {{ __('Delivery before') }}</label>
                                                                        <input type="datetime-local"
                                                                            id="delivery-before"
                                                                            name="delivery_before"
                                                                            class="form-control" required
                                                                            value="{{ now()->format('Y-m-d\TH:i') }}" />
                                                                        <span
                                                                            class="delivery_before-error text-danger text-error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <!-- Address -->
                                                            <div class="mb-3">
                                                                <label for="delivery-address">*
                                                                    {{ __('Address') }}</label>
                                                                <input type="text" id="delivery-address"
                                                                    name="delivery_address" class="form-control"
                                                                    placeholder="{{ __('Enter delivery address') }}"
                                                                    required />
                                                                <span
                                                                    class="delivery_address-error text-danger text-error"></span>
                                                            </div>

                                                            <!-- Location Geocoder + Map -->
                                                            <div class="mb-3" id="delivery-map-section">
                                                                <label for="delivery-location">*
                                                                    {{ __('Location') }}</label>
                                                                <div class="input-group mb-2">
                                                                    <div class="form-control p-0"
                                                                        id="delivery-geocoder">
                                                                    </div>
                                                                    <button type="button" title="تحليل رابط موقع"
                                                                        id="delivery-toggle-link-input"
                                                                        class="input-group-text bg-white">
                                                                        <i class="fas fa-link text-secondary"></i>
                                                                    </button>
                                                                    <button type="button" title="إدخال يدوي"
                                                                        id="delivery-manual-btn"
                                                                        class="input-group-text bg-white">
                                                                        <i class="fas fa-globe text-secondary"></i>
                                                                    </button>
                                                                    <button type="button" title="موقعي الحالي"
                                                                        id="delivery-getCurrentLocation"
                                                                        class="input-group-text bg-white">
                                                                        <i
                                                                            class="fas fa-location-crosshairs text-secondary"></i>
                                                                    </button>
                                                                </div>
                                                                <div id="delivery-link-input-wrapper" class="mt-2"
                                                                    style="display: none;">
                                                                    <div class="input-group">
                                                                        <input type="text" id="delivery-map-link"
                                                                            class="form-control"
                                                                            placeholder="ألصق رابط الموقع هنا" />
                                                                        <button type="button"
                                                                            id="delivery-parse-link"
                                                                            class="btn btn-secondary">
                                                                            تحليل الرابط
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                                <!-- Map Container -->
                                                                <div id="delivery-map-container"
                                                                    class="position-relative rounded overflow-hidden border"
                                                                    style="height: 200px; display: none;">
                                                                    <div
                                                                        class="row mb-2 position-absolute top-0 start-0 m-2 z-3">
                                                                        <div class="col">
                                                                            <input type="number"
                                                                                name="delivery_latitude"
                                                                                step="any" id="delivery-latitude"
                                                                                class="form-control"
                                                                                placeholder="(Latitude)">
                                                                        </div>
                                                                        <div class="col">
                                                                            <input type="number"
                                                                                name="delivery_longitude"
                                                                                step="any" id="delivery-longitude"
                                                                                class="form-control"
                                                                                placeholder="(Longitude)">
                                                                        </div>
                                                                    </div>
                                                                    <button id="delivery-confirm-location"
                                                                        type="button"
                                                                        class="btn btn-primary btn-sm position-absolute top-0 end-0 m-2 z-3"
                                                                        style="display: none;">
                                                                        {{ __('confirm location') }}
                                                                    </button>
                                                                    <div id="delivery-map" class="w-100 h-100"
                                                                        style="display: none;"></div>
                                                                </div>

                                                                <span
                                                                    class="delivery_latitude-error text-danger text-error"></span>
                                                                <span
                                                                    class="delivery_longitude-error text-danger text-error"></span>
                                                                <!-- Hidden Final Address -->

                                                            </div>

                                                            <!-- Note -->
                                                            <div class="mb-3">
                                                                <label for="delivery-note">{{ __('Note') }}</label>
                                                                <input type="text" id="delivery-note"
                                                                    name="delivery_note" class="form-control"
                                                                    placeholder="{{ __('Note') }}" />
                                                                <span
                                                                    class="delivery_note-error text-danger text-error"></span>
                                                            </div>

                                                            <div class="mb-6">
                                                                <label
                                                                    for="delivery-image">{{ __('Image for pickup address') }}</label>

                                                                <div class="form-group mt-2">
                                                                    <img src="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                                                        data-image="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                                                        alt="" id="image"
                                                                        style="width: 120px;    height: 100px;
                                                                object-fit: cover;"
                                                                        class="rounded preview-deliver-image image-input">

                                                                    <input type="file"
                                                                        class="form-control file-deliver-image"
                                                                        id="delivery-image" name="delivery-image"
                                                                        style="display: none" />
                                                                    <span
                                                                        class="image-error text-danger text-error"></span>

                                                                </div>

                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">

                                            <button type="button" id="back-to-step1" class="btn btn-light mt-3"><i
                                                    class="ti ti-arrow-left"></i>{{ __('Back') }}
                                            </button>
                                            <button type="button" id="go-to-step3"
                                                class="btn btn-primary mt-3 mx-2">{{ __('Next') }}
                                                <i class="ti ti-arrow-right"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="step3" role="tabpanel">
                                    <div class="mb-3">
                                        <div id="taskFinalDetails">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="">{{ __('Do you have any Conditions') }}?</label>
                                        <textarea name="conditions" id="conditions" class="form-control" rows="3"
                                            placeholder="{{ __('Write your Conditions') }}"></textarea>
                                        <span class="conditions-error text-danger text-error"></span>
                                    </div>

                                    <button type="button" id="back-to-step2" class="btn btn-light mt-3"><i
                                            class="ti ti-arrow-left"></i>{{ __('Back') }}
                                    </button>
                                    <button type="submit" id="go-to-step2" class="btn btn-primary mt-3 mx-2"> Submit
                                    </button>

                                </div>
                            </div>


                        </div>

                        <!-- Right Column (Empty) -->
                        <div class="col-md-7">
                            <div class="w-full" style="position: sticky; top:0 ">
                                <div id="preview-map" class="w-100" style="height: 80vh">
                                </div>
                                <p id="distance-info"
                                    class="mt-2 text-primary fw-bold position-absolute top-0 end-0 m-2 z-3"></p>
                            </div>

                        </div>

                    </div>
                </form>

            </div>

            <div class="modal-footer pt-3">
                <button type="button" class="btn btn-label-secondary"
                    data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>

        </div>

    </div>
</div>


<div class="modal fade " id="assignModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignTitle">{{ __('Assign Task') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('tasks.assign') }}">
                @csrf
                <div class="modal-body">
                    <div class="col-xl-12">
                        <div class="nav-align-top">
                            <div class="tab-content">
                                <div class="tab-pane fade show active">
                                    <input type="hidden" name="id" id="task-assign-id">
                                    <span class="id-error text-danger text-error"></span>
                                    <div class="mb-4">
                                        <label class="form-label" for="team-name">* {{ __('Driver') }}</label>
                                        <select name="driver" id="task-driver"
                                            class="task-driver form-select"></select>
                                        <span class="name-error text-danger text-error"></span>
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

<div class="modal fade " id="adModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adTitle">{{ __('Edit Task Ad') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('ads.update') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="ad-id">
                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <label for="min-price">* Min Price</label>
                            <input type="number" name="min_price" id="ad-min-price" class="form-control"
                                step="any">
                            <span class="min_price-error text-danger text-error"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="max-price">* Max Price</label>
                            <input type="number" name="max_price" id="ad-max-price" class="form-control"
                                step="any">
                            <span class="max_price-error text-danger text-error"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="not-price">Note</label>
                        <textarea name="note_price" id="ad-not-price" class="form-control"></textarea>
                        <span class="note_price-error text-danger text-error"></span>
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

<div class="modal fade " id="pricingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pricingTitle">{{ __('Edit Task Pricing') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('tasks.pricing.update') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="pricing-id">
                    <div class="row mb-3">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ __('Set the total price Manual') }}</label>
                                <input type="number" id="pricing-total-price" step="any" name="price"
                                    class="form-control">
                                <span class="owner-error text-danger text-error"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="">{{ __('Set the The commission Manual') }}</label>
                                <input type="number" id="pricing-commission" step="any" min="0.00"
                                    name="commission" class="form-control" placeholder="0.00">
                                <span class="owner-error text-danger text-error"></span>
                            </div>
                        </div>
                    </div>


                    <div class="form-group">
                        <label>{{ __('Add the details of the Price') }}</label>
                        <br>
                        <button type="button" class="btn btn-light btn-sm mb-2" id="pricing-add-pricing-details">
                            {{ __('Add Details') }}
                        </button>
                        <div id="pricing-pricing-details-container"></div>
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

<div class="modal fade" id="taskTypeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title mx-auto">اختر نوع المهمة</h5>
                <button type="button" class="btn-close position-absolute end-0 me-3 mt-2" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body text-center d-flex flex-column gap-3 py-4">


                <!-- زر مهمة داخلية -->
                <button
                    class="task_type_template btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 px-4 py-2 rounded-pill"
                    data-template="normal">
                    <i class="fas fa-tasks"></i>
                    <span>إنشاء مهمة داخلية</span>
                </button>

                @if (optional($task_to_template)->value !== null)
                    <!-- زر تصدير عبر الميناء -->
                    <button
                        class="task_type_template btn btn-outline-success d-flex align-items-center justify-content-center gap-2 px-4 py-2 rounded-pill"
                        data-template="task_to">
                        <i class="fas fa-ship"></i>
                        <span>إنشاء مهمة تصدير عبر الميناء</span>
                    </button>
                @endif



                @if (optional($task_from_template)->value !== null)
                    <!-- زر استيراد من الميناء -->
                    <button
                        class="task_type_template btn btn-outline-warning d-flex align-items-center justify-content-center gap-2 px-4 py-2 rounded-pill text-dark"
                        data-template="task_from">
                        <i class="fas fa-dolly-flatbed"></i>
                        <span>إنشاء مهمة استيراد من الميناء</span>
                    </button>
                @endif


            </div>
        </div>
    </div>
</div>
