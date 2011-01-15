$(document).ready(function() {
		
	$('#filetable').dataTable({
            "bStateSave": true,
            "sPaginationType": "full_numbers"
        });

	$('select[multiple="multiple"]').multiSelect();
        
} );
