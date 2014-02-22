$(document).ready(function() {

    $('#filetable').dataTable({
            "bStateSave": true,
            "sPaginationType": "full_numbers",
            "oLanguage": {
                "sUrl": "includes/language/DataTables/datatables." + langLanguage + ".txt"
            }
        });

    $("#checkall").live('click', function() {
        var checked_status = this.checked;
        $(".checkbox").each(function()
        {
            this.checked = checked_status;
        });
    });

    // This section controls the Specific User Permissions on add/edit pages
    var $multiViewObject = $(".multiView");

    if ($multiViewObject.length > 0) {
        $multiViewObject.multiselect({
            selectedText: "# " + langOf + " # " + langSelected,
            uncheckAllText: langUncheckAll,
            checkAllText: langCheckAll,
            noneSelectedText: langNoneSelected
        });

        // Add a multi-select / search to the top of the Specific User Permissions controls
        $multiViewObject.multiselectfilter();
    }
    /* Animated Message */
    $(".close").click(function() {
        $("#last_message").animate({left: "+=10px"}).animate({left: "-5000px"});
    });
    blink();

    // If the user is an admin, then disable the reviewer input
    var $adminUser = $('#cb_admin');

    if ($adminUser.length > 0) {
        $adminUser.change(function() {
            if ($(this).attr("checked")) {
                // checked
                $('#userReviewDepartmentRow').fadeOut(200);
                return;
            } else {
                $('#userReviewDepartmentRow').fadeIn(200);
            }

        })

        function toggleAdminReviewerBoxes() {
            if ($adminUser.not(':checked')) {

                alert('not currently checked');
            } else {
                alert('is currently checked');
            }
        }

    }
    // END admin/reviewer toggles

//        
//        // validate the comment form when it is submitted
//	if($("#settingsForm").length > 0) {
//        $("#settingsForm").validate();
//        // validate signup form on keyup and submit
//	$("#settingsForm").validate({
//		rules: {
//			dataDir: "required",
//                        max_file_size: {
//                            required: true,
//                            number: true
//                        },
//			site_mail: {
//				required: true,
//				email: true
//			}
//		}
//		
//	});
//        } 
});

function blink() {
    $("#last_message").fadeOut(800).fadeIn(800).fadeOut(400).fadeIn(400).fadeOut(400).fadeIn(400);
}
function nudge() {
    $("#last_message").animate({left: "+=5px"}, 20).animate({top: "+=5px"}, 20).animate({top: "-=10px"}, 20).animate({left: "-=10px"}, 20)
            .animate({top: "+=5px"}, 20).animate({left: "+=5px"}, 20)
            .animate({left: "+=5px"}, 20).animate({top: "+=5px"}, 20).animate({top: "-=10px"}, 20).animate({left: "-=10px"}, 20)
            .animate({top: "+=5px"}, 20).animate({left: "+=5px"}, 20);
}

//subselect udf
function showdivs(str, add, table)
{
    var add_value = add;
    var table_value = table;

    if (window.XMLHttpRequest)
    {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    }
    else
    {// code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            document.getElementById("txtHint").innerHTML = xmlhttp.responseText;
        }
    }
    if (add_value == 'add') {
        xmlhttp.open("GET", "ajax_udf.php?q=" + str + '&add_value=' + add_value + '&table=' + table_value, true);
    }
    else if (add_value == 'edit') {
        xmlhttp.open("GET", "ajax_udf.php?q=" + str + '&add_value=' + add_value + '&table=' + table_value, true);
    }
    else {
        xmlhttp.open("GET", "ajax_udf.php?q=" + str + '&add_value=' + add_value, true);
    }
    //xmlhttp.open("GET","ajax_udf.php?q="+str,true);
    xmlhttp.send();
}


function showdropdowns(str, add, table)
{
    var add_value = add;
    var table_value = table;

    if (window.XMLHttpRequest)
    {// code for IE7+, Firefox, Chrome, Opera, Safari
        xmlhttp = new XMLHttpRequest();
    }
    else
    {// code for IE6, IE5
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function()
    {
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
        {
            document.getElementById('txtHint' + table_value).innerHTML = xmlhttp.responseText;
        }
    }
    if (add_value == 'add') {
        xmlhttp.open("GET", "ajax_udf.php?q=" + str + '&add_value=' + add_value + '&table=' + table_value, true);
    }
    else if (add_value == 'edit') {
        xmlhttp.open("GET", "ajax_udf.php?q=" + str + '&add_value=' + add_value + '&table=' + table_value, true);
    }

    xmlhttp.send();
}

function checksec() {
    var i_value = document.getElementById('i_value').value;
    for (i = 0; i <= i_value; i++) {
        $tablename = document.getElementById('tablename' + i);
        if ($tablename && $tablename.length != null) {
            if ($tablename.value) {
                tablename = $tablename.value;
                $tablename.value = document.getElementById('odm_udftbl_' + tablename + '_secondary').value;
            }
        }
    }

}