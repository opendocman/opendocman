(function() {
    'use strict';

    var csrfToken = window.csrf_token || '';
    var csrfFieldName = window.csrf_field_name || 'csrf_token';
    var csrfIndexName = window.csrfIndexName || '';
    var csrfIndex = window.csrfIndex || '';
    var entity = window.crudEntity || '';

    if (!entity) return;

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // Human-readable label for the current entity (users/departments/categories).
    function entityLabel() {
        var labels = { users: 'User', departments: 'Department', categories: 'Category' };
        return labels[entity] || entity;
    }

    // Show a dismissible success/error alert at the top of the CRUD card body.
    function showFlash(message, type) {
        var container = document.querySelector('.card-body');
        if (!container) return;
        var existing = document.getElementById('crudFlash');
        if (existing) existing.remove();
        var alert = document.createElement('div');
        alert.id = 'crudFlash';
        alert.className = 'alert alert-' + (type || 'success') + ' alert-dismissible fade show';
        alert.setAttribute('role', 'alert');
        alert.textContent = message;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-close';
        btn.setAttribute('data-bs-dismiss', 'alert');
        btn.setAttribute('aria-label', 'Close');
        alert.appendChild(btn);
        container.insertBefore(alert, container.firstChild);
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    }

    var paginationSize = parseInt(sessionStorage.getItem('adminCrudPageSize') || '25', 10);

    var columnGetters = {
        users: function() {
            return [
                { title: '', formatter: 'rowSelection', titleFormatter: 'rowSelection', width: 40, headerSort: false },
                { title: 'ID', field: 'id', width: 60 },
                { title: 'Username', field: 'username', widthGrow: 1 },
                { title: 'Last Name', field: 'last_name', widthGrow: 1 },
                { title: 'First Name', field: 'first_name', widthGrow: 1 },
                { title: 'Email', field: 'email', widthGrow: 2 },
                { title: 'Department', field: 'department_name', widthGrow: 1 },
                { title: 'Admin', field: 'is_admin', width: 70, formatter: function(c) { return c.getValue() == 1 ? 'Yes' : 'No'; } },
                { title: 'Reviewer', field: 'is_reviewer', width: 80, formatter: function(c) { return c.getValue() == 1 ? 'Yes' : 'No'; } },
                { title: '', field: 'actions', width: 240, minWidth: 240, maxWidth: 240, headerSort: false, resizable: false, formatter: function(cell) {
                    var id = cell.getData().id;
                    return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                           '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>' +
                           '<button class="btn btn-sm btn-outline-warning rotate-token" data-id="' + id + '">' + (window.rotateLabel || 'Rotate') + '</button>';
                }}
            ];
        },
        departments: function() {
            return [
                { title: '', formatter: 'rowSelection', titleFormatter: 'rowSelection', width: 40, headerSort: false },
                { title: 'ID', field: 'id', width: 60 },
                { title: 'Name', field: 'name', widthGrow: 3 },
                { title: 'Users', field: 'user_count', width: 80 },
                { title: '', field: 'actions', width: 120, minWidth: 120, headerSort: false, resizable: false, formatter: function(cell) {
                    var id = cell.getData().id;
                    return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                           '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>';
                }}
            ];
        },
        categories: function() {
            return [
                { title: '', formatter: 'rowSelection', titleFormatter: 'rowSelection', width: 40, headerSort: false },
                { title: 'ID', field: 'id', width: 60 },
                { title: 'Name', field: 'name', widthGrow: 3 },
                { title: 'Files', field: 'file_count', width: 80 },
                { title: '', field: 'actions', width: 120, minWidth: 120, headerSort: false, resizable: false, formatter: function(cell) {
                    var id = cell.getData().id;
                    return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                           '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>';
                }}
            ];
        },
        email_audit: function() {
            return [
                { title: 'Time', field: 'created', widthGrow: 1, headerSort: false },
                { title: 'From', field: 'from_address', widthGrow: 1 },
                { title: 'Msg ID', field: 'message_id', widthGrow: 1, headerSort: false },
                { title: 'Outcome', field: 'outcome', width: 110, headerSort: false, formatter: function(cell) {
                    var v = cell.getValue();
                    if (v === 'created') return '<span class="badge bg-success">Created</span>';
                    if (v === 'rejected') return '<span class="badge bg-warning text-dark">Rejected</span>';
                    return '<span class="badge bg-danger">Error</span>';
                }},
                { title: 'Document', field: 'document_id', width: 110, headerSort: false, formatter: function(cell) {
                    var v = cell.getValue();
                    if (!v) return '<span class="text-muted">-</span>';
                    return '<a href="details?id=' + v + '">#' + v + '</a>';
                }},
                { title: 'Reason', field: 'reason', widthGrow: 2 }
            ];
        }
    };

    var formBuilders = {
users: function(rowData) {
            var d = rowData || {};
            var isEdit = !!d.id;
            var depts = window.departmentList || [];
            var deptOpts = depts.map(function(dept) {
                var sel = parseInt(dept.id) === parseInt(d.department) ? ' selected' : '';
                return '<option value="' + escapeHtml(dept.id) + '"' + sel + '>' + escapeHtml(dept.name) + '</option>';
            }).join('');

            var pwField = isEdit
                ? '<div class="mb-3"><label class="form-label">Password (leave blank to keep current)</label><input type="password" name="password" class="form-control" maxlength="32"></div>'
                : '<div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="5" maxlength="32"></div>';

            var reviewerDepts = (d.reviewer_depts || '').split(',').filter(Boolean);
            var deptReviewHtml = depts.map(function(dept) {
                var checked = reviewerDepts.indexOf(String(dept.id)) !== -1 ? ' checked' : '';
                return '<div class="form-check form-check-inline"><input type="checkbox" name="department_review[]" value="' + escapeHtml(dept.id) + '" class="form-check-input" id="dr_' + dept.id + '"' + checked + '><label class="form-check-label" for="dr_' + dept.id + '">' + escapeHtml(dept.name) + '</label></div>';
            }).join('');

            return '<form id="crudEntityForm">' +
                '<input type="hidden" name="id" value="' + escapeHtml(d.id || '') + '">' +
                '<div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required value="' + escapeHtml(d.username || '') + '"></div>' +
                '<div class="mb-3"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" required value="' + escapeHtml(d.last_name || '') + '"></div>' +
                '<div class="mb-3"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" required value="' + escapeHtml(d.first_name || '') + '"></div>' +
                '<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="' + escapeHtml(d.email || '') + '"></div>' +
                '<div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="' + escapeHtml(d.phone || '') + '"></div>' +
                '<div class="mb-3"><label class="form-label">Department</label><select name="department" class="form-select">' + deptOpts + '</select></div>' +
                '<div class="mb-3"><label class="form-label">Department Reviewer</label><div>' + deptReviewHtml + '</div></div>' +
                pwField +
                '<div class="mb-3 form-check"><input type="checkbox" name="admin" value="1" class="form-check-input" id="f_admin" ' + (d.is_admin == 1 ? 'checked' : '') + '><label class="form-check-label" for="f_admin">Admin?</label></div>' +
                '<div class="mb-3 form-check"><input type="checkbox" name="can_add" value="1" class="form-check-input" id="f_can_add" ' + (d.can_add == 1 ? 'checked' : '') + '><label class="form-check-label" for="f_can_add">Can Add?</label></div>' +
                '<div class="mb-3 form-check"><input type="checkbox" name="can_checkin" value="1" class="form-check-input" id="f_can_checkin" ' + (d.can_checkin == 1 ? 'checked' : '') + '><label class="form-check-label" for="f_can_checkin">Can Check-In?</label></div>' +
                '</form>';
        },
        departments: function(rowData) {
            var d = rowData || {};
            return '<form id="crudEntityForm">' +
                '<input type="hidden" name="id" value="' + escapeHtml(d.id || '') + '">' +
                '<div class="mb-3"><label class="form-label">Department Name</label><input type="text" name="name" class="form-control" required value="' + escapeHtml(d.name || '') + '"></div>' +
                '</form>';
        },
        categories: function(rowData) {
            var d = rowData || {};
            var html = '<form id="crudEntityForm">' +
                '<input type="hidden" name="id" value="' + escapeHtml(d.id || '') + '">' +
                '<div class="mb-3"><label class="form-label">Category Name</label><input type="text" name="name" class="form-control" required value="' + escapeHtml(d.name || '') + '"></div>' +
                '<hr><h6>Default permissions for documents in this category (optional)</h6><div id="categoryPermsEditor"></div>' +
                '</form>';
            return html;
        }
    };

    function initTable() {
        var el = document.getElementById('crud-table');
        if (!el) return null;

        var table = new Tabulator('#crud-table', {
            layout: 'fitColumns',
            pagination: true,
            paginationMode: 'remote',
            paginationSize: paginationSize,
            paginationSizeSelector: [10, 25, 50, 100],
            movableColumns: true,
            resizableColumns: true,
            placeholder: 'No data available',
            ajaxURL: 'admin_crud_ajax',
            ajaxParams: function() {
                var params = { entity: entity, action: 'list', filter: window.crudFilter || '' };
                if (entity === 'email_audit' && window.crudOutcome) {
                    params.outcome = window.crudOutcome;
                }
                return params;
            },
            ajaxConfig: 'GET',
            ajaxContentType: 'form',
            columns: columnGetters[entity]()
        });

        table.on('pageSizeChanged', function(size) {
            sessionStorage.setItem('adminCrudPageSize', size);
        });

        return table;
    }

    function openAddModal() {
        var labels = { users: 'Add User', departments: 'Add Department', categories: 'Add Category' };
        document.getElementById('crudModalTitle').textContent = labels[entity] || 'Add';
        document.getElementById('crudModalBody').innerHTML = formBuilders[entity](null);
        document.getElementById('crudModalSave').dataset.action = 'add';
        if (entity === 'categories') {
            initPermissionsEditor('#categoryPermsEditor', { departments: window.departmentList || [], users: window.userList || [], labels: window.permEditorLabels || {} });
        }
        new bootstrap.Modal(document.getElementById('crudModal')).show();
    }

    function openEditModal(rowData) {
        var prefix = { users: 'Edit User: ', departments: 'Edit Department: ', categories: 'Edit Category: ' };
        var nameField = { users: 'username', departments: 'name', categories: 'name' };
        document.getElementById('crudModalTitle').textContent = (prefix[entity] || 'Edit ') + (rowData[nameField[entity]] || '');
        document.getElementById('crudModalBody').innerHTML = formBuilders[entity](rowData);
        document.getElementById('crudModalSave').dataset.action = 'edit';
        if (entity === 'categories') {
            initPermissionsEditor('#categoryPermsEditor', { departments: window.departmentList || [], users: window.userList || [], labels: window.permEditorLabels || {} });
            var editor = initPermissionsEditor('#categoryPermsEditor');
            if (editor && rowData.id) {
                fetch('admin_crud_ajax?entity=categories&action=get_perms&id=' + rowData.id)
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (editor.loadTemplate) editor.loadTemplate(data);
                    });
            }
        }
        new bootstrap.Modal(document.getElementById('crudModal')).show();
    }

    function openDeleteModal(rowData) {
        var reassignField = document.getElementById('reassignField');
        var reassignSelect = document.getElementById('reassignSelect');

        if (entity === 'departments' || entity === 'categories') {
            if (reassignField) reassignField.style.display = 'block';
            if (reassignSelect) {
                reassignSelect.innerHTML = '';
                var list = entity === 'departments' ? (window.departmentList || []) : (window.categoryList || []);
                list.forEach(function(item) {
                    if (parseInt(item.id) !== parseInt(rowData.id)) {
                        var opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name;
                        reassignSelect.appendChild(opt);
                    }
                });
            }
        } else if (reassignField) {
            reassignField.style.display = 'none';
        }

        document.getElementById('deleteConfirmBtn').dataset.id = rowData.id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function saveEntity() {
        var form = document.getElementById('crudEntityForm');
        if (!form) return;
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        var action = document.getElementById('crudModalSave').dataset.action;
        var formData = new FormData(form);
        formData.append('entity', entity);
        formData.append('action', action);

        if (entity === 'categories') {
            var editor = initPermissionsEditor('#categoryPermsEditor');
            if (editor) {
                var permData = editor.getData();
                Object.keys(permData.department_permission || {}).forEach(function(deptId) {
                    formData.append('department_permission[' + deptId + ']', permData.department_permission[deptId]);
                });
                Object.keys(permData.user_permission || {}).forEach(function(userId) {
                    formData.append('user_permission[' + userId + ']', permData.user_permission[userId]);
                });
            }
        }

        formData.append(csrfFieldName, csrfToken);
        if (csrfIndexName) {
            formData.append(csrfIndexName, csrfIndex);
        }

        fetch('admin_crud_ajax', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.csrf_token) {
                csrfToken = result.csrf_token;
                csrfIndex = result.csrf_index || '';
            }
            if (result.error) {
                alert(result.error);
                return;
            }
            document.activeElement && document.activeElement.blur();
            var modal = bootstrap.Modal.getInstance(document.getElementById('crudModal'));
            if (modal) modal.hide();
            var table = window.crudTable;
            if (table) table.setPage(1);
            showFlash(entityLabel() + ' saved successfully', 'success');
        })
        .catch(function(err) {
            alert('Error saving: ' + err.message);
        });
    }

    function deleteEntity() {
        var btn = document.getElementById('deleteConfirmBtn');
        var ids = (btn.dataset.ids || btn.dataset.id || '').split(',').filter(Boolean);
        if (ids.length === 0) return;
        var formData = new FormData();
        formData.append('entity', entity);
        formData.append('action', 'delete');
        formData.append('id', ids[0]);
        if (ids.length > 1) {
            for (var i = 0; i < ids.length; i++) {
                formData.append('ids[]', ids[i]);
            }
        }
        formData.append(csrfFieldName, csrfToken);
        if (csrfIndexName) {
            formData.append(csrfIndexName, csrfIndex);
        }

        var reassignSelect = document.getElementById('reassignSelect');
        if (reassignSelect && reassignSelect.style.display !== 'none' && reassignSelect.value) {
            formData.append('assigned_id', reassignSelect.value);
        }

        fetch('admin_crud_ajax', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.csrf_token) {
                csrfToken = result.csrf_token;
                csrfIndex = result.csrf_index || '';
            }
            if (result.error) {
                alert(result.error);
                return;
            }
            document.activeElement && document.activeElement.blur();
            var modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (modal) modal.hide();
            var table = window.crudTable;
            if (table) table.setPage(1);
            showFlash(entityLabel() + ' deleted successfully', 'success');
        })
        .catch(function(err) {
            alert('Error deleting: ' + err.message);
        });
    }

    function deleteMulti() {
        var table = window.crudTable;
        if (!table) return;
        var rows = table.getSelectedRows();
        if (rows.length === 0) return;
        if (!confirm('Delete ' + rows.length + ' selected ' + entity + '?')) return;

        var ids = rows.map(function(r) { return r.getData().id; });
        document.getElementById('deleteConfirmBtn').dataset.ids = ids.join(',');
        var btn = document.getElementById('deleteConfirmBtn');

        if (entity === 'departments' || entity === 'categories') {
            document.getElementById('reassignField').style.display = 'block';
            var sel = document.getElementById('reassignSelect');
            sel.innerHTML = '';
            var list = entity === 'departments' ? (window.departmentList || []) : (window.categoryList || []);
            list.forEach(function(item) {
                if (ids.indexOf(String(item.id)) === -1) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    sel.appendChild(opt);
                }
            });
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        } else {
            btn.dataset.id = ids[0];
            deleteEntity();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var table = initTable();
        window.crudTable = table;

        var addBtn = document.getElementById('addBtn');
        if (addBtn) addBtn.addEventListener('click', openAddModal);
        var modalSave = document.getElementById('crudModalSave');
        if (modalSave) modalSave.addEventListener('click', saveEntity);
        var deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
        if (deleteConfirmBtn) deleteConfirmBtn.addEventListener('click', deleteEntity);

        var multiBtn = document.getElementById('deleteMultiBtn');
        if (multiBtn) {
            table.on('rowSelectionChanged', function() {
                multiBtn.disabled = table.getSelectedRows().length === 0;
            });
            multiBtn.addEventListener('click', deleteMulti);
        }

        document.getElementById('crud-table').addEventListener('click', function(e) {
            var editBtn = e.target.closest('.edit-row');
            var deleteBtn = e.target.closest('.delete-row');
            var rotateBtn = e.target.closest('.rotate-token');
            if (!editBtn && !deleteBtn && !rotateBtn) return;

            var id = (editBtn || deleteBtn || rotateBtn).dataset.id;
            var rowData = null;
            table.getData().forEach(function(row) {
                if (parseInt(row.id) === parseInt(id)) rowData = row;
            });

            if (editBtn && rowData) openEditModal(rowData);
            if (deleteBtn && rowData) openDeleteModal(rowData);
            if (rotateBtn) rotateToken(id);
        });
    });

    function rotateToken(id) {
        var formData = new FormData();
        formData.append('entity', 'users');
        formData.append('action', 'rotate_mail_token');
        formData.append('item', id);
        formData.append(csrfFieldName, csrfToken);
        if (csrfIndexName) {
            formData.append(csrfIndexName, csrfIndex);
        }
        fetch('admin_crud_ajax', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.csrf_token) {
                csrfToken = result.csrf_token;
                csrfIndex = result.csrf_index || '';
            }
            if (result.error) {
                alert(result.error);
                return;
            }
            alert(window.rotateLabel + ': ' + result.token);
        })
        .catch(function(err) {
            alert('Error rotating token: ' + err.message);
        });
    }
})();