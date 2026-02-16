<?php
/**
 * SpaCart Backoffice - Products List
 *
 * Product list page with search, category filter, status filter, pagination,
 * bulk actions, and single product status toggle.
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Produits';
$current_page = 'products';

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// =====================================================================
// POST actions (CSRF-protected)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Bulk delete (set tosell=0, tobuy=0) ---
	if ($action === 'bulk_disable') {
		$ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : array();
		if (!empty($ids) && is_array($ids)) {
			$safe_ids = array();
			foreach ($ids as $pid) {
				$pid = (int) $pid;
				if ($pid > 0) {
					$safe_ids[] = $pid;
				}
			}
			if (!empty($safe_ids)) {
				$sql = "UPDATE ".$prefix."product SET tosell = 0, tobuy = 0, tms = NOW()";
				$sql .= " WHERE rowid IN (".implode(',', $safe_ids).")";
				$sql .= " AND entity = ".$entity;
				$db->query($sql);
				spacartAdminFlash(count($safe_ids).' produit(s) desactive(s).', 'success');
			}
		} else {
			spacartAdminFlash('Veuillez selectionner au moins un produit.', 'warning');
		}
		header('Location: ?page=products');
		exit;
	}

	// --- Toggle single product status ---
	if ($action === 'toggle_status') {
		$toggle_id = (int) ($_POST['product_id'] ?? 0);
		if ($toggle_id > 0) {
			// Get current status
			$sql = "SELECT tosell FROM ".$prefix."product WHERE rowid = ".$toggle_id." AND entity = ".$entity;
			$resql = $db->query($sql);
			if ($resql && $db->num_rows($resql) > 0) {
				$obj = $db->fetch_object($resql);
				$new_status = ((int) $obj->tosell === 1) ? 0 : 1;
				$db->query("UPDATE ".$prefix."product SET tosell = ".$new_status.", tms = NOW() WHERE rowid = ".$toggle_id." AND entity = ".$entity);
				$label = $new_status ? 'en vente' : 'hors vente';
				spacartAdminFlash('Produit mis a jour : '.$label.'.', 'success');
			}
		}
		// Preserve current filters in redirect
		$redirect = '?page=products';
		if (!empty($_POST['redirect_params'])) {
			$redirect .= '&'.ltrim($_POST['redirect_params'], '&');
		}
		header('Location: '.$redirect);
		exit;
	}
}

// =====================================================================
// Filters from GET
// =====================================================================
$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_id    = isset($_GET['cat_id']) ? (int) $_GET['cat_id'] : 0;
$status    = isset($_GET['status']) ? $_GET['status'] : '';
$pg        = isset($_GET['pg']) ? (int) $_GET['pg'] : 1;
$per_page  = 20;

// =====================================================================
// Build WHERE clause
// =====================================================================
$where = "p.entity = ".$entity." AND p.fk_product_type = 0";

if ($search !== '') {
	$s = $db->escape($search);
	$where .= " AND (p.ref LIKE '%".$s."%' OR p.label LIKE '%".$s."%')";
}

if ($cat_id > 0) {
	$where .= " AND EXISTS (SELECT 1 FROM ".$prefix."categorie_product AS cp WHERE cp.fk_product = p.rowid AND cp.fk_categorie = ".$cat_id.")";
}

if ($status === '1') {
	$where .= " AND p.tosell = 1";
} elseif ($status === '0') {
	$where .= " AND p.tosell = 0";
}

// =====================================================================
// Pagination
// =====================================================================
$sql_count = "SELECT COUNT(*) as nb FROM ".$prefix."product AS p WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// =====================================================================
// Fetch products
// =====================================================================
$products = array();
$sql = "SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.tosell, p.tobuy, p.stock, p.tms";
$sql .= " FROM ".$prefix."product AS p";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY p.tms DESC";
$sql .= " LIMIT ".$pagination['limit']." OFFSET ".$pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$products[] = $obj;
	}
}

// =====================================================================
// Fetch categories for filter dropdown
// =====================================================================
$categories = array();
$sql_cat = "SELECT rowid, label FROM ".$prefix."categorie WHERE type = 0 AND entity = ".$entity." ORDER BY label ASC";
$resql_cat = $db->query($sql_cat);
if ($resql_cat) {
	while ($obj = $db->fetch_object($resql_cat)) {
		$categories[] = $obj;
	}
}

// Build query string for preserving filters across toggle actions
$filter_params = array();
if ($search !== '') {
	$filter_params[] = 'search='.urlencode($search);
}
if ($cat_id > 0) {
	$filter_params[] = 'cat_id='.$cat_id;
}
if ($status !== '') {
	$filter_params[] = 'status='.$status;
}
if ($pg > 1) {
	$filter_params[] = 'pg='.$pg;
}
$redirect_params = implode('&', $filter_params);

// Product photos directory
$product_photos_dir = DOL_DATA_ROOT.'/produit/';
$product_photos_url = DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$entity.'&file=';

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1"><i class="bi bi-box-seam me-2"></i>Produits</h1>
		<p class="text-muted mb-0"><?php echo (int) $pagination['total']; ?> produit(s) au total</p>
	</div>
	<div class="d-flex gap-2">
		<button type="button" class="btn btn-outline-secondary" onclick="exportTableCSV('.admin-table', 'produits.csv')" title="Exporter en CSV" aria-label="Exporter en CSV">
			<i class="bi bi-download me-1"></i>Export CSV
		</button>
		<a href="?page=product_edit" class="btn btn-primary">
			<i class="bi bi-plus-lg me-1"></i>Ajouter un produit
		</a>
	</div>
</div>

<!-- Filter Bar -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="products">
			<div class="row g-3 align-items-end">
				<!-- Search -->
				<div class="col-md-4 col-lg-3">
					<label class="form-label" for="filter-search">Recherche</label>
					<input type="text" class="form-control" id="filter-search" name="search" value="<?php echo spacartAdminEscape($search); ?>" placeholder="Ref, libelle...">
				</div>
				<!-- Category -->
				<div class="col-md-3 col-lg-3">
					<label class="form-label" for="filter-category">Categorie</label>
					<select class="form-select" id="filter-category" name="cat_id">
						<option value="0">Toutes les categories</option>
						<?php foreach ($categories as $cat): ?>
							<option value="<?php echo (int) $cat->rowid; ?>" <?php echo ($cat_id === (int) $cat->rowid) ? 'selected' : ''; ?>>
								<?php echo spacartAdminEscape($cat->label); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<!-- Status -->
				<div class="col-md-3 col-lg-2">
					<label class="form-label" for="filter-status">Statut</label>
					<select class="form-select" id="filter-status" name="status">
						<option value="">Tous</option>
						<option value="1" <?php echo ($status === '1') ? 'selected' : ''; ?>>En vente</option>
						<option value="0" <?php echo ($status === '0') ? 'selected' : ''; ?>>Hors vente</option>
					</select>
				</div>
				<!-- Filter button -->
				<div class="col-md-2 col-lg-2">
					<button type="submit" class="btn btn-outline-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>
			</div>
		</form>
	</div>
</div>

<!-- Products Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<form method="post" id="bulkForm">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="bulk_disable">

			<!-- Bulk actions bar -->
			<div class="d-flex align-items-center gap-2 p-3 border-bottom bg-light" id="bulkActionsBar" style="display: none !important;">
				<span class="text-muted" id="selectedCount">0 selectionne(s)</span>
				<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" data-confirm="Desactiver les produits selectionnes ?">
					<i class="bi bi-x-circle me-1"></i>Desactiver la selection
				</button>
			</div>

			<div class="table-responsive">
				<table class="admin-table table-hover mb-0">
					<thead>
						<tr>
							<th style="width: 40px;">
								<input type="checkbox" class="form-check-input" id="checkAll" title="Tout selectionner" aria-label="Tout selectionner">
							</th>
							<th style="width: 60px;">Image</th>
							<th>Ref</th>
							<th>Libelle</th>
							<th class="text-end">Prix TTC</th>
							<th class="text-center">Stock</th>
							<th class="text-center">Statut</th>
							<th>Date modif.</th>
							<th class="text-center" style="width: 120px;">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($products)): ?>
							<tr>
								<td colspan="9">
									<div class="empty-state-inline">
										<div class="empty-state-icon"><i class="bi bi-box-seam"></i></div>
										<p>Aucun produit trouve</p>
										<small class="text-muted">Ajoutez des produits ou modifiez vos filtres</small>
									</div>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($products as $prod): ?>
								<?php
								// Try to find product thumbnail
								$thumb_html = '<div class="product-thumb-placeholder"><i class="bi bi-image text-muted"></i></div>';
								$ref_dir = strtolower(trim($prod->ref));
								$photo_dir = $product_photos_dir.$ref_dir.'/';
								if (is_dir($photo_dir)) {
									$thumbs = glob($photo_dir.'thumbs/*_small.*');
									if (!empty($thumbs)) {
										$thumb_file = basename($thumbs[0]);
										$thumb_url = $product_photos_url.urlencode($ref_dir.'/thumbs/'.$thumb_file);
										$thumb_html = '<img src="'.$thumb_url.'" alt="" class="product-thumb-img" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">';
									} else {
										// Try main images
										$images = glob($photo_dir.'*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
										if (!empty($images)) {
											$img_file = basename($images[0]);
											$img_url = $product_photos_url.urlencode($ref_dir.'/'.$img_file);
											$thumb_html = '<img src="'.$img_url.'" alt="" class="product-thumb-img" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">';
										}
									}
								}
								?>
								<tr>
									<td>
										<input type="checkbox" class="form-check-input product-check" name="product_ids[]" value="<?php echo (int) $prod->rowid; ?>">
									</td>
									<td><?php echo $thumb_html; ?></td>
									<td>
										<a href="?page=product_edit&id=<?php echo (int) $prod->rowid; ?>" class="fw-semibold text-decoration-none">
											<?php echo spacartAdminEscape($prod->ref); ?>
										</a>
									</td>
									<td><?php echo spacartAdminEscape(spacartAdminTruncate($prod->label, 50)); ?></td>
									<td class="text-end"><?php echo spacartAdminFormatPrice((float) $prod->price_ttc); ?></td>
									<td class="text-center">
										<?php
										$stock_val = (int) $prod->stock;
										if ($stock_val <= 0) {
											echo '<span class="stock-badge stock-out">'.$stock_val.'</span>';
										} elseif ($stock_val <= 5) {
											echo '<span class="stock-badge stock-low">'.$stock_val.'</span>';
										} else {
											echo '<span class="stock-badge stock-ok">'.$stock_val.'</span>';
										}
										?>
									</td>
									<td class="text-center">
										<?php if ((int) $prod->tosell === 1): ?>
											<?php echo spacartAdminStatusBadge('active', 'En vente'); ?>
										<?php else: ?>
											<?php echo spacartAdminStatusBadge('inactive', 'Hors vente'); ?>
										<?php endif; ?>
									</td>
									<td><?php echo spacartAdminFormatDate($prod->tms); ?></td>
									<td class="text-center">
										<div class="d-flex justify-content-center gap-1">
											<!-- Edit -->
											<a href="?page=product_edit&id=<?php echo (int) $prod->rowid; ?>" class="btn btn-sm btn-outline-primary" title="Modifier" aria-label="Modifier le produit">
												<i class="bi bi-pencil"></i>
											</a>
											<!-- Toggle status -->
											<form method="post" class="d-inline">
												<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
												<input type="hidden" name="action" value="toggle_status">
												<input type="hidden" name="product_id" value="<?php echo (int) $prod->rowid; ?>">
												<input type="hidden" name="redirect_params" value="<?php echo spacartAdminEscape($redirect_params); ?>">
												<?php if ((int) $prod->tosell === 1): ?>
													<button type="submit" class="btn btn-sm btn-outline-warning" title="Desactiver" aria-label="Desactiver le produit">
														<i class="bi bi-pause-circle"></i>
													</button>
												<?php else: ?>
													<button type="submit" class="btn btn-sm btn-outline-success" title="Activer" aria-label="Activer le produit">
														<i class="bi bi-play-circle"></i>
													</button>
												<?php endif; ?>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</form>
	</div>

	<!-- Pagination -->
	<?php if ($pagination['total_pages'] > 1): ?>
		<div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
			<small class="text-muted">
				<?php echo $pagination['total']; ?> produit(s) -
				Page <?php echo $pagination['current_page']; ?> / <?php echo $pagination['total_pages']; ?>
			</small>
			<nav aria-label="Pagination produits">
				<ul class="pagination pagination-sm mb-0">
					<?php
					$base_url = '?page=products';
					if ($search !== '') {
						$base_url .= '&search='.urlencode($search);
					}
					if ($cat_id > 0) {
						$base_url .= '&cat_id='.$cat_id;
					}
					if ($status !== '') {
						$base_url .= '&status='.$status;
					}
					?>
					<!-- Previous -->
					<li class="page-item <?php echo ($pagination['current_page'] <= 1) ? 'disabled' : ''; ?>">
						<a class="page-link" href="<?php echo $base_url.'&pg='.($pagination['current_page'] - 1); ?>" aria-label="Precedent">
							<i class="bi bi-chevron-left"></i>
						</a>
					</li>
					<?php
					// Show page numbers with ellipsis
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

<!-- Checkbox / Bulk selection JS -->
<script>
(function() {
	'use strict';

	var checkAll = document.getElementById('checkAll');
	var checkboxes = document.querySelectorAll('.product-check');
	var bulkBar = document.getElementById('bulkActionsBar');
	var countSpan = document.getElementById('selectedCount');

	function updateBulkBar() {
		var checked = document.querySelectorAll('.product-check:checked');
		var count = checked.length;
		if (count > 0) {
			bulkBar.style.setProperty('display', 'flex', 'important');
			countSpan.textContent = count + ' selectionne(s)';
		} else {
			bulkBar.style.setProperty('display', 'none', 'important');
		}
	}

	if (checkAll) {
		checkAll.addEventListener('change', function() {
			checkboxes.forEach(function(cb) {
				cb.checked = checkAll.checked;
			});
			updateBulkBar();
		});
	}

	checkboxes.forEach(function(cb) {
		cb.addEventListener('change', function() {
			// Update checkAll state
			var allChecked = true;
			checkboxes.forEach(function(c) {
				if (!c.checked) allChecked = false;
			});
			if (checkAll) checkAll.checked = allChecked;
			updateBulkBar();
		});
	});
})();
</script>

<?php
include __DIR__.'/../includes/footer.php';
