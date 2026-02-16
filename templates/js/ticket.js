var ticket_cats = [],
	ticket_cat1 = '',
	ticket_cat2 = '',
	ticket_cat3 = '',
	ticket_cat4 = '',
	ticket_cat5 = '';

(function($) {
"use strict";
  $(document).ready(function() {
	if (page == 'ticket')
		make_ticket_form();  });
})($);

function make_ticket_form() {	$('#ticket_email').unbind('keyup').on('keyup', function() {		if (this.value && document.getElementById('error_mes').style.display == 'block')
			document.getElementById('error_mes').style.display='none';	});

	$('#ticket_subject').unbind('keyup').on('keyup', function() {
		if (this.value && document.getElementById('error_mes').style.display == 'block')
			document.getElementById('error_mes').style.display='none';
	});

	$('#ticket_message').unbind('keyup').on('keyup', function() {
		if (this.value && document.getElementById('error_mes').style.display == 'block')
			document.getElementById('error_mes').style.display='none';
	});

	$('#post_new_message').unbind('keyup').on('keyup', function() {
		if (this.value && document.getElementById('error_mes').style.display == 'block')
			document.getElementById('error_mes').style.display='none';
	});

	$('#ticketmesform').unbind('submit').on('submit', function() {		if (document.mesform.message.value == '') {			document.getElementById('error_mes').style.display='block';
			return false;
		} else {			document.getElementById('error_mes').style.display='none';
			this.disabled=true;
			submitForm(this, 'create_new');
		}	});
	var html = '<select id="ticket_cat_1" name="ticket_cat_1"><option value=""></option>',
		array = [];

	for (var x in ticket_cats) {		if (in_array(ticket_cats[x][0], array))
			continue;
		array[array.length] = ticket_cats[x][0];		html += '<option value="'+ticket_cats[x][0].replace(/"/g, '&quot;')+'">'+ticket_cats[x][0]+'</option>';	}

	html += '</select><div class="ticket-tooltip" id="ticket-tooltip_1"></div><div class="mdl-tooltip" for="ticket-tooltip_1">Select version</div>';
	$('#ticket_category').html(html);
	$('#ticket_cat_1').change(function() {		$('#ticket_cat_2, #ticket_cat_3, #ticket_cat_4, #ticket_cat_5, #ticket_fields').remove();
		$('#ticket-tooltip_2, #ticket-label_2, #ticket-tooltip_3, #ticket-label_3, #ticket-tooltip_4, #ticket-label_4, #ticket-tooltip_5, #ticket-label_5').remove();
		ticket_cat1 = $(this).val();
		ticket_cat2 = '';
		ticket_cat3 = '';
		ticket_cat4 = '';
		ticket_cat5 = '';
		ticket_category_1();
	});}

function ticket_category_1() {
	var html = '<select id="ticket_cat_2" name="ticket_cat_2"><option value=""></option>',
		array = [],
		current_cat = [];

	for (var x in ticket_cats) {
		if (ticket_cats[x][0] == ticket_cat1) {			if (!ticket_cats[x][1])
				current_cat = [ticket_cats[x][6], ticket_cats[x][7]];

			if (in_array(ticket_cats[x][1], array) || !ticket_cats[x][1])
				continue;

			array[array.length] = ticket_cats[x][1];
			html += '<option value="'+ticket_cats[x][1].replace(/"/g, '&quot;')+'">'+ticket_cats[x][1]+'</option>';
		}
	}

	if (array.length) {
		html += '</select><div class="ticket-tooltip" id="ticket-tooltip_2"></div><div class="mdl-tooltip" for="ticket-tooltip_2">'+(current_cat[1] ? current_cat[1] : 'Select build for AX version selected above. If you are not sure or don’t see your version, choose "I don’t know".')+'</div>';
		$('#ticket_category').append('<div class="ticket-label" id="ticket-label_2">'+(current_cat[0] ? current_cat[0] : 'Build')+'</div>'+html);
		reinitialize_mdl();
		tooltips_clicks();
		$('#ticket_cat_2').change(function() {
			$('#ticket_cat_3, #ticket_cat_4, #ticket_cat_5, #ticket_fields').remove();
			$('#ticket-tooltip_3, #ticket-label_3, #ticket-tooltip_4, #ticket-label_4, #ticket-tooltip_5, #ticket-label_5').remove();
			ticket_cat2 = $(this).val();
			ticket_cat3 = '';
			ticket_cat4 = '';
			ticket_cat5 = '';
			ticket_category_2();
		});
	} else {
		make_tickets_fields();
	}
}

function ticket_category_2() {
	var html = '<select id="ticket_cat_3" name="ticket_cat_3"><option value=""></option>',
		array = [],
		current_cat = [];

	for (var x in ticket_cats) {
		if (ticket_cats[x][0] == ticket_cat1 && ticket_cats[x][1] == ticket_cat2) {
			if (!ticket_cats[x][2])
				current_cat = [ticket_cats[x][6], ticket_cats[x][7]];

			if (in_array(ticket_cats[x][2], array) || !ticket_cats[x][2])
				continue;

			array[array.length] = ticket_cats[x][2];
			html += '<option value="'+ticket_cats[x][2].replace(/"/g, '&quot;')+'">'+ticket_cats[x][2]+'</option>';
		}
	}

	if (array.length) {
		html += '</select>';
		html += '</select><div class="ticket-tooltip" id="ticket-tooltip_3"></div><div class="mdl-tooltip" for="ticket-tooltip_3">'+(current_cat[1] ? current_cat[1] : 'Select service type you want to request. Use guide on the right hand side to get the details on all the services AX prime offers.')+'</div>';
		$('#ticket_category').append('<div class="ticket-label" id="ticket-label_3">'+(current_cat[0] ? current_cat[0] : 'Service')+'</div>'+html);
		reinitialize_mdl();
		tooltips_clicks();
		$('#ticket_cat_3').change(function() {
			$('#ticket_cat_4, #ticket_cat_5, #ticket_fields').remove();
			$('#ticket-tooltip_4, #ticket-label_4, #ticket-tooltip_5, #ticket-label_5').remove();
			ticket_cat3 = $(this).val();
			ticket_cat4 = '';
			ticket_cat5 = '';
			ticket_category_3();
		});
	} else {
		make_tickets_fields();
	}
}

function ticket_category_3() {
	var html = '<select id="ticket_cat_4" name="ticket_cat_4"><option value=""></option>',
		array = [],
		current_cat = [];

	for (var x in ticket_cats) {
		if (ticket_cats[x][0] == ticket_cat1 && ticket_cats[x][1] == ticket_cat2 && ticket_cats[x][2] == ticket_cat3) {
			if (!ticket_cats[x][3])
				current_cat = [ticket_cats[x][6], ticket_cats[x][7]];

			if (in_array(ticket_cats[x][3], array) || !ticket_cats[x][3])
				continue;

			array[array.length] = ticket_cats[x][3];
			html += '<option value="'+ticket_cats[x][3].replace(/"/g, '&quot;')+'">'+ticket_cats[x][3]+'</option>';
		}
	}

	if (array.length) {
		html += '</select><div class="ticket-tooltip" id="ticket-tooltip_4"></div><div class="mdl-tooltip" for="ticket-tooltip_4">'+(current_cat[1] ? current_cat[1] : 'Select functional area that is relevant to your request. If you are not sure or don’t see option that fits, choose "Other".')+'</div>';
		$('#ticket_category').append('<div class="ticket-label" id="ticket-label_4">'+(current_cat[0] ? current_cat[0] : 'Area')+'</div>'+html);
		reinitialize_mdl();
		tooltips_clicks();
		$('#ticket_cat_4').change(function() {
			$('#ticket_cat_5, #ticket_fields').remove();
			$('#ticket-tooltip_5, #ticket-label_5').remove();
			ticket_cat4 = $(this).val();
			ticket_cat5 = '';
			ticket_category_4();
		});
	} else {
		make_tickets_fields();
	}
}

function ticket_category_4() {
	var html = '<select id="ticket_cat_5" name="ticket_cat_5"><option value=""></option>',
		array = [],
		current_cat = [];

	for (var x in ticket_cats) {
		if (ticket_cats[x][0] == ticket_cat1 && ticket_cats[x][1] == ticket_cat2 && ticket_cats[x][2] == ticket_cat3 && ticket_cats[x][3] == ticket_cat4) {
			if (!ticket_cats[x][4])
				current_cat = [ticket_cats[x][6], ticket_cats[x][7]];

			if (in_array(ticket_cats[x][4], array) || !ticket_cats[x][4])
				continue;

			array[array.length] = ticket_cats[x][4];
			html += '<option value="'+ticket_cats[x][4].replace(/"/g, '&quot;')+'">'+ticket_cats[x][4]+'</option>';
		}
	}

	if (array.length) {
		html += '</select><div class="ticket-tooltip" id="ticket-tooltip_5"></div><div class="mdl-tooltip" for="ticket-tooltip_5">'+current_cat[1]+'</div>';
		$('#ticket_category').append('<div class="ticket-label" id="ticket-label_5">'+current_cat[0]+'</div>'+html);
		reinitialize_mdl();
		tooltips_clicks();
		$('#ticket_cat_5').change(function() {
			$('#ticket_fields').remove();
			ticket_cat5 = $(this).val();
			make_tickets_fields();
		});
	} else {
		make_tickets_fields();
	}
}


function make_tickets_fields() {	var html = '<div id="ticket_fields">',
		array = [],
		current_cat = [];

	for (var x in ticket_cats) {
		if (ticket_cats[x][0] == ticket_cat1 && ticket_cats[x][1] == ticket_cat2 && ticket_cats[x][2] == ticket_cat3 && ticket_cats[x][3] == ticket_cat4 && ticket_cats[x][4] == ticket_cat5) {			for (var y in ticket_cats[x][5]) {				if (in_array(ticket_cats[x][5][y][0], array) || !ticket_cats[x][5][y][0])
					continue;

				array[array.length] = ticket_cats[x][5][y][0];
				html += '<div class="mdl-textfield mdl-js-textfield"><textarea class="mdl-textfield__input" data-required="'+ticket_cats[x][5][y][1]+'" id="ticket-field-'+y+'" name="ticket_field['+ticket_cats[x][5][y][0]+']"></textarea><label class="mdl-textfield__label" for="ticket-field-'+y+'">'+ticket_cats[x][5][y][0]+'</label>';
				if (ticket_cats[x][5][y][1])
					html += '<span class="star">*</span>';

				html += '</div><br />';
			}
		}
	}

	if (array.length) {		html += '</div>';
		$('#ticket_category').append(html);
	}

	reinitialize_mdl();
}

function submit_ticket() {	var error = false;	if ($('#ticket_email').length && !$('#ticket_email').val()) {
		error = true;
		func_highlight($('#ticket_email'));
	}

	if (!$('#ticket_subject').val()) {
		error = true;
		func_highlight($('#ticket_subject'));
	}

	if (!$('#ticket_message').val()) {
		error = true;
		func_highlight($('#ticket_message'));
	}

	if (!error) {		document.ticketform.submit();	}
}