<?php
$admin_theme_color = htmlspecialchars($_GET['theme_color']);
$theme_color = htmlspecialchars($_GET['theme_color']);
$theme_color_2 = htmlspecialchars($_GET['theme_color_2']);
if (!$theme_color) {
	if ($config['theme_color']) {
		$theme_color = $config['theme_color'];
		$theme_color_2 = $config['theme_color_2'];
	}
?>
<link rel="stylesheet" href="/files/colorpicker/css/colorpicker.css" type="text/css" />
<style>
.colorpicker {
	z-index: 200;
}
.theme_color {
	width: 400px;
	height: 280px;
	padding: 10px;
	background: #fff;
	border-radius: 10px 10px 0px 0px;
	position: fixed;
	right: 90px;
	bottom: -260px;
	z-index: 100;
	border: 1px solid #ccc;
	border-bottom: 0;
	transition: all .4s ease 0s;
}
.admin-area .theme_color {
	bottom: -240px;
	width: 222px;
}
.admin-area .theme_color h2 {
	line-height: 20px;
}
.admin-area .theme_color h3 {
	line-height: 20px;
    padding: 5px 0 5px 10px;
    margin: 5px 0 5px 0;
}
.theme_color.active {
	bottom: 0;
}
.admin-area .theme_color.active {
	bottom: 0px;
	height: 300px;
}
.theme_color h2 {
	cursor: pointer;
	margin: -10px -10px 0 -10px;
	padding: 10px;
    color: #fff;
border-radius: 10px 10px 0px 0px;
background: var(--theme-color); /* Old browsers */
}
.admin-area .theme_color h2 {
	background: var(--blue);
}
.admin-area .theme_color table tr:last-child > td:last-child {
	display: none;
}
.pre-made-colors, .pre-made-colors-2 {
	padding: 7px 0 0 6px;
}
.pre-made-colors i, .pre-made-colors-2 i {
	float: left;
	width: 30px;
	height: 30px;
	margin: 0 1px 1px 0;
	cursor: pointer;
}
</style>
<script type="text/javascript" src="/files/colorpicker/js/colorpicker.js"></script>

<div class="theme_color">
<h2>Design theme color</h2>
<table>
	<tr>
		<td width="50%">
<h3>Primary color</h3>
<div class="pre-made-colors">
<?php
if ($get['0'] == 'admin') {
	$colors = '#4caf50
#287bff
#013372
#358a69
#66b132
#cedf17
#fabd02
#f2a205
#e65100
#ff6f00
#cc080c
#b3102d
#89114f
#55166d
#000000
#777777
#aaaaaa
#276670
';
} else {
	$colors = '#E65100
#2196f3
#4caf50
#8c8c8c
#ffb300
#009688

#e91e63
#f44336
#9c27b0

#c0ca33

#795548

#4760E9
#FD7C6F
#1bbf3f
#ff1605
#9C13C5
#276670

#000000
';
}
$colors = explode('#', $colors);
foreach ($colors as $v) {
	if ($v)
		echo '<i rel="'.$v.'" style="background: #'.$v.';"></i>';
}
?>
</div>
<div class="clear"></div>
<br />
<style>
#theme_color, #theme_color_2 {
	width: 175px;
	margin: 5px 0 5px 0;
}
.theme_button {
	float: left;
	margin: 5px 0 0 0;
}
</style>
<?php
if ($get['0'] == 'admin') {
?>
<input type="text" id="theme_color" value="<?php if ($admin_theme_color) echo $admin_theme_color; else echo "4caf50"; ?>" />
<?php
} else {
?>
<input type="text" id="theme_color" value="<?php if ($theme_color) echo $theme_color; else echo "e65100"; ?>" />
<?php
}
?>
		</td>
		<td width="50%">
<h3>Alternative color</h3>
<div class="pre-made-colors-2">
<?php
$colors = '#2e2e2e
#0d47a1
#1b5e20
#303030
#ff6f00
#004d40

#880e4f
#b71c1c
#9c27b0

#827717
#3e2723

#0e2f54
#8A3219
#003d02
#380e0c
#450b57
#276670

#000000
';

$colors = explode('#', $colors);
foreach ($colors as $v) {
	if ($v)
	echo '<i rel="'.$v.'" style="background: #'.$v.';"></i>';
}
?>
</div>
<div class="clear"></div>
<br />
<input type="text" id="theme_color_2" value="<?php if ($theme_color_2) echo $theme_color_2; else echo "2e2e2e"; ?>" />
		</td>
	</tr>
</table>

<button class="theme_button">Apply</button><button class="theme_button reset">Reset</button>

</div>
<script>
$('.theme_color h2').on('click', function() {
	$('.theme_color').toggleClass('active');
});

$('#theme_color').ColorPicker({
	onSubmit: function(hsb, hex, rgb, el) {
		$(el).val(hex);
		$(el).ColorPickerHide();
	},
	onBeforeShow: function () {
		$(this).ColorPickerSetColor(this.value);
	},
	onChange: function (hsb, hex, rgb) {
		$('#theme_color').val(hex);
	}
})
.on('keyup', function(){
	$(this).ColorPickerSetColor(this.value);
});

$('#theme_color_2').ColorPicker({
	onSubmit: function(hsb, hex, rgb, el) {
		$(el).val(hex);
		$(el).ColorPickerHide();
	},
	onBeforeShow: function () {
		$(this).ColorPickerSetColor(this.value);
	},
	onChange: function (hsb, hex, rgb) {
		$('#theme_color_2').val(hex);
	}
})
.on('keyup', function(){
	$(this).ColorPickerSetColor(this.value);
});

$('.pre-made-colors i').on('click', function() {
	if ($('.admin-area').size()) {
		$('#theme_color').val($(this).attr('rel'));
		$('.theme_color button:not(.reset)').click();
	} else {
		$('#theme_color').val($(this).attr('rel'));
		$('.theme_color button:not(.reset)').click();
	}
});

$('.pre-made-colors-2 i').on('click', function() {
	$('#theme_color_2').val($(this).attr('rel'));
	$('.theme_color button:not(.reset)').click();
});

$('.theme_color button').on('click', function() {
	var url_add = '';
	if ($('.admin-area').size())
		url_add = '&admin=1';

	if ($(this).hasClass('reset')) {
		if ($('.admin-area').size())
			$('#theme_color').val('4caf50');
		else {
			$('#theme_color').val('e65100');
			$('#theme_color_2').val('2e2e2e');
		}

		$('#custom_style').remove();
		$.ajax({
			url: '/theme.php?theme_color=reset'+url_add
		}).done(function(r) {
		});

		return;
	}

	$.ajax({
		url: '/theme.php?theme_color='+$('#theme_color').val()+'&theme_color_2='+$('#theme_color_2').val()+url_add
	}).done(function(r) {
		$('#custom_style').remove();
		if ($('.admin-area').size()) {
			$('body').append('<style id="custom_style">:root {--blue: #'+$('#theme_color').val()+'}</style>');
		} else
			$('body').append('<style id="custom_style">:root {--theme-color: #'+$('#theme_color').val()+';--theme-color-2: #'+$('#theme_color_2').val()+';}</style>');
	});
});
</script>
<?php
} else {
include 'includes/boot.php';
if (!$design_mode)
	exit;

/*
$hex = "#".$theme_color;
list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
*/
if ($_GET['admin'] && $_GET['theme_color'] == 'reset') {
	$db->query("DELETE FROM config WHERE name='admin_theme_color'");
	exit;
} elseif ($_GET['theme_color'] == 'reset') {
	$db->query("DELETE FROM config WHERE name='theme_color'");
	$db->query("DELETE FROM config WHERE name='theme_color_2'");
	exit;
}

if (DEMO) {
} else {
	if ($_GET['admin']) {
		$db->query("DELETE FROM config WHERE name='admin_theme_color'");
		$db->query("INSERT INTO config SET name='admin_theme_color', value='".$theme_color."'");
	} else {
		$db->query("DELETE FROM config WHERE name='theme_color'");
		$db->query("INSERT INTO config SET name='theme_color', value='".$theme_color."'");
		$db->query("INSERT INTO config SET name='theme_color_2', value='".$theme_color_2."'");
	}
}

#exit($current_location.'/images/themes/'.$name.'.css');
}