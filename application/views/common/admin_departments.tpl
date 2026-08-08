<script>
window.crudEntity = 'departments';
window.departmentList = {$department_list|@json_encode};
</script>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{$g_lang_label_department}</h5>
        <button class="btn btn-primary btn-sm" id="addBtn">+ {$g_lang_label_add}</button>
    </div>
    <div class="card-body">
        <div id="crud-table"></div>
    </div>
</div>

<div class="modal fade" id="crudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crudModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="crudModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{$g_lang_button_cancel}</button>
                <button type="button" class="btn btn-primary" id="crudModalSave">{$g_lang_button_save}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$g_lang_label_confirm_delete}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deleteModalBody">
                <p>{$g_lang_message_confirm_delete_item}</p>
                <div class="mb-3" id="reassignField" style="display:none;">
                    <label class="form-label">{$g_lang_label_reassign_to}</label>
                    <select class="form-select" id="reassignSelect"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{$g_lang_button_cancel}</button>
                <button type="button" class="btn btn-danger" id="deleteConfirmBtn">{$g_lang_label_delete}</button>
            </div>
        </div>
    </div>
</div>