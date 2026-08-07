(function() {
    'use strict';

    var csrfToken = window.csrf_token || '';
    var csrfFieldName = window.csrf_field_name || 'csrf_token';
    var baseUrl = '/';

    var paginationSize = parseInt(sessionStorage.getItem('adminCrudPageSize') || '25', 10);

    function getEntityConfig(entity) {
        var common = {
            pagination: true,
            paginationMode: 'remote',
            paginationSize: paginationSize,
            paginationSizeSelector: [10, 25, 50, 100],
            movableColumns: true,
            resizableColumns: true,
            placeholder: 'No data available',
            ajaxURL: 'admin_crud_ajax',
            ajaxParams: function() { return { entity: entity, action: 'list' }; },
            ajaxConfig: 'GET',
            ajaxContentType: 'form'
        };
        return common;
    }

    function getUserColumns() {
        return [
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Username', field: 'username', widthGrow: 1 },
            { title: 'Last Name', field: 'last_name', widthGrow: 1 },
            { title: 'First Name', field: 'first_name', widthGrow: 1 },
            { title: 'Email', field: 'email', widthGrow: 2 },
            { title: 'Department', field: 'department_name', widthGrow: 1 },
            { title: 'Admin', field: 'is_admin', width: 70, formatter: function(c) { return c.getValue() == 1 ? 'Yes' : 'No'; } },
            { title: '', field: 'actions', width: 120, headerSort: false, formatter: function(cell) {
                var id = cell.getData().id;
                return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                       '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>';
            }}
        ];
    }

    function getDepartmentColumns() {
        return [
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Name', field: 'name', widthGrow: 3 },
            { title: 'Users', field: 'user_count', width: 80 },
            { title: '', field: 'actions', width: 120, headerSort: false, formatter: function(cell) {
                var id = cell.getData().id;
                return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                       '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>';
            }}
        ];
    }

    function getCategoryColumns() {
        return [
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Name', field: 'name', widthGrow: 3 },
            { title: 'Files', field: 'file_count', width: 80 },
            { title: '', field: 'actions', width: 120, headerSort: false, formatter: function(cell) {
                var id = cell.getData().id;
                return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                       '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>';
            }}
        ];
    }

    function initTable(tableId, entity, columns) {
        var el = document.getElementById(tableId);
        if (!el) return null;

        var table = new Tabulator('#' + tableId, Object.assign({}, getEntityConfig(entity), {
            columns: columns,
            layout: 'fitColumns',
        }));

        table.on('pageSizeChanged', function(size) {
            sessionStorage.setItem('adminCrudPageSize', size);
        });

        return table;
    }

    function getUserFormHtml(rowData) {
        var d = rowData || {};
        var depts = window.departmentList || [];
        var deptOpts = depts.map(function(dept) {
            var sel = parseInt(dept.id) === parseInt(d.department) ? ' selected' : '';
            return '<option value="' + dept.id + '"' + sel + '>' + dept.name + '</option>';
        }).join('');

        return '<form id="crudEntityForm">' +
            '<input type="hidden" name="id" value="' + (d.id || '') + '">' +
            '<div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required value="' + (d.username || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" required value="' + (d.last_name || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" required value="' + (d.first_name || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="' + (d.email || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="' + (d.phone || '') + '"></div>' +
            '<div class="mb-3"><label class="form-label">Department</label><select name="department" class="form-select">' + deptOpts + '</select></div>' +
            '<div class="mb-3"><label class="form-label">Password (leave blank to keep current)</label><input type="password" name="password" class="form-control" maxlength="32"></div>' +
            '<div class="mb-3 form-check"><input type="checkbox" name="admin" value="1" class="form-check-input" id="f_admin" ' + (d.is_admin == 1 ? 'checked' : '') + '><label class="form-check-label" for="f_admin">Admin?</label></div>' +
            '<div class="mb-3 form-check"><input type="checkbox" name="can_add" value="1" class="form-check-input" id="f_can_add" ' + (d.can_add == 1 ? 'checked' : '') + '><label class="form-check-label" for="f_can_add">Can Add?</label></div>' +
            '<div class="mb-3 form-check"><input type="checkbox" name="can_checkin" value="1" class="form-check-input" id="f_can_checkin" ' + (d.can_checkin == 1 ? 'checked' : '') + '><label class="form-check-label" for="f_can_checkin">Can Check-In?</label></div>' +
            '</form>';
    }

    function getDeptFormHtml(rowData) {
        var d = rowData || {};
        return '<form id="crudEntityForm">' +
            '<input type="hidden" name="id" value="' + (d.id || '') + '">' +
            '<div class="mb-3"><label class="form-label">Department Name</label><input type="text" name="name" class="form-control" required value="' + (d.name || '') + '"></div>' +
            '</form>';
    }

    function getCatFormHtml(rowData) {
        var d = rowData || {};
        return '<form id="crudEntityForm">' +
            '<input type="hidden" name="id" value="' + (d.id || '') + '">' +
            '<div class="mb-3"><label class="form-label">Category Name</label><input type="text" name="name" class="form-control" required value="' + (d.name || '') + '"></div>' +
            '</form>';
    }

    function openAddModal(entity) {
        var title, html, formAction;
        switch (entity) {
            case 'users':
                title = 'Add User';
                html = getUserFormHtml(null);
                break;
            case 'departments':
                title = 'Add Department';
                html = getDeptFormHtml(null);
                break;
            case 'categories':
                title = 'Add Category';
                html = getCatFormHtml(null);
                break;
        }
        document.getElementById('crudModalTitle').textContent = title;
        document.getElementById('crudModalBody').innerHTML = html;
        document.getElementById('crudModalSave').dataset.entity = entity;
        document.getElementById('crudModalSave').dataset.action = 'add';
        var modal = new bootstrap.Modal(document.getElementById('crudModal'));
        modal.show();
    }

    function openEditModal(entity, rowData) {
        var title, html;
        switch (entity) {
            case 'users':
                title = 'Edit User: ' + rowData.username;
                html = getUserFormHtml(rowData);
                break;
            case 'departments':
                title = 'Edit Department: ' + rowData.name;
                html = getDeptFormHtml(rowData);
                break;
            case 'categories':
                title = 'Edit Category: ' + rowData.name;
                html = getCatFormHtml(rowData);
                break;
        }
        document.getElementById('crudModalTitle').textContent = title;
        document.getElementById('crudModalBody').innerHTML = html;
        document.getElementById('crudModalSave').dataset.entity = entity;
        document.getElementById('crudModalSave').dataset.action = 'edit';
        var modal = new bootstrap.Modal(document.getElementById('crudModal'));
        modal.show();
    }

    function openDeleteModal(entity, rowData) {
        var table = window['crudTable_' + entity];
        var reassignField = document.getElementById('reassignField');
        var reassignSelect = document.getElementById('reassignSelect');

        if (entity === 'departments' || entity === 'categories') {
            reassignField.style.display = 'block';
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
        } else {
            reassignField.style.display = 'none';
        }

        document.getElementById('deleteConfirmBtn').dataset.entity = entity;
        document.getElementById('deleteConfirmBtn').dataset.id = rowData.id;
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    function saveEntity() {
        var form = document.getElementById('crudEntityForm');
        if (!form) return;

        var entity = document.getElementById('crudModalSave').dataset.entity;
        var action = document.getElementById('crudModalSave').dataset.action;
        var formData = new FormData(form);
        formData.append('entity', entity);
        formData.append('action', action);
        formData.append(csrfFieldName, csrfToken);

        fetch('admin_crud_ajax', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.error) {
                alert(result.error);
                return;
            }
            var modal = bootstrap.Modal.getInstance(document.getElementById('crudModal'));
            if (modal) modal.hide();
            var table = window['crudTable_' + entity];
            if (table) table.setPage(1);
        })
        .catch(function(err) {
            alert('Error saving: ' + err.message);
        });
    }

    function deleteEntity() {
        var btn = document.getElementById('deleteConfirmBtn');
        var entity = btn.dataset.entity;
        var id = btn.dataset.id;
        var formData = new FormData();
        formData.append('entity', entity);
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append(csrfFieldName, csrfToken);

        var reassignSelect = document.getElementById('reassignSelect');
        if (reassignSelect.style.display !== 'none' && reassignSelect.value) {
            formData.append('assigned_id', reassignSelect.value);
        }

        fetch('admin_crud_ajax', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (result.error) {
                alert(result.error);
                return;
            }
            var modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (modal) modal.hide();
            var table = window['crudTable_' + entity];
            if (table) table.setPage(1);
        })
        .catch(function(err) {
            alert('Error deleting: ' + err.message);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var usersTable = initTable('users-table', 'users', getUserColumns());
        var deptsTable = initTable('departments-table', 'departments', getDepartmentColumns());
        var catsTable = initTable('categories-table', 'categories', getCategoryColumns());

        window.crudTable_users = usersTable;
        window.crudTable_departments = deptsTable;
        window.crudTable_categories = catsTable;

        document.querySelectorAll('[data-tab]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.admin-crud-sidebar .nav-link').forEach(function(l) {
                    l.classList.remove('active');
                });
                this.classList.add('active');
                var tab = this.dataset.tab;
                document.querySelectorAll('.tab-pane').forEach(function(p) {
                    p.classList.remove('show', 'active');
                });
                var target = document.getElementById('crud-' + tab);
                if (target) {
                    target.classList.add('show', 'active');
                    var table = window['crudTable_' + tab];
                    if (table) table.redraw();
                }
            });
        });

        var sidebar = document.getElementById('adminSidebar');
        var toggleBtn = document.getElementById('sidebarToggle');
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                this.textContent = sidebar.classList.contains('collapsed') ? '\u2192' : '\u2190';
                setTimeout(function() {
                    if (usersTable) usersTable.redraw();
                    if (deptsTable) deptsTable.redraw();
                    if (catsTable) catsTable.redraw();
                }, 350);
            });
        }

        document.getElementById('addUserBtn').addEventListener('click', function() { openAddModal('users'); });
        document.getElementById('addDeptBtn').addEventListener('click', function() { openAddModal('departments'); });
        document.getElementById('addCatBtn').addEventListener('click', function() { openAddModal('categories'); });

        document.getElementById('crudModalSave').addEventListener('click', saveEntity);

        document.getElementById('deleteConfirmBtn').addEventListener('click', deleteEntity);

        document.querySelectorAll('.tab-pane').forEach(function(pane) {
            pane.addEventListener('click', function(e) {
                var editBtn = e.target.closest('.edit-row');
                var deleteBtn = e.target.closest('.delete-row');
                if (!editBtn && !deleteBtn) return;

                var id = (editBtn || deleteBtn).dataset.id;
                var entity = this.id.replace('crud-', '');
                var table = window['crudTable_' + entity];
                if (!table) return;

                var rowData = null;
                table.getData().forEach(function(row) {
                    if (parseInt(row.id) === parseInt(id)) rowData = row;
                });

                if (editBtn && rowData) openEditModal(entity, rowData);
                if (deleteBtn && rowData) openDeleteModal(entity, rowData);
            });
        });
    });
})();