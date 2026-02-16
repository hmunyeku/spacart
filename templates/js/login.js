(function($) {
"use strict";
  $(document).ready(function() {
	if (page == 'login')
		login_clicks();  });
})($);

function login_popup(what) {
  if (!what)
    what = '';
	if (mobile_screen > $(window).width()) {
    if (what == 'W')
      alert('{lng[You need to be logged in to access this page]}');
		return false;
  }
	if (page != 'login') {		$('.register_alert').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, -70)');
		setTimeout(function() {
			$('.register_alert').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
			setTimeout(function() {
				$('.register_alert').remove();
			}, 200);
		}, 200);
		bc = false;
		if ($('.login_alert').length != 0) {			fade();
			$('.login_alert').show();
			$('.login_alert').css('opacity', 0).css('transform', 'matrix(0.1, 0, 0, 0.1, 0, 0)');
			setTimeout(function() {
				$('.login_alert').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
				setTimeout(function() {
					$('.login_alert').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
				}, 200);
			}, 200);
		} else {			var id = alert('<div class="login_screen"></div>', 1, '', 'login_alert not_remove2 hidden');
			$('.login_screen').load('/login', function(r) {				unload();				login_clicks();				$('.login_alert').css('opacity', 0).show();
				$('.login_alert').css('opacity', 0).css('transform', 'matrix(0.1, 0, 0, 0.1, 0, 0)');
				setTimeout(function() {
					$('.login_alert').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
					setTimeout(function() {
						$('.login_alert').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
					}, 200);
				}, 200);
        mdl_elements();
			});
		}
	}

	return false;
}

function restore_password() {	bc = false;
	$('.alert_message').remove();	alert("{lng[Your E-mail]}: <input placeholder=\"{lng[Type your email first]}\" type='text'><div></div>", 1, '', 'res_p');
  mdl_elements();
	$('.res_p input').on('keyup', function() {		$.ajax({url: '/res_p?e='+$(this).val()}).done(function(r) {			$('.res_p div').html(r);
			$('.res_p button').on('click', function() {				$.ajax({url: '/res_p?r='+$('.res_p input').val()});
				$('.res_p').remove();
				bc = false;
				alert("{lng[Link to change password sent to your email]}");			});		});	});
}

function login_clicks() {	$('form[name=login] button').unbind('click').on('click', function() {
		var r = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/,
			email = $('.login_table [name="email"]').val();

		if (!email || !r.test(email)) {
			func_highlight($('.login_table [name="email"]'));
			return false;
		}

		if (!$('.login_table [name="password"]').val()) {
			func_highlight($('.login_table [name="password"]'));
			return false;
		}

		$.ajax({
        	type: "POST",
           	url: '/login',
           	data: $('form[name=login]').serialize(), // serializes the form's elements.
           	success: function(data)
           	{
            	if (data == 'E') {
					$('.register-email-error').fadeIn();
            	} else if (data == 'P') {
					$('.register-email-error-2').fadeIn();
            	} else {
            		self.location = '/';
            	}
           	}
		});
	});
}