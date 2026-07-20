var paginationSize = parseInt(sessionStorage.getItem('tabulatorPageSize') || '25', 10);
var tabulatorDefaults = {
    layout: 'fitColumns',
    pagination: true,
    paginationMode: 'remote',
    paginationSize: paginationSize,
    paginationSizeSelector: [10, 25, 50, 100, 500],
    movableColumns: true,
    resizableColumns: true,
    selectable: true,
    placeholder: 'No data available',
    ajaxURL: 'file_list_ajax',
    ajaxParams: function() {
        var params = { state: parseInt(document.getElementById('file-table').getAttribute('data-state') || '1', 10) };
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('keyword')) {
            params.keyword = urlParams.get('keyword');
            params.where = urlParams.get('where') || 'all';
            params.exact_phrase = urlParams.get('exact_phrase') || '';
            params.case_sensitivity = urlParams.get('case_sensitivity') || '';
        }
        return params;
    },
    ajaxConfig: 'GET',
    ajaxContentType: 'form'
};

function submitDeleteAction(mode, promptMsg) {
    var table = window.fileTable;
    if (!table) return;
    var rows = table.getSelectedRows();
    if (rows.length === 0) return;
    if (!confirm(promptMsg.replace('{n}', rows.length))) return;

    var ids = rows.map(function(r) { return r.getData().id; });
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'delete';
    form.style.display = 'none';
    var addInput = function(n, v) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = n;
        input.value = v;
        form.appendChild(input);
    };
    addInput('mode', mode);
    addInput('num_checkboxes', ids.length);
    ids.forEach(function(id, i) { addInput('id' + i, id); });
    var csrfFields = document.getElementById('delete-csrf-fields');
    if (csrfFields) {
        Array.from(csrfFields.querySelectorAll('input[type="hidden"]')).forEach(function(input) {
            form.appendChild(input.cloneNode());
        });
    }
    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('DOMContentLoaded', function() {
    var tableEl = document.getElementById('file-table');
    if (!tableEl) return;

    var table = new Tabulator('#file-table', Object.assign({}, tabulatorDefaults, {
        columns: [
            { title: '', formatter: 'rowSelection', titleFormatter: 'rowSelection', width: 40, headerSort: false },
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Filename', field: 'filename', widthGrow: 2,
              formatter: function(cell) {
                  return '<a href="' + cell.getData().details_link + '">' + cell.getValue() + '</a>';
              }
            },
            { title: 'Description', field: 'description', widthGrow: 3 },
            { title: 'Status', field: 'lock', width: 80, formatter: function(cell) {
                return cell.getValue() ? '<span class="text-danger">Locked</span>' : '<span class="text-success">Unlocked</span>';
            }},
            { title: 'Created', field: 'created_date', width: 120 },
            { title: 'Modified', field: 'modified_date', width: 120 },
            { title: 'Author', field: 'owner_name', width: 150 },
            { title: 'Department', field: 'dept_name', width: 120 },
            { title: 'Size', field: 'filesize', width: 80 }
        ]
    }));
    window.fileTable = table;

    table.on('pageSizeChanged', function(size) {
        sessionStorage.setItem('tabulatorPageSize', size);
    });

    var deleteBtn = document.getElementById('delete-selected');
    if (deleteBtn) {
        table.on('rowSelectionChanged', function() {
            deleteBtn.disabled = table.getSelectedRows().length === 0;
        });

        deleteBtn.addEventListener('click', function() {
            submitDeleteAction('tmpdel', 'Are you sure you want to archive {n} file(s)?');
        });
    }

    var undeleteBtn = document.getElementById('undelete-selected');
    if (undeleteBtn) {
        table.on('rowSelectionChanged', function() {
            undeleteBtn.disabled = table.getSelectedRows().length === 0;
            if (deletePermanentBtn) deletePermanentBtn.disabled = table.getSelectedRows().length === 0;
        });

        undeleteBtn.addEventListener('click', function() {
            submitDeleteAction('undelete', 'Are you sure you want to undelete {n} file(s)?');
        });
    }

    var deletePermanentBtn = document.getElementById('delete-permanent-selected');
    if (deletePermanentBtn) {
        deletePermanentBtn.addEventListener('click', function() {
            submitDeleteAction('delete_permanent', 'PERMANENTLY delete {n} file(s)? This cannot be undone!');
        });
    }

    var clearStatusBtn = document.getElementById('clear-status-selected');
    if (clearStatusBtn) {
        table.on('rowSelectionChanged', function() {
            clearStatusBtn.disabled = table.getSelectedRows().length === 0;
        });

        clearStatusBtn.addEventListener('click', function() {
            var rows = table.getSelectedRows();
            if (rows.length === 0) return;
            if (!confirm('Clear status for ' + rows.length + ' file(s)?')) return;

            var ids = rows.map(function(r) { return r.getData().id; });
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'file_ops';
            form.style.display = 'none';
            var addInput = function(n, v) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = n;
                input.value = v;
                form.appendChild(input);
            };
            addInput('submit', 'Clear Status');
            ids.forEach(function(id) { addInput('checkbox[]', id); });
            var csrfFields = document.getElementById('clear-csrf-fields');
            if (csrfFields) {
                Array.from(csrfFields.querySelectorAll('input[type="hidden"]')).forEach(function(input) {
                    form.appendChild(input.cloneNode());
                });
            }
            document.body.appendChild(form);
            form.submit();
        });
    }
});
