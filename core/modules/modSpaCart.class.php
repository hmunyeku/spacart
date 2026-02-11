<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \defgroup   spacart     Module SpaCart
 * \brief      Module de vente en ligne SPA pour Dolibarr
 * \file       htdocs/custom/spacart/core/modules/modSpaCart.class.php
 * \ingroup    spacart
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Class modSpaCart
 * Description and activation file for the module SpaCart
 */
class modSpaCart extends DolibarrModules
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Module ID (unique)
		$this->numero = 500100;

		// Family
		$this->family = "portal";
		$this->familyinfo = array(
			'portal' => array(
				'position' => '100',
				'label' => 'Portal'
			)
		);

		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Module de vente en ligne SPA integre a Dolibarr";
		$this->descriptionlong = "Boutique en ligne complete de type Single Page Application (SPA) inspiree de SpaCart, integree nativement avec les produits, commandes et tiers Dolibarr.";
		$this->editor_name = 'CoexDis';
		$this->editor_url = 'https://coexdis.com';
		$this->version = '1.0.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'spacart@spacart';

		// Module parts
		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array(),
			'moduleforexternal' => 0,
		);

		// Directories to create
		$this->dirs = array(
			"/spacart/temp",
			"/spacart/photos",
			"/spacart/photos/products",
			"/spacart/photos/categories",
			"/spacart/photos/brands",
			"/spacart/photos/banners",
			"/spacart/photos/blog",
			"/spacart/photos/news",
			"/spacart/cache",
		);

		// Config pages
		$this->config_page_url = array("setup.php@spacart");

		// Dependencies
		$this->depends = array('modProduct', 'modCommande', 'modSociete');
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('spacart@spacart');

		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "SPACART_ENABLED";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "Enable SpaCart online shop";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_TITLE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "Boutique en ligne";
		$this->const[$r][3] = "Shop title";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_CURRENCY";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "EUR";
		$this->const[$r][3] = "Default currency";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_CURRENCY_SYMBOL";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "€";
		$this->const[$r][3] = "Currency symbol";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_PRODUCTS_PER_PAGE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "12";
		$this->const[$r][3] = "Products per page";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_GUEST_CHECKOUT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "Allow guest checkout";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_THEME_COLOR";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "#2196F3";
		$this->const[$r][3] = "Theme primary color";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_THEME_COLOR_2";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "#1976D2";
		$this->const[$r][3] = "Theme secondary color";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_STRIPE_PUBLISHABLE_KEY";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Stripe publishable key";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_STRIPE_SECRET_KEY";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Stripe secret key";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_STRIPE_WEBHOOK_SECRET";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Stripe webhook secret";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_PAYPAL_CLIENT_ID";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "PayPal client ID";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_PAYPAL_SECRET";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "PayPal secret";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_PAYPAL_SANDBOX";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "PayPal sandbox mode";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_BRAINTREE_MERCHANT_ID";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Braintree merchant ID";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_BRAINTREE_PUBLIC_KEY";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Braintree public key";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_BRAINTREE_PRIVATE_KEY";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Braintree private key";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_BRAINTREE_SANDBOX";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "Braintree sandbox mode";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_COMPANY_NAME";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "CoexDis";
		$this->const[$r][3] = "Company name for shop";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_COMPANY_EMAIL";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Company email for shop";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_COMPANY_SLOGAN";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Company slogan";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_WEIGHT_SYMBOL";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "kg";
		$this->const[$r][3] = "Weight unit symbol";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_FREE_SHIPPING_THRESHOLD";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = "Free shipping above this amount (0=disabled)";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_ABANDONED_CART_DELAY";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "24";
		$this->const[$r][3] = "Hours before sending abandoned cart reminder";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_SHOP_CLOSED";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = "Shop closed (1=yes)";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_RECAPTCHA_SITE_KEY";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "reCAPTCHA site key";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_RECAPTCHA_SECRET_KEY";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "reCAPTCHA secret key";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_TAWKTO_ID";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Tawk.to widget ID for live chat";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SPACART_ANALYTICS_ID";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "Google Analytics ID";
		$this->const[$r][4] = 0;
		$r++;

		// Permissions
		$this->rights = array();
		$this->rights_class = 'spacart';

		$r = 0;

		$r++;
		$this->rights[$r][0] = 500101;
		$this->rights[$r][1] = 'Read SpaCart configuration and orders';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = 500102;
		$this->rights[$r][1] = 'Manage SpaCart configuration';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'admin';

		$r++;
		$this->rights[$r][0] = 500103;
		$this->rights[$r][1] = 'Manage SpaCart content (blog, news, pages)';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'content';

		$r++;
		$this->rights[$r][0] = 500104;
		$this->rights[$r][1] = 'Manage SpaCart products (featured, variants, options)';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'products';

		$r++;
		$this->rights[$r][0] = 500105;
		$this->rights[$r][1] = 'Manage SpaCart coupons and gift cards';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'promotions';

		// Menus
		$this->menu = array();
		$r = 0;

		// Top menu
		$this->menu[$r] = array(
			'fk_menu' => '',
			'type' => 'top',
			'titre' => 'SpaCart',
			'prefix' => img_picto('', 'spacart@spacart', 'class="paddingright pictofixedwidth"'),
			'mainmenu' => 'spacart',
			'leftmenu' => '',
			'url' => '/spacart/admin/dashboard.php',
			'langs' => 'spacart@spacart',
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("spacart")',
			'perms' => '$user->hasRight("spacart", "read")',
			'target' => '',
			'user' => 2,
		);
		$r++;

		// Left menu - Dashboard
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=spacart',
			'type' => 'left',
			'titre' => 'Dashboard',
			'mainmenu' => 'spacart',
			'leftmenu' => 'spacart_dashboard',
			'url' => '/spacart/admin/dashboard.php',
			'langs' => 'spacart@spacart',
			'position' => 100,
			'enabled' => 'isModEnabled("spacart")',
			'perms' => '$user->hasRight("spacart", "read")',
			'target' => '',
			'user' => 2,
		);
		$r++;

		// Left menu - Configuration
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=spacart',
			'type' => 'left',
			'titre' => 'Configuration',
			'mainmenu' => 'spacart',
			'leftmenu' => 'spacart_setup',
			'url' => '/spacart/admin/setup.php',
			'langs' => 'spacart@spacart',
			'position' => 200,
			'enabled' => 'isModEnabled("spacart")',
			'perms' => '$user->hasRight("spacart", "admin")',
			'target' => '',
			'user' => 2,
		);
		$r++;

		// Left menu - Shipping
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=spacart',
			'type' => 'left',
			'titre' => 'Livraison & Taxes',
			'mainmenu' => 'spacart',
			'leftmenu' => 'spacart_shipping',
			'url' => '/spacart/admin/shipping.php',
			'langs' => 'spacart@spacart',
			'position' => 300,
			'enabled' => 'isModEnabled("spacart")',
			'perms' => '$user->hasRight("spacart", "admin")',
			'target' => '',
			'user' => 2,
		);
		$r++;

		// Left menu - Coupons
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=spacart',
			'type' => 'left',
			'titre' => 'Coupons & Cartes cadeaux',
			'mainmenu' => 'spacart',
			'leftmenu' => 'spacart_coupons',
			'url' => '/spacart/admin/coupons.php',
			'langs' => 'spacart@spacart',
			'position' => 400,
			'enabled' => 'isModEnabled("spacart")',
			'perms' => '$user->hasRight("spacart", "promotions")',
			'target' => '',
			'user' => 2,
		);
		$r++;

		// Left menu - Blog
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=spacart',
			'type' => 'left',
			'titre' => 'Blog & News',
			'mainmenu' => 'spacart',
			'leftmenu' => 'spacart_blog',
			'url' => '/spacart/admin/blog.php',
			'langs' => 'spacart@spacart',
			'position' => 500,
			'enabled' => 'isModEnabled("spacart")',
			'perms' => '$user->hasRight("spacart", "content")',
			'target' => '',
			'user' => 2,
		);
		$r++;

		// Left menu - Pages CMS
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=spacart',
			'type' => 'left',
			'titre' => 'Pages & Bannieres',
			'mainmenu' => 'spacart',
			'leftmenu' => 'spacart_pages',
			'url' => '/spacart/admin/pages.php',
			'langs' => 'spacart@spacart',
			'position' => 600,
			'enabled' => 'isModEnabled("spacart")',
			'perms' => '$user->hasRight("spacart", "content")',
			'target' => '',
			'user' => 2,
		);
		$r++;

		// Left menu - Reviews
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=spacart',
			'type' => 'left',
			'titre' => 'Avis & Temoignages',
			'mainmenu' => 'spacart',
			'leftmenu' => 'spacart_reviews',
			'url' => '/spacart/admin/reviews.php',
			'langs' => 'spacart@spacart',
			'position' => 700,
			'enabled' => 'isModEnabled("spacart")',
			'perms' => '$user->hasRight("spacart", "content")',
			'target' => '',
			'user' => 2,
		);
		$r++;

		// Left menu - View Shop
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=spacart',
			'type' => 'left',
			'titre' => 'Voir la boutique',
			'mainmenu' => 'spacart',
			'leftmenu' => 'spacart_viewshop',
			'url' => '/spacart/public/index.php',
			'langs' => 'spacart@spacart',
			'position' => 900,
			'enabled' => 'isModEnabled("spacart")',
			'perms' => '$user->hasRight("spacart", "read")',
			'target' => '_blank',
			'user' => 2,
		);
		$r++;

		// Cron jobs
		$this->cronjobs = array();

		$this->cronjobs[0] = array(
			'label' => 'SpaCart - Paniers abandonnes',
			'jobtype' => 'command',
			'command' => 'php '.DOL_DOCUMENT_ROOT.'/custom/spacart/cron/abandoned_carts.php',
			'classesname' => '',
			'methodename' => '',
			'parameters' => '',
			'comment' => 'Envoie des rappels pour les paniers abandonnes',
			'frequency' => 1,
			'unitfrequency' => 3600,
			'priority' => 50,
			'status' => 0,
			'test' => 'isModEnabled("spacart")',
			'datestart' => null,
			'dateend' => null,
		);

		$this->cronjobs[1] = array(
			'label' => 'SpaCart - Verification paiements Stripe',
			'jobtype' => 'command',
			'command' => 'php '.DOL_DOCUMENT_ROOT.'/custom/spacart/cron/check_stripe.php',
			'classesname' => '',
			'methodename' => '',
			'parameters' => '',
			'comment' => 'Verifie les paiements Stripe en attente',
			'frequency' => 30,
			'unitfrequency' => 60,
			'priority' => 50,
			'status' => 0,
			'test' => 'isModEnabled("spacart")',
			'datestart' => null,
			'dateend' => null,
		);

		// SQL tables
		$this->tables = array();
	}

	/**
	 * Function called when module is enabled
	 *
	 * @param string $options Options when enabling module
	 * @return int 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$result = $this->_load_tables('/spacart/sql/');
		if ($result < 0) {
			return -1;
		}

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled
	 *
	 * @param string $options Options when disabling module
	 * @return int 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
