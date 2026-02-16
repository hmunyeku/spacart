(function($) {
"use strict";
	$(document).ready(function() {
		login_clicks();
	});})($);

function restore_password() {	$('.alert_message').remove();	alert("Your E-mail: <input type='text'><div></div>", 1, '', 'res_p');
	$('.res_p input').on('keyup', function() {		$.ajax({url: '/res_p?e='+$(this).val()}).done(function(r) {			$('.res_p div').html('<br />'+r);
			$('.res_p button').addClass('mdl-button mdl-button--colored mdl-button--raised mdl-js-button mdl-js-ripple-effect');
			reinitialize_mdl();
			$('.res_p button').on('click', function() {				$.ajax({url: '/res_p?r='+$('.res_p input').val()});
				$('.res_p').remove();
				bc = false;
				alert("{lng[Link to change password sent to your email]}");
			});		});	});

	return false;
}

function login_clicks() {		var i = $('form[name=login] input');
		i.on('focus', function() {
			var n = $(this).attr('name');
			if (login_form[n] == $(this).val()) {
				$(this).addClass('def');
				$(this).val('');
				if (n == 'password')
					$(this).attr('type', 'password');
			}
		});

		i.on('blur', function() {
			var n = $(this).attr('name');
			if ($(this).val() == '') {
				if (n == 'password')
					$(this).attr('type', 'password');
			}
		});

		i.on('keyup', function(e) {
			if (e.which == 13 || e.which == 10)
				$('form[name=login] button').trigger('click');

			$('.login_error').hide();
		});

		$('form[name=login] button').unbind('click').on('click', function() {
			var r = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
			for (var x in login_form) {
				var e = $('form[name=login] input[name='+x+']');
				var v = e.val();
				if (v == login_form[x] || v == '' || (x == 'email' && !r.test(v))) {
					$('.login_error').show();
					return false;
				}
			}

			$('form[name=login]').submit();
		});
}