{{-- Signature Modal --}}
<div class="modal fade" id="signatureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-signature me-2"></i>
                    {{ __('Manage Signature') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                {{-- Current Signature Preview --}}
                <div id="signature-preview-container" class="text-center mb-4" style="display: none;">
                    <label class="form-label">{{ __('Current Signature') }}</label>
                    <div class="border rounded p-3 bg-light">
                        <img id="current-signature" src="" alt="{{ __('Current Signature') }}"
                            style="max-height: 120px; max-width: 100%;">
                    </div>
                </div>

                {{-- Tab Navigation --}}
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                            data-bs-target="#tab-draw-signature" aria-selected="true">
                            <i class="ti ti-pencil me-1"></i>
                            {{ __('Draw Signature') }}
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                            data-bs-target="#tab-upload-signature" aria-selected="false">
                            <i class="ti ti-upload me-1"></i>
                            {{ __('Upload Image') }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Draw Signature Tab --}}
                    <div class="tab-pane fade show active" id="tab-draw-signature" role="tabpanel">
                        <div class="signature-pad-container">
                            <label class="form-label">{{ __('Draw your signature below') }}</label>
                            <div class="border rounded" style="background: #fff;">
                                <canvas id="signature-canvas" width="600" height="200"
                                    style="width: 100%; touch-action: none; cursor: crosshair;"></canvas>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-signature">
                                    <i class="ti ti-eraser me-1"></i>
                                    {{ __('Clear') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Upload Signature Tab --}}
                    <div class="tab-pane fade" id="tab-upload-signature" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Upload signature image') }}</label>
                            <input type="file" class="form-control" id="signature-file"
                                accept="image/png,image/jpeg,image/jpg,image/gif">
                            <small class="form-text text-muted">
                                {{ __('Supported formats: PNG, JPG, GIF. Recommended: transparent PNG.') }}
                            </small>
                        </div>
                        <div id="upload-preview" class="text-center" style="display: none;">
                            <img id="upload-preview-img" src="" alt="Preview"
                                style="max-height: 150px; max-width: 100%; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                        </div>
                    </div>
                </div>

                {{-- Hidden fields --}}
                <input type="hidden" id="signature-type" value="">
                <input type="hidden" id="signature-id" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-danger me-auto" id="delete-signature" style="display: none;">
                    <i class="ti ti-trash me-1"></i>
                    {{ __('Delete Signature') }}
                </button>
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-primary" id="save-signature">
                    <i class="ti ti-check me-1"></i>
                    {{ __('Save Signature') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Signature Pad Library (from CDN) --}}
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script>
    window.signatureModalManager = (function() {
        let signaturePad = null;
        let currentType = null;
        let currentId = null;

        function init() {
            const canvas = document.getElementById('signature-canvas');
            if (!canvas) return;

            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)'
            });

            // Resize canvas to fit container
            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);

            // Event listeners
            document.getElementById('clear-signature')?.addEventListener('click', function() {
                signaturePad.clear();
            });

            document.getElementById('signature-file')?.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        document.getElementById('upload-preview-img').src = evt.target.result;
                        document.getElementById('upload-preview').style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });

            document.getElementById('save-signature')?.addEventListener('click', saveSignature);
            document.getElementById('delete-signature')?.addEventListener('click', deleteSignature);

            // Modal event
            document.getElementById('signatureModal')?.addEventListener('shown.bs.modal', function() {
                resizeCanvas();
            });
        }

        function resizeCanvas() {
            const canvas = document.getElementById('signature-canvas');
            if (!canvas || !signaturePad) return;

            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = 200 * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            signaturePad.clear();
        }

        function openModal(type, id) {
            currentType = type;
            currentId = id;

            document.getElementById('signature-type').value = type;
            document.getElementById('signature-id').value = id;

            // Reset UI
            if (signaturePad) signaturePad.clear();
            document.getElementById('signature-file').value = '';
            document.getElementById('upload-preview').style.display = 'none';
            document.getElementById('signature-preview-container').style.display = 'none';
            document.getElementById('delete-signature').style.display = 'none';

            // Load current signature
            fetch(`{{ route('admin.signature.get') }}?type=${type}&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.signature_url) {
                        document.getElementById('current-signature').src = data.signature_url;
                        document.getElementById('signature-preview-container').style.display = 'block';
                        document.getElementById('delete-signature').style.display = 'inline-block';
                    }
                })
                .catch(err => console.error('Error loading signature:', err));

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('signatureModal'));
            modal.show();
        }

        function saveSignature() {
            const type = document.getElementById('signature-type').value;
            const id = document.getElementById('signature-id').value;
            const activeTab = document.querySelector('#signatureModal .tab-pane.active').id;

            let formData = new FormData();
            formData.append('type', type);
            formData.append('id', id);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            if (activeTab === 'tab-draw-signature') {
                if (signaturePad.isEmpty()) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ __("Please draw your signature") }}',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    return;
                }
                formData.append('signature', signaturePad.toDataURL('image/png'));
            } else {
                const file = document.getElementById('signature-file').files[0];
                if (!file) {
                    Swal.fire({
                        icon: 'warning',
                        title: '{{ __("Please select a file") }}',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    return;
                }
                formData.append('signature', file);
            }

            // Show loading
            Swal.fire({
                title: '{{ __("Saving...") }}',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route("admin.signature.upload") }}', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        bootstrap.Modal.getInstance(document.getElementById('signatureModal')).hide();

                        // Refresh page or update UI
                        if (typeof window.refreshDataTable === 'function') {
                            window.refreshDataTable();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: data.message || '{{ __("Error saving signature") }}'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Error saving signature") }}'
                    });
                });
        }

        function deleteSignature() {
            Swal.fire({
                title: '{{ __("Are you sure?") }}',
                text: '{{ __("This will delete the signature") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __("Yes, delete it!") }}',
                cancelButtonText: '{{ __("Cancel") }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    const type = document.getElementById('signature-type').value;
                    const id = document.getElementById('signature-id').value;

                    fetch('{{ route("admin.signature.delete") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                type,
                                id
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: data.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                                bootstrap.Modal.getInstance(document.getElementById('signatureModal')).hide();

                                if (typeof window.refreshDataTable === 'function') {
                                    window.refreshDataTable();
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: data.message
                                });
                            }
                        });
                }
            });
        }

        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', init);

        // Public API
        return {
            open: openModal
        };
    })();
</script>
