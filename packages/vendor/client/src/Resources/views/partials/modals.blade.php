<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-container delete-modal">
        <div class="modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>{{ __('client::clients.confirmations.delete_title') }}</h3>
        <p>{{ __('client::clients.confirmations.delete_message', ['name' => 'ce client']) }}</p>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelDeleteBtn">{{ __('client::clients.actions.cancel') }}</button>
            <button class="btn-danger" id="confirmDeleteBtn">{{ __('client::clients.actions.delete') }}</button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal-overlay" id="importModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fas fa-upload"></i> {{ __('client::clients.import.title') }}</h3>
            <button class="modal-close" id="closeImportModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>{{ __('client::clients.fields.file') }} (CSV, Excel)</label>
                    <input type="file" name="file" class="form-control-modern" accept=".csv,.xlsx,.xls" required>
                    <small class="text-muted">{{ __('client::clients.import.template_intro') }} <a href="{{ route('clients.import.template') }}">{{ __('client::clients.import.template_link') }}</a> {{ __('client::clients.import.template_tail') }}</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelImportBtn">{{ __('client::clients.actions.cancel') }}</button>
            <button class="btn-save" id="confirmImportBtn">{{ __('client::clients.actions.import') }}</button>
        </div>
    </div>
</div>
