(function($) {
"use strict";
  $(document).ready(function() {
	$('.add_album input').on('focus', function() {
		if ($(this).val() == $(this).attr('def'))
			$(this).val('');

		$(this).addClass('def');
	});

	$('.add_album input').on('blur', function() {
		if ($(this).val() == '') {
			$(this).val($(this).attr('def'));
			$(this).removeClass('def');
		}
	});

	$('.add_album button').on('click', function() {
		var e = $('.add_album input');
		if (e.val() != e.attr('def') && e.val())
			$('.add_album form').submit();
		else
			e.effect('highlight');
	});

	$('.edit_album').on('click', function() {
		var o = $(this);
		var i = o.attr('id');
		var tp = o.attr('type');
		bc = false;
		if (page == 'my_groups')
			alert('<div id="edit_'+i+'"><label><input type="radio" name="type" value="0"'+(tp == 0 ? ' checked' : '')+">{lng[public]}</label><br><label><input type='radio' name='type' value='1'"+(tp == 1 ? ' checked' : '')+">{lng[private]}</label><br><label><input type='radio' name='type' value='2'"+(tp == 2 ? ' checked' : '')+">{lng[for_subscribers]}</label><br><br>"+'<input type="text" value="'+$('.'+i).html()+'"><button>{lng[save]}</button></div>', '', '', 1, '', 1);
		else
			alert('<div id="edit_'+i+'"><label><input type="radio" name="type" value="0"'+(tp == 0 ? ' checked' : '')+">{lng[public]}</label><br><label><input type='radio' name='type' value='1'"+(tp == 1 ? ' checked' : '')+">{lng[private]}</label><br><label><input type='radio' name='type' value='2'"+(tp == 2 ? ' checked' : '')+">{lng[for_friends]}</label><br><label><input type='radio' name='type' value='3'"+(tp == 3 ? ' checked' : '')+">{lng[for_friends_and_their_friends]}</label><br><br>"+'<input type="text" value="'+$('.'+i).html()+'"><button>{lng[save]}</button></div>', '', '', 1, '', 1);
		$('#edit_'+i+' input').on('keyup', function(e) {
			if (e.which == 13 || e.which == 10)
				$('#edit_'+i+' button').trigger('click');
		});

		$('#edit_'+i+' button').on('click', function() {
			var p = $(this).parent(), l, v,
				t = p.find('[type="text"]');
			p.find('[type="radio"]').each(function() {
				if ($(this).is(':checked')) {
					v = $(this).val();
					l = $(this).parent().html();
				}
			});

			if (t.val() == '')
				t.effect('highlight');
			else {
				$('#type'+i).html(l);
				$('#type'+i+' input').remove();
				o.attr('type', v);
				$('.'+i).html(t.val());
				$('.alert').remove();
				$.ajax({
					type: 'POST',
					data: 'update='+i+'&updated='+encodeURIComponent(t.val())+'&type='+v
				});
			}
		});
	});

	$('.remove_album').on('click', function() {
		var o = $(this);
		if (!confirmed) {
			confirm(are_you_sure, o);
			return;
		}

		o.parent().remove();
		$.ajax({
			type: 'POST',
			data: 'remove='+o.parent().attr('id')
		});
	});

	$('.albums').sortable({
    	cursor: 'move',
    	update: function(e, ui) {
    	    $(this).sortable("refresh");
    	    sorted = '';
    	    $('.album').each(function() {
    	    	sorted += $(this).attr('id')+',';
    	 	});
    	    $.ajax({
	            type:   'POST',
    	        data:   'sa='+sorted
    	    });
	    }
    });

	clicks();
	sortable();
	drag_init();
  });
})($);

function clicks() {
	$('.product-images img').on('click', function() {		var huge = $(this).attr('huge');		if (huge) {			window.open(huge, '');		}	});

	$('.product-images img.remove').on('click', function() {
		var id = $(this).parent().data('id'),
			url = self.location.href.split('#');

		$.ajax({url: url[0]+'?remove_photo='+id});
		$(this).parent().remove();
	});
}

function sortable() {
    $(".product-images" ).sortable({
    	cursor: 'move',
    	update: function(e, ui) {
    	    $(this).sortable("refresh");
    	    sorted = '';
    	    $('.product-images div').each(function() {
    	    	sorted += $(this).data('id')+',';
    	 	});

    	    $.ajax({
	            type:   'POST',
    	        data:   's='+sorted
    	    });
	    }
    });
}

var file_too_big = "{lng[file_too_big]}",
	bad_image_type = "{lng[bad_image_type]}",
	bad_images = "{lng[bad_images]}";

function drag_init() {
    var dropZone = $('.drag'),
        maxFileSize = 10240000;

    if (typeof(window.FileReader) == 'undefined')
        dropZone.hide();

    dropZone[0].ondragover = function() {
        dropZone.addClass('hover');
        return false;
    };

    dropZone[0].ondragleave = function() {
        dropZone.removeClass('hover');
        return false;
    };

    dropZone[0].ondrop = function(event) {
        event.preventDefault();
        dropZone.removeClass('hover');
        dropZone.addClass('drop');

		var files = event.dataTransfer.files;
		var c = files.length;
		var done = 0;
		var form = new FormData();
		for (var i = 0; i < c; i++) {
			var file = files[i];
    	    if ((file.size <= maxFileSize) && (file.type == 'image/png' || file.type == 'image/jpg' || file.type == 'image/gif' || file.type == 'image/jpeg' || file.type == 'image/bmp')) {
				form.append("file"+i, file);
				done++;
			}
		}

		if (done > 0) {
	        var xhr = new XMLHttpRequest();
	        xhr.upload.addEventListener('progress', uploadProgress, false);
	        xhr.onreadystatechange = function (e) {
		        if (e.target.readyState == 4)
		            if (e.target.status == 200) {
		            	$('.product-images').append(e.currentTarget.responseText);
						clicks();
						sortable();
						$('.pbar').hide();
						$('form[name=upload]').show();
						return;
		            }
		    };

			var url = self.location.href.split('#');
	        xhr.open('POST', url[0]);
	        xhr.send(form);
		} else
			alert(bad_images);
    };

    function uploadProgress(e) {
		$('form[name=upload]').hide();
		$('.pbar').show();
		$('.pbar img').width(Math.floor(e.loaded / e.total * 250));
    }

	$(':file').change(function() {		var done = 0;
		var form = new FormData();
		for (var x in this.files) {
			var file = this.files[x];
    	    if ((file.size <= maxFileSize) && (file.type == 'image/png' || file.type == 'image/jpg' || file.type == 'image/gif' || file.type == 'image/jpeg' || file.type == 'image/bmp')) {
				form.append("file"+x, file);
				done++;
			}
		}

		if (done > 0) {
	        var xhr = new XMLHttpRequest();
	        xhr.upload.addEventListener('progress', uploadProgress, false);
	        xhr.onreadystatechange = function (e) {
		        if (e.target.readyState == 4)
		            if (e.target.status == 200) {		            	$('.product-images').append(e.currentTarget.responseText);
						clicks();
						sortable();
						$('.pbar').hide();
						$('form[name=upload]').show();
						return;
		            }
		    };

			var url = self.location.href.split('#');
	        xhr.open('POST', url[0]);
	        xhr.send(form);
		} else
			alert(bad_images);

		return;
		for (var x in this.files)
			var file = this.files[x];
        	name = file.name;
            size = file.size;
	        type = file.type;
	    	if (file.name.length < 1) {
			} else if (file.type != 'image/png' && file.type != 'image/jpg' && file.type != 'image/gif' && file.type != 'image/jpeg' && file.type != 'image/bmp')
				alert(bad_image_type);
			else {
						var data = new FormData(document.upload),
						xhr = new XMLHttpRequest();
						var url = self.location.href.split('#');
				        xhr.open('POST', url[0]+'?albumid='+albumid);
						xhr.onload = function (e) {
							var s = e.currentTarget.responseText.replace('[""]', '').split('|');
	            			if (simple_ta)
	            				insertImage(s[0], s[1], 1);
	         				else
		            			$('body').append('<img src="'+s[0]+'" onload="insertImage(\''+s[0]+'\', '+s[1]+')" class="hidden">');

							$('.popup').remove();
							unfade();
						};

						xhr.upload.onprogress = function (e) {
							$('form[name=upload]').hide();
							$('.pbar').show();
							$('.pbar img').width(Math.floor(e.loaded / e.total * 250));
						};

						xhr.send(data);
					    $(':file').val('');

						return false;
            		}
			    });
}

function insertImage(u, i, j) {
	bb = true;
	$('.galleries_popup').remove();
	if (j)
		$('.attachments').append('<div id="a'+i+'"><img src="/images/remove.png" class="remove">'+u+'</div>');
    else
		$('.attachments').append('<div id="a'+i+'"><img src="/images/remove.png" class="remove"><img src="'+u+'" onclick="show_photo('+i+')"></div>');

	$('.attachments').show();
	clicks();
	sortable();
	return false;
}