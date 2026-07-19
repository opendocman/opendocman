var tabulatorDefaults = {
    layout: 'fitColumns',
    pagination: true,
    paginationMode: 'remote',
    paginationSize: 25,
    paginationSizeSelector: [10, 25, 50, 100],
    movableColumns: true,
    resizableColumns: true,
    selectable: true,
    placeholder: 'No data available',
    ajaxURL: 'file_list_ajax',
    ajaxParams: function() {
        return { state: 1 };
    },
    ajaxConfig: 'GET',
    ajaxContentType: 'form'
};

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

    var deleteBtn = document.getElementById('delete-selected');
    if (deleteBtn) {
        table.on('rowSelectionChanged', function() {
            deleteBtn.disabled = table.getSelectedRows().length === 0;
        });

        deleteBtn.addEventListener('click', function() {
            var rows = table.getSelectedRows();
            if (rows.length === 0) return;

            var ids = rows.map(function(r) { return r.getData().id; });
            if (!confirm('Are you sure you want to archive ' + ids.length + ' file(s)?')) return;

            var params = new URLSearchParams();
            params.set('mode', 'tmpdel');
            params.set('num_checkboxes', ids.length);
            ids.forEach(function(id, i) { params.set('id' + i, id); });

            window.location.href = 'delete?' + params.toString();
        });
    }
});
