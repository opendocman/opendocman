<div class="mt-3">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{$g_lang_outpage_file_listing}</h3>
            <div>
                <button id="delete-selected" class="btn btn-danger btn-sm" disabled>
                    Delete Selected
                </button>
                <div id="delete-csrf-fields" class="d-none">{$delete_csrf_field}</div>
            </div>
        </div>
        <div class="card-body">
            <div id="file-table"></div>
        </div>
    </div>
</div>
