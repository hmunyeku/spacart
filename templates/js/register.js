var register_form = [];
register_form['firstname'] = "{lng[Firstname]}";
register_form['lastname'] = "{lng[Lastname]}";
register_form['email'] = "{lng[Email]}";
if (page == 'register')
	register_form['password'] = "{lng[Password]}";

register_form['address'] = "{lng[Address]}";
register_form['city'] = "{lng[City]}";
register_form['zipcode'] = "{lng[Zip/Postal code]}";
register_form['phone'] = "{lng[Phone]}";

(function($) {
"use strict";
  $(document).ready(function() {
	if (page == 'register')		register_actions();
  });
})($);

function register_actions() {
	$('form[name=register] button').unbind('click').on('click', function() {
		if (!$('form[name=register] [name="firstname"]').val()) {
			func_highlight($('form[name=register] [name="firstname"]'));
			return false;
		}

		if (!$('form[name=register] [name="lastname"]').val()) {
			func_highlight($('form[name=register] [name="lastname"]'));
			return false;
		}

		var r = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/,
			email = $('form[name=register] [name="email"]').val();

		if (!email || !r.test(email)) {
			func_highlight($('form[name=register] [name="email"]'));
			return false;
		}

		if (!$('form[name=register] [name="password"]').val()) {
			func_highlight($('form[name=register] [name="password"]'));
			return false;
		}

		$.ajax({
        	type: "POST",
           	url: '/register',
           	data: $('form[name=register]').serialize(), // serializes the form's elements.
           	success: function(data)
           	{
							if (data == 'C') {
								recaptchaOnload();
								alert('Captcha is incorrect');
							} else if (data == 'E') {
								recaptchaOnload();
								$('.register-email-error').fadeIn();
            	} else {
            		self.location = '/';
            	}
           	}
		});
	});
}

function register_popup() {
	if (mobile_screen > $(window).width())
		return false;

	if (page != 'register') {
		$('.login_alert').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, -70)');
		setTimeout(function() {
			$('.login_alert').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
			setTimeout(function() {
				$('.login_alert').remove();
			}, 200);
		}, 200);

		bc = false;
		if ($('.register_alert').length != 0) {
			fade();
			$('.register_alert').css('opacity', 0).show();
			$('.register_alert').css('opacity', 0).css('transform', 'matrix(0.1, 0, 0, 0.1, 0, 0)');
			setTimeout(function() {
				$('.register_alert').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
				setTimeout(function() {
					$('.register_alert').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
				}, 200);
			}, 200);
		} else {
			var id = alert('<div class="register_screen"></div>', 1, '', 'register_alert not_remove2 hidden');
				var top = ($(window).scrollTop() + $(window).height() / 2 - 600 / 2) - 50;
				if (top < $(window).scrollTop())
					top = $(window).scrollTop() + 30;

				$('.register_alert').css('top', top + 'px');

			$('.register_screen').load('/register', function(r) {
				unload();
				register_actions();
				$('.register_alert').css('opacity', 0).show();
				$('.register_alert').css('opacity', 0).css('transform', 'matrix(0.1, 0, 0, 0.1, 0, 0)');
				setTimeout(function() {
					$('.register_alert').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
					setTimeout(function() {
						$('.register_alert').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
					}, 200);
				}, 200);
				mdl_elements();
			});
		}
	}

	return false;
}