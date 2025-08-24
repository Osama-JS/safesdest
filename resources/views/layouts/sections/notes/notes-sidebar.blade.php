<!-- Notes Sidebar -->
<div id="notes-sidebar" class="notes-sidebar">
    <div class="notes-sidebar-overlay" id="notes-sidebar-overlay"></div>
    <div class="notes-sidebar-content">
        <!-- Header -->
        <div class="notes-header">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <i class="ti ti-notes me-2"></i>
                    My Notes
                </h5>
                <button type="button" class="btn btn-text-secondary btn-icon rounded-pill" id="notes-sidebar-close">
                    <i class="ti ti-x ti-md"></i>
                </button>
            </div>
        </div>
        <!-- Notes List -->
        <div class="notes-list" id="notes-list">
            <div class="text-center py-4" id="notes-loading">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0 text-muted">Loading notes...</p>
            </div>

            <div class="text-center py-4 d-none" id="no-notes">
                <i class="ti ti-notes-off ti-lg text-muted mb-2"></i>
                <p class="text-muted mb-0">No notes yet</p>
                <small class="text-muted">Click the + button to add your first note</small>
            </div>
        </div>

        <!-- Add Note Form -->
        <div class="notes-add-form">
            <form id="add-note-form" class="mb-4">
                @csrf
                <div class="mb-3">
                    <input type="text" class="form-control" id="note-title" name="title"
                        placeholder="Note title..." required>
                    <span class="text-danger text-error title-error"></span>
                </div>
                <div class="mb-3">
                    <textarea class="form-control" id="note-content" name="content" rows="3" placeholder="Write your note here..."
                        required></textarea>
                    <span class="text-danger text-error content-error"></span>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="ti ti-plus me-1"></i>
                        Add Note
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="cancel-add-note">
                        Cancel
                    </button>
                </div>
            </form>
        </div>


    </div>
</div>

<!-- Edit Note Modal -->
<div class="modal fade" id="edit-note-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-edit me-2"></i>
                    Edit Note
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-note-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit-note-id" name="note_id">
                    <div class="mb-3">
                        <label for="edit-note-title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="edit-note-title" name="title" required>
                        <span class="text-danger text-error title-error"></span>
                    </div>
                    <div class="mb-3">
                        <label for="edit-note-content" class="form-label">Content</label>
                        <textarea class="form-control" id="edit-note-content" name="content" rows="4" required></textarea>
                        <span class="text-danger text-error content-error"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-note-changes">
                    <i class="ti ti-device-floppy me-1"></i>
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .notes-sidebar {
        position: fixed;
        top: 0;
        right: -400px;
        width: 400px;
        height: 100vh;
        z-index: 1050;
        transition: right 0.3s ease-in-out;
    }

    .notes-sidebar.show {
        right: 0;
    }

    .notes-sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.5);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        z-index: -1;
    }

    .notes-sidebar.show .notes-sidebar-overlay {
        opacity: 1;
        visibility: visible;
    }

    .notes-sidebar-content {
        background: var(--bs-body-bg);
        height: 100%;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        border-left: 1px solid var(--bs-border-color);
    }

    .notes-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
    }

    .notes-add-form {
        padding: 1.5rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
    }

    .notes-list {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .note-card {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease-in-out;
        position: relative;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        border-left: 4px solid transparent;
    }

    .note-card:hover {
        border-color: var(--bs-border-color);
        border-left-color: var(--bs-primary);
        box-shadow: 0 4px 12px rgba(115, 103, 240, 0.12);
        transform: translateY(-1px);
    }

    .note-card:nth-child(4n+1) {
        border-left-color: rgba(115, 103, 240, 0.3);
    }

    .note-card:nth-child(4n+2) {
        border-left-color: rgba(40, 199, 111, 0.3);
    }

    .note-card:nth-child(4n+3) {
        border-left-color: rgba(255, 159, 67, 0.3);
    }

    .note-card:nth-child(4n+4) {
        border-left-color: rgba(0, 186, 209, 0.3);
    }

    .note-card:nth-child(4n+1):hover {
        border-left-color: #7367f0;
        box-shadow: 0 4px 12px rgba(115, 103, 240, 0.15);
    }

    .note-card:nth-child(4n+2):hover {
        border-left-color: #28c76f;
        box-shadow: 0 4px 12px rgba(40, 199, 111, 0.15);
    }

    .note-card:nth-child(4n+3):hover {
        border-left-color: #ff9f43;
        box-shadow: 0 4px 12px rgba(255, 159, 67, 0.15);
    }

    .note-card:nth-child(4n+4):hover {
        border-left-color: #00bad1;
        box-shadow: 0 4px 12px rgba(0, 186, 209, 0.15);
    }

    .note-card .note-title {
        font-weight: 600;
        color: var(--bs-heading-color);
        margin-bottom: 0.75rem;
        font-size: 1rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .note-card .note-content {
        color: var(--bs-body-color);
        font-size: 0.875rem;
        line-height: 1.6;
        margin-bottom: 1rem;
        word-wrap: break-word;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .note-card .note-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.75rem;
        color: var(--bs-text-muted);
        border-top: 1px solid rgba(var(--bs-border-color-rgb), 0.5);
        padding-top: 0.75rem;
        margin-top: 0.75rem;
    }

    .note-card .note-actions {
        display: flex;
        gap: 0.375rem;
        opacity: 0.7;
        transition: opacity 0.2s ease-in-out;
    }

    .note-card:hover .note-actions {
        opacity: 1;
    }

    .note-card .note-actions .btn {
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease-in-out;
    }

    .note-card .note-actions .btn:hover {
        transform: scale(1.05);
    }

    @media (max-width: 768px) {
        .notes-sidebar {
            width: 100vw;
            right: -100vw;
        }
    }
</style>
