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

    window.fileTable = new Tabulator('#file-table', Object.assign({}, tabulatorDefaults, {
        columns: [
            { title: '', formatter: 'rowSelection', titleFormatter: 'rowSelection', width: 40, headerSort: false },
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Filename', field: 'filename', widthGrow: 2,
              formatter: function(cell) {
                  var v = cell.getValue();
                  var details = cell.getData().details_link;
                  return '<a href="' + details + '">' + v + '</a>';
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
});
