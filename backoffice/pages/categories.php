<?php
/**
 * SpaCart Backoffice - Categories
 *
 * Manage Dolibarr product categories (llx_categorie, type=0).
 * List, add, edit inline, and delete categories.
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Categories';
$current_page = 'categories';

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// =====================================================================
// POST actions (CSRF-protected)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Add new category ---
	if ($action === 'add_category') {
		$label       = isset($_POST['label']) ? trim($_POST['label']) : '';
		$description = isset($_POST['description']) ? trim($_POST['description']) : '';
		$fk_parent   = isset($_POST['fk_parent']) ? (int) $_POST['fk_parent'] : 0;

		if ($label === '') {
			spacartAdminFlash('Le libelle de la categorie est obligatoire.', 'warning');
		} else {
			$sql = "INSERT INTO ".$prefix."categorie (entity, label, description, fk_parent, type, visible, date_creation)";
			$sql .= " VALUES (".$entity.", '".$db->escape($label)."', '".$db->escape($description)."', ".$fk_parent.", 0, 1, NOW())";
			$resql = $db->query($sql);
			if ($resql) {
				spacartAdminFlash('Categorie "'.$label.'" ajoutee avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la creation de la categorie.', 'danger');
			}
		}
		header('Location: ?page=categories');
		exit;
	}

	// --- Edit category ---
	if ($action === 'edit_category') {
		$edit_id     = (int) ($_POST['category_id'] ?? 0);
		$label       = isset($_POST['label']) ? trim($_POST['label']) : '';
		$description = isset($_POST['description']) ? trim($_POST['description']) : '';
		$fk_parent   = isset($_POST['fk_parent']) ? (int) $_POST['fk_parent'] : 0;

		if ($edit_id <= 0 || $label === '') {
			spacartAdminFlash('Donnees invalides pour la modification.', 'warning');
		} else {
			// Prevent setting parent to self
			if ($fk_parent === $edit_id) {
				$fk_parent = 0;
			}
			$sql = "UPDATE ".$prefix."categorie SET";
			$sql .= " label = '".$db->escape($label)."'";
			$sql .= ", description = '".$db->escape($description)."'";
			$sql .= ", fk_parent = ".$fk_parent;
			$sql .= " WHERE rowid = ".$edit_id;
			$sql .= " AND entity = ".$entity;
			$sql .= " AND type = 0";
			$resql = $db->query($sql);
			if ($resql) {
				spacartAdminFlash('Categorie modifiee avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la modification.', 'danger');
			}
		}
		header('Location: ?page=categories');
		exit;
	}

	// --- Delete category ---
	if ($action === 'delete_category') {
		$del_id = (int) ($_POST['category_id'] ?? 0);
		if ($del_id > 0) {
			// First remove product-category links
			$db->query("DELETE FROM ".$prefix."categorie_product WHERE fk_categorie = ".$del_id);
			// Remove child links (re-parent children to root)
			$db->query("UPDATE ".$prefix."categorie SET fk_parent = 0 WHERE fk_parent = ".$del_id." AND entity = ".$entity);
			// Delete the category itself
			$sql = "DELETE FROM ".$prefix."categorie WHERE rowid = ".$del_id." AND entity = ".$entity." AND type = 0";
			$resql = $db->query($sql);
			if ($resql) {
				spacartAdminFlash('Categorie supprimee.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la suppression.', 'danger');
			}
		}
		header('Location: ?page=categories');
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
$where = "c.entity = ".$entity." AND c.type = 0";

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
// Fetch categories
// =====================================================================
$categories = array();
$sql = "SELECT c.rowid, c.label, c.description, c.fk_parent, c.visible, c.date_creation";
$sql .= " FROM ".$prefix."categorie AS c";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY c.label ASC";
$sql .= " LIMIT ".$pagination['limit']." OFFSET ".$pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$categories[] = $obj;
	}
}

// =====================================================================
// Fetch all categories for parent dropdown (exclude current when editing)
// =====================================================================
$all_categories = array();
$sql_all = "SELECT rowid, label, fk_parent FROM ".$prefix."categorie WHERE type = 0 AND entity = ".$entity." ORDER BY label ASC";
$resql_all = $db->query($sql_all);
if ($resql_all) {
	while ($obj = $db->fetch_object($resql_all)) {
		$all_categories[$obj->rowid] = $obj;
	}
}

// Build a parent name lookup
$parent_names = array();
foreach ($all_categories as $cat) {
	$parent_names[(int) $cat->rowid] = $cat->label;
}

// Category being edited
$edit_cat = null;
if ($edit_id > 0 && isset($all_categories[$edit_id])) {
	$edit_cat = $all_categories[$edit_id];
	// Fetch full description for edit
	$sql_edit = "SELECT description FROM ".$prefix."categorie WHERE rowid = ".$edit_id." AND entity = ".$entity;
	$resql_edit = $db->query($sql_edit);
	if ($resql_edit) {
		$obj_edit = $db->fetch_object($resql_edit);
		if ($obj_edit) {
			$edit_cat->description = $obj_edit->description;
		}
	}
}

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
	<h1 class="h3 mb-2 mb-md-0"><i class="bi bi-folder me-2"></i>Categories</h1>
	<button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addCategoryForm">
		<i class="bi bi-plus-lg me-1"></i>Ajouter une categorie
	</button>
</div>

<!-- Add Category Form (collapsible) -->
<div class="collapse mb-4" id="addCategoryForm">
	<div class="admin-card">
		<div class="card-header">
			<h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nouvelle categorie</h5>
		</div>
		<div class="card-body">
			<form method="post">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="add_category">
				<div class="row g-3">
					<div class="col-md-4">
						<label class="form-label" for="add-label">Libelle <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="add-label" name="label" required maxlength="255" placeholder="Nom de la categorie">
					</div>
					<div class="col-md-4">
						<label class="form-label" for="add-parent">Categorie parente</label>
						<select class="form-select" id="add-parent" name="fk_parent">
							<option value="0">-- Racine --</option>
							<?php foreach ($all_categories as $cat): ?>
								<option value="<?php echo (int) $cat->rowid; ?>">
									<?php echo spacartAdminEscape($cat->label); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-4">
						<label class="form-label" for="add-description">Description</label>
						<input type="text" class="form-control" id="add-description" name="description" maxlength="500" placeholder="Description courte">
					</div>
				</div>
				<div class="mt-3">
					<button type="submit" class="btn btn-success">
						<i class="bi bi-check-lg me-1"></i>Creer
					</button>
					<button type="button" class="btn btn-outline-secondary ms-2" data-bs-toggle="collapse" data-bs-target="#addCategoryForm">
						Annuler
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Edit Category Form (shown when edit=ID) -->
<?php if ($edit_cat): ?>
<div class="mb-4">
	<div class="admin-card border-primary">
		<div class="card-header bg-primary bg-opacity-10">
			<h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Modifier la categorie #<?php echo (int) $edit_id; ?></h5>
		</div>
		<div class="card-body">
			<form method="post">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="edit_category">
				<input type="hidden" name="category_id" value="<?php echo (int) $edit_id; ?>">
				<div class="row g-3">
					<div class="col-md-4">
						<label class="form-label" for="edit-label">Libelle <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit-label" name="label" required maxlength="255" value="<?php echo spacartAdminEscape($edit_cat->label); ?>">
					</div>
					<div class="col-md-4">
						<label class="form-label" for="edit-parent">Categorie parente</label>
						<select class="form-select" id="edit-parent" name="fk_parent">
							<option value="0">-- Racine --</option>
							<?php foreach ($all_categories as $cat): ?>
								<?php if ((int) $cat->rowid !== $edit_id): ?>
									<option value="<?php echo (int) $cat->rowid; ?>" <?php echo ((int) $edit_cat->fk_parent === (int) $cat->rowid) ? 'selected' : ''; ?>>
										<?php echo spacartAdminEscape($cat->label); ?>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-4">
						<label class="form-label" for="edit-description">Description</label>
						<input type="text" class="form-control" id="edit-description" name="description" maxlength="500" value="<?php echo spacartAdminEscape($edit_cat->description ?? ''); ?>">
					</div>
				</div>
				<div class="mt-3">
					<button type="submit" class="btn btn-primary">
						<i class="bi bi-check-lg me-1"></i>Enregistrer
					</button>
					<a href="?page=categories" class="btn btn-outline-secondary ms-2">Annuler</a>
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
			<input type="hidden" name="page" value="categories">
			<div class="row g-3 align-items-end">
				<div class="col-md-6 col-lg-4">
					<label class="form-label" for="filter-search">Recherche</label>
					<input type="text" class="form-control" id="filter-search" name="search" value="<?php echo spacartAdminEscape($search); ?>" placeholder="Libelle, description...">
				</div>
				<div class="col-md-3 col-lg-2">
					<button type="submit" class="btn btn-outline-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>
				<?php if ($search !== ''): ?>
					<div class="col-md-3 col-lg-2">
						<a href="?page=categories" class="btn btn-outline-secondary w-100">
							<i class="bi bi-x-lg me-1"></i>Effacer
						</a>
					</div>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- Categories Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th style="width: 60px;">ID</th>
						<th>Libelle</th>
						<th>Description</th>
						<th>Categorie parente</th>
						<th class="text-center">Visible</th>
						<th>Date creation</th>
						<th class="text-center" style="width: 140px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($categories)): ?>
						<tr>
							<td colspan="7">
								<div class="empty-state-inline">
									<div class="empty-state-icon"><i class="bi bi-folder"></i></div>
									<p>Aucune categorie trouvee</p>
									<small class="text-muted">Ajoutez une categorie ou modifiez vos filtres</small>
								</div>
							</td>
						</tr>
					<?php else: ?>
						<?php foreach ($categories as $cat): ?>
							<tr>
								<td><strong><?php echo (int) $cat->rowid; ?></strong></td>
								<td><?php echo spacartAdminEscape($cat->label); ?></td>
								<td><?php echo spacartAdminEscape(spacartAdminTruncate($cat->description ?? '', 60)); ?></td>
								<td>
									<?php
									$parent_id = (int) $cat->fk_parent;
									if ($parent_id > 0 && isset($parent_names[$parent_id])) {
										echo '<span class="badge bg-light text-dark">';
										echo '<i class="bi bi-folder me-1"></i>';
										echo spacartAdminEscape($parent_names[$parent_id]);
										echo '</span>';
									} else {
										echo '<span class="text-muted">-- Racine --</span>';
									}
									?>
								</td>
								<td class="text-center">
									<?php if ((int) $cat->visible === 1): ?>
										<?php echo spacartAdminStatusBadge('active', 'Oui'); ?>
									<?php else: ?>
										<?php echo spacartAdminStatusBadge('inactive', 'Non'); ?>
									<?php endif; ?>
								</td>
								<td><?php echo spacartAdminFormatDate($cat->date_creation); ?></td>
								<td class="text-center">
									<div class="d-flex justify-content-center gap-1">
										<!-- Edit -->
										<a href="?page=categories&edit=<?php echo (int) $cat->rowid; ?>" class="btn btn-sm btn-outline-primary" title="Modifier" aria-label="Modifier la categorie">
											<i class="bi bi-pencil"></i>
										</a>
										<!-- Delete -->
										<form method="post" class="d-inline">
											<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
											<input type="hidden" name="action" value="delete_category">
											<input type="hidden" name="category_id" value="<?php echo (int) $cat->rowid; ?>">
											<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer la categorie" data-confirm="Supprimer cette categorie ? Les produits associes seront detaches et les sous-categories deplacees a la racine.">
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
				<?php echo $pagination['total']; ?> categorie(s) -
				Page <?php echo $pagination['current_page']; ?> / <?php echo $pagination['total_pages']; ?>
			</small>
			<nav aria-label="Pagination categories">
				<ul class="pagination pagination-sm mb-0">
					<?php
					$base_url = '?page=categories';
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

<?php
include __DIR__.'/../includes/footer.php';
