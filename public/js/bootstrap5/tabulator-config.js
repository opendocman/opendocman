var tabulatorDefaults = {
    layout: 'fitColumns',
    pagination: 'local',
    paginationSize: 25,
    paginationSizeSelector: [10, 25, 50, 100],
    movableColumns: true,
    resizableColumns: true,
    placeholder: 'No data available'
};

document.addEventListener('DOMContentLoaded', function() {
    var dataEl = document.getElementById('file-table-data');
    if (!dataEl) return;
    
    var data = JSON.parse(dataEl.textContent);
    var table = new Tabulator('#file-table', Object.assign({}, tabulatorDefaults, {
        data: data,
        columns: [
            { title: 'ID', field: 'id', width: 60 },
            { title: 'Filename', field: 'filename', widthGrow: 2,
              formatter: function(cell) {
                  var v = cell.getValue();
                  var details = cell.getData().details_link;
                  return '<a href="' + details + '">' + v + '</a>';
              }
            },
            { title: 'Description', field: 'description', widthGrow: 3 },
            { title: 'Created', field: 'created_date', width: 120 },
            { title: 'Modified', field: 'modified_date', width: 120 },
            { title: 'Author', field: 'owner_name', width: 150 },
            { title: 'Department', field: 'dept_name', width: 120 },
            { title: 'Size', field: 'filesize', width: 80 }
        ]
    }));
});
