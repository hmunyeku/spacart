var put_filter_push = '';
(function($) {
"use strict";
  $(document).ready(function() {
	if (page == 'search' || page == 'category' || (page == 'brands' && pageid))
		load_filter();

	products_clicks();
  });
})($);

function products_clicks() {
	if ($('body').hasClass('admin-area'))
		return false;

	$('.navigation a, .sort-by a').unbind('click').on('click', function() {
		var h = $(this).attr('href');
		if (!pushed) {
			pushed = true;
		}

		if ($(this).closest('.bottom-pagination').length) {
			$('html, body').animate({
				scrollTop: $('.products-results').offset().top - 100
			}, 300);
		}

		$.ajax({
			url: h,
			error: function() {
				self.location = h;
			},
			success: function(r) {
				$('#content-loading').remove();
				$('.products-results').html(r);
				window.history.pushState({'page': 'search', 'dontrealodfilter': '1', "html":$('.ajax_container').html(),"pageTitle":$('title').html(), 'bread_crumbs': $('#bread_crumbs_container').html()},"", h);
				ajax_clicks();
				dontloadfilter = true;
				proceed_clicks('search');
			}
		});

		return false;
	});

	$('.products .res-item').unbind('mouseenter').mouseenter(function() {
    var photo_box = $(this).find('.photo');
		photo_box.append('<div class="view-quicklook"><svg><use xlink:href="/images/sprite.svg#search"></use></svg></div>');
		var q = photo_box.find('.view-quicklook'),
			i = photo_box.closest('.photo'),
			product_id_attr = i.find('.product-image').attr('id'); if (!product_id_attr) return; product_id = product_id_attr.replace('pid-', '');

		q.css('left', (i.width() / 2 - 35) + 'px');
		q.css('top', (i.height() / 2 - 20)+'px');
		q.css('z-index', 3);
		q.on('click', function() {
			load_quick_look(product_id);
		});
	});

	$('.products .quick-look').unbind('click').on('click', function() {
		var product_id = $(this).data('productid');
		load_quick_look(product_id);
	});

	$('.products .res-item').unbind('mouseleave').mouseleave(function() {
		$(this).find('.view-quicklook').remove();
	});
	$('.products button').unbind('click').on('click', function() {
		bc = false;
		var btnId = $(this).attr('id'); if (btnId) add_to_cart(btnId.replace('pid',''), 'Y');
	});
}

function load_quick_look(product_id) {
			aload();
			$('body').append('<div class="load hidden"><span class="close close-popup">x</span></div>');
			$('.popup-wrap').css('top', $(window).scrollTop()+'px');

			$('.load').load('/product/'+product_id+'?popup=1', function(r) {
				$('.popup-wrap').height($(window).height());
				unload();
				fade();
				$('.load').html('<span class="close close-popup">x</span>'+r);
				$('.load').addClass('popup product_popup');
				var top = ($(window).scrollTop() + $(window).height() / 2 - $('.product_popup').height() / 2) - 50;
				if (top < $(window).scrollTop())
					top = $(window).scrollTop() + 30;

				$('.product_popup').css('top', top + 'px');

				$(".product_popup img").on('load', function() {
					var top = ($(window).scrollTop() + $(window).height() / 2 - $('.product_popup').height() / 2) - 50;
					if (top < $(window).scrollTop())
						top = $(window).scrollTop() + 30;

					$('.product_popup').css('top', top + 'px');
					$('.product_popup').css('left', ($(window).width() / 2 - $('.product_popup').width() / 2 - 10) + 'px');
				});

				$('.product_popup').css('left', ($(window).width() / 2 - $('.product_popup').width() / 2 - 10) + 'px');
				$('.product_popup').css('opacity', 0).css('transform', 'matrix(0.1, 0, 0, 0.1, 0, 0)');
				setTimeout(function() {
					$('.product_popup').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
					setTimeout(function() {
						$('.product_popup').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
					}, 200);
				}, 200);

				$('.product_popup').show();
				$(window).resize(function() {
					var top = ($(window).scrollTop() + $(window).height() / 2 - $('.product_popup').height() / 2) - 50;
					if (top < $(window).scrollTop())
						top = $(window).scrollTop() + 30;

					$('.product_popup').css('top', top + 'px');

					$('.product_popup').css('left', ($(window).width() / 2 - $('.product_popup').width() / 2 - 10) + 'px');
				});

				$('.product_popup .close, .product_popup .close_popup').on('click', function() {
					removePopups();
					return;
					$('.product_popup').css('opacity', 1).css('transform', 'matrix(1.1, 0, 0, 1.1, 0, 70)');
					setTimeout(function() {
						$('.product_popup').css('transform', 'matrix(1, 0, 0, 1, 0, 0)');
				    	unfade();
					}, 200);

					return;
    				var e = $('.product_popup');
	    			e.slideUp();
					setTimeout(function(){$('body').css('overflow-y', 'auto');e.remove()}, 500);
				});

				$('.product_popup').on('click', function() {
					bc = false;
					bb = true;
				});

				product_clicks()
			});
}

function load_filter(url_params, url_replace) {
	$('#left_filter').html('<div class="cssload-container"><div class="cssload-speeding-wheel"></div></div>');
	if (url_replace)
		var url = url_replace;
	else
		var url = window.location.href;

	if (!strstr(url, '?'))
		url += '?'

	$.ajax({
		url: url+'&load_filter=1'+(url_params ? url_params : '')
	}).done(function(r) {
		$('#left_filter').html(r);
    if (filter_box) {
      $('.filter-box-'+filter_box).html(filter_box_html);
      $('.withfilter .left_filter').animate({
        scrollTop: $('.filter-box-'+filter_box).position().top - 30 + $('.withfilter .left_filter').scrollTop()
      }, 500);
      filter_box = '';
    }

		if (put_filter_push) {
			window.history.pushState({'page': 'search', 'dontrealodfilter': '1', "html":$('.ajax_container').html(),"pageTitle":$('title').html(), 'bread_crumbs': $('#bread_crumbs_container').html()},"", put_filter_push);
			put_filter_push = '';
		}

    ajax_clicks();
		filter_clicks();
	});
}

var filter_box,
  filter_box_html;
function filter_clicks() {
	var url = $('.filter-url').html(),
    filter_url = url ? url.replace("&amp;", "&") : '',
		min_value = parseInt($("#min_price").val()),
		max_value = parseInt($("#max_price").val());

  $('.reset_filter').unbind('click').on('click', function() {
    $('.reset_filter_url').click();
  });

	$('.selected-filter').unbind('click').on('click', function() {
		var what_uncheck = $(this).data('what'),
			url = filter_url,
			id = $(this).data('id'),
			url_params = '';

		$('.selected-filter').each(function() {
			var what = $(this).data('what');
			if (what_uncheck != what) {
				if (what == 'brand') {
					url_params += '&filter[brandid]='+$(this).data('id');
				}

				if (what == 'price') {
					url_params += '&filter[price]='+$(this).data('id');
				}
			}

			if (what == 'attr') {
				if (id != $(this).data('id'))
					url_params += '&filter[attr]['+encodeURIComponent($(this).data('id'))+'][]='+encodeURIComponent($(this).data('oid'));
			}
		});

		load_filter_process(url, url_params);
	});

  $('#left_filter h4.pointer').unbind('click').on('click', function() {
    $(this).next().toggleClass('opened');
    $(this).toggleClass('opened');
  });

	$('#left_filter li').unbind('click').on('click', function() {
    var input = $(this).find('input');
    if (input.is(':checked')) {
      input.prop('checked', false);
      input.attr('checked', false);
  } else {
      input.prop('checked', true);
      input.attr('checked', true);
    }

		var what = $(this).closest('ul').data('what'),
			url = filter_url,
			url_params = '';

		if (what == 'brand') {
			url_params += '&filter[brandid]='+$(this).data('id');
		}

		if (what == 'price') {
			url_params += '&filter[price]='+$(this).data('id');
		}

		if (what == 'attr') {
		}

		$('.selected-filter').each(function() {
			var what = $(this).data('what');
			if (what == 'brand') {
				url_params += '&filter[brandid]='+$(this).data('id');
			}

			if (what == 'price') {
				url_params += '&filter[price]='+$(this).data('id');
			}
    });

    $('.filter-attr input:checked').each(function() {
			url_params += '&filter[attr]['+encodeURIComponent($(this).closest('li').data('id'))+'][]='+encodeURIComponent($(this).closest('li').data('oid'));
    });

    filter_box = $(this).closest('ul').attr('groupid');
    filter_box_html = $(this).closest('ul').html();
		load_filter_process(url, url_params);
	});
}

function load_filter_process(url, url_params) {
    $('body').removeClass('filteropen');
		var h = url+url_params;
		$('html, body').animate({
			scrollTop: $('.products-results').offset().top - 150
		}, 300);

		$('.ajax_container').append('<div id="content-loading" style="margin-top: '+($('.products-results').offset().top - 200)+'px"><div class="cssload-container"><div class="cssload-speeding-wheel"></div></div></div>');
		$('#content-loading').width($('.ajax_container').width());
		$('#content-loading').height($('.ajax_container').height());
		$.ajax({
			url: h,
			error: function() {
				self.location = h;
			},
			success: function(r) {
				$('#content-loading').remove();
				$('.products-results').html(r);
				h = h.replace('filtered=1&amp;', '');
				ajax_clicks();
				put_filter_push = h;
				load_filter(url_params, url);
				dontloadfilter = true;
				proceed_clicks('search');
			}
		});
}

$(window).resize(function() {
  left_filter_max_height();
});

$(window).scroll(function() {
  left_filter_max_height();
});

function left_filter_max_height() {
  if ($('.withfilter .left_filter').length) {
    var max_height = ($(window).height() - 100);
    if ($(window).scrollTop() < 230) {
      max_height = 2000;
    }

    $('.withfilter .left_filter').css('max-height', max_height+'px');
  }
}