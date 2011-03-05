$(document).ready(function() {
		
	$('#filetable').dataTable({
            "bStateSave": true,
            "sPaginationType": "full_numbers"
        });

	$('select[multiple="multiple"]').multiSelect();

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