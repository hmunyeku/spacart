<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/banners.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Banner management (CRUD, toggle status, position ordering)
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title   = 'Bannieres';
$current_page = 'banners';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// Location options
$location_options = array(
	'home'     => 'Accueil',
	'category' => 'Categorie',
	'product'  => 'Produit',
);

// ============================================================
// Determine mode: list vs add/edit
// ============================================================
$mode = 'list';
$edit_id = 0;
if (isset($_GET['action']) && $_GET['action'] === 'add') {
	$mode = 'form';
}
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
	$mode = 'form';
	$edit_id = (int) $_GET['id'];
}

// ============================================================
// POST actions
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=banners');
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Save (add or update) ---
	if ($action === 'save') {
		$id       = (int) ($_POST['id'] ?? 0);
		$title    = trim($_POST['title'] ?? '');
		$image    = trim($_POST['image'] ?? '');
		$link     = trim($_POST['link'] ?? '');
		$location = trim($_POST['location'] ?? 'home');
		$position = max(0, (int) ($_POST['position'] ?? 0));
		$status   = in_array((int) ($_POST['status'] ?? 0), array(0, 1)) ? (int) $_POST['status'] : 0;

		if ($title === '') {
			spacartAdminFlash('Le titre est obligatoire.', 'danger');
			header('Location: ?page=banners&action='.($id > 0 ? 'edit&id='.$id : 'add'));
			exit;
		}

		// Validate location
		if (!array_key_exists($location, $location_options)) {
			$location = 'home';
		}

		if ($id > 0) {
			// Update
			$sql = "UPDATE ".$prefix."spacart_banner SET";
			$sql .= " title = '".$db->escape($title)."'";
			$sql .= ", image = '".$db->escape($image)."'";
			$sql .= ", link = '".$db->escape($link)."'";
			$sql .= ", location = '".$db->escape($location)."'";
			$sql .= ", position = ".$position;
			$sql .= ", status = ".$status;
			$sql .= " WHERE rowid = ".$id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Banniere mise a jour avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour de la banniere.', 'danger');
			}
		} else {
			// Insert
			$sql = "INSERT INTO ".$prefix."spacart_banner";
			$sql .= " (title, image, link, location, position, status, entity, date_creation)";
			$sql .= " VALUES (";
			$sql .= "'".$db->escape($title)."'";
			$sql .= ", '".$db->escape($image)."'";
			$sql .= ", '".$db->escape($link)."'";
			$sql .= ", '".$db->escape($location)."'";
			$sql .= ", ".$position;
			$sql .= ", ".$status;
			$sql .= ", ".$entity;
			$sql .= ", NOW()";
			$sql .= ")";

			if ($db->query($sql)) {
				spacartAdminFlash('Banniere ajoutee avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de l\'ajout de la banniere.', 'danger');
			}
		}

		header('Location: ?page=banners');
		exit;
	}

	// --- Toggle status ---
	if ($action === 'toggle_status' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		$new_status = (int) ($_POST['new_status'] ?? 0);
		if (in_array($new_status, array(0, 1))) {
			$sql = "UPDATE ".$prefix."spacart_banner SET status = ".$new_status;
			$sql .= " WHERE rowid = ".$id." AND entity = ".$entity;
			if ($db->query($sql)) {
				$status_label = ($new_status === 1) ? 'activee' : 'desactivee';
				spacartAdminFlash('Banniere '.$status_label.'.', 'success');
			} else {
				spacartAdminFlash('Erreur lors du changement de statut.', 'danger');
			}
		}
		header('Location: ?page=banners');
		exit;
	}

	// --- Update positions (bulk) ---
	if ($action === 'update_positions' && !empty($_POST['positions']) && is_array($_POST['positions'])) {
		$errors = 0;
		foreach ($_POST['positions'] as $banner_id => $pos) {
			$banner_id = (int) $banner_id;
			$pos = (int) $pos;
			if ($banner_id > 0) {
				$sql = "UPDATE ".$prefix."spacart_banner SET position = ".$pos;
				$sql .= " WHERE rowid = ".$banner_id." AND entity = ".$entity;
				if (!$db->query($sql)) {
					$errors++;
				}
			}
		}
		if ($errors === 0) {
			spacartAdminFlash('Positions mises a jour.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de la mise a jour de certaines positions.', 'danger');
		}
		header('Location: ?page=banners');
		exit;
	}

	// --- Delete ---
	if ($action === 'delete' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		$sql = "DELETE FROM ".$prefix."spacart_banner WHERE rowid = ".$id." AND entity = ".$entity;
		if ($db->query($sql)) {
			spacartAdminFlash('Banniere supprimee.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de la suppression.', 'danger');
		}
		header('Location: ?page=banners');
		exit;
	}
}

// ============================================================
// CSRF token
// ============================================================
$csrf_token = spacartAdminGetCSRFToken();

// ============================================================
// FORM MODE: load existing banner for edit
// ============================================================
$edit_item = null;
if ($mode === 'form' && $edit_id > 0) {
	$sql = "SELECT rowid, title, image, link, location, position, status";
	$sql .= " FROM ".$prefix."spacart_banner";
	$sql .= " WHERE rowid = ".$edit_id;
	$sql .= " AND entity = ".$entity;
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0) {
		$edit_item = $db->fetch_object($resql);
	} else {
		spacartAdminFlash('Banniere introuvable.', 'danger');
		header('Location: ?page=banners');
		exit;
	}
}

// ============================================================
// LIST MODE: fetch banners ordered by position
// ============================================================
$banners = array();
$total_banners = 0;

if ($mode === 'list') {
	// Count
	$sql_count = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_banner WHERE entity = ".$entity;
	$resql_count = $db->query($sql_count);
	if ($resql_count) {
		$obj_count = $db->fetch_object($resql_count);
		$total_banners = (int) $obj_count->nb;
	}

	// Fetch all banners ordered by position (no pagination needed usually, but included for consistency)
	$sql = "SELECT b.rowid, b.title, b.image, b.link, b.location, b.position, b.status, b.date_creation";
	$sql .= " FROM ".$prefix."spacart_banner as b";
	$sql .= " WHERE b.entity = ".$entity;
	$sql .= " ORDER BY b.position ASC, b.date_creation DESC";

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$banners[] = $obj;
		}
		$db->free($resql);
	}
}

// ============================================================
// Include header
// ============================================================
include __DIR__.'/../includes/header.php';
?>

<?php if ($mode === 'form'): ?>
<!-- ============================================================== -->
<!-- ADD / EDIT FORM -->
<!-- ============================================================== -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1"><?php echo $edit_id > 0 ? 'Modifier la banniere' : 'Ajouter une banniere'; ?></h1>
		<p class="text-muted mb-0">
			<a href="?page=banners" class="text-decoration-none">
				<i class="bi bi-arrow-left me-1"></i>Retour a la liste
			</a>
		</p>
	</div>
</div>

<div class="admin-card">
	<div class="card-body">
		<form method="post" action="?page=banners">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="save">
			<input type="hidden" name="id" value="<?php echo (int) $edit_id; ?>">

			<div class="row g-3">
				<!-- Title -->
				<div class="col-md-6">
					<label for="field-title" class="form-label">Titre <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="field-title" name="title"
						   value="<?php echo spacartAdminEscape($edit_item ? $edit_item->title : ''); ?>"
						   required>
				</div>

				<!-- Location -->
				<div class="col-md-3">
					<label for="field-location" class="form-label">Emplacement</label>
					<select class="form-select" id="field-location" name="location">
						<?php foreach ($location_options as $loc_val => $loc_label): ?>
							<option value="<?php echo spacartAdminEscape($loc_val); ?>"<?php echo ($edit_item && $edit_item->location === $loc_val) ? ' selected' : (!$edit_item && $loc_val === 'home' ? ' selected' : ''); ?>>
								<?php echo spacartAdminEscape($loc_label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Position -->
				<div class="col-md-3">
					<label for="field-position" class="form-label">Position</label>
					<input type="number" class="form-control" id="field-position" name="position"
						   value="<?php echo (int) ($edit_item ? $edit_item->position : 0); ?>"
						   min="0" step="1">
					<div class="form-hint">
						<small class="text-muted">Ordre d'affichage (0 = premier)</small>
					</div>
				</div>

				<!-- Image URL -->
				<div class="col-md-8">
					<label for="field-image" class="form-label">URL de l'image</label>
					<input type="url" class="form-control" id="field-image" name="image"
						   value="<?php echo spacartAdminEscape($edit_item ? $edit_item->image : ''); ?>"
						   placeholder="https://...">
				</div>

				<!-- Status -->
				<div class="col-md-4">
					<label for="field-status" class="form-label">Statut</label>
					<select class="form-select" id="field-status" name="status">
						<option value="1"<?php echo ($edit_item && (int) $edit_item->status === 1) ? ' selected' : (!$edit_item ? ' selected' : ''); ?>>Actif</option>
						<option value="0"<?php echo ($edit_item && (int) $edit_item->status === 0) ? ' selected' : ''; ?>>Inactif</option>
					</select>
				</div>

				<!-- Link URL -->
				<div class="col-12">
					<label for="field-link" class="form-label">Lien (URL)</label>
					<input type="url" class="form-control" id="field-link" name="link"
						   value="<?php echo spacartAdminEscape($edit_item ? $edit_item->link : ''); ?>"
						   placeholder="https://...">
					<div class="form-hint">
						<small class="text-muted">URL de destination au clic sur la banniere (optionnel)</small>
					</div>
				</div>

				<?php if ($edit_item && !empty($edit_item->image)): ?>
				<!-- Image preview -->
				<div class="col-12">
					<label class="form-label">Apercu</label>
					<div>
						<img src="<?php echo spacartAdminEscape($edit_item->image); ?>"
							 alt="<?php echo spacartAdminEscape($edit_item->title); ?>"
							 style="max-width:300px; max-height:150px; object-fit:contain; border:1px solid #dee2e6; border-radius:4px;"
							 onerror="this.style.display='none'">
					</div>
				</div>
				<?php endif; ?>
			</div>

			<div class="d-flex justify-content-end mt-4">
				<a href="?page=banners" class="btn btn-outline-secondary me-2">Annuler</a>
				<button type="submit" class="btn btn-primary">
					<i class="bi bi-check-lg me-1"></i><?php echo $edit_id > 0 ? 'Mettre a jour' : 'Ajouter'; ?>
				</button>
			</div>
		</form>
	</div>
</div>

<?php else: ?>
<!-- ============================================================== -->
<!-- LIST MODE -->
<!-- ============================================================== -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1">Bannieres</h1>
		<p class="text-muted mb-0"><?php echo (int) $total_banners; ?> banniere<?php echo $total_banners > 1 ? 's' : ''; ?> au total</p>
	</div>
	<a href="?page=banners&amp;action=add" class="btn btn-primary">
		<i class="bi bi-plus-lg me-1"></i>Ajouter une banniere
	</a>
</div>

<!-- Position reorder form (separate from action forms) -->
<form method="post" action="?page=banners" id="positionsForm" class="mb-3">
	<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
	<input type="hidden" name="action" value="update_positions">
	<?php foreach ($banners as $banner): ?>
		<input type="hidden" class="pos-field" name="positions[<?php echo (int) $banner->rowid; ?>]"
			   id="pos-hidden-<?php echo (int) $banner->rowid; ?>"
			   value="<?php echo (int) $banner->position; ?>">
	<?php endforeach; ?>
</form>

<!-- Banners table -->
<div class="admin-card">
	<div class="card-body p-0">
		<!-- Bulk position save bar -->
		<div class="d-flex align-items-center gap-2 p-3 border-bottom">
			<button type="submit" form="positionsForm" class="btn btn-outline-primary btn-sm">
				<i class="bi bi-arrow-down-up me-1"></i>Enregistrer les positions
			</button>
		</div>

		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th style="width:70px;">Position</th>
						<th style="width:80px;">Image</th>
						<th>Titre</th>
						<th>Lien</th>
						<th>Emplacement</th>
						<th class="text-center">Statut</th>
						<th class="text-center" style="width:150px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($banners)): ?>
					<tr>
						<td colspan="7">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-image"></i></div>
								<p>Aucune banniere trouvee</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($banners as $banner): ?>
						<tr>
							<td>
								<input type="number" class="form-control form-control-sm" style="width:60px;"
									   value="<?php echo (int) $banner->position; ?>"
									   min="0"
									   onchange="document.getElementById('pos-hidden-<?php echo (int) $banner->rowid; ?>').value=this.value;">
							</td>
							<td>
								<?php if (!empty($banner->image)): ?>
									<img src="<?php echo spacartAdminEscape($banner->image); ?>"
										 alt="<?php echo spacartAdminEscape($banner->title); ?>"
										 style="width:60px; height:40px; object-fit:cover; border-radius:4px;"
										 onerror="this.src='data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2240%22%3E%3Crect fill=%22%23dee2e6%22 width=%2260%22 height=%2240%22/%3E%3C/svg%3E'">
								<?php else: ?>
									<span class="d-inline-flex align-items-center justify-content-center bg-light" style="width:60px; height:40px; border-radius:4px;">
										<i class="bi bi-image text-muted"></i>
									</span>
								<?php endif; ?>
							</td>
							<td><strong><?php echo spacartAdminEscape($banner->title); ?></strong></td>
							<td>
								<?php if (!empty($banner->link)): ?>
									<a href="<?php echo spacartAdminEscape($banner->link); ?>" target="_blank" class="text-decoration-none">
										<?php echo spacartAdminEscape(spacartAdminTruncate($banner->link, 40)); ?>
										<i class="bi bi-box-arrow-up-right ms-1 small"></i>
									</a>
								<?php else: ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo spacartAdminEscape(isset($location_options[$banner->location]) ? $location_options[$banner->location] : $banner->location); ?>
							</td>
							<td class="text-center">
								<?php if ((int) $banner->status === 1): ?>
									<?php echo spacartAdminStatusBadge('active', 'Actif'); ?>
								<?php else: ?>
									<?php echo spacartAdminStatusBadge('inactive', 'Inactif'); ?>
								<?php endif; ?>
							</td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Edit -->
									<a href="?page=banners&amp;action=edit&amp;id=<?php echo (int) $banner->rowid; ?>"
									   class="btn btn-sm btn-outline-primary" title="Modifier" aria-label="Modifier la banniere">
										<i class="bi bi-pencil"></i>
									</a>

									<!-- Toggle status -->
									<form method="post" action="?page=banners" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_status">
										<input type="hidden" name="id" value="<?php echo (int) $banner->rowid; ?>">
										<input type="hidden" name="new_status" value="<?php echo ((int) $banner->status === 1) ? '0' : '1'; ?>">
										<?php if ((int) $banner->status === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Desactiver" aria-label="Desactiver la banniere">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Activer" aria-label="Activer la banniere">
												<i class="bi bi-toggle-off"></i>
											</button>
										<?php endif; ?>
									</form>

									<!-- Delete -->
									<form method="post" action="?page=banners" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="id" value="<?php echo (int) $banner->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer la banniere"
												data-confirm="Supprimer cette banniere ?">
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
