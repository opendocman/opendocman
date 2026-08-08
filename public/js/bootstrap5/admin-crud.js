(function() {
    'use strict';

    var csrfToken = window.csrf_token || '';
    var csrfFieldName = window.csrf_field_name || 'csrf_token';
    var csrfIndexName = window.csrfIndexName || '';
    var csrfIndex = window.csrfIndex || '';
    var entity = window.crudEntity || '';

    if (!entity) return;

    var paginationSize = parseInt(sessionStorage.getItem('adminCrudPageSize') || '25', 10);

    var columnGetters = {
        users: function() {
            return [
                { title: 'ID', field: 'id', width: 60 },
                { title: 'Username', field: 'username', widthGrow: 1 },
                { title: 'Last Name', field: 'last_name', widthGrow: 1 },
                { title: 'First Name', field: 'first_name', widthGrow: 1 },
                { title: 'Email', field: 'email', widthGrow: 2 },
                { title: 'Department', field: 'department_name', widthGrow: 1 },
                { title: 'Admin', field: 'is_admin', width: 70, formatter: function(c) { return c.getValue() == 1 ? 'Yes' : 'No'; } },
                { title: 'Reviewer', field: 'is_reviewer', width: 80, formatter: function(c) { return c.getValue() == 1 ? 'Yes' : 'No'; } },
                { title: '', field: 'actions', width: 120, headerSort: false, formatter: function(cell) {
                    var id = cell.getData().id;
                    return '<button class="btn btn-sm btn-outline-primary edit-row" data-id="' + id + '">Edit</button> ' +
                           '<button class="btn btn-sm btn-outline-danger delete-row" data-id="' + id + '">Del</button>';
                }}
            ];
        },
        departments: function() {
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
        },
        categories: function() {
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
    };

    var formBuilders = {
        users: function(rowData) {
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
        },
        departments: function(rowData) {
            var d = rowData || {};
            return '<form id="crudEntityForm">' +
                '<input type="hidden" name="id" value="' + (d.id || '') + '">' +
                '<div class="mb-3"><label class="form-label">Department Name</label><input type="text" name="name" class="form-control" required value="' + (d.name || '') + '"></div>' +
                '</form>';
        },
        categories: function(rowData) {
            var d = rowData || {};
            return '<form id="crudEntityForm">' +
                '<input type="hidden" name="id" value="' + (d.id || '') + '">' +
                '<div class="mb-3"><label class="form-label">Category Name</label><input type="text" name="name" class="form-control" required value="' + (d.name || '') + '"></div>' +
                '</form>';
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
            ajaxParams: function() { return { entity: entity, action: 'list' }; },
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
        new bootstrap.Modal(document.getElementById('crudModal')).show();
    }

    function openEditModal(rowData) {
        var prefix = { users: 'Edit User: ', departments: 'Edit Department: ', categories: 'Edit Category: ' };
        var nameField = { users: 'username', departments: 'name', categories: 'name' };
        document.getElementById('crudModalTitle').textContent = (prefix[entity] || 'Edit ') + (rowData[nameField[entity]] || '');
        document.getElementById('crudModalBody').innerHTML = formBuilders[entity](rowData);
        document.getElementById('crudModalSave').dataset.action = 'edit';
        new bootstrap.Modal(document.getElementById('crudModal')).show();
    }

    function openDeleteModal(rowData) {
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

        document.getElementById('deleteConfirmBtn').dataset.id = rowData.id;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function saveEntity() {
        var form = document.getElementById('crudEntityForm');
        if (!form) return;

        var action = document.getElementById('crudModalSave').dataset.action;
        var formData = new FormData(form);
        formData.append('entity', entity);
        formData.append('action', action);
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
            if (result.error) {
                alert(result.error);
                return;
            }
            var modal = bootstrap.Modal.getInstance(document.getElementById('crudModal'));
            if (modal) modal.hide();
            var table = window.crudTable;
            if (table) table.setPage(1);
        })
        .catch(function(err) {
            alert('Error saving: ' + err.message);
        });
    }

    function deleteEntity() {
        var btn = document.getElementById('deleteConfirmBtn');
        var id = btn.dataset.id;
        var formData = new FormData();
        formData.append('entity', entity);
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append(csrfFieldName, csrfToken);
        if (csrfIndexName) {
            formData.append(csrfIndexName, csrfIndex);
        }

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
            var table = window.crudTable;
            if (table) table.setPage(1);
        })
        .catch(function(err) {
            alert('Error deleting: ' + err.message);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var table = initTable();
        window.crudTable = table;

        document.getElementById('addBtn').addEventListener('click', openAddModal);
        document.getElementById('crudModalSave').addEventListener('click', saveEntity);
        document.getElementById('deleteConfirmBtn').addEventListener('click', deleteEntity);

        document.getElementById('crud-table').addEventListener('click', function(e) {
            var editBtn = e.target.closest('.edit-row');
            var deleteBtn = e.target.closest('.delete-row');
            if (!editBtn && !deleteBtn) return;

            var id = (editBtn || deleteBtn).dataset.id;
            var rowData = null;
            table.getData().forEach(function(row) {
                if (parseInt(row.id) === parseInt(id)) rowData = row;
            });

            if (editBtn && rowData) openEditModal(rowData);
            if (deleteBtn && rowData) openDeleteModal(rowData);
        });
    });
})();