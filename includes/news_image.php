<?php
if (!$image['file'])
	return false;

# Make cache generation by Image Magic
$dir = SITE_ROOT . '/var/photo/news/';
if (!is_dir($dir)) {
	mkdir($dir);
	copy(SITE_ROOT . '/includes/index_file', $dir.'/index.php');
}

$dir .= $image['new_width'].'x'.$image['new_height'];
if (!is_dir($dir)) {
	mkdir($dir);
	copy(SITE_ROOT . '/includes/index_file', $dir.'/index.php');
}

$dir .= '/'.$image['newsid'];
if (!is_dir($dir)) {
	mkdir($dir);
	copy(SITE_ROOT . '/includes/index_file', $dir.'/index.php');
}

$dir .= '/'.$image['imageid'];
if (!is_dir($dir)) {
	mkdir($dir);
	copy(SITE_ROOT . '/includes/index_file', $dir.'/index.php');
}

$new_file = $dir.'/'.$image['file'];
if (!file_exists($new_file)) {
	$file = SITE_ROOT . '/photos/news/'.$image['newsid'].'/'.$image['imageid'].'/'.$image['file'];
	if ($image['x'] < $image['new_width'] && $image['y'] < $image['new_height']) {
		copy($file, $new_file);
	} else {
		global $is_image_magick, $image_magick_quality;
		if ($image_magick_quality)
			$image_magick_quality_string = "-quality ".$image_magick_quality;

		if (!$is_image_magick) {
			include_once SITE_ROOT . '/includes/classes/simpleimage.php';
			if (!$simpleimage)
				$simpleimage = new SimpleImage();
		}

		if ($image['new_width'] / $image['x'] > $image['new_height'] / $image['y']) {
			$height = $image['new_height'];
			$width = $image['x'] * $image['new_height'] / $image['y'];
		} else {
			$width = $image['new_width'];
			$height = $image['y'] * $image['new_width'] / $image['x'];
		}

		if ($is_image_magick) {
			exec("convert \"".$file."\" -resize ".$width."x".$height." ".$image_magick_quality_string." \"".$new_file."\"");
		} else {
			$simpleimage->load($file);
			$simpleimage->resize($width, $height);
			$simpleimage->save($new_file);
		}
	}
}

if ($image['only_url'] == 'Y') {
	echo $current_location.str_replace(SITE_ROOT, '', $dir).'/'.$image['file'];
} else {
	$image['url'] = $current_location.'/photos/news/'.$image['newsid'].'/'.$image['imageid'].'/'.$image['file'];
	if ($image['link']) {
		echo '<a href="'.$image['url'].'"'.($image['blank'] == 'Y'?' target="_blank"':'').'>';
	}

	if ($image['center']) {
		if (!$width)
			list($width, $height) = getimagesize($new_file);

		if ($height < $image['new_height']) {
			$valign = ($image['new_height'] - $height) / 2;
		} else
			$valign = 0;

		if ($width < $image['new_width']) {
			$center = ($image['new_width'] - $width) / 2;
		} else
			$center = 0;

		echo '<img src="'.str_replace(SITE_ROOT, $current_location, $dir).'/'.$image['file'].'" alt="'.escape($image['alt']).'"'.($image['class'] ? ' class="'.$image['class'].'"':'').''.($image['id'] ? ' id="'.$image['id'].'"':'').' style="margin: '.$valign.'px '.$center.'px;" />';
	} else
		echo '<img src="'.str_replace(SITE_ROOT, $current_location, $dir).'/'.$image['file'].'" alt="'.escape($image['alt']).'"'.($image['class'] ? ' class="'.$image['class'].'"':'').''.($image['id'] ? ' id="'.$image['id'].'"':'').' />';
	if ($image['link']) {
		echo '</a>';
	}
}
?>