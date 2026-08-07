<div class="admin-crud-wrapper">
    <nav class="admin-crud-sidebar d-none d-md-block" id="adminSidebar">
        <div class="d-flex justify-content-end p-2">
            <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">&larr;</button>
        </div>
        <div class="nav flex-column">
            <div class="nav-section-header px-3 py-1 text-muted small text-uppercase">{$g_lang_label_management}</div>
            <a class="nav-link active" href="#" data-tab="users" data-bs-toggle="tab" data-bs-target="#crud-users">
                <i class="bi bi-people"></i> {$g_lang_users}
            </a>
            <a class="nav-link" href="#" data-tab="departments" data-bs-toggle="tab" data-bs-target="#crud-departments">
                <i class="bi bi-building"></i> {$g_lang_label_department}
            </a>
            <a class="nav-link" href="#" data-tab="categories" data-bs-toggle="tab" data-bs-target="#crud-categories">
                <i class="bi bi-folder"></i> {$g_lang_category}
            </a>
            <hr class="my-2">
            <a class="nav-link" href="admin">
                <i class="bi bi-arrow-left"></i> {$g_lang_label_dashboard}
            </a>
        </div>
    </nav>

    <main class="admin-crud-main">
        <div class="d-flex align-items-center gap-2 mb-3 d-md-none">
            <button class="sidebar-toggle" id="sidebarToggleMobile" title="Toggle sidebar">&#9776;</button>
            <h5 class="mb-0">{$g_lang_label_admin_crud}</h5>
        </div>

        <div class="tab-content" id="crudTabContent">
            <div class="tab-pane fade show active" id="crud-users" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{$g_lang_users}</h5>
                        <button class="btn btn-primary btn-sm" id="addUserBtn">+ {$g_lang_label_add}</button>
                    </div>
                    <div class="card-body">
                        <div id="users-table"></div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="crud-departments" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{$g_lang_label_department}</h5>
                        <button class="btn btn-primary btn-sm" id="addDeptBtn">+ {$g_lang_label_add}</button>
                    </div>
                    <div class="card-body">
                        <div id="departments-table"></div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="crud-categories" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">{$g_lang_category}</h5>
                        <button class="btn btn-primary btn-sm" id="addCatBtn">+ {$g_lang_label_add}</button>
                    </div>
                    <div class="card-body">
                        <div id="categories-table"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal for entity CRUD forms -->
<div class="modal fade" id="crudModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crudModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="crudModalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{$g_lang_button_cancel}</button>
                <button type="button" class="btn btn-primary" id="crudModalSave">{$g_lang_button_save}</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
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