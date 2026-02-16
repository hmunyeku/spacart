var pw_form = [];
pw_form['new_pswd'] = "{lng[New password]}";
pw_form['con_pswd'] = "{lng[Confirm password]}";
(function($) {
"use strict";
  $(document).ready(function() {
	var i = $('.cpsec input'),
		b = $('.cpsec button'),
		er = $('.cpsec .er');
			i.on('focus', function() {
				var n = $(this).attr('name');
				if (pw_form[n] == $(this).val()) {
					$(this).addClass('def');
					$(this).val('');
					$(this).attr('type', 'password');
				}

				er.hide();
			});

			i.on('blur', function() {
				var n = $(this).attr('name');
				if ($(this).val() == '') {
					$(this).removeClass('def');
					$(this).attr('type', 'text');
					$(this).val(pw_form[n]);
				}
			});

			i.on('keyup', function(e) {
				if (e.which == 13 || e.which == 10)
					b.trigger('click');
			});

			b.on('click', function() {
				var d = '';
				for (var x in pw_form) {
					var e = $('input[name='+x+']');
					var v = e.val();
					if (v == pw_form[x] || v == '') {
						alert("{lng[Please, enter]} "+pw_form[x]);
						return false;
					}

					d += x+'='+encodeURIComponent(v)+'&';
				}

				if ($('input[name=new_pswd]').val() != $('input[name=con_pswd]').val()) {					er.show();
					alert("{lng[Please, enter correct confirm password field]}");
					return false;
				} else
					e.hide();

				document.cpform.submit();
			});
  });
})($);
