<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/shipping.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Shipping methods and zones management
 *
 * Tables: llx_spacart_shipping_method, llx_spacart_shipping_zone
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Livraison';
$current_page = 'shipping';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// -------------------------------------------------------------------
// Active tab
// -------------------------------------------------------------------
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'methods';
if (!in_array($tab, array('methods', 'zones'))) {
	$tab = 'methods';
}

// -------------------------------------------------------------------
// Handle POST actions
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=shipping&tab='.$tab);
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// ---- Shipping Methods ----

	if ($action === 'add_method') {
		$label = isset($_POST['label']) ? trim($_POST['label']) : '';
		$description = isset($_POST['description']) ? trim($_POST['description']) : '';
		$price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
		$free_above = isset($_POST['free_above']) ? (float) $_POST['free_above'] : 0;
		$status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

		if ($label === '') {
			spacartAdminFlash('Le libelle est obligatoire.', 'danger');
		} else {
			$sql = "INSERT INTO ".$prefix."spacart_shipping_method";
			$sql .= " (label, description, price, free_above, status, entity, date_creation)";
			$sql .= " VALUES (";
			$sql .= "'".$db->escape($label)."',";
			$sql .= " '".$db->escape($description)."',";
			$sql .= " ".((float) $price).",";
			$sql .= " ".((float) $free_above).",";
			$sql .= " ".(in_array($status, array(0, 1)) ? $status : 1).",";
			$sql .= " ".$entity.",";
			$sql .= " NOW()";
			$sql .= ")";

			if ($db->query($sql)) {
				spacartAdminFlash('Methode de livraison ajoutee avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de l\'ajout de la methode.', 'danger');
			}
		}
		header('Location: ?page=shipping&tab=methods');
		exit;
	}

	if ($action === 'toggle_method') {
		$method_id = isset($_POST['method_id']) ? (int) $_POST['method_id'] : 0;
		$new_status = isset($_POST['new_status']) ? (int) $_POST['new_status'] : 0;

		if ($method_id > 0 && in_array($new_status, array(0, 1))) {
			$sql = "UPDATE ".$prefix."spacart_shipping_method";
			$sql .= " SET status = ".$new_status;
			$sql .= " WHERE rowid = ".$method_id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Statut de la methode mis a jour.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour.', 'danger');
			}
		}
		header('Location: ?page=shipping&tab=methods');
		exit;
	}

	if ($action === 'delete_method') {
		$method_id = isset($_POST['method_id']) ? (int) $_POST['method_id'] : 0;

		if ($method_id > 0) {
			$sql = "DELETE FROM ".$prefix."spacart_shipping_method";
			$sql .= " WHERE rowid = ".$method_id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Methode de livraison supprimee.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la suppression.', 'danger');
			}
		}
		header('Location: ?page=shipping&tab=methods');
		exit;
	}

	// ---- Shipping Zones ----

	if ($action === 'add_zone') {
		$label = isset($_POST['label']) ? trim($_POST['label']) : '';
		$countries = isset($_POST['countries']) ? trim($_POST['countries']) : '';
		$status = isset($_POST['status']) ? (int) $_POST['status'] : 1;

		if ($label === '') {
			spacartAdminFlash('Le libelle de la zone est obligatoire.', 'danger');
		} else {
			$sql = "INSERT INTO ".$prefix."spacart_shipping_zone";
			$sql .= " (label, countries, status, entity)";
			$sql .= " VALUES (";
			$sql .= "'".$db->escape($label)."',";
			$sql .= " '".$db->escape($countries)."',";
			$sql .= " ".(in_array($status, array(0, 1)) ? $status : 1).",";
			$sql .= " ".$entity;
			$sql .= ")";

			if ($db->query($sql)) {
				spacartAdminFlash('Zone de livraison ajoutee avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de l\'ajout de la zone.', 'danger');
			}
		}
		header('Location: ?page=shipping&tab=zones');
		exit;
	}

	if ($action === 'toggle_zone') {
		$zone_id = isset($_POST['zone_id']) ? (int) $_POST['zone_id'] : 0;
		$new_status = isset($_POST['new_status']) ? (int) $_POST['new_status'] : 0;

		if ($zone_id > 0 && in_array($new_status, array(0, 1))) {
			$sql = "UPDATE ".$prefix."spacart_shipping_zone";
			$sql .= " SET status = ".$new_status;
			$sql .= " WHERE rowid = ".$zone_id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Statut de la zone mis a jour.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour.', 'danger');
			}
		}
		header('Location: ?page=shipping&tab=zones');
		exit;
	}

	if ($action === 'delete_zone') {
		$zone_id = isset($_POST['zone_id']) ? (int) $_POST['zone_id'] : 0;

		if ($zone_id > 0) {
			$sql = "DELETE FROM ".$prefix."spacart_shipping_zone";
			$sql .= " WHERE rowid = ".$zone_id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Zone de livraison supprimee.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la suppression.', 'danger');
			}
		}
		header('Location: ?page=shipping&tab=zones');
		exit;
	}
}

// -------------------------------------------------------------------
// Fetch shipping methods
// -------------------------------------------------------------------
$methods = array();
$sql = "SELECT rowid, label, description, price, free_above, status, date_creation";
$sql .= " FROM ".$prefix."spacart_shipping_method";
$sql .= " WHERE entity = ".$entity;
$sql .= " ORDER BY label ASC";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$methods[] = $obj;
	}
	$db->free($resql);
}

// -------------------------------------------------------------------
// Fetch shipping zones
// -------------------------------------------------------------------
$zones = array();
$sql = "SELECT rowid, label, countries, status";
$sql .= " FROM ".$prefix."spacart_shipping_zone";
$sql .= " WHERE entity = ".$entity;
$sql .= " ORDER BY label ASC";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$zones[] = $obj;
	}
	$db->free($resql);
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
		<h1 class="h3 mb-1">Livraison</h1>
		<p class="text-muted mb-0">Gerer les methodes et zones de livraison</p>
	</div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
	<li class="nav-item">
		<a class="nav-link<?php echo ($tab === 'methods') ? ' active' : ''; ?>" href="?page=shipping&tab=methods">
			<i class="bi bi-truck me-1"></i>Methodes de livraison
			<span class="badge bg-secondary ms-1"><?php echo count($methods); ?></span>
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link<?php echo ($tab === 'zones') ? ' active' : ''; ?>" href="?page=shipping&tab=zones">
			<i class="bi bi-globe me-1"></i>Zones de livraison
			<span class="badge bg-secondary ms-1"><?php echo count($zones); ?></span>
		</a>
	</li>
</ul>

<?php if ($tab === 'methods'): ?>
<!-- ============================================================== -->
<!-- SHIPPING METHODS -->
<!-- ============================================================== -->

<!-- Add Method Form -->
<div class="admin-card mb-4">
	<div class="card-header">
		<h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Ajouter une methode de livraison</h5>
	</div>
	<div class="card-body">
		<form method="post" action="?page=shipping&tab=methods">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="add_method">

			<div class="row g-3">
				<div class="col-md-4">
					<label for="method-label" class="form-label">Libelle <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="method-label" name="label" required
						   placeholder="Ex: Livraison standard">
				</div>
				<div class="col-md-4">
					<label for="method-description" class="form-label">Description</label>
					<input type="text" class="form-control" id="method-description" name="description"
						   placeholder="Ex: 3-5 jours ouvrables">
				</div>
				<div class="col-md-2">
					<label for="method-price" class="form-label">Prix</label>
					<input type="number" class="form-control" id="method-price" name="price"
						   step="0.01" min="0" value="0.00">
				</div>
				<div class="col-md-2">
					<label for="method-free-above" class="form-label">Gratuit au-dessus de</label>
					<input type="number" class="form-control" id="method-free-above" name="free_above"
						   step="0.01" min="0" value="0.00"
						   title="Montant a partir duquel la livraison est gratuite (0 = jamais gratuit)">
				</div>
				<div class="col-md-2">
					<label for="method-status" class="form-label">Statut</label>
					<select class="form-select" id="method-status" name="status">
						<option value="1">Actif</option>
						<option value="0">Inactif</option>
					</select>
				</div>
				<div class="col-md-2 d-flex align-items-end">
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-plus-lg me-1"></i>Ajouter
					</button>
				</div>
			</div>
		</form>
	</div>
</div>

<!-- Methods Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th>Libelle</th>
						<th>Description</th>
						<th class="text-end">Prix</th>
						<th class="text-end">Gratuit au-dessus de</th>
						<th class="text-center">Statut</th>
						<th>Date creation</th>
						<th class="text-center" style="width:120px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($methods)): ?>
					<tr>
						<td colspan="7">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-truck"></i></div>
								<p>Aucune methode de livraison configuree</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($methods as $method): ?>
						<tr>
							<td><strong><?php echo spacartAdminEscape($method->label); ?></strong></td>
							<td><?php echo spacartAdminEscape($method->description ?: '-'); ?></td>
							<td class="text-end"><?php echo spacartAdminFormatPrice($method->price); ?></td>
							<td class="text-end">
								<?php if ((float) $method->free_above > 0): ?>
									<?php echo spacartAdminFormatPrice($method->free_above); ?>
								<?php else: ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
							<td class="text-center">
								<?php if ((int) $method->status === 1): ?>
									<span class="badge badge-status status-active">Actif</span>
								<?php else: ?>
									<span class="badge badge-status status-inactive">Inactif</span>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminFormatDate($method->date_creation, 'd/m/Y'); ?></td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Toggle status -->
									<form method="post" action="?page=shipping&tab=methods" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_method">
										<input type="hidden" name="method_id" value="<?php echo (int) $method->rowid; ?>">
										<input type="hidden" name="new_status" value="<?php echo ((int) $method->status === 1) ? '0' : '1'; ?>">
										<?php if ((int) $method->status === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Desactiver" aria-label="Desactiver la methode">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Activer" aria-label="Activer la methode">
												<i class="bi bi-toggle-off"></i>
											</button>
										<?php endif; ?>
									</form>

									<!-- Delete -->
									<form method="post" action="?page=shipping&tab=methods" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete_method">
										<input type="hidden" name="method_id" value="<?php echo (int) $method->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer la methode"
												data-confirm="Supprimer cette methode de livraison ?">
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
</div>

<?php else: ?>
<!-- ============================================================== -->
<!-- SHIPPING ZONES -->
<!-- ============================================================== -->

<!-- Add Zone Form -->
<div class="admin-card mb-4">
	<div class="card-header">
		<h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Ajouter une zone de livraison</h5>
	</div>
	<div class="card-body">
		<form method="post" action="?page=shipping&tab=zones">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="add_zone">

			<div class="row g-3">
				<div class="col-md-4">
					<label for="zone-label" class="form-label">Libelle <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="zone-label" name="label" required
						   placeholder="Ex: France metropolitaine">
				</div>
				<div class="col-md-4">
					<label for="zone-countries" class="form-label">Pays</label>
					<textarea class="form-control" id="zone-countries" name="countries" rows="2"
							  placeholder="Un pays par ligne ou separes par des virgules (ex: FR, BE, CH)"></textarea>
				</div>
				<div class="col-md-2">
					<label for="zone-status" class="form-label">Statut</label>
					<select class="form-select" id="zone-status" name="status">
						<option value="1">Actif</option>
						<option value="0">Inactif</option>
					</select>
				</div>
				<div class="col-md-2 d-flex align-items-end">
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-plus-lg me-1"></i>Ajouter
					</button>
				</div>
			</div>
		</form>
	</div>
</div>

<!-- Zones Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th>Libelle</th>
						<th>Pays</th>
						<th class="text-center">Statut</th>
						<th class="text-center" style="width:120px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($zones)): ?>
					<tr>
						<td colspan="4">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-globe"></i></div>
								<p>Aucune zone de livraison configuree</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($zones as $zone): ?>
						<tr>
							<td><strong><?php echo spacartAdminEscape($zone->label); ?></strong></td>
							<td>
								<?php if (!empty($zone->countries)): ?>
									<?php echo spacartAdminEscape(spacartAdminTruncate($zone->countries, 80)); ?>
								<?php else: ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
							<td class="text-center">
								<?php if ((int) $zone->status === 1): ?>
									<span class="badge badge-status status-active">Actif</span>
								<?php else: ?>
									<span class="badge badge-status status-inactive">Inactif</span>
								<?php endif; ?>
							</td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Toggle status -->
									<form method="post" action="?page=shipping&tab=zones" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_zone">
										<input type="hidden" name="zone_id" value="<?php echo (int) $zone->rowid; ?>">
										<input type="hidden" name="new_status" value="<?php echo ((int) $zone->status === 1) ? '0' : '1'; ?>">
										<?php if ((int) $zone->status === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Desactiver" aria-label="Desactiver la zone">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Activer" aria-label="Activer la zone">
												<i class="bi bi-toggle-off"></i>
											</button>
										<?php endif; ?>
									</form>

									<!-- Delete -->
									<form method="post" action="?page=shipping&tab=zones" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete_zone">
										<input type="hidden" name="zone_id" value="<?php echo (int) $zone->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer la zone"
												data-confirm="Supprimer cette zone de livraison ?">
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
</div>

<?php endif; ?>

<?php
include __DIR__.'/../includes/footer.php';
