<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/invoices.php
 * \ingroup    spacart
 * \brief      SpaCart Admin - Invoice listing (Dolibarr factures linked to SpaCart)
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title   = 'Factures';
$current_page = 'invoices';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// ============================================================
// CSV Export
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
	// Build WHERE for export (same filters as listing)
	$exp_search = trim($_GET['search'] ?? '');
	$exp_status = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : null;

	$exp_where = "f.entity = ".$entity." AND f.module_source = 'spacart'";
	if ($exp_search !== '') {
		$esc = $db->escape($exp_search);
		$exp_where .= " AND (f.ref LIKE '%".$esc."%' OR s.nom LIKE '%".$esc."%')";
	}
	if ($exp_status !== null) {
		if ($exp_status === 2) {
			// paid
			$exp_where .= " AND f.paye = 1";
		} elseif ($exp_status === 1) {
			// validated (not paid)
			$exp_where .= " AND f.fk_statut = 1 AND f.paye = 0";
		} elseif ($exp_status === 0) {
			// draft
			$exp_where .= " AND f.fk_statut = 0";
		}
	}

	$sql_exp  = "SELECT f.ref, s.nom as customer_name, f.datef, f.total_ttc, f.fk_statut, f.paye";
	$sql_exp .= " FROM ".$prefix."facture as f";
	$sql_exp .= " LEFT JOIN ".$prefix."societe as s ON s.rowid = f.fk_soc";
	$sql_exp .= " WHERE ".$exp_where;
	$sql_exp .= " ORDER BY f.datef DESC";

	header('Content-Type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="spacart_factures_'.date('Y-m-d').'.csv"');
	header('Pragma: no-cache');
	header('Expires: 0');

	$output = fopen('php://output', 'w');
	// BOM for Excel UTF-8
	fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
	fputcsv($output, array('Reference', 'Client', 'Date', 'Total TTC', 'Statut'), ';');

	$resql = $db->query($sql_exp);
	if ($resql) {
		while ($row = $db->fetch_object($resql)) {
			$status_csv = 'Brouillon';
			if ((int) $row->paye === 1) {
				$status_csv = 'Payee';
			} elseif ((int) $row->fk_statut === 1) {
				$status_csv = 'Validee';
			}
			fputcsv($output, array(
				$row->ref,
				$row->customer_name,
				$row->datef,
				number_format((float) $row->total_ttc, 2, ',', ''),
				$status_csv,
			), ';');
		}
		$db->free($resql);
	}
	fclose($output);
	exit;
}

// ============================================================
// Filters from GET
// ============================================================
$search   = trim($_GET['search'] ?? '');
$status   = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : null;
$pg       = max(1, (int) ($_GET['pg'] ?? 1));
$per_page = 20;

// ============================================================
// Build WHERE clause
// ============================================================
$where = "f.entity = ".$entity." AND f.module_source = 'spacart'";

if ($search !== '') {
	$esc_search = $db->escape($search);
	$where .= " AND (f.ref LIKE '%".$esc_search."%' OR s.nom LIKE '%".$esc_search."%')";
}
if ($status !== null) {
	if ($status === 2) {
		// paid
		$where .= " AND f.paye = 1";
	} elseif ($status === 1) {
		// validated (not paid)
		$where .= " AND f.fk_statut = 1 AND f.paye = 0";
	} elseif ($status === 0) {
		// draft
		$where .= " AND f.fk_statut = 0";
	}
}

// ============================================================
// Pagination
// ============================================================
$sql_count  = "SELECT COUNT(*) as nb FROM ".$prefix."facture as f";
$sql_count .= " LEFT JOIN ".$prefix."societe as s ON s.rowid = f.fk_soc";
$sql_count .= " WHERE ".$where;

$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// ============================================================
// Fetch invoices
// ============================================================
$sql  = "SELECT f.rowid, f.ref, f.datef, f.total_ht, f.total_ttc, f.fk_statut, f.paye,";
$sql .= " s.nom as customer_name, s.email";
$sql .= " FROM ".$prefix."facture as f";
$sql .= " LEFT JOIN ".$prefix."societe as s ON s.rowid = f.fk_soc";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY f.datef DESC";
$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

$invoices = array();
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$invoices[] = $obj;
	}
	$db->free($resql);
}

// Build current filter query string for pagination links
$filter_params = array('page' => 'invoices');
if ($search !== '')    $filter_params['search'] = $search;
if ($status !== null)  $filter_params['status'] = $status;

// Build CSV export URL with current filters
$csv_params = array_merge($filter_params, array('action' => 'export_csv'));
$csv_url = '?'.http_build_query($csv_params);

/**
 * Get invoice status badge HTML
 *
 * Dolibarr facture statuses:
 *   fk_statut=0           => Brouillon  (secondary)
 *   fk_statut=1, paye=0   => Validee    (warning)
 *   paye=1                => Payee      (success)
 *
 * @param  int $fk_statut Dolibarr facture status
 * @param  int $paye      Dolibarr paye flag
 * @return string HTML badge
 */
function spacartAdminInvoiceStatusBadge($fk_statut, $paye)
{
	$fk_statut = (int) $fk_statut;
	$paye      = (int) $paye;

	if ($paye === 1) {
		return spacartAdminStatusBadge('delivered', 'Payee');
	}
	if ($fk_statut === 1) {
		return spacartAdminStatusBadge('validated', 'Validee');
	}
	// draft or other
	return spacartAdminStatusBadge('draft', 'Brouillon');
}

// ============================================================
// Include header
// ============================================================
require_once __DIR__.'/../includes/header.php';
?>

<!-- Page header -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between mb-4">
	<h1 class="h3 mb-0"><i class="bi bi-receipt me-2"></i>Factures</h1>
	<div class="d-flex align-items-center gap-2">
		<span class="text-muted"><?php echo (int) $pagination['total']; ?> facture(s) au total</span>
		<a href="<?php echo spacartAdminEscape($csv_url); ?>" class="btn btn-sm btn-outline-success" title="Exporter en CSV">
			<i class="bi bi-download me-1"></i>Export CSV
		</a>
	</div>
</div>

<!-- ============================================================== -->
<!-- Filter bar -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="invoices">
			<div class="row g-3 align-items-end">
				<!-- Search -->
				<div class="col-12 col-md-4">
					<label for="filter_search" class="form-label">Recherche</label>
					<input type="text" class="form-control" id="filter_search" name="search"
						   value="<?php echo spacartAdminEscape($search); ?>"
						   placeholder="Ref. facture ou nom client">
				</div>

				<!-- Status -->
				<div class="col-6 col-md-3">
					<label for="filter_status" class="form-label">Statut</label>
					<select class="form-select" id="filter_status" name="status">
						<option value="">Tous</option>
						<option value="0"<?php echo ($status === 0) ? ' selected' : ''; ?>>Brouillon</option>
						<option value="1"<?php echo ($status === 1) ? ' selected' : ''; ?>>Validee</option>
						<option value="2"<?php echo ($status === 2) ? ' selected' : ''; ?>>Payee</option>
					</select>
				</div>

				<!-- Filter button -->
				<div class="col-6 col-md-3 col-lg-2">
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>

				<!-- Reset link -->
				<div class="col-12 col-md-2 col-lg-1">
					<a href="?page=invoices" class="btn btn-outline-secondary w-100" title="Reinitialiser">
						<i class="bi bi-x-circle"></i>
					</a>
				</div>
			</div>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Invoices table -->
<!-- ============================================================== -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th>Ref.</th>
						<th>Client</th>
						<th>Date</th>
						<th class="text-end">Total TTC</th>
						<th>Statut</th>
						<th style="width:120px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($invoices)): ?>
						<tr>
							<td colspan="6">
								<div class="empty-state-inline">
									<div class="empty-state-icon"><i class="bi bi-receipt"></i></div>
									<p>Aucune facture trouvee</p>
									<small class="text-muted">Modifiez vos filtres ou attendez de nouvelles factures</small>
								</div>
							</td>
						</tr>
					<?php else: ?>
						<?php foreach ($invoices as $inv): ?>
						<tr>
							<td>
								<strong><?php echo spacartAdminEscape($inv->ref); ?></strong>
							</td>
							<td>
								<?php echo spacartAdminEscape($inv->customer_name); ?>
								<?php if (!empty($inv->email)): ?>
									<br><small class="text-muted"><?php echo spacartAdminEscape($inv->email); ?></small>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminFormatDate($inv->datef, 'd/m/Y'); ?></td>
							<td class="text-end"><?php echo spacartAdminFormatPrice((float) $inv->total_ttc); ?></td>
							<td><?php echo spacartAdminInvoiceStatusBadge($inv->fk_statut, $inv->paye); ?></td>
							<td>
								<a href="<?php echo DOL_URL_ROOT; ?>/compta/facture/card.php?facid=<?php echo (int) $inv->rowid; ?>"
								   class="btn btn-sm btn-outline-primary" title="Voir dans Dolibarr" aria-label="Voir la facture dans Dolibarr" target="_blank">
									<i class="bi bi-eye me-1"></i>Dolibarr
								</a>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
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
	<nav aria-label="Pagination factures">
		<ul class="pagination pagination-sm mb-0">
			<?php
			$total_pages = $pagination['total_pages'];
			$current_pg  = $pagination['current_page'];

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
// Include footer
// ============================================================
require_once __DIR__.'/../includes/footer.php';
