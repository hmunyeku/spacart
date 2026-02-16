<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/orders.php
 * \ingroup    spacart
 * \brief      SpaCart Admin - Order list with search, filter, pagination
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title   = 'Commandes';
$current_page = 'orders';

global $db, $conf;

// ============================================================
// POST actions (bulk cancel, single status change)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=orders');
		exit;
	}

	$action = $_POST['action'] ?? '';

	// --- Bulk cancel selected orders ---
	if ($action === 'bulk_cancel' && !empty($_POST['order_ids']) && is_array($_POST['order_ids'])) {
		$ids = array_map('intval', $_POST['order_ids']);
		$ids = array_filter($ids, function ($v) { return $v > 0; });
		if (!empty($ids)) {
			$id_list = implode(',', $ids);
			$sql_update = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = -1";
			$sql_update .= " WHERE rowid IN (".$id_list.")";
			$sql_update .= " AND entity = ".(int) $conf->entity;
			if ($db->query($sql_update)) {
				spacartAdminFlash(count($ids).' commande(s) annulee(s).', 'success');
			} else {
				spacartAdminFlash('Erreur lors de l\'annulation des commandes.', 'danger');
			}
		}
		header('Location: ?page=orders');
		exit;
	}

	// --- Single order status change ---
	if ($action === 'change_status' && !empty($_POST['order_id']) && isset($_POST['new_status'])) {
		$order_id   = (int) $_POST['order_id'];
		$new_status = (int) $_POST['new_status'];
		$allowed    = array(-1, 0, 1, 2, 3);
		if ($order_id > 0 && in_array($new_status, $allowed)) {
			$sql_upd = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = ".$new_status;
			$sql_upd .= " WHERE rowid = ".$order_id;
			$sql_upd .= " AND entity = ".(int) $conf->entity;
			if ($db->query($sql_upd)) {
				spacartAdminFlash('Statut de la commande mis a jour.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour du statut.', 'danger');
			}
		}
		header('Location: ?page=orders');
		exit;
	}
}

// ============================================================
// Filters from GET
// ============================================================
$search    = trim($_GET['search'] ?? '');
$status    = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : null;
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to'] ?? '');
$pg        = max(1, (int) ($_GET['pg'] ?? 1));
$per_page  = 20;

// ============================================================
// Build WHERE clause
// ============================================================
$where = "c.entity = ".(int) $conf->entity;

if ($search !== '') {
	$esc_search = $db->escape($search);
	$where .= " AND (c.ref LIKE '%".$esc_search."%' OR s.nom LIKE '%".$esc_search."%')";
}
if ($status !== null) {
	$where .= " AND c.fk_statut = ".(int) $status;
}
if ($date_from !== '') {
	$where .= " AND c.date_commande >= '".$db->escape($date_from)." 00:00:00'";
}
if ($date_to !== '') {
	$where .= " AND c.date_commande <= '".$db->escape($date_to)." 23:59:59'";
}

// ============================================================
// Pagination
// ============================================================
$sql_count = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."commande as c";
$sql_count .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
$sql_count .= " WHERE ".$where;

$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// ============================================================
// Fetch orders
// ============================================================
$sql  = "SELECT c.rowid, c.ref, c.date_commande, c.total_ht, c.total_ttc, c.fk_statut,";
$sql .= " s.nom as customer_name, s.email";
$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY c.date_commande DESC";
$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

$orders = array();
$resql  = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$orders[] = $obj;
	}
	$db->free($resql);
}

// Build current filter query string for pagination links
$filter_params = array('page' => 'orders');
if ($search !== '')      $filter_params['search']    = $search;
if ($status !== null)    $filter_params['status']    = $status;
if ($date_from !== '')   $filter_params['date_from'] = $date_from;
if ($date_to !== '')     $filter_params['date_to']   = $date_to;

// CSRF token for forms
$csrf_token = spacartAdminGetCSRFToken();

// ============================================================
// Include header (opens HTML, sidebar, topbar, <main>)
// ============================================================
require_once __DIR__.'/../includes/header.php';
?>

<!-- Page header -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between mb-4">
	<div>
		<h1 class="h3 mb-1">Commandes</h1>
		<p class="text-muted mb-0"><?php echo (int) $pagination['total']; ?> commande(s) au total</p>
	</div>
	<div class="d-flex gap-2">
		<button type="button" class="btn btn-outline-secondary" onclick="exportTableCSV('.admin-table', 'commandes.csv')" title="Exporter en CSV" aria-label="Exporter en CSV">
			<i class="bi bi-download me-1"></i>Export CSV
		</button>
	</div>
</div>

<!-- Quick date filters -->
<div class="date-quick-filters mb-3">
	<?php
	$today = date('Y-m-d');
	$seven_days = date('Y-m-d', strtotime('-7 days'));
	$thirty_days = date('Y-m-d', strtotime('-30 days'));
	$quick_base = array('page' => 'orders');
	if ($status !== null) $quick_base['status'] = $status;
	if ($search !== '') $quick_base['search'] = $search;
	?>
	<a href="?<?php echo http_build_query(array_merge($quick_base, array('date_from' => $today, 'date_to' => $today))); ?>" class="btn-quick-date<?php echo ($date_from === $today && $date_to === $today) ? ' active' : ''; ?>">Aujourd'hui</a>
	<a href="?<?php echo http_build_query(array_merge($quick_base, array('date_from' => $seven_days, 'date_to' => $today))); ?>" class="btn-quick-date<?php echo ($date_from === $seven_days && $date_to === $today) ? ' active' : ''; ?>">7 jours</a>
	<a href="?<?php echo http_build_query(array_merge($quick_base, array('date_from' => $thirty_days, 'date_to' => $today))); ?>" class="btn-quick-date<?php echo ($date_from === $thirty_days && $date_to === $today) ? ' active' : ''; ?>">30 jours</a>
	<a href="?<?php echo http_build_query($quick_base); ?>" class="btn-quick-date<?php echo ($date_from === '' && $date_to === '') ? ' active' : ''; ?>">Tout</a>
</div>

<!-- ============================================================== -->
<!-- Filter bar -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="orders">
			<div class="row g-3 align-items-end">
				<!-- Search -->
				<div class="col-12 col-md-3">
					<label for="filter_search" class="form-label">Recherche</label>
					<input type="text" class="form-control" id="filter_search" name="search"
						   value="<?php echo spacartAdminEscape($search); ?>"
						   placeholder="Ref. ou nom client">
				</div>

				<!-- Status -->
				<div class="col-6 col-md-2">
					<label for="filter_status" class="form-label">Statut</label>
					<select class="form-select" id="filter_status" name="status">
						<option value="">Tous</option>
						<option value="0"<?php echo ($status === 0) ? ' selected' : ''; ?>>Brouillon</option>
						<option value="1"<?php echo ($status === 1) ? ' selected' : ''; ?>>Validee</option>
						<option value="2"<?php echo ($status === 2) ? ' selected' : ''; ?>>En cours</option>
						<option value="3"<?php echo ($status === 3) ? ' selected' : ''; ?>>Livree</option>
						<option value="-1"<?php echo ($status === -1) ? ' selected' : ''; ?>>Annulee</option>
					</select>
				</div>

				<!-- Date from -->
				<div class="col-6 col-md-2">
					<label for="filter_date_from" class="form-label">Date debut</label>
					<input type="date" class="form-control" id="filter_date_from" name="date_from"
						   value="<?php echo spacartAdminEscape($date_from); ?>">
				</div>

				<!-- Date to -->
				<div class="col-6 col-md-2">
					<label for="filter_date_to" class="form-label">Date fin</label>
					<input type="date" class="form-control" id="filter_date_to" name="date_to"
						   value="<?php echo spacartAdminEscape($date_to); ?>">
				</div>

				<!-- Filter button -->
				<div class="col-6 col-md-3 col-lg-2">
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>

				<!-- Reset link -->
				<div class="col-12 col-md-1">
					<a href="?page=orders" class="btn btn-outline-secondary w-100" title="Reinitialiser">
						<i class="bi bi-x-circle"></i>
					</a>
				</div>
			</div>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Orders table -->
<!-- ============================================================== -->
<div class="admin-card">
	<div class="card-body p-0">
		<form method="post" id="ordersForm">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="bulk_cancel">

			<!-- Bulk actions bar -->
			<div class="d-flex align-items-center gap-2 p-3 border-bottom">
				<button type="submit" class="btn btn-outline-danger btn-sm btn-delete" data-confirm="Annuler les commandes selectionnees ?">
					<i class="bi bi-x-circle me-1"></i>Annuler la selection
				</button>
			</div>

			<div class="table-responsive">
				<table class="admin-table table table-hover align-middle mb-0">
					<thead>
						<tr>
							<th style="width:40px;">
								<input type="checkbox" class="form-check-input" id="checkAll"
									   onchange="document.querySelectorAll('.order-check').forEach(function(c){c.checked=this.checked}.bind(this));">
							</th>
							<th>Ref.</th>
							<th>Client</th>
							<th>Date</th>
							<th class="text-end">Total HT</th>
							<th class="text-end">Total TTC</th>
							<th>Statut</th>
							<th style="width:100px;">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($orders)): ?>
							<tr>
								<td colspan="8">
									<div class="empty-state-inline">
										<div class="empty-state-icon"><i class="bi bi-box-seam"></i></div>
										<p>Aucune commande trouvee</p>
										<small class="text-muted">Modifiez vos filtres ou attendez de nouvelles commandes</small>
									</div>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($orders as $order): ?>
								<tr>
									<td>
										<input type="checkbox" class="form-check-input order-check"
											   name="order_ids[]" value="<?php echo (int) $order->rowid; ?>">
									</td>
									<td>
										<a href="?page=order_view&amp;id=<?php echo (int) $order->rowid; ?>" class="fw-semibold text-decoration-none">
											<?php echo spacartAdminEscape($order->ref); ?>
										</a>
									</td>
									<td>
										<?php echo spacartAdminEscape($order->customer_name); ?>
										<?php if (!empty($order->email)): ?>
											<br><small class="text-muted"><?php echo spacartAdminEscape($order->email); ?></small>
										<?php endif; ?>
									</td>
									<td><?php echo spacartAdminFormatDate($order->date_commande); ?></td>
									<td class="text-end"><?php echo spacartAdminFormatPrice($order->total_ht); ?></td>
									<td class="text-end"><?php echo spacartAdminFormatPrice($order->total_ttc); ?></td>
									<td><?php echo spacartAdminOrderStatusBadge($order->fk_statut); ?></td>
									<td>
										<a href="?page=order_view&amp;id=<?php echo (int) $order->rowid; ?>"
										   class="btn btn-sm btn-outline-primary" title="Voir" aria-label="Voir la commande">
											<i class="bi bi-eye"></i>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Pagination -->
<!-- ============================================================== -->
<?php if ($pagination['total_pages'] > 1): ?>
<div class="admin-pagination d-flex flex-wrap align-items-center justify-content-between mt-4">
	<div class="text-muted small mb-2 mb-md-0">
		<?php
		$start = $pagination['offset'] + 1;
		$end   = min($pagination['offset'] + $pagination['limit'], $pagination['total']);
		?>
		Affichage <?php echo $start; ?>-<?php echo $end; ?> sur <?php echo $pagination['total']; ?>
	</div>
	<nav aria-label="Pagination commandes">
		<ul class="pagination pagination-sm mb-0">
			<?php
			$total_pages  = $pagination['total_pages'];
			$current_pg   = $pagination['current_page'];

			// Previous
			if ($current_pg > 1):
				$prev_params = array_merge($filter_params, array('pg' => $current_pg - 1));
			?>
				<li class="page-item">
					<a class="page-link" href="?<?php echo http_build_query($prev_params); ?>" aria-label="Precedent">
						<span aria-hidden="true">&laquo;</span>
					</a>
				</li>
			<?php else: ?>
				<li class="page-item disabled"><span class="page-link">&laquo;</span></li>
			<?php endif; ?>

			<?php
			// Determine visible page range (max 7 pages shown)
			$range = 3;
			$pg_start = max(1, $current_pg - $range);
			$pg_end   = min($total_pages, $current_pg + $range);

			if ($pg_start > 1): ?>
				<li class="page-item">
					<a class="page-link" href="?<?php echo http_build_query(array_merge($filter_params, array('pg' => 1))); ?>">1</a>
				</li>
				<?php if ($pg_start > 2): ?>
					<li class="page-item disabled"><span class="page-link">&hellip;</span></li>
				<?php endif; ?>
			<?php endif; ?>

			<?php for ($p = $pg_start; $p <= $pg_end; $p++):
				$pg_params = array_merge($filter_params, array('pg' => $p));
			?>
				<li class="page-item<?php echo ($p === $current_pg) ? ' active' : ''; ?>">
					<a class="page-link" href="?<?php echo http_build_query($pg_params); ?>"><?php echo $p; ?></a>
				</li>
			<?php endfor; ?>

			<?php if ($pg_end < $total_pages): ?>
				<?php if ($pg_end < $total_pages - 1): ?>
					<li class="page-item disabled"><span class="page-link">&hellip;</span></li>
				<?php endif; ?>
				<li class="page-item">
					<a class="page-link" href="?<?php echo http_build_query(array_merge($filter_params, array('pg' => $total_pages))); ?>"><?php echo $total_pages; ?></a>
				</li>
			<?php endif; ?>

			<?php
			// Next
			if ($current_pg < $total_pages):
				$next_params = array_merge($filter_params, array('pg' => $current_pg + 1));
			?>
				<li class="page-item">
					<a class="page-link" href="?<?php echo http_build_query($next_params); ?>" aria-label="Suivant">
						<span aria-hidden="true">&raquo;</span>
					</a>
				</li>
			<?php else: ?>
				<li class="page-item disabled"><span class="page-link">&raquo;</span></li>
			<?php endif; ?>
		</ul>
	</nav>
</div>
<?php endif; ?>

<?php
// ============================================================
// Include footer (closes HTML structure)
// ============================================================
require_once __DIR__.'/../includes/footer.php';
