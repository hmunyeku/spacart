<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/taxes.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Tax rules management with CRUD, search, pagination
 *
 * Table: llx_spacart_tax
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Taxes';
$current_page = 'taxes';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// -------------------------------------------------------------------
// Auto-create table if not exists
// -------------------------------------------------------------------
$sql_create = "CREATE TABLE IF NOT EXISTS ".$prefix."spacart_tax (";
$sql_create .= " rowid INT AUTO_INCREMENT PRIMARY KEY,";
$sql_create .= " label VARCHAR(255) NOT NULL,";
$sql_create .= " rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,";
$sql_create .= " country_code VARCHAR(2) DEFAULT '',";
$sql_create .= " state_code VARCHAR(10) DEFAULT '',";
$sql_create .= " zip_range VARCHAR(50) DEFAULT '',";
$sql_create .= " product_type SMALLINT DEFAULT -1,";
$sql_create .= " active SMALLINT DEFAULT 1,";
$sql_create .= " entity INT DEFAULT 1,";
$sql_create .= " date_creation DATETIME DEFAULT NULL,";
$sql_create .= " tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
$sql_create .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$db->query($sql_create);

// -------------------------------------------------------------------
// Handle POST actions
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=taxes');
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Add tax rule ---
	if ($action === 'add_tax') {
		$label        = isset($_POST['label']) ? trim($_POST['label']) : '';
		$rate         = isset($_POST['rate']) ? (float) $_POST['rate'] : 0;
		$country_code = isset($_POST['country_code']) ? trim($_POST['country_code']) : '';
		$state_code   = isset($_POST['state_code']) ? trim($_POST['state_code']) : '';
		$zip_range    = isset($_POST['zip_range']) ? trim($_POST['zip_range']) : '';
		$product_type = isset($_POST['product_type']) ? (int) $_POST['product_type'] : -1;
		$active       = isset($_POST['active']) ? 1 : 0;

		if ($label === '') {
			spacartAdminFlash('Le libelle est obligatoire.', 'danger');
		} elseif ($rate < 0 || $rate > 100) {
			spacartAdminFlash('Le taux doit etre entre 0 et 100.', 'danger');
		} else {
			$sql = "INSERT INTO ".$prefix."spacart_tax";
			$sql .= " (label, rate, country_code, state_code, zip_range, product_type, active, entity, date_creation)";
			$sql .= " VALUES (";
			$sql .= "'".$db->escape($label)."',";
			$sql .= " ".(float) $rate.",";
			$sql .= " '".$db->escape($country_code)."',";
			$sql .= " '".$db->escape($state_code)."',";
			$sql .= " '".$db->escape($zip_range)."',";
			$sql .= " ".(in_array($product_type, array(-1, 0, 1)) ? $product_type : -1).",";
			$sql .= " ".$active.",";
			$sql .= " ".$entity.",";
			$sql .= " NOW()";
			$sql .= ")";

			if ($db->query($sql)) {
				spacartAdminFlash('Regle de taxe ajoutee avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de l\'ajout de la regle de taxe.', 'danger');
			}
		}
		header('Location: ?page=taxes');
		exit;
	}

	// --- Edit tax rule ---
	if ($action === 'edit_tax') {
		$tax_id       = isset($_POST['tax_id']) ? (int) $_POST['tax_id'] : 0;
		$label        = isset($_POST['label']) ? trim($_POST['label']) : '';
		$rate         = isset($_POST['rate']) ? (float) $_POST['rate'] : 0;
		$country_code = isset($_POST['country_code']) ? trim($_POST['country_code']) : '';
		$state_code   = isset($_POST['state_code']) ? trim($_POST['state_code']) : '';
		$zip_range    = isset($_POST['zip_range']) ? trim($_POST['zip_range']) : '';
		$product_type = isset($_POST['product_type']) ? (int) $_POST['product_type'] : -1;
		$active       = isset($_POST['active']) ? 1 : 0;

		if ($tax_id <= 0) {
			spacartAdminFlash('Identifiant de taxe invalide.', 'danger');
		} elseif ($label === '') {
			spacartAdminFlash('Le libelle est obligatoire.', 'danger');
		} elseif ($rate < 0 || $rate > 100) {
			spacartAdminFlash('Le taux doit etre entre 0 et 100.', 'danger');
		} else {
			$sql = "UPDATE ".$prefix."spacart_tax SET";
			$sql .= " label = '".$db->escape($label)."',";
			$sql .= " rate = ".(float) $rate.",";
			$sql .= " country_code = '".$db->escape($country_code)."',";
			$sql .= " state_code = '".$db->escape($state_code)."',";
			$sql .= " zip_range = '".$db->escape($zip_range)."',";
			$sql .= " product_type = ".(in_array($product_type, array(-1, 0, 1)) ? $product_type : -1).",";
			$sql .= " active = ".$active;
			$sql .= " WHERE rowid = ".$tax_id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Regle de taxe mise a jour avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour de la regle de taxe.', 'danger');
			}
		}
		header('Location: ?page=taxes');
		exit;
	}

	// --- Toggle active status ---
	if ($action === 'toggle_tax') {
		$tax_id     = isset($_POST['tax_id']) ? (int) $_POST['tax_id'] : 0;
		$new_status = isset($_POST['new_status']) ? (int) $_POST['new_status'] : 0;

		if ($tax_id > 0 && in_array($new_status, array(0, 1))) {
			$sql = "UPDATE ".$prefix."spacart_tax";
			$sql .= " SET active = ".$new_status;
			$sql .= " WHERE rowid = ".$tax_id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Statut de la taxe mis a jour.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour.', 'danger');
			}
		}
		header('Location: ?page=taxes');
		exit;
	}

	// --- Delete tax rule ---
	if ($action === 'delete_tax') {
		$tax_id = isset($_POST['tax_id']) ? (int) $_POST['tax_id'] : 0;

		if ($tax_id > 0) {
			$sql = "DELETE FROM ".$prefix."spacart_tax";
			$sql .= " WHERE rowid = ".$tax_id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Regle de taxe supprimee.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la suppression.', 'danger');
			}
		}
		header('Location: ?page=taxes');
		exit;
	}
}

// -------------------------------------------------------------------
// Filters from GET
// -------------------------------------------------------------------
$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$pg        = max(1, (int) (isset($_GET['pg']) ? $_GET['pg'] : 1));
$per_page  = 20;
$edit_id   = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

// -------------------------------------------------------------------
// Build WHERE clause
// -------------------------------------------------------------------
$where = "t.entity = ".$entity;

if ($search !== '') {
	$esc_search = $db->escape($search);
	$where .= " AND (t.label LIKE '%".$esc_search."%'";
	$where .= " OR t.country_code LIKE '%".$esc_search."%')";
}

// -------------------------------------------------------------------
// Pagination
// -------------------------------------------------------------------
$sql_count  = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_tax as t WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// Total unfiltered for subtitle
$sql_total   = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_tax WHERE entity = ".$entity;
$resql_total = $db->query($sql_total);
$obj_total   = $db->fetch_object($resql_total);
$total_taxes = $obj_total ? (int) $obj_total->nb : 0;

// -------------------------------------------------------------------
// Fetch tax rules
// -------------------------------------------------------------------
$taxes = array();
$sql  = "SELECT t.rowid, t.label, t.rate, t.country_code, t.state_code, t.zip_range,";
$sql .= " t.product_type, t.active, t.date_creation";
$sql .= " FROM ".$prefix."spacart_tax as t";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY t.label ASC";
$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$taxes[] = $obj;
	}
	$db->free($resql);
}

// -------------------------------------------------------------------
// Fetch tax to edit (if edit mode)
// -------------------------------------------------------------------
$edit_tax = null;
if ($edit_id > 0) {
	$sql_edit = "SELECT rowid, label, rate, country_code, state_code, zip_range, product_type, active";
	$sql_edit .= " FROM ".$prefix."spacart_tax";
	$sql_edit .= " WHERE rowid = ".$edit_id;
	$sql_edit .= " AND entity = ".$entity;
	$resql_edit = $db->query($sql_edit);
	if ($resql_edit && $db->num_rows($resql_edit) > 0) {
		$edit_tax = $db->fetch_object($resql_edit);
	}
	$db->free($resql_edit);
}

// -------------------------------------------------------------------
// Fetch countries for dropdown
// -------------------------------------------------------------------
$countries = array();
$sql_countries = "SELECT code, label FROM ".$prefix."c_country WHERE active = 1 ORDER BY label ASC";
$resql_countries = $db->query($sql_countries);
if ($resql_countries) {
	while ($obj = $db->fetch_object($resql_countries)) {
		$countries[] = $obj;
	}
	$db->free($resql_countries);
}

// Build filter query string for pagination links
$filter_params = array('page' => 'taxes');
if ($search !== '') {
	$filter_params['search'] = $search;
}

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

/**
 * Get product type label in French
 *
 * @param  int    $type Product type code (-1, 0, 1)
 * @return string French label
 */
function _taxProductTypeLabel($type)
{
	$labels = array(
		-1 => 'Tous',
		0  => 'Produits',
		1  => 'Services',
	);
	return isset($labels[(int) $type]) ? $labels[(int) $type] : 'Tous';
}

// -------------------------------------------------------------------
// Include header
// -------------------------------------------------------------------
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1">Taxes</h1>
		<p class="text-muted mb-0"><?php echo (int) $total_taxes; ?> regle<?php echo $total_taxes > 1 ? 's' : ''; ?> de taxe au total</p>
	</div>
</div>

<!-- ============================================================== -->
<!-- Add / Edit Tax Form -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-header">
		<h5 class="mb-0">
			<?php if ($edit_tax): ?>
				<i class="bi bi-pencil-square me-2"></i>Modifier la regle de taxe
			<?php else: ?>
				<i class="bi bi-plus-circle me-2"></i>Ajouter une regle de taxe
			<?php endif; ?>
		</h5>
	</div>
	<div class="card-body">
		<form method="post" action="?page=taxes">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<?php if ($edit_tax): ?>
				<input type="hidden" name="action" value="edit_tax">
				<input type="hidden" name="tax_id" value="<?php echo (int) $edit_tax->rowid; ?>">
			<?php else: ?>
				<input type="hidden" name="action" value="add_tax">
			<?php endif; ?>

			<div class="row g-3">
				<!-- Label -->
				<div class="col-md-3">
					<label for="tax-label" class="form-label">Libelle <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="tax-label" name="label" required
						   placeholder="Ex: TVA France 20%"
						   value="<?php echo $edit_tax ? spacartAdminEscape($edit_tax->label) : ''; ?>">
				</div>

				<!-- Rate -->
				<div class="col-md-2">
					<label for="tax-rate" class="form-label">Taux (%) <span class="text-danger">*</span></label>
					<input type="number" class="form-control" id="tax-rate" name="rate"
						   step="0.01" min="0" max="100" required
						   value="<?php echo $edit_tax ? spacartAdminEscape($edit_tax->rate) : '0.00'; ?>">
				</div>

				<!-- Country -->
				<div class="col-md-2">
					<label for="tax-country" class="form-label">Pays</label>
					<select class="form-select" id="tax-country" name="country_code">
						<option value="">-- Tous --</option>
						<?php foreach ($countries as $country): ?>
							<option value="<?php echo spacartAdminEscape($country->code); ?>"
								<?php echo ($edit_tax && $edit_tax->country_code === $country->code) ? ' selected' : ''; ?>>
								<?php echo spacartAdminEscape($country->code.' - '.$country->label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- State code -->
				<div class="col-md-1">
					<label for="tax-state" class="form-label">Region</label>
					<input type="text" class="form-control" id="tax-state" name="state_code"
						   maxlength="10" placeholder="Ex: IDF"
						   value="<?php echo $edit_tax ? spacartAdminEscape($edit_tax->state_code) : ''; ?>">
				</div>

				<!-- Zip range -->
				<div class="col-md-2">
					<label for="tax-zip" class="form-label">Plage CP</label>
					<input type="text" class="form-control" id="tax-zip" name="zip_range"
						   maxlength="50" placeholder="Ex: 75000-75999"
						   value="<?php echo $edit_tax ? spacartAdminEscape($edit_tax->zip_range) : ''; ?>">
				</div>

				<!-- Product type -->
				<div class="col-md-2">
					<label for="tax-product-type" class="form-label">Type produit</label>
					<select class="form-select" id="tax-product-type" name="product_type">
						<option value="-1"<?php echo ($edit_tax && (int) $edit_tax->product_type === -1) ? ' selected' : ''; ?>>Tous</option>
						<option value="0"<?php echo ($edit_tax && (int) $edit_tax->product_type === 0) ? ' selected' : ''; ?>>Produits</option>
						<option value="1"<?php echo ($edit_tax && (int) $edit_tax->product_type === 1) ? ' selected' : ''; ?>>Services</option>
					</select>
				</div>
			</div>

			<div class="row g-3 mt-1">
				<!-- Active checkbox -->
				<div class="col-md-2">
					<div class="form-check mt-2">
						<input class="form-check-input" type="checkbox" id="tax-active" name="active" value="1"
							   <?php echo (!$edit_tax || (int) $edit_tax->active === 1) ? 'checked' : ''; ?>>
						<label class="form-check-label" for="tax-active">Actif</label>
					</div>
				</div>

				<!-- Submit -->
				<div class="col-md-2 d-flex align-items-end">
					<button type="submit" class="btn btn-primary w-100">
						<?php if ($edit_tax): ?>
							<i class="bi bi-check-lg me-1"></i>Modifier
						<?php else: ?>
							<i class="bi bi-plus-lg me-1"></i>Ajouter
						<?php endif; ?>
					</button>
				</div>

				<?php if ($edit_tax): ?>
				<div class="col-md-2 d-flex align-items-end">
					<a href="?page=taxes" class="btn btn-outline-secondary w-100">
						<i class="bi bi-x-circle me-1"></i>Annuler
					</a>
				</div>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Filter bar -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="taxes">
			<div class="row g-3 align-items-end">
				<!-- Search -->
				<div class="col-12 col-md-5">
					<label for="filter_search" class="form-label">Recherche par libelle</label>
					<input type="text" class="form-control" id="filter_search" name="search"
						   value="<?php echo spacartAdminEscape($search); ?>"
						   placeholder="Libelle ou code pays...">
				</div>

				<!-- Filter button -->
				<div class="col-6 col-md-2">
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>

				<!-- Reset -->
				<?php if ($search !== ''): ?>
				<div class="col-6 col-md-2">
					<a href="?page=taxes" class="btn btn-outline-secondary w-100">
						<i class="bi bi-x-circle me-1"></i>Reinitialiser
					</a>
				</div>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Tax Rules Table -->
<!-- ============================================================== -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th>Libelle</th>
						<th class="text-end">Taux</th>
						<th>Pays</th>
						<th>Region</th>
						<th>Plage CP</th>
						<th>Type produit</th>
						<th class="text-center">Statut</th>
						<th>Date creation</th>
						<th class="text-center" style="width:150px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($taxes)): ?>
					<tr>
						<td colspan="9">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-percent"></i></div>
								<p>Aucune regle de taxe trouvee</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($taxes as $tax): ?>
						<tr>
							<td><strong><?php echo spacartAdminEscape($tax->label); ?></strong></td>
							<td class="text-end"><?php echo number_format((float) $tax->rate, 2, ',', ' '); ?>%</td>
							<td>
								<?php if (!empty($tax->country_code)): ?>
									<?php echo spacartAdminEscape($tax->country_code); ?>
								<?php else: ?>
									<span class="text-muted">Tous</span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo !empty($tax->state_code) ? spacartAdminEscape($tax->state_code) : '<span class="text-muted">-</span>'; ?>
							</td>
							<td>
								<?php echo !empty($tax->zip_range) ? spacartAdminEscape($tax->zip_range) : '<span class="text-muted">-</span>'; ?>
							</td>
							<td>
								<?php echo spacartAdminEscape(_taxProductTypeLabel($tax->product_type)); ?>
							</td>
							<td class="text-center">
								<?php if ((int) $tax->active === 1): ?>
									<span class="badge badge-status status-active">Actif</span>
								<?php else: ?>
									<span class="badge badge-status status-inactive">Inactif</span>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminFormatDate($tax->date_creation, 'd/m/Y'); ?></td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Edit -->
									<a href="?page=taxes&amp;edit=<?php echo (int) $tax->rowid; ?>"
									   class="btn btn-sm btn-outline-primary" title="Modifier" aria-label="Modifier la taxe">
										<i class="bi bi-pencil"></i>
									</a>

									<!-- Toggle status -->
									<form method="post" action="?page=taxes" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_tax">
										<input type="hidden" name="tax_id" value="<?php echo (int) $tax->rowid; ?>">
										<input type="hidden" name="new_status" value="<?php echo ((int) $tax->active === 1) ? '0' : '1'; ?>">
										<?php if ((int) $tax->active === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Desactiver" aria-label="Desactiver la taxe">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Activer" aria-label="Activer la taxe">
												<i class="bi bi-toggle-off"></i>
											</button>
										<?php endif; ?>
									</form>

									<!-- Delete -->
									<form method="post" action="?page=taxes" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete_tax">
										<input type="hidden" name="tax_id" value="<?php echo (int) $tax->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer la taxe"
												data-confirm="Supprimer cette regle de taxe ?">
											<i class="bi bi-trash"></i>
										</button>
									</form>
								</div>
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
				sur <?php echo (int) $pagination['total']; ?> regle<?php echo $pagination['total'] > 1 ? 's' : ''; ?>
			</div>
			<nav aria-label="Pagination taxes">
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
