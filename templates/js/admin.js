var current_multirow = 0;
function admin_left_menu() {	var height = $('.content').height();
	if (height < $(window).height())
		height = $(window).height() - 40;
	$('.left-admin-menu').height(height);
}

$(window).resize(function() {});

(function($) {
"use strict";
	$(document).ready(function() {
		$('.login-enter').keyup(function(e) {
			if (e.key == 'Enter') {
				$('.loginform').submit();
			}
		});

		header_search_form();
	$('.goback-admin').attr('href', window.location.href);	var menu_fast_click = false;
	$('.admin_menu a.opener').on('click', function() {
		setCookie('admin_menu', $(this).attr('id'), 30);

		$('.admin_menu div').slideUp();
		var parent = $(this).parent();
		if (menu_fast_click) {
			menu_fast_click = false;
			parent.find('div').slideDown(150);
		} else
			parent.find('div').slideDown(350);
	});	admin_left_menu();
	setTimeout(function() {		admin_left_menu();
		setTimeout(function() {
			admin_left_menu();
		}, 2000);
	}, 1000);
	var left_menu_id = getCookie('admin_menu');
	console.log(left_menu_id);
	if (left_menu_id) {
		menu_fast_click = true;
		bc = false;
		$('#'+left_menu_id).click();
	}

	ajax_clicks_admin();
	});
//Code here
})($);

$(window).scroll(function() {	var top = $(window).scrollTop() - 40;
	if (top < 30)
		top = 0;
});

function submitForm(formObj, formMode, e) {
    if (!e && typeof(window.event) != 'undefined') e = event;

    if (e) {
        if (e.stopPropagation) e.stopPropagation();
        else e.cancelBubble = true;
    }

    if (!formObj)
        return false;

    if (formObj.tagName != "FORM") {
        if (!formObj.form)
            return false;

        formObj = formObj.form;
    }

    if (formObj.mode) formObj.mode.value = formMode;

    if (typeof(window.$) != 'undefined') {
        var r = $(formObj).triggerHandler('submit');
        if (r === false)
            return false;
    }

    return formObj.submit();
}

function check_all(form, prefix, flag) {
  if (!form)
    return;

  if (prefix)
    var reg = new RegExp("^"+prefix, "");
  for (var i = 0; i < form.elements.length; i++) {
    if (form.elements[i].type == "checkbox" && (!prefix || form.elements[i].name.search(reg) == 0) && !form.elements[i].disabled)
      form.elements[i].checked = flag;
  }
}

function copy_clean_url(from_field, to_field) {
  if (typeof from_field == "undefined" || typeof from_field.value == "undefined") {
    return;
  }

  if (typeof to_field == "undefined" || typeof to_field.value == "undefined") {
    return;
  }

  to_field.value = from_field.value.replace(/[\&]/g, '-and-').replace(/[^a-zA-Z0-9._-]/g, '-').replace(/[-]+/g, '-').replace(/-$/, '');

	$(to_field).parent().addClass('is-dirty');
  return true;
}

function change_state(e) {	$(e).each(function() {		if ($(this).is(':checked'))
			$(this).removeAttr('checked');
		else
			$(this).get(0).checked = true;
	});
}

function ajax_clicks_admin() {
	states_actions();
	init_translate();	current_multirow = 0;	custom_elements();
	reinitialize_mdl();
	$('.close-alerts').unbind('click').on('click', function() {		$('.alerts').slideUp();	});

	$('#wenabled').change(function() {
		var checked = $(this).is(':checked') ? 1 : 0;
		$('.warehouses-area').fadeToggle();
		console.log('1');
		$.ajax({
			method: 'POST',
			data: 'wenabled='+checked
		});
	});

	$('.collapse').on('click', function() {
		var i = $(this).attr('id'),
			c = $('#'+i+'_content');
		if (c.is(':visible')) {
			$(this).removeClass('minus');
			c.hide();
		} else {
			$(this).addClass('minus');
			c.show();
		}
	});

	$('#data_type').change(function() {
		if ($(this).val() == 'g') {
			$('#data_view_type').html("<option value='s'>{lng[Select box]}</option><option value='p'>{lng[Squares]}</option>");
		} else {
			$('#data_view_type').html("<option value='t'>{lng[Text area]}</option><option value='i'>{lng[Input box]}</option>");
		}
	});

	$('.define-var-wh').on('click', function() {
		var variantid = $(this).data('variantid');
		$('.warehouses').hide();
		$('#warehouses-'+variantid).fadeIn();
	});

	$('.update-whs').on('click', function() {
		var itemid = $(this).data('itemid');
		$('.warehouses').hide();
		$('#warehouses-'+itemid).fadeIn();
	});

	$('.warehouses .cancel').on('click', function() {
		$('.warehouses').fadeOut();
	});

	$('.warehouses .save').on('click', function(e) {
		e.preventDefault();
		var pop = $(this).closest('.warehouses'),
			form = pop.find('form'),
			itemid = pop.data('itemid');

		aload();
	    $.ajax({
           type: "POST",
           data: form.serialize(), // serializes the form's elements.
           success: function(r) {
				unload();
				if (r == 'E') {
					alert('Something wrong');
				} else {
					$('.warehouses').hide();
					alert('Data saved');
					if (!$('[name="vform"]').length)
						setTimeout(function() {

						}, 500);
				}
			}
		});
	});

	$("#date_from, #date_to").datepicker({
    onSelect: function(dateText) {
      $(this).closest('.mdl-textfield').addClass('is-dirty');
    }
	});

	if ($('#chart-line').length)
		init_chart_line();

	$('.navigation-admin a, .admin-location a, .db-stats-3 a, .lines-table a, .admin-tabs a, .ajax_link, .left-admin-menu li div a, .navigation a').unbind('click').on('click', function(e) {
		if ($(this).hasClass('no-ajax'))
			return true;

		if ($(this).closest('.navigation-admin').length) {
        $('.navigation-admin li').removeClass('clicked');
				$(this).closest('li').addClass('clicked');
    }

		$('html, body').animate({
			scrollTop: 0
		}, 500);

		var h = $(this).attr('href');
		$('body').append('<div id="content-loading"><div class="cssload-container"><div class="cssload-speeding-wheel"></div></div></div>');
		$.ajax({
			dataType: 'json',
			url: create_ajax_link(h),
			error: function() {				self.location = h;
			},
			   success: function(r, textStatus, request) {
					$('#content-loading').remove();
				$('body').attr('id', 'body-'+r[3]);
				$('.ajax_container').html(r[0]);
				$('#bread_crumbs_container').html(r[2]);
				ajax_clicks_admin();
				page = r[3];
				window.history.pushState({"html":r[0],"pageTitle":r[1], 'bread_crumbs': r[2], 'page': r[3], 'parentid': r[4], 'pageid': r[5]},"", h);
				document.title = r[1];
				pageid = r[5];
			}
		});

		return false;
	});

	$('form:not(.noajax)').on('submit', function(event) {	    event.preventDefault();
		$('html, body').animate({
			scrollTop: 0
		}, 100);

		if ($(this).attr('href'))
			var h = $(this).attr('href');
		else if ($(this).attr('action'))
			var h = $(this).attr('action');
		else
			var h = window.location.href;

		$('.goback-admin').attr('href', h);
		$('body').append('<div id="content-loading"><div class="cssload-container"><div class="cssload-speeding-wheel"></div></div></div>');
		try {
			for (instance in CKEDITOR.instances)
		    	CKEDITOR.instances[instance].updateElement();
		} catch (err) {
		}

		var form_data = new FormData($(this)[0]),
			files = [];

		$(this).find('input').each(function() {			if ($(this).attr('type') == 'file') {				if (!$(this).val()) {					files[files.length] = $(this).attr('name');
					form_data.delete($(this).attr('name'));				}
			}		});

		$.ajax({
			url: h,
			type: "POST",
		    data: form_data,
		    cache: false,
		    contentType: false,
		    processData: false,
			error: function() {
				self.location = h;
			},
		   success: function(r, textStatus, request) {
				$('#content-loading').remove();		        if (request.getResponseHeader('where_redirect')) {		        	$('.goback-admin').attr('href', request.getResponseHeader('where_redirect'));		  		}

				$('#content-loading').remove();				$('.goback-admin').click();
			}
		});

		return false;
	});

	try {
		CKEDITOR.replace( 'ck_editor' );
		CKEDITOR.replace( 'ck_editor_2' );
	} catch (err) {
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

function submitBrand() {
	if (document.brandform.name.value == '') {
		document.brandform.name.focus();
		bc = false;
		alert("{lng[Brand name cannot be empty]}");
	} else
		$('form[name="brandform"]').submit();
}

function submit_category() {
	if (!document.category_form.title.value) {
		alert("{lng[Please, enter category title]}");
		bc = false;
		document.category_form.title.focus();
	} else {		$('form[name="category_form"]').submit();
	}
}

function duplicate_row(r, e) {
	if (e.html() == '+') {
		var h = r.html().split("[0]").join("["+(current_multirow+1)+"]");
		r.parent().append('<tr id="tmp_row">'+h+'</tr>');
		$('#tmp_row .duplicate_plus').html('-');
		$('#tmp_row').attr('id', '');
		current_multirow++;
	} else
		e.parent().parent().remove();
}

function import_checkboxes(val) {
	if (val == 'Y')
		$('[name=exportForm] input').prop('checked', true);
	else
		$('[name=exportForm] input').prop('checked', false);
}

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

function wholetoggle(id, el) {
	$('#wholesale-'+id).toggle(0,
		function() {
		$(this).is(":visible") ? el.html('[-]') : el.html('[+]');
		}
	);
}

function remove_wp(id) {
	$('#wp_tr-'+id).addClass('removed_wp');
	$('#wp_tr-'+id+' .removed input').val('Y');
	$('#wp_tr-'+id+' .removed img[alt=Remove]').hide();
	$('#wp_tr-'+id+' .removed .wprem').show();
	if ($('#wp_tr-'+id+' .removed .help').length > 0) {
		$('#wp_tr-'+id+' .removed .help').show();
	} else {
		$('#wp_tr-'+id+' .removed').append('<div class="help">{lng[Click the <b>Update</b><br />button to complete<br />removing]}</div>');
	}

	setTimeout('hidewphelp()', 1000);
}
function hidewphelp() {
	$('.removed .help').fadeOut();
}
function restore_wp(id) {
	$('#wp_tr-'+id).removeClass('removed_wp');
	$('#wp_tr-'+id+' .removed input').val('');
	$('#wp_tr-'+id+' .removed .wprem').hide();
	$('#wp_tr-'+id+' .removed img[alt=Remove]').show();
}
function all_possible() {
	$('.new_variant option').attr('selected', true);
	submitForm(document.vform, 'add');
}

function submitTaxForm(name) {
	if (name == 'rate') {
		if (!$('[name="rate_value"]').val()) {
			func_highlight($('[name="rate_value"]'));
			return false;
		}
	} else {
		if (!$('[name="tax_service_name"]').val()) {
			func_highlight($('[name="tax_service_name"]'));
			return false;
		}
	}

	return true;
}

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

function header_search_form() {
	$('#quick_search_form select').change(function() {
		$('#quick_search_form input').trigger('keyup');
	});

	var timeout = '';
	$('#quick_search_form input').keyup(function() {
		clearTimeout(timeout);
		timeout = setTimeout(function() {
			var val = $('#quick_search_form input').val();
			instant_search_ajax = $.ajax({
				url: '/admin/instant_search?where='+$('#quick_search_form select').val()+'&q='+encodeURIComponent(val)
			}).done(function(r) {
				$('.instant-search').html(r);
				$('.instant-search').show();
				ajax_clicks_admin();
			});
		}, 500);
	});
}

$('body').on('click', function(event) {
	if ($(event.target).closest('#quick_search_form').length)
		return;

	$('.instant-search').hide();
});

var agencyLineChart, agencyBarChart, agencyDoughnutChart;
const CHART_COLORS = {
  white: "#fff",
  green: "#4BD158",
  purple: "#A265FF",
  yellow: "#FFB202",
  pink: "#ED02AD",
  blue: "#218EF5",
  lightBlue: "#4A8EFF",
  red: "#EF364F",
  cyan: "#38C1F2",
  orange: "#F19B36",
  lightGreen: "#32E28B",
};

const TOOLTIP_SETTINGS = {
  titleColor: CHART_COLORS.white,
  bodyColor: CHART_COLORS.white,
  borderWidth: 1,
  borderColor: "rgb(65, 66, 67)",
  borderRadius: 10,
  padding: 15,
  titleFontSize: 16,
  bodyFontSize: 20,
  bodyWeight: "bold",
};

const GRID_SETTINGS = {
  color: "#363636",
};

const gradientTooltipPlugin = {
  id: "gradientTooltip",
  beforeDraw: function (chart) {
    const tooltip = chart.tooltip;
    if (tooltip._active && tooltip._active.length) {
      const ctx = chart.ctx;
      const tooltipModel = tooltip;

      const gradient = ctx.createLinearGradient(
        tooltipModel.x,
        tooltipModel.y - tooltipModel.height,
        tooltipModel.x,
        tooltipModel.y
      );

      gradient.addColorStop(0, "rgba(45, 46, 46, 1)");
      gradient.addColorStop(1, "rgb(33, 36, 36)");

      tooltipModel.gradientBackground = gradient;
    }
  },
};

const DOUGHNUT_CHART_CONFIG = {
  data: {
    labels: ["name1", "name2", "name3"],
    values: [60, 20, 20],
    colors: [
      CHART_COLORS.red,
      CHART_COLORS.cyan,
      CHART_COLORS.orange,
      CHART_COLORS.lightGreen,
    ],
  },

  display: {
    cutout: "70%",
    borderRadius: 12,
    borderWidth: 0,
    spacing: 8,
  },
};

function init_chart_line() {
  const canvas = document.getElementById(canvasId);
  const ctx = canvas.getContext("2d");
  const formattedLabels = formatMonthLabels(LINE_CHART_CONFIG.data.labels);
  var labels = [],
      values = [],
      json = $.parseJSON($('.agency_income_chart').html());

  for (var x in json) {
    labels[labels.length] = json[x].lbl;
    values[values.length] = json[x].total;
  }

  const chartConfig = {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        {
          label: "",
          data: values,
          borderColor: LINE_CHART_CONFIG.display.lineColor,
          backgroundColor: function (context) {
            const chart = context.chart;
            const { ctx, chartArea } = chart;
            return chartArea ? createGradient(ctx, chartArea) : null;
          },
          fill: true,
          tension: LINE_CHART_CONFIG.display.tension,
          pointBackgroundColor: "transparent",
          pointBorderColor: "transparent",
          pointHoverBackgroundColor: LINE_CHART_CONFIG.display.pointHoverColor,
          pointHoverBorderColor:
            LINE_CHART_CONFIG.display.pointHoverBorderColor,
          pointHoverRadius: LINE_CHART_CONFIG.display.pointHoverRadius,
        },
      ],
    },
    plugins: [verticalLinePlugin, gradientTooltipPlugin],
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          min: LINE_CHART_CONFIG.scaleY.min,
          max: LINE_CHART_CONFIG.scaleY.max,
          ticks: {
            callback: function (value) {
              const allowedValues = LINE_CHART_CONFIG.scaleY.allowedValues;

              if (!allowedValues) {
                return LINE_CHART_CONFIG.scaleY.format(value);
              } else if (allowedValues.includes(value)) {
                return LINE_CHART_CONFIG.scaleY.format(value);
              } else {
                return null;
              }
            },
            stepSize: LINE_CHART_CONFIG.scaleY.stepSize,
            color: CHART_COLORS.white,
          },
          grid: { color: GRID_SETTINGS.color },
        },
        x: {
          grid: { color: GRID_SETTINGS.color },
          ticks: { color: CHART_COLORS.white, autoSkip: false },
        },
      },
      plugins: {
        tooltip: {
          titleFont: { size: TOOLTIP_SETTINGS.titleFontSize },
          titleAlign: "center",
          bodyFont: {
            size: TOOLTIP_SETTINGS.bodyFontSize,
            weight: TOOLTIP_SETTINGS.bodyWeight,
          },
          bodyAlign: "center",
          callbacks: {
            title: function (context) {
              const fullDate = new Date(
                LINE_CHART_CONFIG.data.labels[context[0].dataIndex]
              );
              return '';
              return fullDate.toLocaleDateString("en", {
                day: "numeric",
                month: "long",
              });
            },
            label: function (context) {
              return LINE_CHART_CONFIG.scaleY.format(context.parsed.y);
            },
          },
          backgroundColor: function (context) {
            return context.chart.tooltip.gradientBackground;
          },
          titleColor: TOOLTIP_SETTINGS.titleColor,
          bodyColor: TOOLTIP_SETTINGS.bodyColor,
          borderColor: TOOLTIP_SETTINGS.borderColor,
          borderWidth: TOOLTIP_SETTINGS.borderWidth,
          displayColors: false,
          cornerRadius: TOOLTIP_SETTINGS.borderRadius,
          padding: TOOLTIP_SETTINGS.padding,
        },
        legend: { display: false },
      },
      interaction: {
        intersect: false,
        mode: "index",
      },
    },
  };

  agencyLineChart = new Chart(ctx, chartConfig);
  return agencyLineChart;
}