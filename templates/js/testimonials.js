function check_new_testimonial() {	var error = false;

	if (!$('#testimonial_name').val()) {
		func_highlight($('#testimonial_name'));
		error = true;
	}

	if (!$('#testimonial_message').val()) {		func_highlight($('#testimonial_message'));		error = true;
	}

	if (error)
		return false;}