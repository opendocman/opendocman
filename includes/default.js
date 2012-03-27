$(document).ready(function() {
		
    $('#filetable').dataTable({
        "bStateSave": true,
        "sPaginationType": "full_numbers",
        "oLanguage": {
            "sUrl": "includes/language/DataTables/datatables." + langLanguage + ".txt"
        }
    });

    $("#checkall").live('click',function () {
        var checked_status = this.checked;       
        $(".checkbox").each(function()
            {
                this.checked = checked_status;
            });
        });

    // This section controls the Specific User Permissions on add/edit pages
    var $multiViewObject = $(".multiView");

    if($multiViewObject.length>0) {
        $multiViewObject.multiselect({
            selectedText:   "# " + langOf + " # " + langSelected,
            uncheckAllText: langUncheckAll,
            checkAllText:   langCheckAll,
            noneSelectedText: langNoneSelected
        });

        // Add a multi-select / search to the top of the Specific User Permissions controls
        $multiViewObject.multiselectfilter();
    }
    
    var forbiddenDeptSelect = $("#deptForbiddenSelect");
    var viewDeptSelect = $("#deptViewSelect");
    var readDeptSelect = $("#deptReadSelect");
    var modifyDeptSelect = $("#deptModifySelect");
    var adminDeptSelect = $("#deptAdminSelect");
    
    if(forbiddenDeptSelect.length>0){
        
        forbiddenDeptSelect.bind("multiselectclick", function(event, ui) {
            console.log('Value ' + ui.value);
            console.log('Text:' + ui.text);
            var val = ui.value;

            var index;
            
            $("#deptViewOption4").attr("selected", "selected");
            
            $('#deptViewSelect option[value]').each(function(key,value) {
                deptViewOption = $(value);
                thisItemViewVal = deptViewOption.val();
                console.log('deptViewSelect Option Value = ' + thisItemViewVal);

                if(thisItemViewVal === val) {
                    console.log('thisItemViewVal: ' + thisItemViewVal + ", val: " + val);
                    console.log('deptViewOption.attr:' + deptViewOption.attr("selected"));
                    console.log(deptViewOption);
                     
                        console.log(deptViewOption.name + ' is selected');
                        
                        deptViewOption.attr("selected", false);
//                    } else {
//                        deptViewOption.attr("selected", "selected");

                    console.log('deptViewOption Selected? ' + deptViewOption.attr("selected"))
                }
                index++;
            });
            
            
        })
    }
        /* Animated Message */
        $(".close").click(function(){
            $("#last_message").animate({left:"+=10px"}).animate({left:"-5000px"});
        });
        blink();
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

//    userReviewCheck = $('#userReviewCheck');
//    userReviewDepartmentsList = $('#userReviewDepartmentsList');
//    if(userReviewCheck.length > 0) {
//        if(userReviewCheck.attr('checked')) {
//            //alert('yes, checked');
//             userReviewDepartmentsList.attr('disabled','disabled');
//        }
//        
//        userReviewCheck.click(function(){
//            
//        });
//       
//    }

});

        function blink(){
            $("#last_message").fadeOut(800).fadeIn(800).fadeOut(400).fadeIn(400).fadeOut(400).fadeIn(400);
        }
        function nudge(){
            $("#last_message").animate({left:"+=5px"},20).animate({top:"+=5px"},20).animate({top:"-=10px"},20).animate({left:"-=10px"},20)
            .animate({top:"+=5px"},20).animate({left:"+=5px"},20)
            .animate({left:"+=5px"},20).animate({top:"+=5px"},20).animate({top:"-=10px"},20).animate({left:"-=10px"},20)
            .animate({top:"+=5px"},20).animate({left:"+=5px"},20);
        }