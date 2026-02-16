<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/countries.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Active countries management for shipping zones
 *
 * Uses Dolibarr core table llx_c_country to toggle active/favorite status.
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Pays';
$current_page = 'countries';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// -------------------------------------------------------------------
// Common EU/international country codes for bulk-enable
// -------------------------------------------------------------------
$common_codes = array('FR', 'BE', 'CH', 'LU', 'DE', 'ES', 'IT', 'PT', 'GB', 'US', 'CA', 'MA');

// -------------------------------------------------------------------
// Handle POST actions
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=countries');
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Toggle active status for a single country ---
	if ($action === 'toggle_active') {
		$country_id = isset($_POST['country_id']) ? (int) $_POST['country_id'] : 0;
		$new_active = isset($_POST['new_active']) ? (int) $_POST['new_active'] : 0;

		if ($country_id > 0 && in_array($new_active, array(0, 1))) {
			$sql = "UPDATE ".$prefix."c_country SET active = ".$new_active." WHERE rowid = ".$country_id;

			if ($db->query($sql)) {
				spacartAdminFlash('Statut du pays mis a jour.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour.', 'danger');
			}
		}

		// Preserve current filters in redirect
		$redirect_params = array('page' => 'countries');
		if (!empty($_POST['redirect_search'])) {
			$redirect_params['search'] = $_POST['redirect_search'];
		}
		if (isset($_POST['redirect_filter']) && $_POST['redirect_filter'] !== '') {
			$redirect_params['filter'] = $_POST['redirect_filter'];
		}
		if (!empty($_POST['redirect_pg'])) {
			$redirect_params['pg'] = $_POST['redirect_pg'];
		}
		header('Location: ?'.http_build_query($redirect_params));
		exit;
	}

	// --- Toggle favorite status for a single country ---
	if ($action === 'toggle_favorite') {
		$country_id   = isset($_POST['country_id']) ? (int) $_POST['country_id'] : 0;
		$new_favorite = isset($_POST['new_favorite']) ? (int) $_POST['new_favorite'] : 0;

		if ($country_id > 0 && in_array($new_favorite, array(0, 1))) {
			$sql = "UPDATE ".$prefix."c_country SET favorite = ".$new_favorite." WHERE rowid = ".$country_id;

			if ($db->query($sql)) {
				spacartAdminFlash('Favori mis a jour.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour.', 'danger');
			}
		}

		$redirect_params = array('page' => 'countries');
		if (!empty($_POST['redirect_search'])) {
			$redirect_params['search'] = $_POST['redirect_search'];
		}
		if (isset($_POST['redirect_filter']) && $_POST['redirect_filter'] !== '') {
			$redirect_params['filter'] = $_POST['redirect_filter'];
		}
		if (!empty($_POST['redirect_pg'])) {
			$redirect_params['pg'] = $_POST['redirect_pg'];
		}
		header('Location: ?'.http_build_query($redirect_params));
		exit;
	}

	// --- Enable common countries (bulk) ---
	if ($action === 'enable_common') {
		$in_list = array();
		foreach ($common_codes as $code) {
			$in_list[] = "'".$db->escape($code)."'";
		}
		$sql = "UPDATE ".$prefix."c_country SET active = 1 WHERE code IN (".implode(',', $in_list).")";

		if ($db->query($sql)) {
			spacartAdminFlash(count($common_codes).' pays courants actives avec succes.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de l\'activation des pays courants.', 'danger');
		}
		header('Location: ?page=countries');
		exit;
	}

	// --- Disable all countries ---
	if ($action === 'disable_all') {
		$sql = "UPDATE ".$prefix."c_country SET active = 0";

		if ($db->query($sql)) {
			spacartAdminFlash('Tous les pays ont ete desactives.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de la desactivation.', 'danger');
		}
		header('Location: ?page=countries');
		exit;
	}
}

// -------------------------------------------------------------------
// Filters from GET
// -------------------------------------------------------------------
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_active = isset($_GET['filter']) ? $_GET['filter'] : '';
$pg           = max(1, (int) (isset($_GET['pg']) ? $_GET['pg'] : 1));
$per_page     = 30;

// -------------------------------------------------------------------
// Build WHERE clause
// -------------------------------------------------------------------
$where = "1 = 1";

if ($search !== '') {
	$esc_search = $db->escape($search);
	$where .= " AND (c.label LIKE '%".$esc_search."%'";
	$where .= " OR c.code LIKE '%".$esc_search."%'";
	$where .= " OR c.code_iso LIKE '%".$esc_search."%')";
}

if ($filter_active === 'active') {
	$where .= " AND c.active = 1";
} elseif ($filter_active === 'inactive') {
	$where .= " AND c.active = 0";
}

// -------------------------------------------------------------------
// Pagination
// -------------------------------------------------------------------
$sql_count  = "SELECT COUNT(*) as nb FROM ".$prefix."c_country as c WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// Count active countries for subtitle
$sql_active_count   = "SELECT COUNT(*) as nb FROM ".$prefix."c_country WHERE active = 1";
$resql_active_count = $db->query($sql_active_count);
$obj_active_count   = $db->fetch_object($resql_active_count);
$total_active = $obj_active_count ? (int) $obj_active_count->nb : 0;

// Total countries
$sql_total_count   = "SELECT COUNT(*) as nb FROM ".$prefix."c_country";
$resql_total_count = $db->query($sql_total_count);
$obj_total_count   = $db->fetch_object($resql_total_count);
$total_countries = $obj_total_count ? (int) $obj_total_count->nb : 0;

// -------------------------------------------------------------------
// Fetch countries
// -------------------------------------------------------------------
$country_list = array();
$sql  = "SELECT c.rowid, c.code, c.code_iso, c.label, c.active, c.favorite";
$sql .= " FROM ".$prefix."c_country as c";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY c.active DESC, c.favorite DESC, c.label ASC";
$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$country_list[] = $obj;
	}
	$db->free($resql);
}

// Build filter query string for pagination links
$filter_params = array('page' => 'countries');
if ($search !== '') {
	$filter_params['search'] = $search;
}
if ($filter_active !== '') {
	$filter_params['filter'] = $filter_active;
}

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

// -------------------------------------------------------------------
// Include header
// -------------------------------------------------------------------
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1">Pays</h1>
		<p class="text-muted mb-0"><?php echo (int) $total_active; ?> pays actif<?php echo $total_active > 1 ? 's' : ''; ?> sur <?php echo (int) $total_countries; ?></p>
	</div>
	<div class="d-flex gap-2">
		<!-- Enable common countries -->
		<form method="post" action="?page=countries" class="d-inline">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="enable_common">
			<button type="submit" class="btn btn-success"
					data-confirm="Activer les pays courants (FR, BE, CH, LU, DE, ES, IT, PT, GB, US, CA, MA) ?">
				<i class="bi bi-check-all me-1"></i>Activer courants
			</button>
		</form>

		<!-- Disable all -->
		<form method="post" action="?page=countries" class="d-inline">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="disable_all">
			<button type="submit" class="btn btn-outline-danger btn-delete"
					data-confirm="Desactiver tous les pays ? Cette action est irreversible.">
				<i class="bi bi-x-circle me-1"></i>Tout desactiver
			</button>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Filter bar -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="countries">
			<div class="row g-3 align-items-end">
				<!-- Search -->
				<div class="col-12 col-md-4">
					<label for="filter_search" class="form-label">Recherche</label>
					<input type="text" class="form-control" id="filter_search" name="search"
						   value="<?php echo spacartAdminEscape($search); ?>"
						   placeholder="Nom ou code pays...">
				</div>

				<!-- Active filter -->
				<div class="col-6 col-md-3">
					<label for="filter_active" class="form-label">Afficher</label>
					<select class="form-select" id="filter_active" name="filter">
						<option value="">Tous les pays</option>
						<option value="active"<?php echo ($filter_active === 'active') ? ' selected' : ''; ?>>Actifs uniquement</option>
						<option value="inactive"<?php echo ($filter_active === 'inactive') ? ' selected' : ''; ?>>Inactifs uniquement</option>
					</select>
				</div>

				<!-- Filter button -->
				<div class="col-6 col-md-2">
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>

				<!-- Reset -->
				<?php if ($search !== '' || $filter_active !== ''): ?>
				<div class="col-12 col-md-2">
					<a href="?page=countries" class="btn btn-outline-secondary w-100">
						<i class="bi bi-x-circle me-1"></i>Reinitialiser
					</a>
				</div>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Countries Table -->
<!-- ============================================================== -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th style="width:50px;"></th>
						<th style="width:80px;">Code</th>
						<th>Nom</th>
						<th class="text-center" style="width:100px;">Actif</th>
						<th class="text-center" style="width:100px;">Favori</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($country_list)): ?>
					<tr>
						<td colspan="5">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-globe"></i></div>
								<p>Aucun pays trouve</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($country_list as $country):
							$code_lower = strtolower($country->code_iso ? $country->code_iso : $country->code);
						?>
						<tr>
							<!-- Flag -->
							<td class="text-center">
								<?php if (!empty($code_lower)): ?>
									<img src="https://flagcdn.com/24x18/<?php echo spacartAdminEscape($code_lower); ?>.png"
										 srcset="https://flagcdn.com/48x36/<?php echo spacartAdminEscape($code_lower); ?>.png 2x"
										 width="24" height="18"
										 alt="<?php echo spacartAdminEscape($country->code); ?>"
										 loading="lazy"
										 onerror="this.style.display='none'">
								<?php endif; ?>
							</td>

							<!-- Code -->
							<td>
								<strong class="font-monospace"><?php echo spacartAdminEscape($country->code); ?></strong>
							</td>

							<!-- Label -->
							<td><?php echo spacartAdminEscape($country->label); ?></td>

							<!-- Active toggle -->
							<td class="text-center">
								<form method="post" action="?page=countries" class="d-inline">
									<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
									<input type="hidden" name="action" value="toggle_active">
									<input type="hidden" name="country_id" value="<?php echo (int) $country->rowid; ?>">
									<input type="hidden" name="new_active" value="<?php echo ((int) $country->active === 1) ? '0' : '1'; ?>">
									<input type="hidden" name="redirect_search" value="<?php echo spacartAdminEscape($search); ?>">
									<input type="hidden" name="redirect_filter" value="<?php echo spacartAdminEscape($filter_active); ?>">
									<input type="hidden" name="redirect_pg" value="<?php echo (int) $pagination['current_page']; ?>">
									<?php if ((int) $country->active === 1): ?>
										<button type="submit" class="btn btn-sm btn-success" title="Actif - cliquer pour desactiver" aria-label="Desactiver le pays">
											<i class="bi bi-toggle-on"></i>
										</button>
									<?php else: ?>
										<button type="submit" class="btn btn-sm btn-outline-secondary" title="Inactif - cliquer pour activer" aria-label="Activer le pays">
											<i class="bi bi-toggle-off"></i>
										</button>
									<?php endif; ?>
								</form>
							</td>

							<!-- Favorite toggle -->
							<td class="text-center">
								<form method="post" action="?page=countries" class="d-inline">
									<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
									<input type="hidden" name="action" value="toggle_favorite">
									<input type="hidden" name="country_id" value="<?php echo (int) $country->rowid; ?>">
									<input type="hidden" name="new_favorite" value="<?php echo ((int) $country->favorite === 1) ? '0' : '1'; ?>">
									<input type="hidden" name="redirect_search" value="<?php echo spacartAdminEscape($search); ?>">
									<input type="hidden" name="redirect_filter" value="<?php echo spacartAdminEscape($filter_active); ?>">
									<input type="hidden" name="redirect_pg" value="<?php echo (int) $pagination['current_page']; ?>">
									<?php if ((int) $country->favorite === 1): ?>
										<button type="submit" class="btn btn-sm btn-warning" title="Favori - cliquer pour retirer" aria-label="Retirer des favoris">
											<i class="bi bi-star-fill"></i>
										</button>
									<?php else: ?>
										<button type="submit" class="btn btn-sm btn-outline-secondary" title="Non favori - cliquer pour ajouter" aria-label="Ajouter aux favoris">
											<i class="bi bi-star"></i>
										</button>
									<?php endif; ?>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php if ($pagination['total_pages'] > 1): ?>
	<!-- Pagination -->
	<div class="card-footer">
		<div class="d-flex flex-wrap justify-content-between align-items-center">
			<div class="text-muted small mb-2 mb-md-0">
				Affichage de <?php echo (int) $pagination['offset'] + 1; ?>
				a <?php echo min($pagination['offset'] + $pagination['limit'], $pagination['total']); ?>
				sur <?php echo (int) $pagination['total']; ?> pays
			</div>
			<nav aria-label="Pagination pays">
				<ul class="pagination pagination-sm mb-0">
					<?php
					$base_params = $filter_params;
					$total_pages = $pagination['total_pages'];
					$cp          = $pagination['current_page'];

					// Previous
					$prev_disabled = ($cp <= 1) ? ' disabled' : '';
					$prev_params   = array_merge($base_params, array('pg' => $cp - 1));
					?>
					<li class="page-item<?php echo $prev_disabled; ?>">
						<a class="page-link" href="?<?php echo http_build_query($prev_params); ?>" aria-label="Precedent">
							<i class="bi bi-chevron-left"></i>
						</a>
					</li>

					<?php
					// Page range
					$start = max(1, $cp - 2);
					$end   = min($total_pages, $cp + 2);
					if ($cp <= 3) {
						$end = min($total_pages, 5);
					}
					if ($cp >= $total_pages - 2) {
						$start = max(1, $total_pages - 4);
					}

					if ($start > 1): ?>
						<li class="page-item">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($base_params, array('pg' => 1))); ?>">1</a>
						</li>
						<?php if ($start > 2): ?>
						<li class="page-item disabled"><span class="page-link">...</span></li>
						<?php endif; ?>
					<?php endif; ?>

					<?php for ($i = $start; $i <= $end; $i++): ?>
						<li class="page-item<?php echo ($i === $cp) ? ' active' : ''; ?>">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($base_params, array('pg' => $i))); ?>"><?php echo $i; ?></a>
						</li>
					<?php endfor; ?>

					<?php if ($end < $total_pages): ?>
						<?php if ($end < $total_pages - 1): ?>
						<li class="page-item disabled"><span class="page-link">...</span></li>
						<?php endif; ?>
						<li class="page-item">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($base_params, array('pg' => $total_pages))); ?>"><?php echo $total_pages; ?></a>
						</li>
					<?php endif; ?>

					<?php
					// Next
					$next_disabled = ($cp >= $total_pages) ? ' disabled' : '';
					$next_params   = array_merge($base_params, array('pg' => $cp + 1));
					?>
					<li class="page-item<?php echo $next_disabled; ?>">
						<a class="page-link" href="?<?php echo http_build_query($next_params); ?>" aria-label="Suivant">
							<i class="bi bi-chevron-right"></i>
						</a>
					</li>
				</ul>
			</nav>
		</div>
	</div>
	<?php endif; ?>
</div>

<?php
include __DIR__.'/../includes/footer.php';
