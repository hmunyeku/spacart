var $window = $(window),
	original_content,
	original_pageid,
	original_title,
	original_page,
	original_bread_crumbs,
	$animation_elements = $('.page-container-2, .page-container-news, .page-container-blog'),
	are_you_sure = "{lng[are_you_sure]}",
	mobile_screen = 850,
	instant_search_ajax,
	yes = "{lng[yes]}",
	no = "{lng[no]}",
	ok = "{lng[ok]}",
	chat_w = 0,
	alerts = Math.floor(Math.random() * 10000),
	at,
	bc = true,
	confirmed = false,
	ajaxed = false,
	hp = false,
	ie = (window.navigator.appName == "Microsoft Internet Explorer"),
	ff = navigator.userAgent.toLowerCase().indexOf('firefox') > -1,
	wid,rtm,
	cursor = [],
	$ = jQuery,
	pushed = false,
	original_content, original_title, original_bread_crumbs,
	body_loading_t,
	body_id,
	pushed_history_ok = false,
	mobile_design = false,
	dontloadfilter = false;

if (typeof window.janrain !== 'object') window.janrain = {};
if (typeof window.janrain.settings !== 'object') window.janrain.settings = {};

$(window).scroll(function() {
	on_scroll_header();
	scrolltop();
});

$(document).on('keyup', function(e) {
	if (e.keyCode == 27) {
		removePopups();
	}
});

(function($) {
"use strict";
  $(document).ready(function() {
		if (!getCookie('allow_cookies')) {
				$('body').append('<div class="allow-cookies"><p>{lng[We use cookies for better user experience]}</p><button>{lng[Accept]}</button></div>');
				$('.allow-cookies button').on('click', function() {
					$('.allow-cookies').remove();
					setCookie('allow_cookies', '1', 30);
				});
    }

    setInterval(function() {
        $.ajax({
            url: '/refresh'
        });
    }, 15000);

	body_id = $('body').attr('id');
	on_scroll_header();
	mobile_design = $('body').data('mobile');
	$('#subsform').on('submit', function() {
		var r = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
		if ($('#sub-email').val() && r.test($('#sub-email').val())) {
			$.ajax({
				url:'/subscribe?email='+$('#sub-email').val()
			}).done(function(r) {
				alert(r);
				$('.alert_message').css('top', '150px');
				unfade();
			});
		} else {
			func_highlight($('#subscribe'));
		}

		return false;
	});

	$(document).mousemove(function(e) {
		cursor = [e.pageX, e.pageY];
	});

	$('.alerts').on('click', function() {
		bc = false;
	});

	$(document).on('click', function() {
		if (bc != false) {
			$('.popup, .alert_message, .alerts').not('.login_alert').remove();
			$('.login_alert').hide();
			unfade();
		}

		bc = true;
	});

	$('.search input').on('focus', function() {
		if ($(this).val() == 'Search')
			$(this).val('');
	});

    $(document).tooltip();

	original_content = $('.ajax_container').html();
	original_title = $('title').html();
	original_page = page;
	original_bread_crumbs = $('#bread_crumbs_container').html();

	if (page == 'home' || page == 'category' || (page == 'brands' && pageid))
		is_ajax_page = true;

	if (is_ajax_page)
		ajax_clicks();

    // AutoTranslate: re-translate after SPA AJAX page loads
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('its_ajax_page=1') > -1) {
            // A new page was loaded via SPA, re-trigger translation
            if (window.SpaCartAutoTranslate && window.SpaCartAutoTranslate.enabled) {
                setTimeout(function() {
                    window.SpaCartAutoTranslate.init();
                }, 300);
            }
        }
    });

	$('.currency_select a').on('click', function() {
		aload();
		$.ajax({
			url: '/set_currency/'+$(this).data('id')
		}).done(function() {
			location.reload();
		});

		return false;
	});

	$('.language_select a').on('click', function() {
		aload();
		$.ajax({
			url: '/set_language/'+$(this).data('id')
		}).done(function() {
			location.reload();
		});

		return false;
	});
                              1
	$('.currency_select select').change(function() {
		aload();
		$.ajax({
			url: '/set_currency/'+$(this).val()
		}).done(function() {
			location.reload();
		});
	});

	$('.language_select select').change(function() {
		aload();
		$.ajax({
			url: '/set_language/'+$(this).val()
		}).done(function() {
			location.reload();
		});
	});

	cart_clicks();
	original_pageid = pageid;
	instant_search();
	responsive_init();
	scrolltop();
  });
})($);

function ajax_clicks() {
	if ($('html.area-a').length)
		return;

	init_translate();
	$(document).tooltip();
		$('.filter_switcher').unbind('click').on('click', function() {
			$('body').toggleClass('filteropen');
		});
	$('body').removeClass('filteropen');
	left_filter_max_height();
	mdl_elements();
	$animation_elements = $('.page-container-2, .page-container-news, .page-container-blog')
	$window.trigger('scroll');
	mobile_menu_clicked = true;
	mobile_menu_open();

	try {
		ga('set', 'page', window.location.pathname);
		ga('send', 'pageview');
	} catch (err) {
	}

	$('.searchform').unbind('submit').on('submit', function() {
		var substring = $(this).find('input[type="text"]').val();
		if (!substring)
			return false;

		if (mobile_design) {
			$('html, body').animate({
				scrollTop: 0
			}, 500);
		}

		var h = '/search?q='+substring;
		if (!pushed) {
			pushed = true;
		}

		$('body').append('<div id="content-loading"><div class="cssload-container"><div class="cssload-speeding-wheel"></div></div></div>');
		$.ajax({
			dataType: 'json',
			url: h,
			error: function() {
				self.location = h;
			},
			success: function(r) {
				$('#content-loading').remove();
				$('body').attr('id', 'body-search');
				$('.ajax_container').html(r[0]);
				$('#bread_crumbs_container').html(r[2]);
				ajax_clicks();
				window.history.pushState({"html":r[0],"pageTitle":r[1], 'bread_crumbs': r[2], 'page': r[3], 'parentid': r[4], 'pageid': r[5]},"", h);
				document.title = r[1];
				proceed_clicks(r[3]);
				tabs_clicks();
			}
		});

		return false;
	});

	$('#menu a, a.ajax_link, .ajax_mobile_link, .logo-link, .bread_crumbs a, #subcategories a, .brands a, .test-links a, .foot a').unbind('click').on('click', function(e) {
		if ($(this).hasClass('no-ajax'))
			return true;

		if ($(this).closest('.product_popup').length)
			$('.fade').click();

		if (mobile_design) {
			$('html, body').animate({
				scrollTop: 0
			}, 500);
			mobile_menu_clicked = false;
			$('.mobile-left_menu').animate({left: -500}, 500);
    	} else {
			$('html, body').animate({
				scrollTop: 0
			}, 500);
    	}

		var h = $(this).attr('href');
		if (!pushed) {
			pushed = true;
		}

		var site_loader_to = setTimeout(function() {
			$('body').append('<div id="content-loading"><div class="cssload-container"><div class="cssload-speeding-wheel"></div></div></div>');
		}, 1000);

		$.ajax({
			dataType: 'json',
			url: create_ajax_link(h),
			error: function() {
				self.location = h;
			},
			success: function(r) {
				clearTimeout(site_loader_to);
				$('#content-loading').remove();

				$('#content-loading').remove();
				$('body').attr('id', 'body-'+r[3]);
				$('.ajax_container').html(r[0]);

				$('#bread_crumbs_container').html(r[2]);
				ajax_clicks();
				page = r[3];
				window.history.pushState({"html":r[0],"pageTitle":r[1], 'bread_crumbs': r[2], 'page': r[3], 'parentid': r[4], 'pageid': r[5]},"", h);
				document.title = r[1];
				pageid = r[5];
				recalc_menus(r[3], r[4]);
				proceed_clicks(r[3]);
				tabs_clicks();
			}
		});

		return false;
	});

	try {
		login_clicks();
		register_actions();
	} catch (err) {
	}

	try {
		profile_clicks();
	} catch (err) {
	}

	contact_form();
}

function contact_form() {
	$('#body-help .submit_help').unbind('click').on('click', function() {
		var allgood = true;
		$('#help_form input').each(function() {
			if (!$(this).val()) {
				$(this).addClass('error');
				allgood = false;
			}
		});

		$('#help_form textarea').each(function() {
			if (!$(this).val()) {
				$(this).addClass('error');
				allgood = false;
			}
		});

		if (allgood)
			document.help.submit();
	});
}

window.onpopstate = function(e) {
	if (current_area == 'A') {
		self.location = window.location.href;
		return false;
	}

	if (is_ajax_page) {
		$('.page-container-2, #home-tabs').remove();
		if (e.state) {
			$('.ajax_container').html(e.state.html);
			$('body').attr('id', 'body-'+e.state.page);
			document.title = e.state.pageTitle;
			$('#bread_crumbs_container').html(e.state.bread_crumbs);
			if (e.state.parentid || (e.state.page != 'category' && e.state.page != 'search'))
				recalc_menus(e.state.page, e.state.parentid);

			var this_page = e.state.page;
			if (e.state.dontrealodfilter)
				dontloadfilter = true;

			pageid = e.state.pageid;
		} else {
			$('.ajax_container').html(original_content);
			document.title = original_title;
			$('#bread_crumbs_container').html(original_bread_crumbs);
			recalc_menus(page, parentid);
			var this_page = original_page;
			pageid = original_pageid;
			$('body').attr('id', body_id);
		}

		ajax_clicks();
		proceed_clicks(this_page);
		tabs_clicks();
	}
};

function proceed_clicks(whats_page) {
	if (whats_page == 'category' || whats_page == 'search' || (whats_page == 'brands' && pageid)) {
		$('body').addClass('withfilter');
		if (dontloadfilter)
			dontloadfilter = false;
		else
			load_filter();
	} else {
		$('body').removeClass('withfilter');
	}

	products_clicks();
	banners();
	product_clicks();
	filter_clicks();
	cart_actions();
	coupon_actions();
	cart_clicks();
	checkout_actions();
}

$(window).resize(function() {
	if ($('.fade').length > 0)
		fade();
});

function aload() {
	$('.loading').show();
}

function unload() {
	$('.loading').hide();
}

function alert(message, nook, h, classes, noshake, alert2, nofade) {
	clearTimeout(at);
	var id = alerts;
	if (alert2)
	    $('body').append('<div class="alert_message '+classes+'" id="a'+id+'" tabindex="'+id+'"><div class="background"></div><div class="alert_content"><span class="close_alert">x</span>'+message+(nook ? '' : '<div class="close-alert"><button>'+ok+'</button>')+'</div></div>');
	else {
		if (!nofade)
			fade();

		bc = false;
	    $('body').append('<div class="alert_message '+classes+'" id="a'+id+'" tabindex="'+id+'"><span class="close_alert">x</span>'+message+(nook ? '' : '<div class="close-alert"><button>'+ok+'</button>')+'</div></div>');
	}

	if (noshake == 2) {
		$('#a'+id).hide();
		$('#a'+id).show();
	}

	$('#a'+id+' .close_alert').on('click', function() {
		var othis = $(this);
		bc = false;
		if (alert2)
	    	var e = $(this).parent().parent();
		else
	    	var e = $(this).parent();

		e.css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
		setTimeout(function() {
			e.css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
			if (othis.closest('.login_alert').length || othis.closest('.register_alert').length)
		    	unfade();

	    	if (!$('.popup').length && !$('.register_alert').length && !$('.login_alert').length)
		    	unfade();

			setTimeout(function() {
				e.remove();
			}, 200);
		}, 200);

	});

	$('#a'+id+' .close-alert button').on('click', function() {
		bc = false;
		bb = true;
    	var e = $('#a'+id);
		e.css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
		setTimeout(function() {
			e.css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
	    	if (!$('.popup').length && !$('.register_alert').length && !$('.login_alert').length)
		    	unfade();

			setTimeout(function() {
				e.remove();
			}, 200);
		}, 200);

	});

	$('.alert_message').on('click', function() {
		bc = false;
		bb = true;
	});

	if (h)
		at = setTimeout(function(){$('#a'+id).fadeOut();unfade();}, 5000);

	$('#a'+id).focus();
	$('#a'+id).on('keyup', function(e) {
		if (e.which == 27) {
			$('#a'+id).hide();
    		if (!$('.popup').length)
				unfade();
		}
	});

	alerts += 1;

	return id;
}

function confirm(text, o, goto) {
	if (!text)
		text = are_you_sure;

	$('.confirm').remove();
	bc = false;
	alert(text+'<br /><br /><button class="yes">'+yes+'</button> <button onclick="javascript: $(\'.confirm\').remove(); unfade();" class="no">'+no+'</button>', 1, '', 'confirm', 2);
	$('.confirm').focus();
	$('.confirm .yes').on('click', function() {
		bc = false;
		confirmed = true;
		if (goto)
			self.location = goto;
		else
			o.trigger('click');

		unfade();
		$('.confirm').remove();
		confirmed = false;
	});

	$('.confirm').keydown(function(e) {
		if (e.which == 13)
			$('.confirm .yes').trigger('click');
		else if (e.which == 27)
			$('.confirm .no').trigger('click');
	});
}

function fade() {
	if ($('.fade').length == 0) {
		$('html').prepend('<div class="fade"></div>');
		$('.fade').height($(document).height());
		$('.fade').show();
	} else
		$('.fade').height($(document).height());

	$('.fade').unbind('click').on('click', function() {
		bc = false;
		removePopups();
return;
		bc = true;
	});
}


function removePopups(no_unfade) {
	if (current_area == 'C')
		setTimeout(function() {
			oid = 0;
			product_clicks();
		}, 500);

	if (no_unfade) {
		$('.popup, .alert, .alert_message:not(.not_remove)').remove();
		return;
	}

	$('.popup, .alert, .alert_message').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
	setTimeout(function() {
		$('.popup, .alert, .alert_message').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
    	unfade();

		setTimeout(function() {
			$('.popup, .alert, .alert_message:not(.not_remove)').remove();
		}, 200);
	}, 200);
}

function unfade() {
	clearTimeout(at);
	if (($('.checkout_popup').is(':visible') || $('.product_popup').is(':visible')) && ($('.login_alert').is(':visible') || $('.register_alert').is(':visible'))) {
		setTimeout(function() {
			if (!$('.checkout_popup').is(':visible') && !$('.product_popup').is(':visible')) {
				$('.prev').remove();
				$('.fade').remove();
			}
		}, 500);
		return;
	}

	$('.prev').remove();
	$('.fade').remove();
}

function price_format(price, thousand_delim, decimal_delim, precision) {
	var thousand_delim = (arguments.length > 1 && thousand_delim !== false) ? thousand_delim : '';
	var decimal_delim = (arguments.length > 2 && decimal_delim !== false) ? decimal_delim : '.';
	var precision = (arguments.length > 3 && precision !== false) ? precision : '2';

	if (precision > 0) {
		precision = Math.pow(10, precision);
		price = Math.round(price*precision)/precision;
		var top = Math.floor(price);
		var bottom = Math.round((price-top)*precision)+precision;

	} else {
		var top = Math.round(price);
		var bottom = 0;
	}

	top = top+"";
	bottom = bottom+"";
	var cnt = 0;
	for (var x = top.length; x >= 0; x--) {
		if (cnt % 3 == 0 && cnt > 0 && x > 0)
			top = top.substr(0, x)+thousand_delim+top.substr(x, top.length);

		cnt++;
	}

	return (bottom > 0) ? (top+decimal_delim+bottom.substr(1, bottom.length)) : top;
}

function IsNumeric(num) {
     return (num >=0 || num < 0);
}

function cart_clicks() {
	mdl_elements();
	$('.cart-link').unbind('click').on('click', function() {
		if (mobile_screen > $(window).width())
			return true;


		$('.cart_popup').remove();
		if (!$('.checkout_popup').length)
			aload();

		$('.checkout_popup').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, -70)');
		setTimeout(function() {
			$('.checkout_popup').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
			setTimeout(function() {
				$('.checkout_popup').remove();
			}, 200);
		}, 200);

		$('body').append('<div class="load hidden"><span class="close close-popup">x</span></div>');
		$.ajax({
			dataType: 'json',
			url: '/cart',
			error: function() {
				alert('We cannot process this request. Please, contact site administrator.');
			},
			success: function(r) {
				$('.load').html('<span class="close close-popup">x</span>'+r[0]);
				unload();
				fade();
				$('.load').addClass('popup cart_popup').removeClass('load');
				var top = ($(window).scrollTop() + $(window).height() / 2 - $('.cart_popup').height() / 2) - 50;
				if (top < $(window).scrollTop())
					top = $(window).scrollTop() + 30;

				$('.cart_popup').css('top', top + 'px');
				$('.cart_popup').css('left', ($(window).width() / 2 - $('.cart_popup').width() / 2 - 10) + 'px');
				$('.cart_popup').css('opacity', 0).css('transform', 'matrix(0.1, 0, 0, 0.1, 0, 0)');
				setTimeout(function() {
					$('.cart_popup').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
					setTimeout(function() {
						$('.cart_popup').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
					}, 200);
				}, 200);

				$('.cart_popup').show();
				$(window).resize(function() {
					var top = ($(window).scrollTop() + $(window).height() / 2 - $('.cart_popup').height() / 2) - 50;
					if (top < $(window).scrollTop())
						top = $(window).scrollTop() + 30;

					$('.cart_popup').css('top', top + 'px');
					$('.cart_popup').css('left', ($(window).width() / 2 - $('.cart_popup').width() / 2 - 10) + 'px');
				});

				$('.cart_popup .close, .cart_popup .close_popup').on('click', function() {
					removePopups();
				});

				$('.cart_popup').on('click', function() {
					bc = false;
					bb = true;
				});

				cart_actions();
				coupon_actions();
				cart_clicks();
			}
		});

		return false;
	});

	$('.wishlist-link').unbind('click').on('click', function() {
		$('.wl_popup').remove();
		$('body').append('<div class="load hidden"><span class="close close-popup">x</span></div>');
		$.ajax({
			dataType: 'json',
			url: '/wishlist',
			error: function() {
				alert('We cannot process this request. Please, contact site administrator.');
			},
			success: function(r) {
				$('.load').html('<span class="close close-popup">x</span>'+r[0]);
				unload();
				fade();
				$('.load').addClass('popup wl_popup').removeClass('load');
				var top = ($(window).scrollTop() + $(window).height() / 2 - $('.wl_popup').height() / 2) - 50;
				if (top < $(window).scrollTop())
					top = $(window).scrollTop() + 30;

				$('.wl_popup').css('top', top + 'px');
				$('.wl_popup').css('left', ($(window).width() / 2 - $('.wl_popup').width() / 2 - 10) + 'px');
				$('.wl_popup').css('opacity', 0).css('transform', 'matrix(0.1, 0, 0, 0.1, 0, 0)');
				setTimeout(function() {
					$('.wl_popup').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
					setTimeout(function() {
						$('.wl_popup').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
					}, 200);
				}, 200);

				$('.wl_popup').show();
				$(window).resize(function() {
					var top = ($(window).scrollTop() + $(window).height() / 2 - $('.cart_popup').height() / 2) - 50;
					if (top < $(window).scrollTop())
						top = $(window).scrollTop() + 30;

					$('.wl_popup').css('top', top + 'px');
					$('.wl_popup').css('left', ($(window).width() / 2 - $('.cart_popup').width() / 2 - 10) + 'px');
				});

				$('.wl_popup .close, .wl_popup .close_popup').on('click', function() {
					removePopups();
				});

				$('.wl_popup').on('click', function() {
					bc = false;
					bb = true;
				});

				wl_actions();
				init_translate();
			}
		});

		return false;
	});

	$('.checkout-link').unbind('click').on('click', function() {
		if (mobile_screen > $(window).width())
			return true;

		if ($('.checkout_popup').length) {
			$('.checkout_popup').remove();
			aload();
		} else if (!$('.cart_popup').length)
			aload();

		$('.cart_popup').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, -70)');
		setTimeout(function() {
			$('.cart_popup').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
			setTimeout(function() {
				$('.cart_popup').remove();
			}, 200);
		}, 200);

		$('body').append('<div class="load hidden"><span class="close close-popup">x</span></div>');
		$.ajax({
			dataType: 'json',
			url: '/checkout',
			error: function() {
				alert('We cannot process this request. Please, contact site administrator.');
			},
			success: function(r) {
				$('.load').html('<span class="close close-popup">x</span>'+r[0]);
				unload();
				fade();
				$('.load').addClass('popup checkout_popup').removeClass('load');
				var top = ($(window).scrollTop() + $(window).height() / 2 - $('.checkout_popup').height() / 2) - 50;
				if (top < $(window).scrollTop())
					top = $(window).scrollTop() + 30;

				$('.checkout_popup').css('top', top + 'px');
				$('.checkout_popup').css('left', ($(window).width() / 2 - $('.checkout_popup').width() / 2 - 10) + 'px');
				$('.checkout_popup').css('opacity', 0).css('transform', 'matrix(0.1, 0, 0, 0.1, 0, 0)');
				setTimeout(function() {
					$('.checkout_popup').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
					setTimeout(function() {
						$('.checkout_popup').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
					}, 200);
				}, 200);

				$('.checkout_popup').show();
				$(window).resize(function() {
					var top = ($(window).scrollTop() + $(window).height() / 2 - $('.checkout_popup').height() / 2) - 50;
					if (top < $(window).scrollTop())
						top = $(window).scrollTop() + 30;

					$('.checkout_popup').css('top', top + 'px');
					$('.checkout_popup').css('left', ($(window).width() / 2 - $('.checkout_popup').width() / 2 - 10) + 'px');
				});

				$('.checkout_popup .close, .checkout_popup .close_popup').on('click', function() {
					removePopups();
				});

				$('.checkout_popup').on('click', function() {
					bc = false;
					bb = true;
				});

				checkout_actions();
				checkout_changes();
				$('#place_order *').attr('disabled', true);
				coupon_actions();
				cart_clicks();
				init_translate();
			}
		});

		return false;
	});
}

function cart_actions() {
	init_translate();
	$('.cart-quantity').on('keyup', function() {
		var max = $(this).data('max');
		if ($(this).val() > max)
			$(this).val(max);
	});

	$('.clear-cart').on('click', function() {
		$.ajax({url: '/cart/clear'}).done(function(r) {
			$('#minicart').html(r);
			$('#head_mobile #minicart').html(r);
			cart_clicks();
		});
		removePopups();
		return false;
	});

	$('.update-cart').on('click', function(e) {
	    $.ajax({
           type: "POST",
           url: '/cart',
           data: $("#cartform").serialize(),
           success: function(data) {
				$('.cart-link').click();
           }
         });

	    e.preventDefault();
		return false;
	});

	$('.remove-link').on('click', function() {
		var url = $(this).attr('href');
		$.ajax({url: url}).done(function(r) {
			$('#minicart').html(r);
			$('#head_mobile #minicart').html(r);
			if ($('#minicart .cart-link').length) {
				cart_clicks();
				$('.cart-link').click();
			} else
				removePopups();
		});

		return false;
	});
}

function wl_actions() {
	$('.clear-wl').on('click', function() {
		$.ajax({url: '/wishlist/clear'}).done(function(r) {});
		removePopups();
		return false;
	});

	$('.remove-wl-link').on('click', function() {
		var url = $(this).attr('href');
		$.ajax({url: url}).done(function(r) {
			$('.wishlist-link').click();
		});

		return false;
	});
}

function recalculate_shipping(shippingid) {
	$('#place_order').animate({opacity: .6 }, 50);
	$('#place_order *').attr('disabled', true);
    $.ajax({
		type: "POST",
		url: '/checkout?shippingid='+shippingid,
		success: function(data) {
			$('#place_order').animate({opacity: 1 }, 200);
			$('#place_order *').attr('disabled', false);
			$('#place_order').html(data);
			checkout_actions();
			$('#paymentid').trigger('change');
		}
	});
}

function checkout_actions() {
	configure_stripe();
	states_actions();
	if ($('#same_address').is(':checked')) {
		$('.billing_address input').removeProp('required');
	} else {
		$('.billing_address input').prop('required', true);
	}

	$('#same_address').unbind('change').change(function() {
		if ($(this).is(':checked')) {
			$('.billing_address input').removeProp('required');
		} else {
			$('.billing_address input').prop('required', true);
		}

		$('.billing_address').toggle();
	});

	$('#paymentid').change(function() {
	        if ($(this).val() == '2') {
	        	$("#cc-info").show()
	        	$("#place-order").hide()
	        } else {
	        	$("#cc-info").hide()
	        	$("#place-order").show()
	        }
	});

	$('#checkout_user_form').unbind('submit').on('submit', function(e) {
	    e.preventDefault();
		var allgood = true;
		$('#checkout_user_form input').each(function() {
			var o = $(this);
			if (!o.val()) {
			}
		});

		if (!allgood)
			return false;

	    $.ajax({
           type: "POST",
           url: '/checkout/user_form',
           data: $('#checkout_user_form').serialize(),
           success: function(data) {
				if (data == 'Email') {
	                alert('Enterd email already registered for another user');
				} else {
					$('#place_order').animate({opacity: 1 }, 200);
					$('#place_order *').attr('disabled', false);
					$('#place_order').html(data);
					init_translate();
					checkout_actions();
					$('html, body').animate({
						scrollTop: $('#checkoutform').offset().top - 200
					}, 500);
					if ($('#paymentid').val() == '2')
						$('#cc-info').show();
						mdl_elements()
				}
           }
         });

		return false;
	});

	$('#place_order button').unbind('click').on('click', function(e) {
		if ($('#paymentid').val() == 7) {
			aload();
		    $.ajax({
    	    	type: "POST",
				url: '/checkout/place_order',
				data: $('#checkoutform').serialize(),
				success: function(data)
				{
stripe_lib.redirectToCheckout({
  // Make the id field from the Checkout Session creation API response
  // available to this file, so you can provide it as parameter here
  // instead of the {{CHECKOUT_SESSION_ID}} placeholder.
  sessionId: data
}).then(function (result) {
	alert("{lng[Unable to redirect to Stripe]}");
  // If `redirectToCheckout` fails due to a browser or network
  // error, display the localized error message to your customer
  // using `result.error.message`.
});
						alert('{lng[Please, wait we are connecting you to Stripe]}');
				}
			});

			return false;
		} else if ($('#paymentid').val() == '2') {
			aload();
			hostedFieldsInstance_obj.tokenize(function (err, payload) {
				if (err) {
					unload();
					alert(err.message);
					console.error(err);
					return;
				}

    braintree.threeDSecure.create({
      authorization: client_token,
      version: 2
	}, function (createError, threeDSecure) {
  threeDSecure.on('lookup-complete', function (data, next) {
    // check lookup data

    next();
  });

threeDSecure.verifyCard({
  amount: $('#order_total').val(),
  nonce: payload.nonce,
  bin: payload.details.bin
  // other fields such as billing address
}, function (verifyError, payload) {
							unload();
  if (verifyError) {
  	alert(verifyError.message);
    if (verifyError.code === 'THREEDS_VERIFY_CARD_CANCELED_BY_MERCHANT ') {
      // flow was cancelled by merchant, 3ds info can be found in the payload
      // for cancelVerifyCard
    }

    return;
  }

        if ('undefined' != typeof payload.verificationDetails && payload.verificationDetails.liabilityShiftPossible == false && payload.verificationDetails.liabilityShifted == false && this.isAcceptNo3dSecure == false) {
        }


        		$('#payment-method-nonce').val(payload.nonce);
			    $.ajax({
	    	       type: "POST",
        		   url: '/checkout/place_order',
	    	       data: $('#checkoutform').serialize(), // serializes the form's elements.
    		       success: function(data)
	        	   {
						if (data == 'Error') {
							unload();
		            	    alert('Please, contact site administrator');
						} else if (data == 'StripeError') {
							$('#stripe_token').val('');
							$('#payment-method-nonce').val('');
							$('.checkout_popup').css('opacity', 1);
	            		    alert('There was error processing your credit card');
						} else {
							self.location = '/invoice/'+data+'/success';
						}
		        	}
	    		});

});
    });

return;

        		$('#payment-method-nonce').val(payload.nonce);
			    $.ajax({
	    	       type: "POST",
        		   url: '/checkout/place_order',
	    	       data: $('#checkoutform').serialize(), // serializes the form's elements.
    		       success: function(data)
	        	   {
						if (data == 'Error') {
							unload();
		            	    alert('Please, contact site administrator');
						} else if (data == 'StripeError') {
							$('#stripe_token').val('');
							$('#payment-method-nonce').val('');
							$('.checkout_popup').css('opacity', 1);
	            		    alert('There was error processing your credit card');
						} else {
							self.location = '/invoice/'+data+'/success';
						}
		        	}
	    		});
        	});
		} else {
		    $.ajax({
    	       type: "POST",
        	   url: '/checkout/place_order',
	           data: $('#checkoutform').serialize(), // serializes the form's elements.
    	       success: function(data)
        	   {
					if (data == 'Error') {
	            	    alert('Please, contact site administrator');
					} else if (data == 'StripeError') {
						$('#stripe_token').val('');
						$('.checkout_popup').css('opacity', 1);
	            	    alert('There was error processing your credit card');
					} else if (strstr(data, 'paypal.com')) {
						$('body').append(data);
						document.paypalform.submit();
						alert('{lng[Please, wait we are connecting you to PayPal]}');
					} else {
						self.location = '/invoice/'+data+'/success';
					}
	           }
	         });
		}
	});

	$('.apply_gc').on('click', function() {
		bc = false;
		var id = alert('<div class="coupon_screen"></div>', 1, '', 'coupon_alert');
		$('.coupon_screen').html('<br/><div class="group"><input type="text" size="30" required /><span class="highlight"></span><span class="bar"></span><label>{lng[Enter your Gift Card code here]}</label></div><div class="coupon-error"></div><button>{lng[Apply]}</button><br/><br/>');
		$('.coupon_screen input').on('keyup', function() {
			$('.coupon-error').hide();
		});

		$('.coupon_screen button').on('click', function() {
			if (!$('.coupon_screen input').val()) {
				func_highlight($('.coupon_screen input'));
				return false;
			}

			$.ajax({url: '/checkout?gc='+$('.coupon_screen input').val()}).done(function(r) {
				if (r == 'S') {
					$('.coupon_alert').remove();
					refresh_coupon();
				} else {
					$('.coupon-error').html(r);
					$('.coupon-error').show();
				}
			});
		});
	});

	$('.apply_coupon').on('click', function() {
		bc = false;
		var id = alert('<div class="coupon_screen"></div>', 1, '', 'coupon_alert');
		$('.coupon_screen').html('<div class="group"><input type="text" size="30" required /><span class="highlight"></span><span class="bar"></span><label>{lng[Enter your coupon code here]}</label></div><div class="coupon-error"></div><button>{lng[Apply]}</button><br/><br/>');
		$('.coupon_screen input').on('keyup', function() {
			$('.coupon-error').hide();
		});

		$('.coupon_screen button').on('click', function() {
			if (!$('.coupon_screen input').val()) {
				func_highlight($('.coupon_screen input'));
				return false;
			}

			$.ajax({url: '/checkout?coupon='+$('.coupon_screen input').val()}).done(function(r) {
				if (r == 'S') {
					$('.coupon_alert').remove();
					refresh_coupon();
				} else {
					$('.coupon-error').html(r);
					$('.coupon-error').show();
				}
			});
		});
	});

	$('#local_pickup').change(function() {
		if ($(this).is(':checked')) {
			recalculate_shipping('L');
		} else {
			recalculate_shipping($('[name="shippingid"]').val());
		}

	});
}

var handler,
	stripe_loaded = false,
	stripe_lib;
function configure_stripe() {
	if (stripe_loaded)
		return;

  	stripe_loaded = true;
$.ajax({
  url: "https://js.stripe.com/v3/",
  dataType: "script",
  success: function() {
	stripe_lib = Stripe(stripe_key);
  }
});
}


function checkout_changes() {
	$('#checkout_user_form input').on('paste change keyup click', function() {
		$('#place_order *').attr('disabled', true);
		$('#place_order').css('opacity', .3);
	});

	$('#checkout_user_form select').change(function() {
		$('#place_order *').attr('disabled', true);
		$('#place_order').css('opacity', .3);
	});
}

function coupon_actions() {
	$('.remove_coupon').on('click', function() {
		$.ajax({url:'/checkout/remove_coupon'}).done(function() {
			refresh_coupon();
		});
	});

	$('.remove_gc').on('click', function() {
		$.ajax({url:'/checkout/remove_gc'}).done(function() {
			refresh_coupon();
		});
	});
}

function refresh_coupon() {
	if ($('.checkout_popup').is(':visible'))
		$('.checkout-link').click();
	else if ($('.cart_popup').is(':visible'))
		$('.cart-link').click();
	else
		window.location.reload();
}

function setCookie(cn,v,ed,seconds) {
	if (seconds) {
		var date = new Date(new Date().getTime() + ed * 1000);
		document.cookie=cn + "=" + v + ((ed==null) ? "" : "; expires="+date.toUTCString())+'; path=/;';//
	} else {
		var e = new Date();
		e.setDate(e.getDate() + ed);
		document.cookie=cn + "=" + v + ((ed==null) ? "" : "; expires="+e.toUTCString())+'; path=/;';
	}
}

function getCookie(cn) {
	var i, x, y, a = document.cookie.split(";");
	for (i = 0; i < a.length; i++) {
		x = a[i].substr(0, a[i].indexOf("="));
		y = a[i].substr(a[i].indexOf("=") + 1);
		x = x.replace(/^\s+|\s+$/g,"");
		if (x == cn)
			return y;
	}
}

function func_highlight(el) {
	if ($('html.area-c').length) {
		el.addClass('error');
		return;
	}

	el.css('background', '#ff3000');
	setTimeout(function() {
		el.css('background', '#fff');
		setTimeout(function() {
			el.css('background', '#ff3000');
			setTimeout(function() {
				el.css('background', '#fff');
			}, 200);
		}, 200);
	}, 100);
}

function print_invoice(el) {
	$('#iframe-invoice').remove();
	$("<iframe id='iframe-invoice' name='invoice' style='height: 0px; width: 0px;' src='" + el.attr('href') + "' />").appendTo('body');
	$('#iframe-invoice').load(function() {
		window.frames['invoice'].focus();
		window.frames['invoice'].print();
	});

	return false;
}

function strstr(haystack, needle, bool) {
	var pos = 0;

	pos = haystack.indexOf(needle);
	if (pos == -1)
		return false;
	else {
		if (bool)
			return haystack.substr( 0, pos );
		else
			return haystack.slice( pos );
	}
}

function recalc_menus(pg, pi) {
	$('.menu-container li').removeClass('active');
	if (pi) {
		$('li#menu-'+pi).addClass('active');
	} else {
		$('li#menu-'+pg).addClass('active');
	}
}

function on_scroll_header() {
	if ($('body').data('mobile') || $('body').hasClass('admin-area'))
		return;

	if ($(this).scrollTop() > 190) {
		$('body').addClass('scrolled');
	} else {
		$('body').removeClass('scrolled');
	}
}

function instant_search() {
	$('.search input').unbind('focus').on('focus', function() {
		var val = $(this).val();
		if (val.length < 2) {
			$('.instant-search').html("<div class='enter-3-chars'>{lng[Enter at least 2 characters]}</div>");
			return;
		}

		setTimeout(function() {
			search_instant(val);
		}, 300);
	});

	$('.search').unbind('mouseleave').mouseleave(function() {
		$('.instant-search').fadeOut();
	});

	$('.search').unbind('mouseover').mouseover(function() {
		return false;
		$('.instant-search').fadeIn();
		var val = $(this).find('input').val();
		if (val.length < 2) {
			$('.instant-search').html("<div class='enter-3-chars'>{lng[Enter 2 characters]}</div>");
			return;
		}

		setTimeout(function() {
			search_instant(val);
		}, 300);
	});

	$('.search input').on('keyup', function() {
		if ($(this).val().length < 2) {
			$('.instant-search').html("<div class='enter-3-chars'>{lng[Enter 2 characters]}</div>");
			return;
        }

		search_instant($(this).val());
	});
}

function search_instant(val) {
		try {
			instant_search_ajax.abort();
		} catch (err) {
		}

		instant_search_ajax = $.ajax({
			url: '/instant_search?q='+encodeURIComponent(val)
		}).done(function(r) {
			$('.instant-search').html(r);
			$('.instant-search').show();
			ajax_clicks();
			$('.more-no-search').on('click', function() {
				if (mobile_screen > $(window).width()) {
					$('.searchform_desktop').submit();
        } else
					$('.searchform_mobile').submit();

				$('.instant-search').hide();
			});
		});
}

function responsive_init() {
	$('.navigation-toggle').on('click', function() {
		mobile_menu_open();
		$('body').removeClass('filteropen');
	});
}

var mobile_menu_clicked = false;
$('.mobile-menu-fade').on('click', function() {
	mobile_menu_open();
});

function mobile_menu_open() {
		if (mobile_menu_clicked) {
			mobile_menu_clicked = false;
			$('.mobile-left_menu').animate({left: -500}, 500);
			$('.navigation-toggle').removeClass('is-active');
			$('.mobile-menu-fade').fadeOut();
		} else {
			var top = $(window).scrollTop() + 80;
			$('.mobile-left_menu').css('top', top+'px');
			mobile_menu_clicked = true;
			$('.mobile-left_menu').animate({left: 0}, 500);
			$('.navigation-toggle').addClass('is-active');
			$('.mobile-menu-fade').fadeIn();
		}
}

function create_ajax_link(href) {
	try {
		if (href.indexOf('?') == -1) {
			href = href+'?its_ajax_page=1';
		} else {
			href = href+'&its_ajax_page=1';
		}
	} catch (err) {
	}

	return href;
}

function scrolltop() {
	if ($(window).scrollTop() > 100)
		$('#scrolltop').show();
	else
		$('#scrolltop').hide();

	$('#scrolltop').unbind('click').on('click', function() {
		$('html, body').animate({
			scrollTop: 0
		}, 500);
	});
}

function check_if_in_view() {
	return false;
  var window_height = $window.height();
  var window_top_position = $window.scrollTop();
  var window_bottom_position = (window_top_position + window_height);

  $.each($animation_elements, function() {
    var $element = $(this);
    var element_height = $element.outerHeight();
    var element_top_position = $element.offset().top;
    var element_bottom_position = (element_top_position + element_height);

    //check to see if this current container is within viewport
    if ((element_bottom_position >= window_top_position) &&
        (element_top_position <= window_bottom_position)) {
      $element.addClass('in-view');
    } else {
    }
  });
}

function close_ticket(id, what) {
	if (what == '1') {
		$('#ticket-status-'+id).html("{lng[Closed]}");
		var sid = 'C';
		alert('Ticket closed');
	} else {
		$('#ticket-status-'+id).html("{lng[Cancelled]}");
		alert('Ticket cancelled');
		var sid = '3';
	}

	$('#ticket-status-links-'+id).html("");
	$.ajax({url: '/ticket/status/'+id+'/'+sid});
}

$('body').on('keyup focus', function(event) {
	if ($(event.target).prop('nodeName').toLowerCase() == 'input' || $(event.target).prop('nodeName').toLowerCase() == 'textarea') {
		$(event.target).removeClass('error');
	}
});

function mdl_elements() {
    $('div.group:not(.mdl_element)').each(function() {
			$(this).addClass('mdl_element');
			if ($(this).find('input').val()) {
        $(this).addClass('withval');
      }
			
			$(this).find('input').on('input', function() {
				if ($(this).val()) {
          $(this).parent().addClass('withval');
        } else
					$(this).parent().removeClass('withval');
			});

			if ($(this).find('textarea').val()) {
        $(this).addClass('withval');
      }
			
			$(this).find('textarea').on('input', function() {
				if ($(this).val()) {
          $(this).parent().addClass('withval');
        } else
					$(this).parent().removeClass('withval');
			});
		});
}

function init_translate() {
    $('.translate').unbind('click').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var lbl = $(this).find('.hidden.translate-phrase').html(),
					word = $(this).find('.hidden.word').html();
			alert('<textarea id="translate_me">'+lbl+'</textarea><div class="close-alert"><button>Save</button></div>', '1', '', 'translate-alert');
			$('.translate-alert').on('click', function() {
				$.ajax({
					url: '/translate?lbl='+encodeURIComponent(word)+'&translate='+encodeURIComponent($('.translate-alert textarea').val())
				});
			});
    });
}

function str_replace(search, replace, subject) {
	return subject.split(search).join(replace);
}