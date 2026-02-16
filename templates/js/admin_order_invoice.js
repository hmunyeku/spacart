(function($) {
"use strict";
	$('.update-whs').on('click', function() {		var itemid = $(this).data('itemid');
		$('.warehouses').hide();
		$('#warehouses-'+itemid).fadeIn();	});})($);
