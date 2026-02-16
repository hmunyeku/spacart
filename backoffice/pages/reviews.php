<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/reviews.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Product reviews moderation (approve/reject/delete)
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title   = 'Avis clients';
$current_page = 'reviews';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// ============================================================
// POST actions
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=reviews');
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Approve ---
	if ($action === 'approve' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		$sql = "UPDATE ".$prefix."spacart_review SET status = 1 WHERE rowid = ".$id." AND entity = ".$entity;
		if ($db->query($sql)) {
			spacartAdminFlash('Avis approuve.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de l\'approbation.', 'danger');
		}
		header('Location: ?page=reviews');
		exit;
	}

	// --- Reject ---
	if ($action === 'reject' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		$sql = "UPDATE ".$prefix."spacart_review SET status = 2 WHERE rowid = ".$id." AND entity = ".$entity;
		if ($db->query($sql)) {
			spacartAdminFlash('Avis rejete.', 'success');
		} else {
			spacartAdminFlash('Erreur lors du rejet.', 'danger');
		}
		header('Location: ?page=reviews');
		exit;
	}

	// --- Set pending ---
	if ($action === 'pending' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		$sql = "UPDATE ".$prefix."spacart_review SET status = 0 WHERE rowid = ".$id." AND entity = ".$entity;
		if ($db->query($sql)) {
			spacartAdminFlash('Avis remis en attente.', 'success');
		} else {
			spacartAdminFlash('Erreur lors du changement de statut.', 'danger');
		}
		header('Location: ?page=reviews');
		exit;
	}

	// --- Delete ---
	if ($action === 'delete' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		$sql = "DELETE FROM ".$prefix."spacart_review WHERE rowid = ".$id." AND entity = ".$entity;
		if ($db->query($sql)) {
			spacartAdminFlash('Avis supprime.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de la suppression.', 'danger');
		}
		header('Location: ?page=reviews');
		exit;
	}

	// --- Bulk approve ---
	if ($action === 'bulk_approve' && !empty($_POST['review_ids']) && is_array($_POST['review_ids'])) {
		$ids = array_map('intval', $_POST['review_ids']);
		$ids = array_filter($ids, function ($v) { return $v > 0; });
		if (!empty($ids)) {
			$id_list = implode(',', $ids);
			$sql = "UPDATE ".$prefix."spacart_review SET status = 1";
			$sql .= " WHERE rowid IN (".$id_list.")";
			$sql .= " AND entity = ".$entity;
			if ($db->query($sql)) {
				spacartAdminFlash(count($ids).' avis approuve(s).', 'success');
			} else {
				spacartAdminFlash('Erreur lors de l\'approbation groupee.', 'danger');
			}
		}
		header('Location: ?page=reviews');
		exit;
	}

	// --- Bulk reject ---
	if ($action === 'bulk_reject' && !empty($_POST['review_ids']) && is_array($_POST['review_ids'])) {
		$ids = array_map('intval', $_POST['review_ids']);
		$ids = array_filter($ids, function ($v) { return $v > 0; });
		if (!empty($ids)) {
			$id_list = implode(',', $ids);
			$sql = "UPDATE ".$prefix."spacart_review SET status = 2";
			$sql .= " WHERE rowid IN (".$id_list.")";
			$sql .= " AND entity = ".$entity;
			if ($db->query($sql)) {
				spacartAdminFlash(count($ids).' avis rejete(s).', 'success');
			} else {
				spacartAdminFlash('Erreur lors du rejet groupe.', 'danger');
			}
		}
		header('Location: ?page=reviews');
		exit;
	}
}

// ============================================================
// Filters from GET
// ============================================================
$filter_status = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : -1;
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$pg            = max(1, (int) ($_GET['pg'] ?? 1));
$per_page      = 20;

// ============================================================
// Build WHERE clause
// ============================================================
$where = "r.entity = ".$entity;

if ($filter_status !== -1) {
	$where .= " AND r.status = ".(int) $filter_status;
}

if ($search !== '') {
	$search_esc = $db->escape($search);
	$where .= " AND (r.customer_name LIKE '%".$search_esc."%'";
	$where .= " OR r.title LIKE '%".$search_esc."%'";
	$where .= " OR r.comment LIKE '%".$search_esc."%'";
	$where .= " OR p.label LIKE '%".$search_esc."%')";
}

// ============================================================
// Pagination
// ============================================================
$sql_count = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_review as r";
$sql_count .= " LEFT JOIN ".$prefix."product as p ON p.rowid = r.fk_product";
$sql_count .= " WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// ============================================================
// Fetch reviews with product name
// ============================================================
$reviews = array();
$sql  = "SELECT r.rowid, r.fk_product, r.customer_name, r.title, r.comment, r.rating, r.status, r.date_creation,";
$sql .= " p.label as product_name, p.ref as product_ref";
$sql .= " FROM ".$prefix."spacart_review as r";
$sql .= " LEFT JOIN ".$prefix."product as p ON p.rowid = r.fk_product";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY r.date_creation DESC";
$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$reviews[] = $obj;
	}
	$db->free($resql);
}

// Count by status for quick filters
$status_counts = array('all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0);
$sql_stats = "SELECT status, COUNT(*) as nb FROM ".$prefix."spacart_review WHERE entity = ".$entity." GROUP BY status";
$resql_stats = $db->query($sql_stats);
if ($resql_stats) {
	while ($obj = $db->fetch_object($resql_stats)) {
		$status_counts['all'] += (int) $obj->nb;
		if ((int) $obj->status === 0) $status_counts['pending'] = (int) $obj->nb;
		if ((int) $obj->status === 1) $status_counts['approved'] = (int) $obj->nb;
		if ((int) $obj->status === 2) $status_counts['rejected'] = (int) $obj->nb;
	}
}

// Filter params for pagination links
$filter_params = array('page' => 'reviews');
if ($filter_status !== -1) $filter_params['status'] = $filter_status;
if ($search !== '') $filter_params['search'] = $search;

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

// ============================================================
// Include header
// ============================================================
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1"><i class="bi bi-star me-2"></i>Avis clients</h1>
		<p class="text-muted mb-0"><?php echo (int) $status_counts['all']; ?> avis au total</p>
	</div>
</div>

<!-- Status quick-filter pills -->
<div class="mb-3">
	<div class="d-flex flex-wrap gap-2">
		<a href="?page=reviews" class="btn btn-sm <?php echo ($filter_status === -1 && $search === '') ? 'btn-primary' : 'btn-outline-secondary'; ?>">
			Tous <span class="badge bg-light text-dark ms-1"><?php echo (int) $status_counts['all']; ?></span>
		</a>
		<a href="?page=reviews&amp;status=0" class="btn btn-sm <?php echo ($filter_status === 0) ? 'btn-warning' : 'btn-outline-warning'; ?>">
			En attente <span class="badge bg-light text-dark ms-1"><?php echo (int) $status_counts['pending']; ?></span>
		</a>
		<a href="?page=reviews&amp;status=1" class="btn btn-sm <?php echo ($filter_status === 1) ? 'btn-success' : 'btn-outline-success'; ?>">
			Approuves <span class="badge bg-light text-dark ms-1"><?php echo (int) $status_counts['approved']; ?></span>
		</a>
		<a href="?page=reviews&amp;status=2" class="btn btn-sm <?php echo ($filter_status === 2) ? 'btn-danger' : 'btn-outline-danger'; ?>">
			Rejetes <span class="badge bg-light text-dark ms-1"><?php echo (int) $status_counts['rejected']; ?></span>
		</a>
	</div>
</div>

<!-- Filter bar -->
<div class="filter-bar mb-4">
	<form method="get" action="" class="row g-2 align-items-end">
		<input type="hidden" name="page" value="reviews">
		<?php if ($filter_status !== -1): ?>
			<input type="hidden" name="status" value="<?php echo (int) $filter_status; ?>">
		<?php endif; ?>

		<div class="col-md-5">
			<label for="filter-search" class="form-label">Recherche</label>
			<input type="text" class="form-control" id="filter-search" name="search"
				   value="<?php echo spacartAdminEscape($search); ?>"
				   placeholder="Nom client, titre, produit...">
		</div>

		<div class="col-md-3">
			<label for="filter-status" class="form-label">Statut</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">Tous</option>
				<option value="0"<?php echo ($filter_status === 0) ? ' selected' : ''; ?>>En attente</option>
				<option value="1"<?php echo ($filter_status === 1) ? ' selected' : ''; ?>>Approuve</option>
				<option value="2"<?php echo ($filter_status === 2) ? ' selected' : ''; ?>>Rejete</option>
			</select>
		</div>

		<div class="col-md-2">
			<button type="submit" class="btn btn-primary w-100">
				<i class="bi bi-funnel me-1"></i>Filtrer
			</button>
		</div>

		<?php if ($search !== '' || $filter_status !== -1): ?>
		<div class="col-md-2">
			<a href="?page=reviews" class="btn btn-outline-secondary w-100">
				<i class="bi bi-x-circle me-1"></i>Reinitialiser
			</a>
		</div>
		<?php endif; ?>
	</form>
</div>

<!-- Bulk action form (separate, uses form attribute on checkboxes) -->
<form method="post" action="?page=reviews" id="reviewsBulkForm">
	<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
	<input type="hidden" name="action" value="" id="bulkAction">
</form>

<!-- Reviews table -->
<div class="admin-card">
	<div class="card-body p-0">
		<!-- Bulk actions bar -->
		<div class="d-flex align-items-center gap-2 p-3 border-bottom">
			<button type="submit" form="reviewsBulkForm" class="btn btn-outline-success btn-sm"
					onclick="document.getElementById('bulkAction').value='bulk_approve';"
					data-confirm="Approuver les avis selectionnes ?">
				<i class="bi bi-check-lg me-1"></i>Approuver la selection
			</button>
			<button type="submit" form="reviewsBulkForm" class="btn btn-outline-danger btn-sm"
					onclick="document.getElementById('bulkAction').value='bulk_reject';"
					data-confirm="Rejeter les avis selectionnes ?">
				<i class="bi bi-x-lg me-1"></i>Rejeter la selection
			</button>
		</div>

		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th style="width:40px;">
							<input type="checkbox" class="form-check-input" id="checkAll"
								   onchange="document.querySelectorAll('.review-check').forEach(function(c){c.checked=this.checked}.bind(this));">
						</th>
						<th>Produit</th>
						<th>Client</th>
						<th>Note</th>
						<th>Commentaire</th>
						<th class="text-center">Statut</th>
						<th>Date</th>
						<th class="text-center" style="width:150px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($reviews)): ?>
					<tr>
						<td colspan="8">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-star"></i></div>
								<p>Aucun avis trouve</p>
								<small class="text-muted">Les avis clients apparaitront ici</small>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($reviews as $review): ?>
						<tr>
							<td>
								<input type="checkbox" class="form-check-input review-check"
									   form="reviewsBulkForm"
									   name="review_ids[]" value="<?php echo (int) $review->rowid; ?>">
							</td>
							<td>
								<?php if (!empty($review->product_name)): ?>
									<strong><?php echo spacartAdminEscape($review->product_name); ?></strong>
									<?php if (!empty($review->product_ref)): ?>
										<br><small class="text-muted"><?php echo spacartAdminEscape($review->product_ref); ?></small>
									<?php endif; ?>
								<?php else: ?>
									<span class="text-muted">Produit #<?php echo (int) $review->fk_product; ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminEscape($review->customer_name); ?></td>
							<td>
								<span class="text-warning">
									<?php for ($s = 1; $s <= 5; $s++): ?>
										<i class="bi <?php echo $s <= (int) $review->rating ? 'bi-star-fill' : 'bi-star'; ?>"></i>
									<?php endfor; ?>
								</span>
							</td>
							<td>
								<?php if (!empty($review->title)): ?>
									<strong><?php echo spacartAdminEscape(spacartAdminTruncate($review->title, 40)); ?></strong><br>
								<?php endif; ?>
								<span class="text-muted"><?php echo spacartAdminEscape(spacartAdminTruncate($review->comment, 60)); ?></span>
							</td>
							<td class="text-center">
								<?php if ((int) $review->status === 1): ?>
									<?php echo spacartAdminStatusBadge('active', 'Approuve'); ?>
								<?php elseif ((int) $review->status === 2): ?>
									<?php echo spacartAdminStatusBadge('cancelled', 'Rejete'); ?>
								<?php else: ?>
									<?php echo spacartAdminStatusBadge('pending', 'En attente', true); ?>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminFormatDate($review->date_creation, 'd/m/Y'); ?></td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<?php if ((int) $review->status !== 1): ?>
									<!-- Approve -->
									<form method="post" action="?page=reviews" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="approve">
										<input type="hidden" name="id" value="<?php echo (int) $review->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-success" title="Approuver" aria-label="Approuver l'avis">
											<i class="bi bi-check-lg"></i>
										</button>
									</form>
									<?php endif; ?>

									<?php if ((int) $review->status !== 2): ?>
									<!-- Reject -->
									<form method="post" action="?page=reviews" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="reject">
										<input type="hidden" name="id" value="<?php echo (int) $review->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-warning" title="Rejeter" aria-label="Rejeter l'avis">
											<i class="bi bi-x-lg"></i>
										</button>
									</form>
									<?php endif; ?>

									<!-- Delete -->
									<form method="post" action="?page=reviews" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="id" value="<?php echo (int) $review->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer l'avis" data-confirm="Supprimer cet avis ?">
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
				sur <?php echo (int) $pagination['total']; ?> avis
			</div>
			<nav aria-label="Pagination avis">
				<ul class="pagination pagination-sm mb-0">
					<?php
					$base_params = $filter_params;
					$total_pages = $pagination['total_pages'];
					$cp = $pagination['current_page'];

					// Previous
					$prev_disabled = ($cp <= 1) ? ' disabled' : '';
					$prev_params = array_merge($base_params, array('pg' => $cp - 1));
					?>
					<li class="page-item<?php echo $prev_disabled; ?>">
						<a class="page-link" href="?<?php echo http_build_query($prev_params); ?>" aria-label="Precedent">
							<i class="bi bi-chevron-left"></i>
						</a>
					</li>

					<?php
					$start = max(1, $cp - 2);
					$end = min($total_pages, $cp + 2);
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
					$next_disabled = ($cp >= $total_pages) ? ' disabled' : '';
					$next_params = array_merge($base_params, array('pg' => $cp + 1));
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
