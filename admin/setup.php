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

	// Reverse proxy / Public URL
	$_old_public_url = getDolGlobalString('SPACART_PUBLIC_URL');
	$res = dolibarr_set_const($db, "SPACART_PUBLIC_URL", GETPOST('SPACART_PUBLIC_URL', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	// Auto-derive SPACART_PUBLIC_DOMAIN from URL
	$_new_public_url = GETPOST('SPACART_PUBLIC_URL', 'alpha');
	if (!empty($_new_public_url)) {
		$_parsed_pub = parse_url($_new_public_url);
		if (!empty($_parsed_pub['host'])) {
			dolibarr_set_const($db, "SPACART_PUBLIC_DOMAIN", $_parsed_pub['host'], 'chaine', 0, '', $conf->entity);
		}
	} else {
		dolibarr_set_const($db, "SPACART_PUBLIC_DOMAIN", '', 'chaine', 0, '', $conf->entity);
	}
	// Clear CSS/template cache when proxy URL changes
	if ($_old_public_url !== $_new_public_url) {
		$_spacart_root = dirname(__DIR__);
		// Delete compiled CSS cache to force recompilation with new paths
		$_css_cache_dir = $_spacart_root . '/var/cache/other/css';
		if (is_dir($_css_cache_dir)) {
			$_css_files = glob($_css_cache_dir . '/*.css');
			if ($_css_files) {
				foreach ($_css_files as $_cf) { @unlink($_cf); }
			}
		}
		@unlink($_spacart_root . '/var/cache/css.css');
		// Reset template timestamps to force recompilation
		$db->query("UPDATE templates SET time = 0 WHERE 1");
	}

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

	// Customer mode
	$res = dolibarr_set_const($db, "SPACART_CUSTOMER_MODE", GETPOST('SPACART_CUSTOMER_MODE', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_GENERIC_CUSTOMER_ID", GETPOST('SPACART_GENERIC_CUSTOMER_ID', 'int'), 'chaine', 0, '', $conf->entity);
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

	// AutoTranslate settings
	$res = dolibarr_set_const($db, "SPACART_AUTOTRANSLATE_ENABLED", GETPOST('SPACART_AUTOTRANSLATE_ENABLED', 'int'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_AUTOTRANSLATE_SOURCE_LANG", GETPOST('SPACART_AUTOTRANSLATE_SOURCE_LANG', 'alpha'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
	$res = dolibarr_set_const($db, "SPACART_AUTOTRANSLATE_EXCLUDE_SELECTORS", GETPOST('SPACART_AUTOTRANSLATE_EXCLUDE_SELECTORS', 'alpha'), 'chaine', 0, '', $conf->entity);
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
$_pub_url_val = getDolGlobalString('SPACART_PUBLIC_URL');
if (!empty($_pub_url_val)) {
	$shopUrl = $_pub_url_val . '/';
} else {
	$shopUrl = DOL_URL_ROOT.'/custom/spacart/';
}
print '<div class="info">';
print 'URL de la boutique : <a href="'.$shopUrl.'" target="_blank"><strong>'.$shopUrl.'</strong></a>';
if (!empty($_pub_url_val)) {
	print '<br><small>URL interne (ERP) : <a href="'.DOL_URL_ROOT.'/custom/spacart/" target="_blank">'.DOL_URL_ROOT.'/custom/spacart/</a></small>';
}
print '</div>';
print '<br>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// ---- Reverse Proxy / Public URL ----
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">URL publique (Reverse Proxy / Domaine d&eacute;di&eacute;)</td></tr>';

print '<tr class="oddeven"><td style="width:35%;">URL publique de la boutique</td><td>';
print '<input type="text" name="SPACART_PUBLIC_URL" value="'.dol_escape_htmltag(getDolGlobalString('SPACART_PUBLIC_URL')).'" size="60" placeholder="https://shop.coexdis.com">';
print '<br><small class="opacitymedium">Si configur&eacute;, toutes les URLs seront r&eacute;&eacute;crites vers cette adresse publique.<br>Laissez vide pour utiliser le chemin par d&eacute;faut (<code>/custom/spacart</code>).</small>';
print '</td></tr>';

// ---- Dynamic help section ----
$_pub_url = getDolGlobalString('SPACART_PUBLIC_URL');
$_pub_domain = getDolGlobalString('SPACART_PUBLIC_DOMAIN');
$_erp_url = DOL_MAIN_URL_ROOT; // e.g. https://erp.coexdis.com
$_erp_host = parse_url($_erp_url, PHP_URL_HOST); // e.g. erp.coexdis.com

if (!empty($_pub_url)) {
	$_parsed = parse_url($_pub_url);
	$_pub_scheme = isset($_parsed['scheme']) ? $_parsed['scheme'] : 'https';
	$_pub_host = isset($_parsed['host']) ? $_parsed['host'] : '';
	$_pub_path = isset($_parsed['path']) ? rtrim($_parsed['path'], '/') : '';
	$_pub_port = isset($_parsed['port']) ? $_parsed['port'] : ($_pub_scheme === 'https' ? 443 : 80);

	// Detect mode: subdomain/dedicated domain vs subdirectory
	$_is_subdirectory = (!empty($_pub_path) && $_pub_host === $_erp_host);
	$_is_subdomain = (!$_is_subdirectory);

	print '<tr class="oddeven"><td>Domaine public (auto-d&eacute;riv&eacute;)</td><td>';
	print '<code>'.dol_escape_htmltag($_pub_domain).'</code>';
	print '</td></tr>';

	// --- Configuration help ---
	print '<tr class="oddeven"><td>Configuration serveur web</td><td>';

	$_pre_style = 'style="background:#1e1e2e;color:#cdd6f4;padding:12px 15px;border-radius:6px;font-size:12px;line-height:1.5;overflow-x:auto;white-space:pre;font-family:Consolas,Monaco,monospace;border:1px solid #45475a;"';

	if ($_is_subdomain) {
		// ============================
		// CAS 1 : Sous-domaine ou domaine dédié
		// ============================
		$_ssl_cert = '/etc/letsencrypt/live/'.dol_escape_htmltag($_pub_host).'/fullchain.pem';
		$_ssl_key  = '/etc/letsencrypt/live/'.dol_escape_htmltag($_pub_host).'/privkey.pem';

		$_nginx_conf = 'server {
    listen 443 ssl http2;
    server_name '.dol_escape_htmltag($_pub_host).';

    ssl_certificate     '.$_ssl_cert.';
    ssl_certificate_key '.$_ssl_key.';

    location / {
        proxy_pass '.dol_escape_htmltag($_erp_url).'/custom/spacart/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # R&eacute;&eacute;criture optionnelle des r&eacute;ponses HTML
        sub_filter \'/custom/spacart\' \'\';
        sub_filter_once off;
        sub_filter_types text/html text/css application/javascript;
    }
}

# Redirection HTTP -&gt; HTTPS
server {
    listen 80;
    server_name '.dol_escape_htmltag($_pub_host).';
    return 301 https://$host$request_uri;
}';

		$_apache_conf = '&lt;VirtualHost *:443&gt;
    ServerName '.dol_escape_htmltag($_pub_host).'

    SSLEngine on
    SSLCertificateFile    '.$_ssl_cert.'
    SSLCertificateKeyFile '.$_ssl_key.'

    ProxyPreserveHost On
    ProxyPass        / '.dol_escape_htmltag($_erp_url).'/custom/spacart/
    ProxyPassReverse / '.dol_escape_htmltag($_erp_url).'/custom/spacart/

    RequestHeader set X-Forwarded-Proto "https"
&lt;/VirtualHost&gt;

# Redirection HTTP -&gt; HTTPS
&lt;VirtualHost *:80&gt;
    ServerName '.dol_escape_htmltag($_pub_host).'
    Redirect permanent / https://'.dol_escape_htmltag($_pub_host).'/
&lt;/VirtualHost&gt;';

		print '<div style="margin-bottom:8px;">';
		print '<span class="badge badge-info">&nbsp;'.dol_escape_htmltag($_pub_host).'&nbsp;</span> ';
		if ($_pub_host !== $_erp_host) {
			print 'Sous-domaine / domaine d&eacute;di&eacute; d&eacute;tect&eacute;';
		}
		print '</div>';

		print '<details style="margin-bottom:8px;"><summary style="cursor:pointer;font-weight:600;color:#0068b4;">&#9654; Configuration Nginx <small>(copier-coller)</small></summary>';
		print '<pre '.$_pre_style.'>'.$_nginx_conf.'</pre>';
		print '<small class="opacitymedium">Fichier : <code>/etc/nginx/sites-available/'.dol_escape_htmltag($_pub_host).'.conf</code></small>';
		print '</details>';

		print '<details style="margin-bottom:8px;"><summary style="cursor:pointer;font-weight:600;color:#0068b4;">&#9654; Configuration Apache <small>(copier-coller)</small></summary>';
		print '<pre '.$_pre_style.'>'.$_apache_conf.'</pre>';
		print '<small class="opacitymedium">N&eacute;cessite : <code>mod_proxy</code>, <code>mod_proxy_http</code>, <code>mod_ssl</code>, <code>mod_headers</code></small>';
		print '</details>';

	} else {
		// ============================
		// CAS 2 : Sous-répertoire sur même domaine
		// ============================
		$_nginx_conf = '# Dans le bloc server {} existant de '.dol_escape_htmltag($_erp_host).'
location '.dol_escape_htmltag($_pub_path).'/ {
    alias /var/www/vhosts/coexdis.com/erp/htdocs/custom/spacart/;

    # Ou via proxy interne :
    # proxy_pass '.dol_escape_htmltag($_erp_url).'/custom/spacart/;
    # proxy_set_header Host $host;
    # proxy_set_header X-Real-IP $remote_addr;
    # proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    # proxy_set_header X-Forwarded-Proto $scheme;

    # R&eacute;&eacute;criture
    sub_filter \'/custom/spacart\' \''.dol_escape_htmltag($_pub_path).'\';
    sub_filter_once off;
    sub_filter_types text/html text/css application/javascript;
}';

		$_apache_conf = '# Dans le VirtualHost existant de '.dol_escape_htmltag($_erp_host).'
Alias '.dol_escape_htmltag($_pub_path).' /var/www/vhosts/coexdis.com/erp/htdocs/custom/spacart

# Ou via proxy interne :
# ProxyPass        '.dol_escape_htmltag($_pub_path).'/ '.dol_escape_htmltag($_erp_url).'/custom/spacart/
# ProxyPassReverse '.dol_escape_htmltag($_pub_path).'/ '.dol_escape_htmltag($_erp_url).'/custom/spacart/';

		print '<div style="margin-bottom:8px;">';
		print '<span class="badge badge-info">&nbsp;'.dol_escape_htmltag($_pub_path).'&nbsp;</span> ';
		print 'Sous-r&eacute;pertoire d&eacute;tect&eacute; sur le m&ecirc;me domaine';
		print '</div>';

		print '<details style="margin-bottom:8px;"><summary style="cursor:pointer;font-weight:600;color:#0068b4;">&#9654; Configuration Nginx <small>(copier-coller)</small></summary>';
		print '<pre '.$_pre_style.'>'.$_nginx_conf.'</pre>';
		print '</details>';

		print '<details style="margin-bottom:8px;"><summary style="cursor:pointer;font-weight:600;color:#0068b4;">&#9654; Configuration Apache <small>(copier-coller)</small></summary>';
		print '<pre '.$_pre_style.'>'.$_apache_conf.'</pre>';
		print '</details>';
	}

	// ---- Notes communes ----
	print '<details style="margin-bottom:4px;"><summary style="cursor:pointer;font-weight:600;color:#555;">&#9654; Notes importantes</summary>';
	print '<ul style="margin:8px 0;padding-left:20px;font-size:12px;line-height:1.6;">';
	print '<li><strong>SSL :</strong> Cr&eacute;er le certificat : <code>certbot certonly --nginx -d '.dol_escape_htmltag($_pub_host).'</code></li>';
	print '<li><strong>sub_filter</strong> est optionnel : SpaCart r&eacute;&eacute;crit d&eacute;j&agrave; les URLs c&ocirc;t&eacute; PHP. Le sub_filter est une couche de s&eacute;curit&eacute; suppl&eacute;mentaire.</li>';
	if ($_pub_host !== $_erp_host) {
		print '<li><strong>DNS :</strong> Ajouter un enregistrement <code>A</code> ou <code>CNAME</code> pour <code>'.dol_escape_htmltag($_pub_host).'</code> pointant vers le serveur ERP.</li>';
	}
	print '<li><strong>V&eacute;rification :</strong> <code>curl -sI '.dol_escape_htmltag($_pub_url).'/ | head -5</code></li>';
	print '<li><strong>D&eacute;sactivation :</strong> Videz le champ ci-dessus et enregistrez pour revenir au chemin standard <code>/custom/spacart</code>.</li>';
	print '</ul>';
	print '</details>';

	print '</td></tr>';

} else {
	// No URL configured — just show a hint
	print '<tr class="oddeven"><td colspan="2">';
	print '<small class="opacitymedium">&#128712; Configurez une URL publique ci-dessus pour obtenir les instructions de configuration Nginx / Apache pr&ecirc;tes &agrave; copier-coller.</small>';
	print '</td></tr>';
}

print '</table>';
print '<br>';

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

// ---- Customer Mode (Phase 3) ----
$customer_mode = getDolGlobalString('SPACART_CUSTOMER_MODE', 'individual');
$generic_id = getDolGlobalInt('SPACART_GENERIC_CUSTOMER_ID', 0);

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">Mode client e-commerce</td></tr>';

print '<tr class="oddeven"><td style="width:35%;">Mode de creation des clients</td><td>';
print '<select name="SPACART_CUSTOMER_MODE" class="flat minwidth200" id="spacart_customer_mode" onchange="document.getElementById(\'generic_row\').style.display=(this.value==\'generic\'?\'\':\'none\');">';
print '<option value="individual"'.($customer_mode == 'individual' ? ' selected' : '').'>Individuel &mdash; 1 tiers par client web</option>';
print '<option value="generic"'.($customer_mode == 'generic' ? ' selected' : '').'>G&eacute;n&eacute;rique &mdash; 1 tiers commun + contacts</option>';
print '</select>';
print '<br><small class="opacitymedium"><strong>Individuel</strong> : chaque inscription cr&eacute;e un tiers (soci&eacute;t&eacute;) dans Dolibarr, avec la cat&eacute;gorie "E-commerce SpaCart".<br>';
print '<strong>G&eacute;n&eacute;rique</strong> : un seul tiers "CLIENTS WEB SPACART" est cr&eacute;&eacute;, chaque client devient un contact rattach&eacute;.</small>';
print '</td></tr>';

print '<tr class="oddeven" id="generic_row" style="'.($customer_mode != 'generic' ? 'display:none;' : '').'">';
print '<td>ID du tiers g&eacute;n&eacute;rique (llx_societe.rowid)</td><td>';
print '<input type="number" name="SPACART_GENERIC_CUSTOMER_ID" value="'.$generic_id.'" min="0">';
print '<br><small class="opacitymedium">Laisser 0 pour cr&eacute;er automatiquement le tiers "CLIENTS WEB SPACART" lors de la premi&egrave;re inscription.</small>';
print '</td></tr>';

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
$recaptcha_site_key = getDolGlobalString('SPACART_RECAPTCHA_SITE_KEY');
$recaptcha_secret_key = getDolGlobalString('SPACART_RECAPTCHA_SECRET_KEY');
$recaptcha_configured = (!empty($recaptcha_site_key) && !empty($recaptcha_secret_key));

// Test reCAPTCHA keys if requested
$recaptcha_test_result = '';
if ($action == 'test_recaptcha' && $recaptcha_configured) {
	// Validate site key format (should be a valid reCAPTCHA key)
	if (strlen($recaptcha_site_key) < 20) {
		$recaptcha_test_result = '<span style="color:#c0392b;"><strong>&#10008;</strong> La cl&eacute; site semble invalide (trop courte).</span>';
	} else {
		// Test secret key by sending a dummy verification request to Google
		$verify_url = 'https://www.google.com/recaptcha/api/siteverify';
		$verify_data = array('secret' => $recaptcha_secret_key, 'response' => 'test_verification');
		$options = array(
			'http' => array(
				'header' => "Content-type: application/x-www-form-urlencoded\r\n",
				'method' => 'POST',
				'content' => http_build_query($verify_data),
				'timeout' => 10
			)
		);
		$context = stream_context_create($options);
		$result = @file_get_contents($verify_url, false, $context);
		if ($result === false) {
			$recaptcha_test_result = '<span style="color:#e67e22;"><strong>&#9888;</strong> Impossible de contacter Google (v&eacute;rifiez la connexion internet du serveur).</span>';
		} else {
			$json = json_decode($result, true);
			if (isset($json['error-codes']) && in_array('invalid-input-secret', $json['error-codes'])) {
				$recaptcha_test_result = '<span style="color:#c0392b;"><strong>&#10008;</strong> La cl&eacute; secr&egrave;te est <strong>invalide</strong>. V&eacute;rifiez-la dans la console Google reCAPTCHA.</span>';
			} else {
				// If we get timeout-or-duplicate or missing-input-response (expected with dummy token), secret key is valid
				$recaptcha_test_result = '<span style="color:#27ae60;"><strong>&#10004;</strong> Cl&eacute; secr&egrave;te <strong>valid&eacute;e</strong> par Google. Le reCAPTCHA est op&eacute;rationnel.</span>';
			}
		}
	}
}

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">reCAPTCHA v2 &mdash; Protection anti-spam';
if ($recaptcha_configured) {
	print ' <span class="badge badge-status4" style="margin-left:8px;" title="reCAPTCHA actif sur 6 formulaires">Actif</span>';
} else {
	print ' <span class="badge badge-status8" style="margin-left:8px;" title="reCAPTCHA d&eacute;sactiv&eacute;">Inactif</span>';
}
print '</td></tr>';

// Status and info row
print '<tr class="oddeven"><td colspan="2" style="padding:8px 12px;">';
if ($recaptcha_configured) {
	print '<span style="color:#27ae60;">&#10004; <strong>Configur&eacute;</strong></span> &mdash; Protection active sur : Inscription, Avis produit, Blog, Contact, Tickets support, T&eacute;moignages';
} else {
	print '<span style="color:#e74c3c;">&#10008; <strong>Non configur&eacute;</strong></span> &mdash; ';
	print 'Cr&eacute;ez vos cl&eacute;s sur <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">Google reCAPTCHA Admin</a>';
	print '<br><small>Type : <strong>reCAPTCHA v2</strong> (case &agrave; cocher "Je ne suis pas un robot") &mdash; Domaine : <strong>erp.coexdis.com</strong></small>';
}
print '</td></tr>';

// Test result row (if test was performed)
if (!empty($recaptcha_test_result)) {
	print '<tr class="oddeven"><td colspan="2" style="padding:8px 12px;background:#f9f9f9;">';
	print '<strong>R&eacute;sultat du test :</strong> '.$recaptcha_test_result;
	print '</td></tr>';
}

print '<tr class="oddeven"><td style="width:35%;">Cl&eacute; site (Site Key)</td><td>';
print '<input type="text" name="SPACART_RECAPTCHA_SITE_KEY" value="'.dol_escape_htmltag($recaptcha_site_key).'" size="60" placeholder="6Le..."></td></tr>';

print '<tr class="oddeven"><td>Cl&eacute; secr&egrave;te (Secret Key)</td><td>';
print '<input type="password" name="SPACART_RECAPTCHA_SECRET_KEY" value="'.dol_escape_htmltag($recaptcha_secret_key).'" size="60" placeholder="6Le...">';
if ($recaptcha_configured) {
	print ' &nbsp; <a class="button button-info smallpaddingimp" href="'.$_SERVER["PHP_SELF"].'?action=test_recaptcha&token='.newToken().'" title="Tester la validit&eacute; des cl&eacute;s">&#128269; Tester</a>';
}
print '</td></tr>';

print '</table>';

print '<br>';

// ---- Other Integrations ----
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">Autres int&eacute;grations</td></tr>';

print '<tr class="oddeven"><td style="width:35%;">Tawk.to &mdash; Chat en direct (Site ID)</td><td>';
print '<input type="text" name="SPACART_TAWKTO_ID" value="'.getDolGlobalString('SPACART_TAWKTO_ID').'" size="60" placeholder="Ex: 5a1b2c3d4e5f6..."></td></tr>';

print '<tr class="oddeven"><td>Google Analytics (ID de mesure)</td><td>';
print '<input type="text" name="SPACART_ANALYTICS_ID" value="'.getDolGlobalString('SPACART_ANALYTICS_ID').'" size="30" placeholder="G-XXXXXXXXXX"></td></tr>';

print '</table>';
print '<br>';


// -------- AutoTranslate - Traduction automatique --------
$at_enabled = getDolGlobalString('SPACART_AUTOTRANSLATE_ENABLED', '0');
$at_source_lang = getDolGlobalString('SPACART_AUTOTRANSLATE_SOURCE_LANG', 'French');
$at_exclude = getDolGlobalString('SPACART_AUTOTRANSLATE_EXCLUDE_SELECTORS', '.notranslate, .price, .ef-price, #cart-total, .currency_select');

print '<br>';
print load_fiche_titre('Traduction automatique (AutoTranslate)', '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td style="width:35%;">Param&egrave;tre</td><td>Valeur</td></tr>';

// Status
$at_badge = $at_enabled ? '<span class="badge badge-status4">Actif</span>' : '<span class="badge badge-status8">Inactif</span>';
print '<tr class="oddeven"><td>Activer la traduction automatique du contenu '.$at_badge.'</td><td>';
print '<select name="SPACART_AUTOTRANSLATE_ENABLED" class="flat minwidth100">';
print '<option value="0"'.($at_enabled ? '' : ' selected').'>Non</option>';
print '<option value="1"'.($at_enabled ? ' selected' : '').'>Oui</option>';
print '</select>';
print '<br><small class="opacitymedium">Traduit automatiquement le contenu dynamique (produits, actualit&eacute;s, pages CMS) quand le visiteur change de langue. Utilise Google Translate via un proxy PHP avec cache en base de données pour des performances optimales.</small>';
print '</td></tr>';

// Source language
$lang_options = array('French' => 'Fran&ccedil;ais (French)', 'English' => 'English', 'German' => 'Deutsch (German)', 'Russian' => 'Russian');
print '<tr class="oddeven"><td>Langue source du contenu</td><td>';
print '<select name="SPACART_AUTOTRANSLATE_SOURCE_LANG" class="flat minwidth200">';
foreach ($lang_options as $code => $label) {
    $sel = ($at_source_lang == $code) ? ' selected' : '';
    print '<option value="'.$code.'"'.$sel.'>'.$label.'</option>';
}
print '</select>';
print '<br><small class="opacitymedium">Langue dans laquelle votre contenu est r&eacute;dig&eacute; (produits, news, pages). Le contenu sera traduit <b>depuis</b> cette langue.</small>';
print '</td></tr>';

// Exclude selectors
print '<tr class="oddeven"><td>S&eacute;lecteurs CSS &agrave; exclure</td><td>';
print '<input type="text" name="SPACART_AUTOTRANSLATE_EXCLUDE_SELECTORS" class="flat minwidth500" value="'.dol_escape_htmltag($at_exclude).'">';
print '<br><small class="opacitymedium">&Eacute;l&eacute;ments &agrave; ne pas traduire (s&eacute;par&eacute;s par des virgules). Ex: .price, #cart-total, .notranslate</small>';
print '</td></tr>';

// Info row - available languages
print '<tr class="oddeven"><td>Langues disponibles</td><td>';
$lang_list_sql = "SELECT code, name FROM languages_codes WHERE active = 1 ORDER BY orderby";
$result_langs = $db->query($lang_list_sql);
if ($result_langs) {
    $langs_arr = array();
    while ($obj = $db->fetch_object($result_langs)) {
        $flag_icon = '<img src="/custom/spacart/images/flags/'.$obj->code.'.png" style="height:14px;margin-right:4px;" alt="'.$obj->code.'">';
        $is_source = (strtolower(substr($at_source_lang, 0, 2)) == $obj->code) ? ' <span class="badge badge-status1">source</span>' : '';
        $langs_arr[] = $flag_icon.$obj->name.$is_source;
    }
    print implode(' &nbsp; | &nbsp; ', $langs_arr);
} else {
    print '<span class="opacitymedium">Impossible de lire la table languages_codes</span>';
}
print '<br><small class="opacitymedium">G&eacute;rez les langues dans Administration SpaCart > Languages. Le visiteur choisit sa langue via le s&eacute;lecteur en haut du site.</small>';
print '</td></tr>';

// How it works
print '<tr class="oddeven"><td>Fonctionnement</td><td>';
print '<small class="opacitymedium">';
print '1. Le visiteur clique sur un drapeau de langue<br>';
print '2. Les strings UI statiques sont charg&eacute;es depuis la base de donn&eacute;es<br>';
print '3. Le contenu dynamique (noms produits, descriptions, actualit&eacute;s, pages CMS) est traduit automatiquement c&ocirc;t&eacute; navigateur<br>';
print '4. Les traductions sont mises en cache en base de données (table spacart_translation_cache) pour des performances optimales<br>';
print '5. Les prix, devises et s&eacute;lecteurs exclus ne sont jamais traduits';
print '</small>';
print '</td></tr>';

print '</table>';

print '<div class="center">';
print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
