function import_checkboxes(val) {	if (val == 'Y')		$('[name=exportForm] input').attr('checked', true);
	else		$('[name=exportForm] input').attr('checked', false);
}