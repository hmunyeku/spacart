(function($) {
"use strict";
  $(document).ready(function() {
	states_actions();
  });
})($);

function states_actions() {
	$('#country').unbind('change').change(function() {
		console.log('Country changed');
		bc = false;
		if (states[$(this).val()]) {
			var s = states[$(this).val()]['states'],
				html = '<select name="posted_data[state]" id="state">';
			for (var x in s)
				html += '<option value="'+s[x]['code']+'"'+(s[x]['code'] == user_state ? ' selected' : '')+'>'+s[x]['state']+'</option>';

			html += '</select>';
			if ($('.admin-area').length)
				$('#state').closest('td').html('<div class="select-title">{lng[State]}</div>'+html);
			else
				$('#state').parent().html(html);

			$('#state').unbind('change').change(function() {
				user_state = $(this).val();
			});
		} else {
			if ($('.admin-area').length)
				$('#state').closest('td').html('<input type="text" name="posted_data[state]" id="state" value="'+user_state+'" /></td>');
			else
				$('#state').parent().html('<input type="text" name="posted_data[state]" id="state" value="'+user_state+'" /></td>');

			$('#state').unbind('keyup').on('keyup', function() {
				user_state = $(this).val();
			});

			if ($('.admin-area').length) {
				try {
					custom_elements();
					reinitialize_mdl();
				} catch (err) {
				}
			}
		}
	});

	$('#b_country').unbind('change').change(function() {
		console.log('Country changed');
		bc = false;
		if (states[$(this).val()]) {
			var s = states[$(this).val()]['states'],
				html = '<select name="posted_data[b_state]" id="b_state">';

			for (var x in s)
				html += '<option value="'+s[x]['code']+'"'+(s[x]['code'] == user_state_b ? ' selected' : '')+'>'+s[x]['state']+'</option>';

			html += '</select>';
			$('#b_state').parent().html(html);

			$('#b_state').unbind('change').change(function() {
				user_state_b = $(this).val();
			});
		} else {
			$('#b_state').parent().html('<input type="text" name="posted_data[b_state]" id="b_state" required value="'+user_state+'" /></td>');

			$('#b_state').unbind('keyup').on('keyup', function() {
				user_state_b = $(this).val();
			});
		}
	});

	$('#country_checkout').unbind('change').change(function() {
		console.log('Country changed');
		bc = false;
		if (states[$(this).val()]) {
			var s = states[$(this).val()]['states'],
				html = '<select name="posted_data[state]" id="state_checkout">';

			for (var x in s)
				html += '<option value="'+s[x]['code']+'"'+(s[x]['code'] == user_state ? ' selected' : '')+'>'+s[x]['state']+'</option>';

			html += '</select>';
			$('#state_checkout').parent().html(html);
			$('#state_checkout').unbind('change').change(function() {
				user_state = $(this).val();
			});
		} else {
			$('#state_checkout').parent().html('<input type="text" name="posted_data[state]" id="state_checkout" value="'+user_state+'" /></td>');
			$('#state_checkout').unbind('keyup').on('keyup', function() {
				user_state = $(this).val();
			});
		}

		try {
			checkout_changes();
		} catch (err) {
		}
	});

	setTimeout(function() {
		$('#country_checkout').trigger('change');
		$('#country').trigger('change');
		$('#b_country').trigger('change');
	}, 100);
}