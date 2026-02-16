function normalizeSelect(name) {
	var tmp = document.getElementById(name);
	if (tmp)
		tmp.options[tmp.options.length-1] = null;
}

function moveSelect(left, right, type) {
	if (type != 'R') {
		var tmp = left;
		left = right;
		right = tmp;
	}

	if (!left || !right)
		return false;

	while (right.selectedIndex != -1) {
		left.options[left.options.length] = new Option(right.options[right.selectedIndex].text, right.options[right.selectedIndex].value);
		right.options[right.selectedIndex] = null;
	}

	return true;
}

function saveSelects(objects) {
	if (!objects)
		return false;

	for (var sel = 0; sel < objects.length; sel++) {
		if (document.getElementById(objects[sel]))
			if (document.getElementById(objects[sel] + "_store").value == '')
				for (var x = 0; x < document.getElementById(objects[sel]).options.length; x++)
					document.getElementById(objects[sel]+"_store").value += document.getElementById(objects[sel]).options[x].value + ";";
	}

	return true;
}