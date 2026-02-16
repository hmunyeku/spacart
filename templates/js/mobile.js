var mobile_menu_clicked = false;
(function($) {
"use strict";
  $(document).ready(function() {
	setMetaScale();
  });
})($);

function mobile_menu_open() {
		if (mobile_menu_clicked) {
			mobile_menu_clicked = false;
			$('.mobile-left_menu').animate({left: -500}, 500);
		} else {
			var top = $(window).scrollTop() + 106;
			$('.mobile-left_menu').css('top', top+'px');
			mobile_menu_clicked = true;
			$('.mobile-left_menu').animate({left: 0}, 500);
		}
}

function login_clicks() {
		$('form[name=login]').on('submit', function() {
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

			$('form[name=login]').submit();
		});
}

function setMetaScale() {
	var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
	if (iOS)
		$('body').addClass('its_ios');

	$('body').show();

	var orientation = window.orientation;

	if (iOS) {
		if (typeof orientation !== "undefined") {
			if (orientation == 0) {
				var w = window.screen.width,
					x = w / 320;
			} else {
				var w = window.screen.height,
					x = w / 320;
			}
		} else {
			var mql = window.matchMedia("(orientation: portrait)");
			var orientation = screen.orientation.angle;
			if (orientation == 0) {
				var w = window.screen.width,
					x = w / 320;
			} else {
				var w = window.screen.height,
					x = w / 320;
			}
		}
	} else
		var w = window.screen.width,
			x = w / 320;

	$('#viewport').attr('content', 'width=320, initial-scale='+x+', user-scalable=no');
}

window.addEventListener("orientationchange", function(event) {
	setTimeout(function() {
		setMetaScale();
	}, 500);

	return;
}, false);