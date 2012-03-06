$(document).ready(function() {
		
	$('#filetable').dataTable({
            "bStateSave": true,
            "sPaginationType": "full_numbers"
        });

        $('#checkall').click(function () {
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
            checkAllText:   langCheckAll        
        });

        // Add a multi-select / search to the top of the Specific User Permissions controls
        $multiViewObject.multiselectfilter();
    }
        /* Animated Message */
        $(".close").click(function(){
            $("#last_message").animate({left:"+=10px"}).animate({left:"-5000px"});
        });
        blink();
} );

        function blink(){
            $("#last_message").fadeOut(800).fadeIn(800).fadeOut(400).fadeIn(400).fadeOut(400).fadeIn(400);
        }
        function nudge(){
            $("#last_message").animate({left:"+=5px"},20).animate({top:"+=5px"},20).animate({top:"-=10px"},20).animate({left:"-=10px"},20)
            .animate({top:"+=5px"},20).animate({left:"+=5px"},20)
            .animate({left:"+=5px"},20).animate({top:"+=5px"},20).animate({top:"-=10px"},20).animate({left:"-=10px"},20)
            .animate({top:"+=5px"},20).animate({left:"+=5px"},20);
        }