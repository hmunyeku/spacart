function popup_product() {
	aload();
	$.ajax({url: current_location+'/admin/popup_product/'}).done(function(r) {
		fade();
		unload();
		$('body').append('<div class="popup products_popup"><img src="/images/close.png" class="close"/>'+r+'</div>');
		$(".products_popup img").one("load", function() {
		var top = ($(window).scrollTop() + $(window).height() / 2 - $('.products_popup').height() / 2);
			if (top < $(window).scrollTop())
						top = 30;

			$('.products_popup').css('top', top + 'px');
		});

		$('.products_popup').css('left', ($(window).width() / 2 - $('.products_popup').width() / 2 - 10) + 'px');
		$(window).resize(function() {
			var top = ($(window).scrollTop() + $(window).height() / 2 - $('.products_popup').height() / 2);
			if (top < $(window).scrollTop())
				top = 30;

			$('.products_popup').css('top', top + 'px');
			$('.products_popup').css('left', ($(window).width() / 2 - $('.products_popup').width() / 2 - 10) + 'px');
		});

		$('.products_popup .close, .products_popup .close_popup').on('click', function() {
			var e = $('.products_popup');
  			e.slideUp();
	    	unfade();
			setTimeout(function(){e.remove()}, 500);
		});

		$('.products_popup').on('click', function() {
			bc = false;
			bb = true;
		});

		$('#popup_product .categories select').dblclick(function() {
			aload();
			$.ajax({url: current_location + '/admin/popup_product/'+$(this).val()}).done(function(r) {
				unload();
				$('#popup_product .products').html(r);
				$('#popup_product .products select').dblclick(function() {
					popup_product_pid.value = $(this).val();
					popup_product_pname.value = $(this).find('option:selected').text();
					var e = $('.products_popup');
	  				e.hide();
			    	unfade();
					setTimeout(function(){e.remove()}, 500);
				});
			});
		});
	});
}