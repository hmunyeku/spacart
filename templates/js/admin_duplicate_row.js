var current_multirow = 0;
function duplicate_row(r, e) {	if (e.html() == '+') {		var h = r.html().split("[0]").join("["+(current_multirow+1)+"]");
		r.parent().append('<tr id="tmp_row">'+h+'</tr>');
		$('#tmp_row .duplicate_plus').html('-');
		$('#tmp_row').attr('id', '');
		current_multirow++;
	} else
		e.parent().parent().remove();}