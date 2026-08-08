<script>
window.crudEntity = 'categories';
window.categoryList = {$category_list|@json_encode};
window.userList = {$user_list|@json_encode};
window.csrfIndexName = '{$csrf_index_name}';
window.csrfIndex = '{$csrf_index_value}';
</script>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{$g_lang_category}</h5>
        <button class="btn btn-primary btn-sm" id="addBtn">+ {$g_lang_label_add}</button>
        <button class="btn btn-danger btn-sm" id="deleteMultiBtn" disabled>{$g_lang_label_delete}</button>
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