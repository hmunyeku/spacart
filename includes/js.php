<?php
if (!DEVELOPMENT)
	include SITE_ROOT.'/includes/classes/class.JavaScriptPacker.php';

$dir = SITE_ROOT.'/var/cache/'.$lng.'/js';

foreach ($js as $v) {
	if (!file_exists($dir.'/'.$v.'.js') || $templates['js'][$v] != filemtime(SITE_ROOT.'/templates/js/'.$v.'.js') || DEVELOPMENT) {
		$script = file_get_contents(SITE_ROOT.'/templates/js/'.$v.'.js');
		$tmp = explode("\r", $script);
		$script = implode("\r\n", $tmp);
		preg_match_all("/\{lng\[(.*?)\]\}/", $script, $matches);
		if (!empty($matches['1']['0'])) {
			foreach ($matches['0'] as $k2=>$v2) {
				$to = $matches['1'][$k2];
				$tmp = $db->field("SELECT translation FROM languages WHERE lng='".$lng."' AND word='".addslashes($to)."'");
				if (empty($tmp)) {
					$db->query("INSERT INTO languages SET lng='".$lng."', word='".addslashes($to)."', translation='".addslashes($to)."'");
					$script = str_replace($v2, $to, $script);
				} else {
					$tmp = str_replace('"', '\"', $tmp);
					$script = str_replace($v2, $tmp, $script);
				}
			}
		}

		$packed = $script;
		if ($v == 'jquery.min') {
		} elseif ($v == 'jquery.zoom.min') {
			$packed = '/*!Zoom v1.7.11 - 2013-11-12	Enlarge images on click or mouseover.	(c) 2013 Jack Moore - http://www.jacklmoore.com/zoom	license: http://www.opensource.org/licenses/mit-license.php*/'.$packed;
		} elseif ($v == 'jquery.ui.sortable') {
			$packed = '/*! jQuery UI Sortable 1.10.2
* http://jqueryui.com
* Copyright 2013 jQuery Foundation and other contributors Licensed MIT */'.$packed;
		} elseif ($v == 'jquery-ui.min') {
			$packed = '/*! jQuery UI
* http://jqueryui.com
* Copyright (c) 2013 jQuery Foundation and other contributors Licensed MIT */'.$packed;
		} elseif ($v == 'scroll') {
			$packed = '/*! Copyright (c) 2011 Brandon Aaron (http://brandonaaron.net)
 * Licensed under the MIT License (LICENSE.txt).
 *
 * Thanks to: http://adomas.org/javascript-mouse-wheel/ for some pointers.
 * Thanks to: Mathias Bank(http://www.mathias-bank.de) for a scope bug fix.
 * Thanks to: Seamus Leahy for adding deltaX and deltaY
 *
 * Version: 3.0.6
 *
 * Requires: 1.2.2+
 */
'.$packed;
		} elseif ($v == 'jquery.gradientPicker') {
			$packed = '/**
@author Matt Crinklaw-Vogt (tantaman)
*/'.$packed;
		} elseif ($v == 'jquery.ui.draggable') {
			$packed = '/*! jQuery UI Draggable 1.10.2
* http://jqueryui.com
* Copyright 2013 jQuery Foundation and other contributors Licensed MIT */'.$packed;
		} elseif ($v == 'colorpicker') {
			$packed = '/**
 *
 * Color picker
 * Author: Stefan Petre www.eyecon.ro
 *
 * Dual licensed under the MIT and GPL licenses
 *
 */'.$packed;
		}

		// URL rewriting for individual JS files (Dolibarr subdir install)
		if (!empty($web_dir) && $web_dir != '/') {
			$packed = preg_replace('#([\x27\x22])/((?!/)(?!http)(?!data:)[a-zA-Z])#', '$1' . $web_dir . '/$2', $packed);
			$packed = str_replace($web_dir . $web_dir, $web_dir, $packed);
			$packed = str_replace($web_dir . '/http', '/http', $packed);
		}
		file_put_contents($dir.'/'.$v.'.js', $packed);
		$db->query("REPLACE INTO templates SET lng='js', template='".$v."', time='".filemtime(SITE_ROOT.'/templates/js/'.$v.'.js')."'");
	}
}

global $css_js_cache;

$default_js = func_get_ajax_js();
$js_cache = '';
foreach ($default_js as $v) {
	$js_cache .= file_get_contents(SITE_ROOT . '/var/cache/'.$lng.'/js/'.$v.'.js');
}

// URL rewriting: prefix relative paths with $web_dir for Dolibarr subdir install
if (!empty($web_dir) && $web_dir != '/') {
    $js_cache = preg_replace('#([\x27\x22])/((?!/)(?!http)(?!data:)[a-zA-Z])#', '$1' . $web_dir . '/$2', $js_cache);
    // Remove double web_dir
    $js_cache = str_replace($web_dir . $web_dir, $web_dir, $js_cache);
    // Fix broken JS regexes: .replace(/"/PREFIX/g => .replace(/"/g
    $js_cache = preg_replace('#\.replace\(/(.{1,5})' . preg_quote($web_dir, '#') . '/([gims]{1,4})\b#', '.replace(/$1/$2', $js_cache);
}

$fp = fopen(SITE_ROOT . '/var/cache/'.$lng.'/js.js', 'w');
fputs($fp, $js_cache);
fclose($fp);

echo '<script src="'.$web_dir.'/var/cache/'.$lng.'/js.js?'.$css_js_cache.'"></script>';

$already = array();
foreach ($js as $v) {
	if (!$already[$v]) {
		if (!in_array($v, $default_js))
			echo '<script async src="'.$web_dir.'/var/cache/'.$lng.'/js/'.$v.'.js?'.$css_js_cache.'"></script>';

		$already[$v] = 1;
	}
}