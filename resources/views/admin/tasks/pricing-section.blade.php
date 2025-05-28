<div class="form-group">
    <label for="">{{ __('Set the total price Manual') }}</label>
    <input type="number" id="total-price" step="any" name="manual_total_pricing" class="form-control">
    <span class="owner-error text-danger text-error"></span>
</div>
<div class="form-group">
    <label for="">{{ __('Set the The commission Manual') }}</label>
    <input type="number" id="task-commission" step="any" min="0.00" name="manual_commission"
        class="form-control" placeholder="0.00">
    <span class="owner-error text-danger text-error"></span>
</div>
<div class="form-group">
    <label>{{ __('Add the details of the Price') }}</label>
    <br>
    <button type="button" class="btn btn-light btn-sm mb-2" id="add-pricing-details">
        {{ __('Add Details') }}
    </button>
    <div id="pricing-details-container"></div>
</div>
