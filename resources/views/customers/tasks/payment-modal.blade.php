<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            <i class="ti ti-credit-card fs-5 text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">{{ __('Pay For Task') }}</h5>
                        <small class="text-muted" id="paymentModalAmount">0.00 SAR</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="paymentForm" action="{{ route('payment.initiate') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="paymentTaskId" value="">
                <input type="hidden" name="purpose" value="task_payment">
                <input type="hidden" name="amount" id="paymentTaskAmount" value="">
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">

                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-3">{{ __('Select Payment Method') }}</label>
                        <div class="row g-3">
                            <!-- HyperPay Visa/Mastercard -->
                            <div class="col-6">
                                <label class="payment-method-label w-100 h-100">
                                    <input type="radio" name="payment_method" value="hyperpay_visa" class="d-none payment-radio" checked>
                                    <div class="payment-card text-center p-3 border rounded shadow-sm h-100">
                                        <i class="ti ti-credit-card mb-2 fs-2 text-primary"></i>
                                        <h6 class="mb-0">Visa / MC</h6>
                                    </div>
                                </label>
                            </div>
                            
                            <!-- HyperPay Mada -->
                            <div class="col-6">
                                <label class="payment-method-label w-100 h-100">
                                    <input type="radio" name="payment_method" value="hyperpay_mada" class="d-none payment-radio">
                                    <div class="payment-card text-center p-3 border rounded shadow-sm h-100">
                                        <i class="ti ti-device-mobile mb-2 fs-2 text-info"></i>
                                        <h6 class="mb-0">Mada</h6>
                                    </div>
                                </label>
                            </div>

                            <!-- Wallet -->
                            <div class="col-6">
                                <label class="payment-method-label w-100 h-100">
                                    <input type="radio" name="payment_method" value="wallet" class="d-none payment-radio">
                                    <div class="payment-card text-center p-3 border rounded shadow-sm h-100">
                                        <i class="ti ti-wallet mb-2 fs-2 text-success"></i>
                                        <h6 class="mb-0">{{ __('Wallet') }}</h6>
                                    </div>
                                </label>
                            </div>

                            <!-- Bank Transfer -->
                            <div class="col-6">
                                <label class="payment-method-label w-100 h-100">
                                    <input type="radio" name="payment_method" value="bank_transfer" class="d-none payment-radio">
                                    <div class="payment-card text-center p-3 border rounded shadow-sm h-100">
                                        <i class="ti ti-building-bank mb-2 fs-2 text-warning"></i>
                                        <h6 class="mb-0">{{ __('Bank Transfer') }}</h6>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Transfer Details (Hidden by default) -->
                    <div id="bankTransferDetails" class="d-none bg-light p-3 rounded mb-3 border">
                        <div class="alert alert-info py-2 mb-3">
                            <i class="ti ti-info-circle me-1"></i>
                            {{ __('Please transfer the total amount to the following bank account, then upload the receipt.') }}
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">{{ __('Bank Name') }}</label>
                            <input type="text" class="form-control" value="Al Rajhi Bank" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('IBAN') }}</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="SA1234567890123456789012" id="ibanInput" disabled>
                                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('SA1234567890123456789012')">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">{{ __('Receipt Number') }}</label>
                            <input type="text" class="form-control" name="receipt_number" id="receipt_number">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label required">{{ __('Upload Receipt') }}</label>
                            <input type="file" class="form-control" name="receipt_image" id="receipt_image" accept="image/*,.pdf">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="submitPaymentBtn">
                        <i class="ti ti-check me-1"></i> {{ __('Confirm Payment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.payment-radio:checked + .payment-card {
    border-color: var(--bs-primary) !important;
    background-color: rgba(var(--bs-primary-rgb), 0.05);
    transform: scale(1.02);
}
.payment-card {
    cursor: pointer;
    transition: all 0.2s ease;
}
.payment-card:hover {
    border-color: var(--bs-primary) !important;
}
</style>

<script>
function openPaymentModal(taskId, amount) {
    document.getElementById('paymentTaskId').value = taskId;
    document.getElementById('paymentTaskAmount').value = amount;
    document.getElementById('paymentModalAmount').innerText = parseFloat(amount).toFixed(2) + ' SAR';
    
    // Reset form
    document.getElementById('paymentForm').reset();
    document.querySelectorAll('.payment-radio')[0].checked = true;
    document.getElementById('bankTransferDetails').classList.add('d-none');
    
    var modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Toggle bank transfer details
    const paymentRadios = document.querySelectorAll('.payment-radio');
    const bankTransferDetails = document.getElementById('bankTransferDetails');
    const receiptNumber = document.getElementById('receipt_number');
    const receiptImage = document.getElementById('receipt_image');

    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'bank_transfer') {
                bankTransferDetails.classList.remove('d-none');
                receiptNumber.required = true;
                receiptImage.required = true;
            } else {
                bankTransferDetails.classList.add('d-none');
                receiptNumber.required = false;
                receiptImage.required = false;
            }
        });
    });

    // Handle form submission via AJAX
    const form = document.getElementById('paymentForm');
    if(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitPaymentBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> {{ __('Processing...') }}';
            btn.disabled = true;

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.payment_url || data.url) {
                        // Redirect to Hyperpay
                        window.location.href = data.payment_url || data.url;
                    } else {
                        // Wallet or Bank Transfer success
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('Success') }}',
                            text: data.message || data.success_msg,
                            confirmButtonText: '{{ __('OK') }}'
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Error') }}',
                        text: data.message || data.error || '{{ __('Payment failed') }}',
                        confirmButtonText: '{{ __('OK') }}'
                    });
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '{{ __('Error') }}',
                    text: '{{ __('An unexpected error occurred. Please try again.') }}',
                    confirmButtonText: '{{ __('OK') }}'
                });
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    }
});
</script>
