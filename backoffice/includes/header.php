<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/includes/header.php
 * \ingroup    spacart
 * \brief      SpaCart admin backoffice header - sidebar + topbar + flash messages
 *
 * Expected variables before include:
 *   $page_title  (string)  - Page title for <title> and breadcrumb
 *   $current_page (string) - Sidebar active page key (e.g. 'dashboard', 'orders', 'products')
 *   $admin_user  (object)  - Admin user object from spacartAdminCheck()
 */

if (!defined('SPACART_ADMIN')) {
	die('Direct access not allowed');
}

// Defaults
if (empty($page_title)) {
	$page_title = 'Tableau de bord';
}
if (empty($current_page)) {
	$current_page = 'dashboard';
}

// Sidebar navigation structure
$sidebar_menu = array(
	'dashboard' => array(
		'label' => 'Tableau de bord',
		'icon'  => 'bi-speedometer2',
		'url'   => '?page=dashboard',
	),
	'statistics' => array(
		'label' => 'Statistiques',
		'icon'  => 'bi-graph-up',
		'url'   => '?page=statistics',
	),
	'predictions' => array(
		'label' => 'Prédictions IA',
		'icon'  => 'bi-lightning-charge',
		'url'   => '?page=predictions',
	),
	'ventes' => array(
		'label'    => 'Ventes',
		'icon'     => 'bi-box-seam',
		'children' => array(
			'orders'   => array('label' => 'Commandes', 'url' => '?page=orders'),
			'invoices' => array('label' => 'Factures',  'url' => '?page=invoices'),
		),
	),
	'catalogue' => array(
		'label'    => 'Catalogue',
		'icon'     => 'bi-tags',
		'children' => array(
			'products'   => array('label' => 'Produits',    'url' => '?page=products'),
			'categories' => array('label' => 'Categories',  'url' => '?page=categories'),
			'brands'     => array('label' => 'Marques',     'url' => '?page=brands'),
			'reviews'    => array('label' => 'Avis',        'url' => '?page=reviews'),
		),
	),
	'customers' => array(
		'label' => 'Clients',
		'icon'  => 'bi-people',
		'url'   => '?page=customers',
	),
	'contenu' => array(
		'label'    => 'Contenu',
		'icon'     => 'bi-file-earmark-text',
		'children' => array(
			'pages_cms'    => array('label' => 'Pages CMS',    'url' => '?page=pages_cms'),
			'blog'         => array('label' => 'Blog',         'url' => '?page=blog'),
			'news'         => array('label' => 'Actualites',   'url' => '?page=news'),
			'testimonials' => array('label' => 'Temoignages',  'url' => '?page=testimonials'),
			'banners'      => array('label' => 'Bannieres',    'url' => '?page=banners'),
			'homepage'     => array('label' => 'Page d\'accueil', 'url' => '?page=homepage'),
		),
	),
	'marketing' => array(
		'label'    => 'Marketing',
		'icon'     => 'bi-currency-euro',
		'children' => array(
			'coupons'     => array('label' => 'Coupons',        'url' => '?page=coupons'),
			'giftcards'   => array('label' => 'Cartes cadeaux', 'url' => '?page=giftcards'),
			'subscribers' => array('label' => 'Newsletter',     'url' => '?page=subscribers'),
		),
	),
	'livraison' => array(
		'label'    => 'Livraison & Taxes',
		'icon'     => 'bi-truck',
		'children' => array(
			'shipping'  => array('label' => 'Livraison',  'url' => '?page=shipping'),
			'taxes'     => array('label' => 'Taxes',      'url' => '?page=taxes'),
			'countries' => array('label' => 'Pays',       'url' => '?page=countries'),
		),
	),
	'configuration' => array(
		'label'    => 'Configuration',
		'icon'     => 'bi-gear',
		'children' => array(
			'settings'   => array('label' => 'General',    'url' => '?page=settings'),
			'theme'      => array('label' => 'Theme',      'url' => '?page=theme'),
			'languages'  => array('label' => 'Langues',    'url' => '?page=languages'),
			'currencies' => array('label' => 'Devises',    'url' => '?page=currencies'),
			'admin_users'=> array('label' => 'Utilisateurs','url' => '?page=admin_users'),
		),
	),
);

// Determine which collapsible sections should be open
$open_sections = array();
foreach ($sidebar_menu as $key => $item) {
	if (!empty($item['children'])) {
		foreach ($item['children'] as $child_key => $child) {
			if ($child_key === $current_page) {
				$open_sections[$key] = true;
				break;
			}
		}
	}
}

// Admin display name
$admin_display_name = 'Admin';
if (!empty($admin_user)) {
	if (!empty($admin_user->firstname)) {
		$admin_display_name = htmlspecialchars($admin_user->firstname);
		if (!empty($admin_user->lastname)) {
			$admin_display_name .= ' '.htmlspecialchars($admin_user->lastname);
		}
	} elseif (!empty($admin_user->login)) {
		$admin_display_name = htmlspecialchars($admin_user->login);
	} elseif (!empty($admin_user->email)) {
		$admin_display_name = htmlspecialchars($admin_user->email);
	}
}

// Retrieve and clear flash messages
$flash_messages = array();
if (function_exists('spacartAdminGetFlash')) {
	$flash_messages = spacartAdminGetFlash();
} elseif (!empty($_SESSION['spacart_admin_flash'])) {
	$flash_messages = $_SESSION['spacart_admin_flash'];
	unset($_SESSION['spacart_admin_flash']);
}

// Public shop URL
$shop_url = '';
if (defined('SPACART_PUBLIC_URL')) {
	$shop_url = SPACART_PUBLIC_URL;
} elseif (defined('DOL_URL_ROOT')) {
	$shop_url = DOL_URL_ROOT.'/custom/spacart/public/';
} else {
	$shop_url = '../public/';
}

// Generate admin initials for avatar
$admin_initials = 'A';
if (!empty($admin_user)) {
	if (!empty($admin_user->firstname) && !empty($admin_user->lastname)) {
		$admin_initials = mb_strtoupper(mb_substr($admin_user->firstname, 0, 1).mb_substr($admin_user->lastname, 0, 1));
	} elseif (!empty($admin_user->firstname)) {
		$admin_initials = mb_strtoupper(mb_substr($admin_user->firstname, 0, 2));
	} elseif (!empty($admin_user->login)) {
		$admin_initials = mb_strtoupper(mb_substr($admin_user->login, 0, 2));
	} elseif (!empty($admin_user->email)) {
		$admin_initials = mb_strtoupper(mb_substr($admin_user->email, 0, 2));
	}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($page_title); ?> - SpaCart Admin</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
	<link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-body">
<div id="nprogress-bar"></div>

<!-- ============================================================== -->
<!-- SIDEBAR -->
<!-- ============================================================== -->
<aside class="admin-sidebar" id="adminSidebar">

	<!-- Logo -->
	<div class="sidebar-logo">
		<a href="?page=dashboard">
			<span class="logo-icon"><i class="bi bi-cart4"></i></span>
			<span class="logo-text">SpaCart</span>
			<span class="logo-sub">Admin</span>
		</a>
		<button type="button" class="sidebar-close" id="sidebarClose"><i class="bi bi-x-lg"></i></button>
	</div>

	<!-- Navigation -->
	<nav class="sidebar-nav">
		<ul class="sidebar-menu">
		<?php
		// Section label mapping for sidebar groups
		$section_labels = array(
			'dashboard'     => null,
			'statistics'    => null,
			'ventes'        => 'Ventes',
			'catalogue'     => 'Catalogue',
			'customers'     => null,
			'contenu'       => 'Contenu',
			'marketing'     => 'Marketing',
			'livraison'     => 'Livraison',
			'configuration' => 'Configuration',
		);
		$prev_section = null;
		foreach ($sidebar_menu as $key => $item):
			// Insert section label if defined
			if (isset($section_labels[$key]) && $section_labels[$key] !== null && $section_labels[$key] !== $prev_section):
				$prev_section = $section_labels[$key];
		?>
			<li class="sidebar-section-label"><?php echo htmlspecialchars($section_labels[$key]); ?></li>
		<?php endif; ?>
			<?php if (!empty($item['children'])): ?>
				<?php $is_open = !empty($open_sections[$key]); ?>
				<li class="nav-group<?php echo $is_open ? ' open' : ''; ?>">
					<a class="nav-parent<?php echo $is_open ? '' : ' collapsed'; ?>" data-bs-toggle="collapse" href="#nav-<?php echo htmlspecialchars($key); ?>" role="button" aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>">
						<i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i>
						<span><?php echo htmlspecialchars($item['label']); ?></span>
						<i class="bi bi-chevron-down nav-arrow"></i>
					</a>
					<ul class="nav-sub collapse<?php echo $is_open ? ' show' : ''; ?>" id="nav-<?php echo htmlspecialchars($key); ?>">
						<?php foreach ($item['children'] as $child_key => $child): ?>
							<li class="nav-item<?php echo ($child_key === $current_page) ? ' active' : ''; ?>">
								<a href="<?php echo htmlspecialchars($child['url']); ?>"><?php echo htmlspecialchars($child['label']); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</li>
			<?php else: ?>
				<li class="nav-item<?php echo ($key === $current_page) ? ' active' : ''; ?>">
					<a href="<?php echo htmlspecialchars($item['url']); ?>">
						<i class="bi <?php echo htmlspecialchars($item['icon']); ?>"></i>
						<span><?php echo htmlspecialchars($item['label']); ?></span>
					</a>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
		</ul>
	</nav>

	<!-- Sidebar footer -->
	<div class="sidebar-footer">
		<span>SpaCart v2.0</span>
	</div>
</aside>

<!-- Sidebar overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ============================================================== -->
<!-- TOP BAR -->
<!-- ============================================================== -->
<header class="admin-topbar" id="adminTopbar">
	<div class="topbar-left">
		<button type="button" class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb mb-0">
				<li class="breadcrumb-item"><a href="?page=dashboard">Accueil</a></li>
				<li class="breadcrumb-item active"><?php echo htmlspecialchars($page_title); ?></li>
			</ol>
		</nav>
	</div>
	<div class="topbar-right">
		<!-- Global Search (Ctrl+K) -->
		<div class="topbar-search d-none d-md-block" id="topbarSearch" onclick="document.dispatchEvent(new KeyboardEvent('keydown',{key:'k',ctrlKey:true}))">
			<i class="bi bi-search search-icon"></i>
			<input type="text" class="topbar-search-input" placeholder="Rechercher..." readonly aria-label="Recherche globale">
			<kbd>Ctrl+K</kbd>
		</div>
		<!-- Dark mode toggle -->
		<button type="button" class="dark-mode-toggle" id="darkModeToggle" aria-label="Basculer le mode sombre" data-bs-toggle="tooltip" title="Mode sombre">
			<i class="bi bi-moon"></i>
		</button>
		<a href="<?php echo htmlspecialchars($shop_url); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
			<i class="bi bi-shop me-1"></i><span class="d-none d-md-inline">Voir la boutique</span>
		</a>
		<div class="dropdown">
			<button class="admin-dropdown-toggle" data-bs-toggle="dropdown">
				<span class="admin-avatar"><?php echo htmlspecialchars($admin_initials); ?></span>
				<span class="d-none d-md-inline"><?php echo $admin_display_name; ?></span>
				<i class="bi bi-chevron-down" style="font-size:10px;opacity:0.5"></i>
			</button>
			<ul class="dropdown-menu dropdown-menu-end">
				<li><a class="dropdown-item" href="?page=settings"><i class="bi bi-gear me-2"></i>Configuration</a></li>
				<li><hr class="dropdown-divider"></li>
				<li><a class="dropdown-item text-danger" href="?action=logout"><i class="bi bi-box-arrow-right me-2"></i>Deconnexion</a></li>
			</ul>
		</div>
	</div>
</header>

<!-- ============================================================== -->
<!-- MAIN CONTENT AREA -->
<!-- ============================================================== -->
<main class="admin-main" id="adminMain">

	<?php if (!empty($flash_messages)): ?>
		<?php foreach ($flash_messages as $flash): ?>
			<?php
			$flash_type = 'info';
			$flash_text = '';
			$flash_icon = 'bi-info-circle';
			if (is_array($flash)) {
				$flash_type = !empty($flash['type']) ? $flash['type'] : 'info';
				$flash_text = !empty($flash['message']) ? $flash['message'] : '';
			} else {
				$flash_text = (string) $flash;
			}
			// Map type to Bootstrap alert class
			$alert_class = 'alert-info';
			switch ($flash_type) {
				case 'success':
					$alert_class = 'alert-success';
					$flash_icon = 'bi-check-circle';
					break;
				case 'error':
				case 'danger':
					$alert_class = 'alert-danger';
					$flash_icon = 'bi-exclamation-triangle';
					break;
				case 'warning':
					$alert_class = 'alert-warning';
					$flash_icon = 'bi-exclamation-circle';
					break;
				default:
					$alert_class = 'alert-info';
					$flash_icon = 'bi-info-circle';
					break;
			}
			?>
			<div class="alert <?php echo $alert_class; ?> alert-dismissible fade show d-flex align-items-center" role="alert">
				<i class="bi <?php echo $flash_icon; ?> me-2"></i>
				<div><?php echo $flash_text; ?></div>
				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>

	<!-- Page content begins here (closed by footer.php) -->
	<div class="admin-content">
