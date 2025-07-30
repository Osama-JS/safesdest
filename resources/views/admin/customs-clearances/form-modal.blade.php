<!-- Add/Edit Customs Clearance Modal -->
<div class="modal fade" id="submitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modelTitle">{{ __('Add New Customs Clearance') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form class="add-new-clearance pt-0 form_submit" method="POST" action="{{ route('admin.customs-clearances.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="customs_clearance_id">
                    
                    <div class="row">
                        <!-- Owner Type Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="owner_type" class="form-label">{{ __('Owner Type') }} *</label>
                            <select name="owner_type" id="owner_type" class="form-select" required>
                                <option value="">{{ __('Select Owner Type') }}</option>
                                <option value="admin">{{ __('Administrator') }}</option>
                                <option value="customer">{{ __('Customer') }}</option>
                            </select>
                            <span class="owner_type-error text-danger text-error"></span>
                        </div>

                        <!-- Customer Selection (Hidden by default) -->
                        <div class="col-md-6 mb-3" id="customer_select_div" style="display: none;">
                            <label for="customer_id" class="form-label">{{ __('Customer') }} *</label>
                            <select name="customer_id" id="customer_id" class="form-select">
                                <option value="">{{ __('Select Customer') }}</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            <span class="customer_id-error text-danger text-error"></span>
                        </div>

                        <!-- Template Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="select-template" class="form-label">{{ __('Form Template') }} *</label>
                            <select name="template" id="select-template" class="form-select" required>
                                <option value="">{{ __('Select Template') }}</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                            <span class="template-error text-danger text-error"></span>
                        </div>

                        <!-- Price -->
                        <div class="col-md-6 mb-3">
                            <label for="customs-price" class="form-label">{{ __('Price') }}</label>
                            <div class="input-group">
                                <input type="number" name="price" id="customs-price" class="form-control" 
                                       placeholder="0.00" step="0.01" min="0">
                                <span class="input-group-text">{{ __('SAR') }}</span>
                            </div>
                            <span class="price-error text-danger text-error"></span>
                        </div>

                        <!-- Public/Private -->
                        <div class="col-md-6 mb-3">
                            <label for="customs-public" class="form-label">{{ __('Visibility') }}</label>
                            <select name="is_public" id="customs-public" class="form-select">
                                <option value="0">{{ __('Private') }}</option>
                                <option value="1">{{ __('Public') }}</option>
                            </select>
                            <span class="is_public-error text-danger text-error"></span>
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12 mb-3">
                            <label for="customs-notes" class="form-label">{{ __('Notes') }}</label>
                            <textarea name="notes" id="customs-notes" class="form-control" 
                                      placeholder="{{ __('Enter your notes or description') }}" rows="3"></textarea>
                            <span class="notes-error text-danger text-error"></span>
                        </div>

                        <!-- Dynamic Form Fields -->
                        <div class="col-md-12">
                            <div id="additional-form">
                                <!-- Dynamic fields will be loaded here -->
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
