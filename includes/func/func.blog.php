<?php
#
# Blog bbcodes
#
function func_blog_convert_string(&$str) {
	# Common BB Codes
	$bb_codes = array('b', 'i', 's', 'u');
	foreach ($bb_codes as $code) {
		$c = preg_match_all( '/\\['.$code.'\\](.*)\\[\\/'.$code.'\\]/isU', $str, $m );
		if ($c != 0) {
			for ($i = 0; $i < $c; $i++) {
				$str = str_replace($m[0][$i], '<'.$code.'>'.$m[1][$i].'</'.$code.'>', $str);
			}
		}
	}

	# Image
	$c = preg_match_all( '/\\[img\\](.*)\\[\\/img\\]/isU', $str, $m );
	if ($c != 0) {
		for ($i = 0; $i < $c; $i++) {
			$str = str_replace($m[0][$i], '<img src="'.$m[1][$i].'">', $str);
		}
	}

	# Link
	$c = preg_match_all( '/\\[url\\](.*)\\[\\/url\\]/isU', $str, $m );
	if ($c != 0) {
		for ($i = 0; $i < $c; $i++) {
			$str = str_replace($m[0][$i], '<a rel="nofollow" href="'.$m[1][$i].'">'.$m[1][$i].'</a>', $str);
		}
	}

	# E-mail
	$c = preg_match_all( '/\\[email\\](.*)\\[\\/email\\]/isU', $str, $m );
	if ($c != 0) {
		for ($i = 0; $i < $c; $i++) {
			$str = str_replace($m[0][$i], '<a rel="nofollow" href="mailto: '.$m[1][$i].'">'.$m[1][$i].'</a>', $str);
		}
	}

	# List
	$c = preg_match_all( '/\\[list\\](.*)\\[\\/list\\]/isU', $str, $m );
	if ($c != 0) {
		for ($i = 0; $i < $c; $i++) {
		        $to_replace = "<ul>";
			$c2 = preg_match_all( '/\\[\*\\](.*)\\[\\/\*\\]/isU', $m['1']['0'], $m2);
			if ($c2 != 0) {
				for ($j = 0; $j < $c2; $j++) {
				        $to_replace .= "<li>".$m2[1][$j]."</li>";
				}
			}

		        $to_replace .= "</ul>";

			$str = str_replace($m[0][$i], $to_replace, $str);
		}
	}

	# Quote with author
	$c = preg_match_all( '/\\[quote=(.*)\\](.*)\\[\\/quote\\]/isU', $str, $m );
	if ($c != 0) {
		for ($i = 0; $i < $c; $i++) {
			$str = str_replace($m[0][$i], '<fieldset style="margin: 0px 20px; padding: 5px 10px; background: #eeeeee; border: 1px solid #999999;"><legend>'.$m[1][$i].'</legend>'.$m[2][$i].'</fieldset>', $str);
		}
	}

	# Quote
	$c = preg_match_all( '/\\[quote\\](.*)\\[\\/quote\\]/isU', $str, $m );
	if ($c != 0) {
		for ($i = 0; $i < $c; $i++) {
			$str = str_replace($m[0][$i], '<div style="margin: 0px 20px; padding: 5px 10px; background: #eeeeee; border: 1px solid #999999;">'.$m[1][$i].'</div>', $str);
		}
	}

	# Code
	$c = preg_match_all( '/\\[code\\](.*)\\[\\/code\\]/isU', $str, $m );
	if ($c != 0) {
		for ($i = 0; $i < $c; $i++) {
			$str = str_replace($m[0][$i], '<fieldset style="margin: 0px 20px; padding: 5px 10px; background: #eeeeee; border: 1px solid #999999;"><legend>'.lng('Code').'</legend>'.$m[1][$i].'</fieldset>', $str);
		}
	}

	return $str;
}

