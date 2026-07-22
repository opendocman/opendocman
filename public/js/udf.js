// Sub-select UDF AJAX: switch between primary/secondary items on the UDF edit page
function showdivs(str, add, table)
{
    var add_value = add;
    var table_value = table;

    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }
    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('txtHint').innerHTML = xmlhttp.responseText;
        }
    };
    if (add_value == 'add') {
        xmlhttp.open('GET', 'ajax_udf?q=' + str + '&add_value=' + add_value + '&table=' + table_value, true);
    } else if (add_value == 'edit') {
        xmlhttp.open('GET', 'ajax_udf?q=' + str + '&add_value=' + add_value + '&table=' + table_value, true);
    } else {
        xmlhttp.open('GET', 'ajax_udf?q=' + str + '&add_value=' + add_value, true);
    }
    xmlhttp.send();
}

// Sub-select UDF AJAX: populate secondary dropdowns on add/edit file forms
function showdropdowns(str, add, table)
{
    var add_value = add;
    var table_value = table;

    if (window.XMLHttpRequest) {
        xmlhttp = new XMLHttpRequest();
    } else {
        xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
    }
    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            document.getElementById('txtHint' + table_value).innerHTML = xmlhttp.responseText;
        }
    };
    if (add_value == 'add') {
        xmlhttp.open('GET', 'ajax_udf?q=' + str + '&add_value=' + add_value + '&table=' + table_value, true);
    } else if (add_value == 'edit') {
        xmlhttp.open('GET', 'ajax_udf?q=' + str + '&add_value=' + add_value + '&table=' + table_value, true);
    }

    xmlhttp.send();
}
