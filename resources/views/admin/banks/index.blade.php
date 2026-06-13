@extends('layouts/layoutMaster')

@section('title', __('Banks'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Banks List') }}</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBankModal">
            {{ __('Add Bank') }}
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success m-3">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('Bank Name') }}</th>
                    <th>{{ __('Bank Code') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach($banks as $bank)
                <tr>
                    <td>{{ $bank->id }}</td>
                    <td>{{ $bank->name }}</td>
                    <td>{{ $bank->code ?? '-' }}</td>
                    <td>
                        <span class="badge bg-{{ $bank->is_active ? 'success' : 'danger' }}">
                            {{ $bank->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-icon edit-bank-btn" 
                            data-id="{{ $bank->id }}" 
                            data-name="{{ $bank->name }}" 
                            data-code="{{ $bank->code }}" 
                            data-status="{{ $bank->is_active ? '1' : '0' }}" 
                            data-bs-toggle="modal" data-bs-target="#editBankModal">
                            <i class="ti ti-pencil"></i>
                        </button>
                        <form action="{{ route('admin.banks.destroy', $bank->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this bank?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon text-danger"><i class="ti ti-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.banks.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add Bank') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-3">
                            <label for="name" class="form-label">{{ __('Bank Name') }}</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-3">
                            <label for="code" class="form-label">{{ __('Bank Code') }}</label>
                            <input type="text" id="code" name="code" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editBankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="editBankForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Bank') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-3">
                            <label for="edit_name" class="form-label">{{ __('Bank Name') }}</label>
                            <input type="text" id="edit_name" name="name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-3">
                            <label for="edit_code" class="form-label">{{ __('Bank Code') }}</label>
                            <input type="text" id="edit_code" name="code" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                <label class="form-check-label" for="edit_is_active">{{ __('Active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editButtons = document.querySelectorAll('.edit-bank-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const code = this.getAttribute('data-code');
                const status = this.getAttribute('data-status');

                document.getElementById('edit_name').value = name;
                document.getElementById('edit_code').value = code;
                document.getElementById('edit_is_active').checked = status === '1';
                
                document.getElementById('editBankForm').action = '/admin/banks/' + id;
            });
        });

        // AJAX submit for Edit Bank Form
        $('#editBankForm').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);
            const url = form.attr('action');
            
            $.ajax({
                type: 'POST',
                url: url,
                data: form.serialize(),
                success: function (response) {
                    if (response.success) {
                        $('#editBankModal').modal('hide');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'success', title: response.message, showConfirmButton: false, timer: 1500 }).then(() => location.reload());
                        } else {
                            location.reload();
                        }
                    }
                },
                error: function (xhr) {
                    let errorMessage = 'An error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: errorMessage });
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        });
        
        // AJAX submit for Add Bank Form
        $('#addBankModal form').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);
            const url = form.attr('action');
            
            $.ajax({
                type: 'POST',
                url: url,
                data: form.serialize(),
                success: function (response) {
                    if (response.success) {
                        $('#addBankModal').modal('hide');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({ icon: 'success', title: response.message, showConfirmButton: false, timer: 1500 }).then(() => location.reload());
                        } else {
                            location.reload();
                        }
                    }
                },
                error: function (xhr) {
                    let errorMessage = 'An error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error', text: errorMessage });
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        });
    });
</script>
@endsection
