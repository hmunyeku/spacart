<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * SpaCart Template Engine & Core Functions
 * Ported from SpaCart's custom template engine, adapted for Dolibarr
 */

/**
 * Compile a template file with variable substitution
 *
 * Supports:
 *   {$variable}                   => <?php echo $variable; ?>
 *   {foreach $arr as $k=>$v}     => PHP foreach
 *   {/foreach}                    => endforeach
 *   {if condition}                => PHP if
 *   {elseif condition}            => PHP elseif
 *   {else}                        => PHP else
 *   {/if}                         => endif
 *   {price $var}                  => spacartFormatPrice($var)
 *   {weight $var}                 => formatted weight
 *   {lng[key]}                    => translation lookup
 *   {include="path/file.php"}     => include sub-template
 *   {assign $var=value}           => variable assignment
 *
 * @param string $template_path  Full path to template file
 * @param array  $vars           Variables to pass to the template
 * @param bool   $use_cache      Whether to cache compiled templates
 * @return string Rendered HTML
 */
function spacart_render($template_path, $vars = array(), $use_cache = true)
{
	global $spacart_config, $spacart_cart, $spacart_customer, $spacart_categories, $spacart_pages;

	if (!file_exists($template_path)) {
		return '<!-- Template not found: '.basename($template_path).' -->';
	}

	// Check cache
	$cache_file = '';
	if ($use_cache && defined('SPACART_CACHE_PATH')) {
		$cache_dir = SPACART_CACHE_PATH;
		if (!is_dir($cache_dir)) {
			@mkdir($cache_dir, 0755, true);
		}
		$cache_file = $cache_dir.'/'.md5($template_path).'.php';

		if (file_exists($cache_file) && filemtime($cache_file) >= filemtime($template_path)) {
			// Use cached compiled template
			extract($vars);
			ob_start();
			include $cache_file;
			return ob_get_clean();
		}
	}

	// Read template source
	$source = file_get_contents($template_path);

	// Compile template
	$compiled = spacart_compile($source);

	// Save to cache
	if (!empty($cache_file)) {
		file_put_contents($cache_file, $compiled);
	}

	// Render
	extract($vars);
	ob_start();
	eval('?>'.$compiled);
	return ob_get_clean();
}

/**
 * Compile template source into PHP code
 *
 * @param string $source Template source
 * @return string Compiled PHP code
 */
function spacart_compile($source)
{
	$compiled = $source;

	// {include="path/to/template.php"} => include
	$compiled = preg_replace(
		'/\{include="([^"]+)"\}/',
		'<?php echo spacart_render(SPACART_TPL_PATH."/\\1", get_defined_vars(), true); ?>',
		$compiled
	);

	// {assign $var=value}
	$compiled = preg_replace(
		'/\{assign\s+\$(\w+)\s*=\s*(.+?)\}/',
		'<?php $\\1 = \\2; ?>',
		$compiled
	);

	// {foreach $arr as $k=>$v} ... {/foreach}
	$compiled = preg_replace(
		'/\{foreach\s+(.+?)\s+as\s+(.+?)\}/',
		'<?php foreach(\\1 as \\2) { ?>',
		$compiled
	);
	$compiled = str_replace('{/foreach}', '<?php } ?>', $compiled);

	// {if condition} ... {elseif ...} ... {else} ... {/if}
	$compiled = preg_replace(
		'/\{if\s+(.+?)\}/',
		'<?php if(\\1) { ?>',
		$compiled
	);
	$compiled = preg_replace(
		'/\{elseif\s+(.+?)\}/',
		'<?php } elseif(\\1) { ?>',
		$compiled
	);
	$compiled = str_replace('{else}', '<?php } else { ?>', $compiled);
	$compiled = str_replace('{/if}', '<?php } ?>', $compiled);

	// {price $var} => formatted price
	$compiled = preg_replace(
		'/\{price\s+(.+?)\}/',
		'<?php echo spacartFormatPrice(\\1); ?>',
		$compiled
	);

	// {weight $var} => formatted weight
	$compiled = preg_replace(
		'/\{weight\s+(.+?)\}/',
		'<?php echo number_format((float)(\\1), 2, ",", " ")." ".getDolGlobalString("SPACART_WEIGHT_SYMBOL", "kg"); ?>',
		$compiled
	);

	// {lng[key]} => translation
	$compiled = preg_replace_callback(
		'/\{lng\[([^\]]+)\]\}/',
		function($matches) {
			$key = trim($matches[1]);
			return '<?php echo spacart_translate("'.addslashes($key).'"); ?>';
		},
		$compiled
	);

	// {$variable} => echo (must be last to avoid conflicts)
	$compiled = preg_replace(
		'/\{\$(\w+(?:\[.*?\])*(?:->\w+)*)\}/',
		'<?php echo htmlspecialchars($\\1 ?? "", ENT_QUOTES, "UTF-8"); ?>',
		$compiled
	);

	// {$variable|raw} => echo without escaping
	$compiled = preg_replace(
		'/\{\$(\w+(?:\[.*?\])*(?:->\w+)*)\|raw\}/',
		'<?php echo $\\1 ?? ""; ?>',
		$compiled
	);

	return $compiled;
}

/**
 * Translation function for SpaCart templates
 *
 * @param string $key Translation key
 * @return string
 */
function spacart_translate($key)
{
	global $langs;

	// Try SpaCart lang file first
	$trans = $langs->transnoentitiesaliases($key);
	if ($trans != $key) {
		return $trans;
	}

	// Fallback to key itself
	return $key;
}

/**
 * Send email using Dolibarr's mailer
 *
 * @param string $to       Recipient email
 * @param string $subject  Subject
 * @param string $body     HTML body
 * @param string $from     Sender email (optional)
 * @return bool
 */
function spacart_send_mail($to, $subject, $body, $from = '')
{
	global $conf, $langs;

	if (empty($from)) {
		$from = getDolGlobalString('SPACART_COMPANY_EMAIL', getDolGlobalString('MAIN_MAIL_EMAIL_FROM'));
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

	$mail = new CMailFile(
		$subject,
		$to,
		$from,
		$body,
		array(), // files
		array(), // mime
		array(), // filename
		'', // cc
		'', // bcc
		0, // delivery receipt
		1, // is html
		'', // errors to
		'', // css
		'', // trackid
		'', // moreinheader
		'standard', // sendcontext
		'' // replyto
	);

	$result = $mail->sendfile();
	return ($result > 0);
}

/**
 * Render an email template
 *
 * @param string $template  Template name (e.g., 'order_confirmation')
 * @param array  $vars      Variables
 * @return string HTML
 */
function spacart_render_mail($template, $vars = array())
{
	$path = SPACART_TPL_PATH.'/mail/'.$template.'.php';
	return spacart_render($path, $vars, false);
}

/**
 * Get product photo URL
 *
 * @param int    $product_id Product rowid
 * @param string $ref        Product ref
 * @param string $filename   Specific filename (optional)
 * @return string URL or empty
 */
function spacart_product_photo_url($product_id, $ref, $filename = '')
{
	global $conf;

	$photo_dir = DOL_DATA_ROOT.'/produit/'.$ref.'/';

	if (!empty($filename) && file_exists($photo_dir.$filename)) {
		return DOL_URL_ROOT.'/document.php?modulepart=produit&attachment=0&file='.urlencode($ref.'/'.$filename);
	}

	// Find first image
	if (is_dir($photo_dir)) {
		$files = scandir($photo_dir);
		foreach ($files as $f) {
			if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f) && strpos($f, 'thumbs') === false && substr($f, 0, 1) !== '.') {
				return DOL_URL_ROOT.'/document.php?modulepart=produit&attachment=0&file='.urlencode($ref.'/'.$f);
			}
		}
	}

	// No photo placeholder
	return SPACART_URL.'/img/no-photo.png';
}

/**
 * Get all photos for a product
 *
 * @param string $ref Product ref
 * @return array Array of photo URLs
 */
function spacart_product_photos($ref)
{
	$photos = array();
	$photo_dir = DOL_DATA_ROOT.'/produit/'.$ref.'/';

	if (is_dir($photo_dir)) {
		$files = scandir($photo_dir);
		foreach ($files as $f) {
			if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f) && strpos($f, 'thumbs') === false && substr($f, 0, 1) !== '.') {
				$photos[] = DOL_URL_ROOT.'/document.php?modulepart=produit&attachment=0&file='.urlencode($ref.'/'.$f);
			}
		}
	}

	return $photos;
}

/**
 * Build breadcrumbs HTML
 *
 * @param array $items Array of [label, url] pairs
 * @return string HTML
 */
function spacart_breadcrumbs($items)
{
	$html = '<nav class="spacart-breadcrumbs"><ul>';
	$html .= '<li><a href="#/">Accueil</a></li>';
	foreach ($items as $item) {
		if (!empty($item['url'])) {
			$html .= '<li><a href="'.htmlspecialchars($item['url']).'">'.htmlspecialchars($item['label']).'</a></li>';
		} else {
			$html .= '<li class="active">'.htmlspecialchars($item['label']).'</li>';
		}
	}
	$html .= '</ul></nav>';
	return $html;
}

/**
 * Build category tree from flat array
 *
 * @param array $categories Flat list of category objects
 * @param int   $parent_id  Parent ID to start from
 * @return array Nested tree
 */
function spacart_build_category_tree($categories, $parent_id = 0)
{
	$tree = array();
	foreach ($categories as $cat) {
		if ((int) $cat->fk_parent == $parent_id) {
			$children = spacart_build_category_tree($categories, (int) $cat->rowid);
			$node = array(
				'id'       => (int) $cat->rowid,
				'label'    => $cat->label,
				'description' => $cat->description,
				'children' => $children,
			);
			$tree[] = $node;
		}
	}
	return $tree;
}

/**
 * JSON response helper for API
 *
 * @param mixed $data    Data to encode
 * @param int   $status  HTTP status code
 */
function spacart_json_response($data, $status = 200)
{
	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

/**
 * JSON error response
 *
 * @param string $message Error message
 * @param int    $status  HTTP status code
 */
function spacart_json_error($message, $status = 400)
{
	spacart_json_response(array('error' => true, 'message' => $message), $status);
}
