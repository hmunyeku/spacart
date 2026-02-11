<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       lib/spacart.lib.php
 * \ingroup    spacart
 * \brief      Library of functions for SpaCart module
 */

/**
 * Prepare admin pages header (tabs)
 *
 * @return array Array of tabs
 */
function spacartAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("spacart@spacart");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/spacart/admin/dashboard.php", 1);
	$head[$h][1] = $langs->trans("Dashboard");
	$head[$h][2] = 'dashboard';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Configuration");
	$head[$h][2] = 'setup';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/shipping.php", 1);
	$head[$h][1] = $langs->trans("Livraison & Taxes");
	$head[$h][2] = 'shipping';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/coupons.php", 1);
	$head[$h][1] = $langs->trans("Coupons");
	$head[$h][2] = 'coupons';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/giftcards.php", 1);
	$head[$h][1] = $langs->trans("Cartes cadeaux");
	$head[$h][2] = 'giftcards';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/blog.php", 1);
	$head[$h][1] = $langs->trans("Blog");
	$head[$h][2] = 'blog';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/news.php", 1);
	$head[$h][1] = $langs->trans("News");
	$head[$h][2] = 'news';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/pages.php", 1);
	$head[$h][1] = $langs->trans("Pages CMS");
	$head[$h][2] = 'pages';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/banners.php", 1);
	$head[$h][1] = $langs->trans("Bannieres");
	$head[$h][2] = 'banners';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/reviews.php", 1);
	$head[$h][1] = $langs->trans("Avis");
	$head[$h][2] = 'reviews';
	$h++;

	$head[$h][0] = dol_buildpath("/spacart/admin/testimonials.php", 1);
	$head[$h][1] = $langs->trans("Temoignages");
	$head[$h][2] = 'testimonials';
	$h++;

	return $head;
}

/**
 * Get SpaCart module base path
 *
 * @return string
 */
function spacartGetBasePath()
{
	return dol_buildpath('/spacart', 0);
}

/**
 * Get SpaCart public URL
 *
 * @return string
 */
function spacartGetPublicUrl()
{
	return dol_buildpath('/spacart/public/', 1);
}

/**
 * Format price for SpaCart display
 *
 * @param float  $amount   Amount
 * @param string $currency Currency code
 * @return string
 */
function spacartFormatPrice($amount, $currency = '')
{
	global $conf;

	if (empty($currency)) {
		$currency = getDolGlobalString('SPACART_CURRENCY_SYMBOL', '€');
	}

	return number_format((float) $amount, 2, ',', ' ').' '.$currency;
}

/**
 * Generate a unique session token
 *
 * @return string
 */
function spacartGenerateToken()
{
	return bin2hex(random_bytes(32));
}

/**
 * Generate a secure password hash
 *
 * @param string $password Plain text password
 * @return string Hashed password
 */
function spacartHashPassword($password)
{
	return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 *
 * @param string $password  Plain text password
 * @param string $hash      Stored hash
 * @return bool
 */
function spacartVerifyPassword($password, $hash)
{
	return password_verify($password, $hash);
}

/**
 * Generate a URL-friendly slug
 *
 * @param string $text Text to slugify
 * @return string
 */
function spacartSlugify($text)
{
	$text = preg_replace('~[^\pL\d]+~u', '-', $text);
	$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	$text = preg_replace('~[^-\w]+~', '', $text);
	$text = trim($text, '-');
	$text = preg_replace('~-+~', '-', $text);
	$text = strtolower($text);

	if (empty($text)) {
		return 'n-a';
	}

	return $text;
}
