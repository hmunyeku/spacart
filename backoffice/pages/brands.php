<?php
/**
 * SpaCart Backoffice - Marques (Brands)
 *
 * Manage brand categories. Brands are Dolibarr categories under the parent
 * category defined by SPACART_BRAND_CATEGORY_ID.
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Marques';
$current_page = 'brands';

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// Brand parent category ID from Dolibarr config
$brand_parent = getDolGlobalInt('SPACART_BRAND_CATEGORY_ID', 0);

// =====================================================================
// POST actions (CSRF-protected)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Add new brand ---
	if ($action === 'add_brand') {
		$label       = isset($_POST['label']) ? trim($_POST['label']) : '';
		$description = isset($_POST['description']) ? trim($_POST['description']) : '';

		if ($brand_parent <= 0) {
			spacartAdminFlash('La categorie parente des marques (SPACART_BRAND_CATEGORY_ID) n\'est pas configuree. Allez dans Configuration.', 'danger');
		} elseif ($label === '') {
			spacartAdminFlash('Le nom de la marque est obligatoire.', 'warning');
		} else {
			$sql = "INSERT INTO ".$prefix."categorie (entity, label, description, fk_parent, type, visible, date_creation)";
			$sql .= " VALUES (".$entity.", '".$db->escape($label)."', '".$db->escape($description)."', ".$brand_parent.", 0, 1, NOW())";
			$resql = $db->query($sql);
			if ($resql) {
				spacartAdminFlash('Marque "'.$label.'" ajoutee avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la creation de la marque.', 'danger');
			}
		}
		header('Location: ?page=brands');
		exit;
	}

	// --- Edit brand ---
	if ($action === 'edit_brand') {
		$edit_id     = (int) ($_POST['brand_id'] ?? 0);
		$label       = isset($_POST['label']) ? trim($_POST['label']) : '';
		$description = isset($_POST['description']) ? trim($_POST['description']) : '';

		if ($edit_id <= 0 || $label === '') {
			spacartAdminFlash('Donnees invalides pour la modification.', 'warning');
		} else {
			$sql = "UPDATE ".$prefix."categorie SET";
			$sql .= " label = '".$db->escape($label)."'";
			$sql .= ", description = '".$db->escape($description)."'";
			$sql .= " WHERE rowid = ".$edit_id;
			$sql .= " AND entity = ".$entity;
			$sql .= " AND type = 0";
			$sql .= " AND fk_parent = ".$brand_parent;
			$resql = $db->query($sql);
			if ($resql) {
				spacartAdminFlash('Marque modifiee avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la modification.', 'danger');
			}
		}
		header('Location: ?page=brands');
		exit;
	}

	// --- Delete brand ---
	if ($action === 'delete_brand') {
		$del_id = (int) ($_POST['brand_id'] ?? 0);
		if ($del_id > 0) {
			// Remove product-category links for this brand
			$db->query("DELETE FROM ".$prefix."categorie_product WHERE fk_categorie = ".$del_id);
			// Delete the brand category (only if it belongs to the brand parent)
			$sql = "DELETE FROM ".$prefix."categorie WHERE rowid = ".$del_id;
			$sql .= " AND entity = ".$entity;
			$sql .= " AND type = 0";
			$sql .= " AND fk_parent = ".$brand_parent;
			$resql = $db->query($sql);
			if ($resql) {
				spacartAdminFlash('Marque supprimee.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la suppression.', 'danger');
			}
		}
		header('Location: ?page=brands');
		exit;
	}
}

// =====================================================================
// Filters from GET
// =====================================================================
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';
$pg       = isset($_GET['pg']) ? (int) $_GET['pg'] : 1;
$per_page = 20;
$edit_id  = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

// =====================================================================
// Build WHERE clause
// =====================================================================
$where = "c.entity = ".$entity." AND c.type = 0 AND c.fk_parent = ".$brand_parent;

if ($search !== '') {
	$s = $db->escape($search);
	$where .= " AND (c.label LIKE '%".$s."%' OR c.description LIKE '%".$s."%')";
}

// =====================================================================
// Pagination
// =====================================================================
$sql_count = "SELECT COUNT(*) as nb FROM ".$prefix."categorie AS c WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// =====================================================================
// Fetch brands
// =====================================================================
$brands = array();
if ($brand_parent > 0) {
	$sql = "SELECT c.rowid, c.label, c.description, c.visible, c.date_creation";
	$sql .= " FROM ".$prefix."categorie AS c";
	$sql .= " WHERE ".$where;
	$sql .= " ORDER BY c.label ASC";
	$sql .= " LIMIT ".$pagination['limit']." OFFSET ".$pagination['offset'];

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$brands[] = $obj;
		}
	}
}

// Brand being edited
$edit_brand = null;
if ($edit_id > 0 && $brand_parent > 0) {
	$sql_edit = "SELECT rowid, label, description FROM ".$prefix."categorie";
	$sql_edit .= " WHERE rowid = ".$edit_id." AND entity = ".$entity." AND type = 0 AND fk_parent = ".$brand_parent;
	$resql_edit = $db->query($sql_edit);
	if ($resql_edit && $db->num_rows($resql_edit) > 0) {
		$edit_brand = $db->fetch_object($resql_edit);
	}
}

// Category photos directory (Dolibarr stores category images in categorie/)
$cat_photos_url = DOL_URL_ROOT.'/viewimage.php?modulepart=category&entity='.$entity.'&file=';

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
	<h1 class="h3 mb-2 mb-md-0"><i class="bi bi-award me-2"></i>Marques</h1>
	<?php if ($brand_parent > 0): ?>
		<button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addBrandForm">
			<i class="bi bi-plus-lg me-1"></i>Ajouter une marque
		</button>
	<?php endif; ?>
</div>

<!-- Warning if brand parent not configured -->
<?php if ($brand_parent <= 0): ?>
<div class="alert alert-warning d-flex align-items-center" role="alert">
	<i class="bi bi-exclamation-triangle me-2 fs-5"></i>
	<div>
		La constante <strong>SPACART_BRAND_CATEGORY_ID</strong> n'est pas configuree.
		Veuillez definir la categorie parente des marques dans
		<a href="?page=settings" class="alert-link">Configuration</a>.
	</div>
</div>
<?php else: ?>

<!-- Add Brand Form (collapsible) -->
<div class="collapse mb-4" id="addBrandForm">
	<div class="admin-card">
		<div class="card-header">
			<h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nouvelle marque</h5>
		</div>
		<div class="card-body">
			<form method="post">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="add_brand">
				<div class="row g-3">
					<div class="col-md-5">
						<label class="form-label" for="add-label">Nom de la marque <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="add-label" name="label" required maxlength="255" placeholder="Ex: Nike, Samsung...">
					</div>
					<div class="col-md-7">
						<label class="form-label" for="add-description">Description</label>
						<input type="text" class="form-control" id="add-description" name="description" maxlength="500" placeholder="Description courte de la marque">
					</div>
				</div>
				<div class="mt-3">
					<button type="submit" class="btn btn-success">
						<i class="bi bi-check-lg me-1"></i>Creer
					</button>
					<button type="button" class="btn btn-outline-secondary ms-2" data-bs-toggle="collapse" data-bs-target="#addBrandForm">
						Annuler
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Edit Brand Form (shown when edit=ID) -->
<?php if ($edit_brand): ?>
<div class="mb-4">
	<div class="admin-card border-primary">
		<div class="card-header bg-primary bg-opacity-10">
			<h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Modifier la marque #<?php echo (int) $edit_id; ?></h5>
		</div>
		<div class="card-body">
			<form method="post">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="edit_brand">
				<input type="hidden" name="brand_id" value="<?php echo (int) $edit_id; ?>">
				<div class="row g-3">
					<div class="col-md-5">
						<label class="form-label" for="edit-label">Nom de la marque <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit-label" name="label" required maxlength="255" value="<?php echo spacartAdminEscape($edit_brand->label); ?>">
					</div>
					<div class="col-md-7">
						<label class="form-label" for="edit-description">Description</label>
						<input type="text" class="form-control" id="edit-description" name="description" maxlength="500" value="<?php echo spacartAdminEscape($edit_brand->description ?? ''); ?>">
					</div>
				</div>
				<div class="mt-3">
					<button type="submit" class="btn btn-primary">
						<i class="bi bi-check-lg me-1"></i>Enregistrer
					</button>
					<a href="?page=brands" class="btn btn-outline-secondary ms-2">Annuler</a>
				</div>
			</form>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="brands">
			<div class="row g-3 align-items-end">
				<div class="col-md-6 col-lg-4">
					<label class="form-label" for="filter-search">Recherche</label>
					<input type="text" class="form-control" id="filter-search" name="search" value="<?php echo spacartAdminEscape($search); ?>" placeholder="Nom de marque...">
				</div>
				<div class="col-md-3 col-lg-2">
					<button type="submit" class="btn btn-outline-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>
				<?php if ($search !== ''): ?>
					<div class="col-md-3 col-lg-2">
						<a href="?page=brands" class="btn btn-outline-secondary w-100">
							<i class="bi bi-x-lg me-1"></i>Effacer
						</a>
					</div>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- Brands Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th style="width: 60px;">ID</th>
						<th style="width: 70px;">Photo</th>
						<th>Nom</th>
						<th>Description</th>
						<th class="text-center">Visible</th>
						<th>Date creation</th>
						<th class="text-center" style="width: 140px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($brands)): ?>
						<tr>
							<td colspan="7">
								<div class="empty-state-inline">
									<div class="empty-state-icon"><i class="bi bi-award"></i></div>
									<p>Aucune marque trouvee</p>
									<small class="text-muted">Ajoutez une marque ou modifiez vos filtres</small>
								</div>
							</td>
						</tr>
					<?php else: ?>
						<?php foreach ($brands as $brand): ?>
							<?php
							// Try to find category photo
							$photo_html = '<div class="product-thumb-placeholder"><i class="bi bi-image text-muted"></i></div>';
							$cat_dir = DOL_DATA_ROOT.'/categorie/'.(int) $brand->rowid.'/';
							if (is_dir($cat_dir)) {
								$images = glob($cat_dir.'*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
								if (!empty($images)) {
									$img_file = basename($images[0]);
									$img_url = $cat_photos_url.urlencode((int) $brand->rowid.'/'.$img_file);
									$photo_html = '<img src="'.$img_url.'" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">';
								}
							}
							?>
							<tr>
								<td><strong><?php echo (int) $brand->rowid; ?></strong></td>
								<td><?php echo $photo_html; ?></td>
								<td><?php echo spacartAdminEscape($brand->label); ?></td>
								<td><?php echo spacartAdminEscape(spacartAdminTruncate($brand->description ?? '', 80)); ?></td>
								<td class="text-center">
									<?php if ((int) $brand->visible === 1): ?>
										<?php echo spacartAdminStatusBadge('active', 'Oui'); ?>
									<?php else: ?>
										<?php echo spacartAdminStatusBadge('inactive', 'Non'); ?>
									<?php endif; ?>
								</td>
								<td><?php echo spacartAdminFormatDate($brand->date_creation); ?></td>
								<td class="text-center">
									<div class="d-flex justify-content-center gap-1">
										<!-- Edit -->
										<a href="?page=brands&edit=<?php echo (int) $brand->rowid; ?>" class="btn btn-sm btn-outline-primary" title="Modifier" aria-label="Modifier la marque">
											<i class="bi bi-pencil"></i>
										</a>
										<!-- Delete -->
										<form method="post" class="d-inline">
											<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
											<input type="hidden" name="action" value="delete_brand">
											<input type="hidden" name="brand_id" value="<?php echo (int) $brand->rowid; ?>">
											<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer la marque" data-confirm="Supprimer cette marque ? Les produits associes seront detaches.">
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

	<!-- Pagination -->
	<?php if ($pagination['total_pages'] > 1): ?>
		<div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
			<small class="text-muted">
				<?php echo $pagination['total']; ?> marque(s) -
				Page <?php echo $pagination['current_page']; ?> / <?php echo $pagination['total_pages']; ?>
			</small>
			<nav aria-label="Pagination marques">
				<ul class="pagination pagination-sm mb-0">
					<?php
					$base_url = '?page=brands';
					if ($search !== '') {
						$base_url .= '&search='.urlencode($search);
					}
					?>
					<!-- Previous -->
					<li class="page-item <?php echo ($pagination['current_page'] <= 1) ? 'disabled' : ''; ?>">
						<a class="page-link" href="<?php echo $base_url.'&pg='.($pagination['current_page'] - 1); ?>" aria-label="Precedent">
							<i class="bi bi-chevron-left"></i>
						</a>
					</li>
					<?php
					$total_pages = $pagination['total_pages'];
					$cp = $pagination['current_page'];
					$start = max(1, $cp - 2);
					$end = min($total_pages, $cp + 2);

					if ($start > 1) {
						echo '<li class="page-item"><a class="page-link" href="'.$base_url.'&pg=1">1</a></li>';
						if ($start > 2) {
							echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
						}
					}

					for ($i = $start; $i <= $end; $i++) {
						$active = ($i === $cp) ? ' active' : '';
						echo '<li class="page-item'.$active.'"><a class="page-link" href="'.$base_url.'&pg='.$i.'">'.$i.'</a></li>';
					}

					if ($end < $total_pages) {
						if ($end < $total_pages - 1) {
							echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
						}
						echo '<li class="page-item"><a class="page-link" href="'.$base_url.'&pg='.$total_pages.'">'.$total_pages.'</a></li>';
					}
					?>
					<!-- Next -->
					<li class="page-item <?php echo ($pagination['current_page'] >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
						<a class="page-link" href="<?php echo $base_url.'&pg='.($pagination['current_page'] + 1); ?>" aria-label="Suivant">
							<i class="bi bi-chevron-right"></i>
						</a>
					</li>
				</ul>
			</nav>
		</div>
	<?php endif; ?>
</div>

<?php endif; /* end brand_parent > 0 check */ ?>

<?php
include __DIR__.'/../includes/footer.php';
