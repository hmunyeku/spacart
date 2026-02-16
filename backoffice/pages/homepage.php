<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/homepage.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Homepage sections ordering and visibility
 *
 * Manages which sections appear on the frontend homepage and in what order.
 * Config is stored in llx_const (name/value per entity) using SPACART_ prefix keys.
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Page d\'accueil';
$current_page = 'homepage';

// -------------------------------------------------------------------
// Section definitions
// Each section has: key, label, icon, has_limit (boolean)
// -------------------------------------------------------------------
$homepage_sections = array(
	'SPACART_HOME_BANNERS' => array(
		'label'     => 'Banniere slider',
		'icon'      => 'bi-images',
		'has_limit' => false,
	),
	'SPACART_HOME_USP' => array(
		'label'     => 'Barre USP (avantages)',
		'icon'      => 'bi-award',
		'has_limit' => false,
	),
	'SPACART_HOME_FEATURED' => array(
		'label'     => 'Produits en vedette',
		'icon'      => 'bi-star',
		'has_limit' => true,
	),
	'SPACART_HOME_BESTSELLERS' => array(
		'label'     => 'Meilleures ventes',
		'icon'      => 'bi-graph-up-arrow',
		'has_limit' => true,
	),
	'SPACART_HOME_MOSTVIEWED' => array(
		'label'     => 'Les plus consultes',
		'icon'      => 'bi-eye',
		'has_limit' => true,
	),
	'SPACART_HOME_NEWARRIVALS' => array(
		'label'     => 'Nouveautes',
		'icon'      => 'bi-box-seam',
		'has_limit' => true,
	),
	'SPACART_HOME_CATEGORIES' => array(
		'label'     => 'Categories',
		'icon'      => 'bi-grid',
		'has_limit' => false,
	),
	'SPACART_HOME_TESTIMONIALS' => array(
		'label'     => 'Temoignages',
		'icon'      => 'bi-chat-quote',
		'has_limit' => false,
	),
	'SPACART_HOME_NEWS' => array(
		'label'     => 'Dernieres actualites',
		'icon'      => 'bi-newspaper',
		'has_limit' => true,
	),
	'SPACART_HOME_BLOG' => array(
		'label'     => 'Blog',
		'icon'      => 'bi-journal-richtext',
		'has_limit' => true,
	),
	'SPACART_HOME_NEWSLETTER' => array(
		'label'     => 'Newsletter',
		'icon'      => 'bi-envelope-paper',
		'has_limit' => false,
	),
);

// -------------------------------------------------------------------
// Handle POST: save homepage settings
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	$entity = (int) $conf->entity;
	$errors = 0;

	$db->begin();

	foreach ($homepage_sections as $key => $section_def) {
		// Active: checkbox unchecked = 0
		$active = !empty($_POST[$key.'_ACTIVE']) ? '1' : '0';
		// Sort order
		$sort_order = isset($_POST[$key.'_ORDER']) ? (int) $_POST[$key.'_ORDER'] : 0;
		// Limit (only for sections that have it)
		$limit_val = '';
		if ($section_def['has_limit']) {
			$limit_val = isset($_POST[$key.'_LIMIT']) ? (int) $_POST[$key.'_LIMIT'] : 8;
			if ($limit_val < 1) {
				$limit_val = 8;
			}
		}

		// Save _ACTIVE
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name, value, type, visible, entity)";
		$sql .= " VALUES ('".$db->escape($key.'_ACTIVE')."', '".$db->escape($active)."', 'chaine', 0, ".$entity.")";
		$sql .= " ON DUPLICATE KEY UPDATE value = '".$db->escape($active)."'";
		if (!$db->query($sql)) {
			$errors++;
		}

		// Save _ORDER
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name, value, type, visible, entity)";
		$sql .= " VALUES ('".$db->escape($key.'_ORDER')."', '".$db->escape((string) $sort_order)."', 'chaine', 0, ".$entity.")";
		$sql .= " ON DUPLICATE KEY UPDATE value = '".$db->escape((string) $sort_order)."'";
		if (!$db->query($sql)) {
			$errors++;
		}

		// Save _LIMIT if applicable
		if ($section_def['has_limit']) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name, value, type, visible, entity)";
			$sql .= " VALUES ('".$db->escape($key.'_LIMIT')."', '".$db->escape((string) $limit_val)."', 'chaine', 0, ".$entity.")";
			$sql .= " ON DUPLICATE KEY UPDATE value = '".$db->escape((string) $limit_val)."'";
			if (!$db->query($sql)) {
				$errors++;
			}
		}
	}

	if ($errors === 0) {
		$db->commit();
		spacartAdminFlash('La configuration de la page d\'accueil a ete enregistree.', 'success');
	} else {
		$db->rollback();
		spacartAdminFlash('Erreur lors de l\'enregistrement ('.$errors.' erreur'.($errors > 1 ? 's' : '').').', 'danger');
	}

	header('Location: ?page=homepage');
	exit;
}

// -------------------------------------------------------------------
// Load current values from llx_const
// -------------------------------------------------------------------
$entity = (int) $conf->entity;
$const_values = array();

$sql_load = "SELECT name, value FROM ".MAIN_DB_PREFIX."const";
$sql_load .= " WHERE name LIKE 'SPACART_HOME_%'";
$sql_load .= " AND entity = ".$entity;
$resql_load = $db->query($sql_load);
if ($resql_load) {
	while ($obj = $db->fetch_object($resql_load)) {
		$const_values[$obj->name] = $obj->value;
	}
}

/**
 * Get a const value with fallback
 *
 * @param  string $key     Const name
 * @param  string $default Default value
 * @return string
 */
function _homeVal($key, $default = '')
{
	global $const_values;
	return isset($const_values[$key]) ? $const_values[$key] : $default;
}

// Build sorted section list for display
$sections_display = array();
$default_order = 1;
foreach ($homepage_sections as $key => $section_def) {
	$sections_display[] = array(
		'key'        => $key,
		'label'      => $section_def['label'],
		'icon'       => $section_def['icon'],
		'has_limit'  => $section_def['has_limit'],
		'active'     => (int) _homeVal($key.'_ACTIVE', '1'),
		'sort_order' => (int) _homeVal($key.'_ORDER', (string) $default_order),
		'limit'      => $section_def['has_limit'] ? (int) _homeVal($key.'_LIMIT', '8') : 0,
	);
	$default_order++;
}

// Sort by sort_order for display
usort($sections_display, function ($a, $b) {
	return $a['sort_order'] - $b['sort_order'];
});

// -------------------------------------------------------------------
// CSRF token
// -------------------------------------------------------------------
$csrf_token = spacartAdminGetCSRFToken();

// -------------------------------------------------------------------
// Include header
// -------------------------------------------------------------------
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1">Page d'accueil</h1>
		<p class="text-muted mb-0">Configurez l'ordre et la visibilite des sections de la page d'accueil</p>
	</div>
</div>

<!-- Homepage Sections Form -->
<form method="post" action="?page=homepage" id="homepageForm">
	<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">

	<div class="admin-card">
		<div class="card-body p-0">
			<div class="table-responsive">
				<table class="admin-table table-hover mb-0">
					<thead>
						<tr>
							<th style="width: 80px;" class="text-center">Ordre</th>
							<th>Section</th>
							<th style="width: 120px;" class="text-center">Active</th>
							<th style="width: 120px;" class="text-center">Limite</th>
						</tr>
					</thead>
					<tbody id="sections-tbody">
						<?php foreach ($sections_display as $section): ?>
						<tr>
							<td class="text-center">
								<input type="number"
									   class="form-control form-control-sm text-center"
									   name="<?php echo spacartAdminEscape($section['key']); ?>_ORDER"
									   value="<?php echo (int) $section['sort_order']; ?>"
									   min="1" max="99"
									   style="width: 70px; margin: 0 auto;">
							</td>
							<td>
								<i class="bi <?php echo spacartAdminEscape($section['icon']); ?> me-2 text-primary"></i>
								<strong><?php echo spacartAdminEscape($section['label']); ?></strong>
								<br>
								<small class="text-muted"><?php echo spacartAdminEscape($section['key']); ?></small>
							</td>
							<td class="text-center">
								<div class="form-check form-switch d-flex justify-content-center">
									<input class="form-check-input toggle-switch"
										   type="checkbox"
										   role="switch"
										   id="toggle-<?php echo spacartAdminEscape($section['key']); ?>"
										   name="<?php echo spacartAdminEscape($section['key']); ?>_ACTIVE"
										   value="1"
										   <?php echo ($section['active'] === 1) ? 'checked' : ''; ?>>
								</div>
							</td>
							<td class="text-center">
								<?php if ($section['has_limit']): ?>
								<input type="number"
									   class="form-control form-control-sm text-center"
									   name="<?php echo spacartAdminEscape($section['key']); ?>_LIMIT"
									   value="<?php echo (int) $section['limit']; ?>"
									   min="1" max="50"
									   style="width: 70px; margin: 0 auto;">
								<?php else: ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Legend -->
	<div class="alert alert-info mt-3">
		<i class="bi bi-info-circle me-2"></i>
		<strong>Ordre :</strong> Numero qui determine la position de la section sur la page (1 = en haut).
		<strong>Limite :</strong> Nombre d'elements a afficher (produits, articles, etc.) pour les sections qui le permettent.
	</div>

	<!-- Save Button -->
	<div class="d-flex justify-content-end mb-4">
		<button type="submit" class="btn btn-primary btn-lg">
			<i class="bi bi-check-lg me-2"></i>Enregistrer
		</button>
	</div>
</form>

<!-- Toggle label update script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	'use strict';

	// Visual feedback on toggle switches
	var toggles = document.querySelectorAll('.toggle-switch');
	toggles.forEach(function(toggle) {
		toggle.addEventListener('change', function() {
			var row = this.closest('tr');
			if (row) {
				if (this.checked) {
					row.classList.remove('table-secondary');
					row.style.opacity = '1';
				} else {
					row.classList.add('table-secondary');
					row.style.opacity = '0.6';
				}
			}
		});
		// Apply initial state
		if (!toggle.checked) {
			var row = toggle.closest('tr');
			if (row) {
				row.classList.add('table-secondary');
				row.style.opacity = '0.6';
			}
		}
	});
});
</script>

<?php
include __DIR__.'/../includes/footer.php';
