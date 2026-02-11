<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       admin/setup.php
 * \ingroup    spacart
 * \brief      SpaCart module configuration page
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dirname(__DIR__).'/lib/spacart.lib.php';

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Load translations
$langs->loadLangs(array("admin", "spacart@spacart"));

// Parameters
$action = GETPOST('action', 'aZ09');

// Actions
if ($action == 'update') {
	$error = 0;

	// General settings
	$res = dolibarr_set_const($db, "SPACART_TITLE", GETPOST('SPACART_TITLE', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_COMPANY_NAME", GETPOST('SPACART_COMPANY_NAME', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_COMPANY_EMAIL", GETPOST('SPACART_COMPANY_EMAIL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_COMPANY_SLOGAN", GETPOST('SPACART_COMPANY_SLOGAN', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_CURRENCY", GETPOST('SPACART_CURRENCY', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_CURRENCY_SYMBOL", GETPOST('SPACART_CURRENCY_SYMBOL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_WEIGHT_SYMBOL", GETPOST('SPACART_WEIGHT_SYMBOL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_PRODUCTS_PER_PAGE", GETPOST('SPACART_PRODUCTS_PER_PAGE', 'int'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_GUEST_CHECKOUT", GETPOST('SPACART_GUEST_CHECKOUT', 'int'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_FREE_SHIPPING_THRESHOLD", GETPOST('SPACART_FREE_SHIPPING_THRESHOLD', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_ABANDONED_CART_DELAY", GETPOST('SPACART_ABANDONED_CART_DELAY', 'int'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_SHOP_CLOSED", GETPOST('SPACART_SHOP_CLOSED', 'int'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;

	// Theme
	$res = dolibarr_set_const($db, "SPACART_THEME_COLOR", GETPOST('SPACART_THEME_COLOR', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_THEME_COLOR_2", GETPOST('SPACART_THEME_COLOR_2', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;

	// Technical user for API operations
	$res = dolibarr_set_const($db, "SPACART_TECHNICAL_USER_ID", GETPOST('SPACART_TECHNICAL_USER_ID', 'int'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;

	// Brand category
	$res = dolibarr_set_const($db, "SPACART_BRAND_CATEGORY_ID", GETPOST('SPACART_BRAND_CATEGORY_ID', 'int'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;

	// Multi-currency
	$res = dolibarr_set_const($db, "SPACART_CURRENCIES", GETPOST('SPACART_CURRENCIES', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;

	// reCAPTCHA & Integrations
	$res = dolibarr_set_const($db, "SPACART_RECAPTCHA_SITE_KEY", GETPOST('SPACART_RECAPTCHA_SITE_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_RECAPTCHA_SECRET_KEY", GETPOST('SPACART_RECAPTCHA_SECRET_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_TAWKTO_ID", GETPOST('SPACART_TAWKTO_ID', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_ANALYTICS_ID", GETPOST('SPACART_ANALYTICS_ID', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
 */

$page_name = "SpaCartSetup";
llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'setup', $langs->trans("SpaCart"), -1, 'spacart@spacart');

// Shop URL
$shopUrl = DOL_URL_ROOT.'/custom/spacart/public/';
print '<div class="info">';
print 'URL de la boutique : <a href="'.$shopUrl.'" target="_blank"><strong>'.$shopUrl.'</strong></a>';
print '</div>';
print '<br>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// ---- General settings ----
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("GeneralSettings").'</td></tr>';

// Shop title
print '<tr class="oddeven"><td>'.$langs->trans("ShopTitle").'</td><td>';
print '<input type="text" name="SPACART_TITLE" value="'.getDolGlobalString('SPACART_TITLE').'" size="60"></td></tr>';

// Company name
print '<tr class="oddeven"><td>'.$langs->trans("CompanyName").'</td><td>';
print '<input type="text" name="SPACART_COMPANY_NAME" value="'.getDolGlobalString('SPACART_COMPANY_NAME').'" size="60"></td></tr>';

// Company email
print '<tr class="oddeven"><td>'.$langs->trans("CompanyEmail").'</td><td>';
print '<input type="text" name="SPACART_COMPANY_EMAIL" value="'.getDolGlobalString('SPACART_COMPANY_EMAIL').'" size="60"></td></tr>';

// Company slogan
print '<tr class="oddeven"><td>'.$langs->trans("CompanySlogan").'</td><td>';
print '<input type="text" name="SPACART_COMPANY_SLOGAN" value="'.getDolGlobalString('SPACART_COMPANY_SLOGAN').'" size="60"></td></tr>';

// Currency
print '<tr class="oddeven"><td>'.$langs->trans("Currency").'</td><td>';
print '<input type="text" name="SPACART_CURRENCY" value="'.getDolGlobalString('SPACART_CURRENCY').'" size="10"></td></tr>';

// Currency symbol
print '<tr class="oddeven"><td>'.$langs->trans("CurrencySymbol").'</td><td>';
print '<input type="text" name="SPACART_CURRENCY_SYMBOL" value="'.getDolGlobalString('SPACART_CURRENCY_SYMBOL').'" size="5"></td></tr>';

// Weight symbol
print '<tr class="oddeven"><td>'.$langs->trans("WeightSymbol").'</td><td>';
print '<input type="text" name="SPACART_WEIGHT_SYMBOL" value="'.getDolGlobalString('SPACART_WEIGHT_SYMBOL').'" size="5"></td></tr>';

// Products per page
print '<tr class="oddeven"><td>'.$langs->trans("ProductsPerPage").'</td><td>';
print '<input type="number" name="SPACART_PRODUCTS_PER_PAGE" value="'.getDolGlobalString('SPACART_PRODUCTS_PER_PAGE').'" min="4" max="100"></td></tr>';

// Guest checkout
print '<tr class="oddeven"><td>'.$langs->trans("GuestCheckout").'</td><td>';
print $form->selectyesno("SPACART_GUEST_CHECKOUT", getDolGlobalString('SPACART_GUEST_CHECKOUT'), 1);
print '</td></tr>';

// Free shipping threshold
print '<tr class="oddeven"><td>'.$langs->trans("FreeShippingThreshold").' (0 = '.$langs->trans("Disabled").')</td><td>';
print '<input type="text" name="SPACART_FREE_SHIPPING_THRESHOLD" value="'.getDolGlobalString('SPACART_FREE_SHIPPING_THRESHOLD').'" size="10"></td></tr>';

// Abandoned cart delay
print '<tr class="oddeven"><td>'.$langs->trans("AbandonedCartDelay").'</td><td>';
print '<input type="number" name="SPACART_ABANDONED_CART_DELAY" value="'.getDolGlobalString('SPACART_ABANDONED_CART_DELAY').'" min="1" max="720"> h</td></tr>';

// Shop closed
print '<tr class="oddeven"><td>'.$langs->trans("ShopClosed").'</td><td>';
print $form->selectyesno("SPACART_SHOP_CLOSED", getDolGlobalString('SPACART_SHOP_CLOSED'), 1);
print '</td></tr>';

print '</table>';
print '<br>';

// ---- Theme ----
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ThemeSettings").'</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("ThemeColor").'</td><td>';
print '<input type="color" name="SPACART_THEME_COLOR" value="'.getDolGlobalString('SPACART_THEME_COLOR').'"></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("ThemeColor2").'</td><td>';
print '<input type="color" name="SPACART_THEME_COLOR_2" value="'.getDolGlobalString('SPACART_THEME_COLOR_2').'"></td></tr>';

print '</table>';
print '<br>';

// ---- Dolibarr Integration ----
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">Integration Dolibarr</td></tr>';

// Technical user
print '<tr class="oddeven"><td>ID utilisateur technique (pour les operations API/cron)</td><td>';
print '<input type="number" name="SPACART_TECHNICAL_USER_ID" value="'.getDolGlobalInt('SPACART_TECHNICAL_USER_ID', 1).'" min="1"></td></tr>';

// Brand category
print '<tr class="oddeven"><td>ID categorie racine des marques</td><td>';
print '<input type="number" name="SPACART_BRAND_CATEGORY_ID" value="'.getDolGlobalInt('SPACART_BRAND_CATEGORY_ID', 0).'" min="0"></td></tr>';

// Multi-currency
print '<tr class="oddeven"><td>Devises supplementaires (ex: USD,GBP)</td><td>';
print '<input type="text" name="SPACART_CURRENCIES" value="'.getDolGlobalString('SPACART_CURRENCIES').'" size="30" placeholder="USD,GBP,CHF"></td></tr>';

print '</table>';
print '<br>';

// ---- Paiement (info) ----
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">Moyens de paiement</td></tr>';

// Stripe status
$stripeEnabled = isModEnabled('stripe');
print '<tr class="oddeven"><td>Stripe</td><td>';
if ($stripeEnabled) {
	$stripeLive = getDolGlobalString('STRIPE_LIVE');
	print '<span class="badge badge-status4">Actif</span> ('.($stripeLive ? 'Production' : 'Test').')';
	print ' - <a href="'.DOL_URL_ROOT.'/stripe/admin/stripe.php">Configurer</a>';
} else {
	print '<span class="badge badge-status8">Inactif</span>';
	print ' - <a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword=stripe">Activer le module</a>';
}
print '</td></tr>';

// PayPal status
$paypalEnabled = isModEnabled('paypal');
print '<tr class="oddeven"><td>PayPal</td><td>';
if ($paypalEnabled) {
	$paypalSandbox = getDolGlobalString('PAYPAL_API_SANDBOX');
	print '<span class="badge badge-status4">Actif</span> ('.($paypalSandbox ? 'Sandbox' : 'Production').')';
	print ' - <a href="'.DOL_URL_ROOT.'/paypal/admin/paypal.php">Configurer</a>';
} else {
	print '<span class="badge badge-status8">Inactif</span>';
	print ' - <a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword=paypal">Activer le module</a>';
}
print '</td></tr>';

print '<tr class="oddeven"><td>Virement bancaire</td><td><span class="badge badge-status4">Toujours disponible</span></td></tr>';
print '<tr class="oddeven"><td>Paiement a la livraison</td><td><span class="badge badge-status4">Toujours disponible</span></td></tr>';

print '</table>';
print '<br>';

// ---- reCAPTCHA & Integrations ----
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">Integrations</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("RecaptchaSiteKey").'</td><td>';
print '<input type="text" name="SPACART_RECAPTCHA_SITE_KEY" value="'.getDolGlobalString('SPACART_RECAPTCHA_SITE_KEY').'" size="60"></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("RecaptchaSecretKey").'</td><td>';
print '<input type="password" name="SPACART_RECAPTCHA_SECRET_KEY" value="'.getDolGlobalString('SPACART_RECAPTCHA_SECRET_KEY').'" size="60"></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("TawkToId").'</td><td>';
print '<input type="text" name="SPACART_TAWKTO_ID" value="'.getDolGlobalString('SPACART_TAWKTO_ID').'" size="60"></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("AnalyticsId").'</td><td>';
print '<input type="text" name="SPACART_ANALYTICS_ID" value="'.getDolGlobalString('SPACART_ANALYTICS_ID').'" size="30"></td></tr>';

print '</table>';
print '<br>';

print '<div class="center">';
print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
