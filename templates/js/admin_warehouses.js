(function($) {
"use strict";
	$('#wenabled').change(function() {		var checked = $(this).is(':checked') ? 1 : 0;		$('.warehouses-area').fadeToggle();
		console.log('1');
		$.ajax({			method: 'POST',			data: 'wenabled='+checked
		});	});})($);
