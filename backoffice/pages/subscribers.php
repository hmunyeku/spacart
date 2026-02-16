<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/subscribers.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Newsletter subscriber management
 *
 * Table: llx_spacart_subscriber
 * Columns: rowid, email, status (1=active, 0=unsubscribed), entity, date_creation
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Newsletter';
$current_page = 'subscribers';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// -------------------------------------------------------------------
// Handle CSV export (before any HTML output)
// -------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
	$sql_export = "SELECT email, date_creation FROM ".$prefix."spacart_subscriber";
	$sql_export .= " WHERE entity = ".$entity;
	$sql_export .= " AND status = 1";
	$sql_export .= " ORDER BY date_creation DESC";
	$resql_export = $db->query($sql_export);

	header('Content-Type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="newsletter_subscribers_'.date('Y-m-d').'.csv"');
	header('Cache-Control: no-cache, no-store, must-revalidate');

	$output = fopen('php://output', 'w');
	// UTF-8 BOM for Excel compatibility
	fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
	fputcsv($output, array('Email', 'Date inscription'), ';');

	if ($resql_export) {
		while ($row = $db->fetch_object($resql_export)) {
			fputcsv($output, array(
				$row->email,
				spacartAdminFormatDate($row->date_creation, 'd/m/Y H:i'),
			), ';');
		}
		$db->free($resql_export);
	}

	fclose($output);
	exit;
}

// -------------------------------------------------------------------
// Handle POST actions
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=subscribers');
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	if ($action === 'toggle_status') {
		$sub_id = isset($_POST['subscriber_id']) ? (int) $_POST['subscriber_id'] : 0;
		$new_status = isset($_POST['new_status']) ? (int) $_POST['new_status'] : 0;

		if ($sub_id > 0 && in_array($new_status, array(0, 1))) {
			$sql = "UPDATE ".$prefix."spacart_subscriber";
			$sql .= " SET status = ".$new_status;
			$sql .= " WHERE rowid = ".$sub_id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				$label = ($new_status === 1) ? 'reabonne' : 'desabonne';
				spacartAdminFlash('L\'abonne a ete '.$label.' avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour du statut.', 'danger');
			}
		}

		// Preserve filters on redirect
		$redirect_params = array('page' => 'subscribers');
		if (!empty($_POST['redirect_search'])) {
			$redirect_params['search'] = $_POST['redirect_search'];
		}
		if (!empty($_POST['redirect_p'])) {
			$redirect_params['p'] = $_POST['redirect_p'];
		}
		header('Location: ?'.http_build_query($redirect_params));
		exit;
	}

	if ($action === 'delete') {
		$sub_id = isset($_POST['subscriber_id']) ? (int) $_POST['subscriber_id'] : 0;

		if ($sub_id > 0) {
			$sql = "DELETE FROM ".$prefix."spacart_subscriber";
			$sql .= " WHERE rowid = ".$sub_id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Abonne supprime avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la suppression.', 'danger');
			}
		}

		$redirect_params = array('page' => 'subscribers');
		if (!empty($_POST['redirect_search'])) {
			$redirect_params['search'] = $_POST['redirect_search'];
		}
		if (!empty($_POST['redirect_p'])) {
			$redirect_params['p'] = $_POST['redirect_p'];
		}
		header('Location: ?'.http_build_query($redirect_params));
		exit;
	}
}

// -------------------------------------------------------------------
// Filter parameters
// -------------------------------------------------------------------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$current_p = isset($_GET['p']) ? (int) $_GET['p'] : 1;
$per_page = 20;

// -------------------------------------------------------------------
// Build WHERE clause
// -------------------------------------------------------------------
$where = "s.entity = ".$entity;

if ($search !== '') {
	$search_esc = $db->escape($search);
	$where .= " AND s.email LIKE '%".$search_esc."%'";
}

// -------------------------------------------------------------------
// Statistics
// -------------------------------------------------------------------
$sql_active = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_subscriber as s WHERE s.entity = ".$entity." AND s.status = 1";
$resql_active = $db->query($sql_active);
$obj_active = $resql_active ? $db->fetch_object($resql_active) : null;
$total_active = $obj_active ? (int) $obj_active->nb : 0;

$sql_unsub = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_subscriber as s WHERE s.entity = ".$entity." AND s.status = 0";
$resql_unsub = $db->query($sql_unsub);
$obj_unsub = $resql_unsub ? $db->fetch_object($resql_unsub) : null;
$total_unsubscribed = $obj_unsub ? (int) $obj_unsub->nb : 0;

$total_all = $total_active + $total_unsubscribed;

// -------------------------------------------------------------------
// Pagination
// -------------------------------------------------------------------
$sql_count = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_subscriber as s WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $current_p, $per_page);

// -------------------------------------------------------------------
// Fetch subscribers
// -------------------------------------------------------------------
$subscribers = array();
$sql = "SELECT s.rowid, s.email, s.status, s.date_creation";
$sql .= " FROM ".$prefix."spacart_subscriber as s";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY s.date_creation DESC";
$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$subscribers[] = $obj;
	}
	$db->free($resql);
}

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

// Build filter params for pagination links
$filter_params = array('page' => 'subscribers');
if ($search !== '') {
	$filter_params['search'] = $search;
}

// -------------------------------------------------------------------
// Include header
// -------------------------------------------------------------------
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1">Newsletter</h1>
		<p class="text-muted mb-0"><?php echo (int) $total_all; ?> abonne<?php echo $total_all > 1 ? 's' : ''; ?> au total</p>
	</div>
	<div>
		<a href="?page=subscribers&export=csv" class="btn btn-outline-success">
			<i class="bi bi-download me-1"></i>Exporter CSV
		</a>
	</div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
	<div class="col-sm-6 col-md-3">
		<div class="admin-card h-100">
			<div class="card-body text-center">
				<div class="fs-2 fw-bold text-success"><?php echo (int) $total_active; ?></div>
				<div class="text-muted small">Abonnes actifs</div>
			</div>
		</div>
	</div>
	<div class="col-sm-6 col-md-3">
		<div class="admin-card h-100">
			<div class="card-body text-center">
				<div class="fs-2 fw-bold text-secondary"><?php echo (int) $total_unsubscribed; ?></div>
				<div class="text-muted small">Desabonnes</div>
			</div>
		</div>
	</div>
</div>

<!-- Search Bar -->
<div class="filter-bar mb-4">
	<form method="get" action="" class="row g-2 align-items-end">
		<input type="hidden" name="page" value="subscribers">

		<div class="col-md-6">
			<label for="filter-search" class="form-label">Rechercher par email</label>
			<input type="text" class="form-control" id="filter-search" name="search"
				   value="<?php echo spacartAdminEscape($search); ?>"
				   placeholder="Adresse email...">
		</div>

		<div class="col-md-2">
			<button type="submit" class="btn btn-primary w-100">
				<i class="bi bi-search me-1"></i>Rechercher
			</button>
		</div>

		<?php if ($search !== ''): ?>
		<div class="col-md-2">
			<a href="?page=subscribers" class="btn btn-outline-secondary w-100">
				<i class="bi bi-x-circle me-1"></i>Reinitialiser
			</a>
		</div>
		<?php endif; ?>
	</form>
</div>

<!-- Subscribers Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th>Email</th>
						<th class="text-center">Statut</th>
						<th>Date inscription</th>
						<th class="text-center" style="width:120px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($subscribers)): ?>
					<tr>
						<td colspan="4">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-envelope"></i></div>
								<p>Aucun abonne trouve</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($subscribers as $sub): ?>
						<tr>
							<td>
								<a href="mailto:<?php echo spacartAdminEscape($sub->email); ?>">
									<?php echo spacartAdminEscape($sub->email); ?>
								</a>
							</td>
							<td class="text-center">
								<?php if ((int) $sub->status === 1): ?>
									<span class="badge badge-status status-active">Actif</span>
								<?php else: ?>
									<span class="badge badge-status status-inactive">Desabonne</span>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminFormatDate($sub->date_creation, 'd/m/Y H:i'); ?></td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Toggle status -->
									<form method="post" action="?page=subscribers" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_status">
										<input type="hidden" name="subscriber_id" value="<?php echo (int) $sub->rowid; ?>">
										<input type="hidden" name="new_status" value="<?php echo ((int) $sub->status === 1) ? '0' : '1'; ?>">
										<input type="hidden" name="redirect_search" value="<?php echo spacartAdminEscape($search); ?>">
										<input type="hidden" name="redirect_p" value="<?php echo (int) $pagination['current_page']; ?>">
										<?php if ((int) $sub->status === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Desabonner" aria-label="Desabonner"
													data-confirm="Desabonner cet email ?">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Reabonner" aria-label="Reabonner">
												<i class="bi bi-toggle-off"></i>
											</button>
										<?php endif; ?>
									</form>

									<!-- Delete -->
									<form method="post" action="?page=subscribers" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="subscriber_id" value="<?php echo (int) $sub->rowid; ?>">
										<input type="hidden" name="redirect_search" value="<?php echo spacartAdminEscape($search); ?>">
										<input type="hidden" name="redirect_p" value="<?php echo (int) $pagination['current_page']; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer l'abonne"
												data-confirm="Supprimer definitivement cet abonne ?">
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
				sur <?php echo (int) $pagination['total']; ?> abonne<?php echo $pagination['total'] > 1 ? 's' : ''; ?>
			</div>
			<nav aria-label="Pagination abonnes">
				<ul class="pagination pagination-sm mb-0">
					<?php
					// Previous
					$prev_disabled = ($pagination['current_page'] <= 1) ? ' disabled' : '';
					$prev_params = array_merge($filter_params, array('p' => $pagination['current_page'] - 1));
					?>
					<li class="page-item<?php echo $prev_disabled; ?>">
						<a class="page-link" href="?<?php echo http_build_query($prev_params); ?>" aria-label="Precedent">
							<span aria-hidden="true">&laquo;</span>
						</a>
					</li>

					<?php
					$total_pages = $pagination['total_pages'];
					$cp = $pagination['current_page'];

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
							<a class="page-link" href="?<?php echo http_build_query(array_merge($filter_params, array('p' => 1))); ?>">1</a>
						</li>
						<?php if ($start > 2): ?>
						<li class="page-item disabled"><span class="page-link">&hellip;</span></li>
						<?php endif; ?>
					<?php endif; ?>

					<?php for ($i = $start; $i <= $end; $i++): ?>
						<li class="page-item<?php echo ($i === $cp) ? ' active' : ''; ?>">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($filter_params, array('p' => $i))); ?>"><?php echo $i; ?></a>
						</li>
					<?php endfor; ?>

					<?php if ($end < $total_pages): ?>
						<?php if ($end < $total_pages - 1): ?>
						<li class="page-item disabled"><span class="page-link">&hellip;</span></li>
						<?php endif; ?>
						<li class="page-item">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($filter_params, array('p' => $total_pages))); ?>"><?php echo $total_pages; ?></a>
						</li>
					<?php endif; ?>

					<?php
					// Next
					$next_disabled = ($pagination['current_page'] >= $total_pages) ? ' disabled' : '';
					$next_params = array_merge($filter_params, array('p' => $pagination['current_page'] + 1));
					?>
					<li class="page-item<?php echo $next_disabled; ?>">
						<a class="page-link" href="?<?php echo http_build_query($next_params); ?>" aria-label="Suivant">
							<span aria-hidden="true">&raquo;</span>
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
