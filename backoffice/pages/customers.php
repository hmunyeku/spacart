<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/customers.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Customer list with search, filters, and pagination
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Clients';
$current_page = 'customers';

// -------------------------------------------------------------------
// Handle POST actions (toggle status)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	$action = isset($_POST['action']) ? $_POST['action'] : '';

	if ($action === 'toggle_status') {
		$customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
		$new_status = isset($_POST['new_status']) ? (int) $_POST['new_status'] : 0;

		if ($customer_id > 0 && in_array($new_status, array(0, 1))) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."spacart_customer";
			$sql .= " SET status = ".$new_status;
			$sql .= " WHERE rowid = ".$customer_id;
			$sql .= " AND entity = ".(int) $conf->entity;
			$resql = $db->query($sql);

			if ($resql) {
				$status_label = ($new_status === 1) ? 'active' : 'inactif';
				spacartAdminFlash('Le statut du client a ete mis a jour ('.$status_label.').', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour du statut.', 'danger');
			}
		} else {
			spacartAdminFlash('Parametres invalides.', 'danger');
		}

		// Redirect to avoid form resubmission (preserve filters)
		$redirect_params = array('page' => 'customers');
		if (!empty($_POST['redirect_search'])) {
			$redirect_params['search'] = $_POST['redirect_search'];
		}
		if (isset($_POST['redirect_status']) && $_POST['redirect_status'] !== '') {
			$redirect_params['status'] = $_POST['redirect_status'];
		}
		if (!empty($_POST['redirect_p'])) {
			$redirect_params['p'] = $_POST['redirect_p'];
		}
		header('Location: ?'.http_build_query($redirect_params));
		exit;
	}
}

// -------------------------------------------------------------------
// Include header
// -------------------------------------------------------------------
include __DIR__.'/../includes/header.php';

// -------------------------------------------------------------------
// Filter parameters
// -------------------------------------------------------------------
$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : -1;
$current_p = isset($_GET['p']) ? (int) $_GET['p'] : 1;
$per_page = 20;

// -------------------------------------------------------------------
// Build WHERE clause
// -------------------------------------------------------------------
$where = "c.entity = ".$entity;

if ($search !== '') {
	$search_esc = $db->escape($search);
	$where .= " AND (c.firstname LIKE '%".$search_esc."%'";
	$where .= " OR c.lastname LIKE '%".$search_esc."%'";
	$where .= " OR c.email LIKE '%".$search_esc."%'";
	$where .= " OR c.company_name LIKE '%".$search_esc."%')";
}

if ($filter_status !== -1) {
	$where .= " AND c.status = ".(int) $filter_status;
}

// -------------------------------------------------------------------
// Pagination
// -------------------------------------------------------------------
$sql_count = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_customer as c WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $current_p, $per_page);

// Total customers (unfiltered) for subtitle
$sql_total = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_customer as c WHERE c.entity = ".$entity;
$resql_total = $db->query($sql_total);
$obj_total = $db->fetch_object($resql_total);
$total_customers = $obj_total ? (int) $obj_total->nb : 0;

// -------------------------------------------------------------------
// Fetch customers
// -------------------------------------------------------------------
$customers = array();
$sql = "SELECT c.rowid, c.email, c.firstname, c.lastname, c.company_name, c.phone,";
$sql .= " c.fk_soc, c.status, c.date_creation, c.date_last_login";
$sql .= " FROM ".$prefix."spacart_customer as c";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY c.date_creation DESC";
$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$customers[] = $obj;
	}
}

// -------------------------------------------------------------------
// CSRF token for forms
// -------------------------------------------------------------------
$csrf_token = spacartAdminGetCSRFToken();
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1"><i class="bi bi-people me-2"></i>Clients</h1>
		<p class="text-muted mb-0"><?php echo (int) $total_customers; ?> client<?php echo $total_customers > 1 ? 's' : ''; ?> au total</p>
	</div>
	<button type="button" class="btn btn-outline-secondary" onclick="exportTableCSV('.admin-table', 'clients.csv')" title="Exporter en CSV" aria-label="Exporter en CSV">
		<i class="bi bi-download me-1"></i>Export CSV
	</button>
</div>

<!-- Filter Bar -->
<div class="filter-bar mb-4">
	<form method="get" action="" class="row g-2 align-items-end">
		<input type="hidden" name="page" value="customers">

		<div class="col-md-5">
			<label for="filter-search" class="form-label">Recherche</label>
			<input type="text" class="form-control" id="filter-search" name="search"
				   value="<?php echo spacartAdminEscape($search); ?>"
				   placeholder="Nom, email, societe...">
		</div>

		<div class="col-md-3">
			<label for="filter-status" class="form-label">Statut</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">Tous</option>
				<option value="1"<?php echo ($filter_status === 1) ? ' selected' : ''; ?>>Actif</option>
				<option value="0"<?php echo ($filter_status === 0) ? ' selected' : ''; ?>>Inactif</option>
			</select>
		</div>

		<div class="col-md-2">
			<button type="submit" class="btn btn-primary w-100">
				<i class="bi bi-funnel me-1"></i>Filtrer
			</button>
		</div>

		<?php if ($search !== '' || $filter_status !== -1): ?>
		<div class="col-md-2">
			<a href="?page=customers" class="btn btn-outline-secondary w-100">
				<i class="bi bi-x-circle me-1"></i>Reinitialiser
			</a>
		</div>
		<?php endif; ?>
	</form>
</div>

<!-- Customers Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th>Nom</th>
						<th>Email</th>
						<th>Societe</th>
						<th>Telephone</th>
						<th class="text-center">Statut</th>
						<th>Inscription</th>
						<th>Derniere connexion</th>
						<th class="text-center">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($customers)): ?>
					<tr>
						<td colspan="8" class="text-center text-muted py-4">
							<i class="bi bi-people fs-1 d-block mb-2"></i>
							Aucun client trouve.
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($customers as $customer): ?>
						<tr>
							<td>
								<strong><?php echo spacartAdminEscape(trim($customer->firstname.' '.$customer->lastname)); ?></strong>
							</td>
							<td>
								<a href="mailto:<?php echo spacartAdminEscape($customer->email); ?>">
									<?php echo spacartAdminEscape($customer->email); ?>
								</a>
							</td>
							<td><?php echo spacartAdminEscape($customer->company_name ?: '-'); ?></td>
							<td><?php echo spacartAdminEscape($customer->phone ?: '-'); ?></td>
							<td class="text-center">
								<?php if ((int) $customer->status === 1): ?>
									<span class="badge badge-status status-active">Actif</span>
								<?php else: ?>
									<span class="badge badge-status status-inactive">Inactif</span>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminFormatDate($customer->date_creation, 'd/m/Y'); ?></td>
							<td><?php echo $customer->date_last_login ? spacartAdminFormatDate($customer->date_last_login) : '<span class="text-muted">-</span>'; ?></td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- View customer -->
									<a href="?page=customer_view&amp;id=<?php echo (int) $customer->rowid; ?>"
									   class="btn btn-sm btn-outline-primary" title="Voir le client" aria-label="Voir le client">
										<i class="bi bi-eye"></i>
									</a>

									<!-- Toggle status -->
									<form method="post" action="?page=customers" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_status">
										<input type="hidden" name="customer_id" value="<?php echo (int) $customer->rowid; ?>">
										<input type="hidden" name="new_status" value="<?php echo ((int) $customer->status === 1) ? '0' : '1'; ?>">
										<input type="hidden" name="redirect_search" value="<?php echo spacartAdminEscape($search); ?>">
										<input type="hidden" name="redirect_status" value="<?php echo ($filter_status !== -1) ? (int) $filter_status : ''; ?>">
										<input type="hidden" name="redirect_p" value="<?php echo (int) $pagination['current_page']; ?>">
										<?php if ((int) $customer->status === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Desactiver" aria-label="Desactiver le client" data-confirm="Desactiver ce client ?">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Activer" aria-label="Activer le client" data-confirm="Activer ce client ?">
												<i class="bi bi-toggle-off"></i>
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
	</div>

	<?php if ($pagination['total_pages'] > 1): ?>
	<!-- Pagination -->
	<div class="card-footer">
		<div class="d-flex flex-wrap justify-content-between align-items-center">
			<div class="text-muted small mb-2 mb-md-0">
				Affichage de <?php echo (int) $pagination['offset'] + 1; ?>
				a <?php echo min($pagination['offset'] + $pagination['limit'], $pagination['total']); ?>
				sur <?php echo (int) $pagination['total']; ?> client<?php echo $pagination['total'] > 1 ? 's' : ''; ?>
			</div>
			<nav aria-label="Pagination clients">
				<ul class="pagination pagination-sm mb-0">
					<?php
					// Build base URL with current filters
					$base_params = array('page' => 'customers');
					if ($search !== '') {
						$base_params['search'] = $search;
					}
					if ($filter_status !== -1) {
						$base_params['status'] = $filter_status;
					}

					// Previous
					$prev_disabled = ($pagination['current_page'] <= 1) ? ' disabled' : '';
					$prev_params = array_merge($base_params, array('p' => $pagination['current_page'] - 1));
					?>
					<li class="page-item<?php echo $prev_disabled; ?>">
						<a class="page-link" href="?<?php echo http_build_query($prev_params); ?>" aria-label="Precedent">
							<i class="bi bi-chevron-left"></i>
						</a>
					</li>

					<?php
					// Page numbers with ellipsis
					$total_pages = $pagination['total_pages'];
					$cp = $pagination['current_page'];

					// Calculate range to display
					$start = max(1, $cp - 2);
					$end = min($total_pages, $cp + 2);

					// Adjust if near edges
					if ($cp <= 3) {
						$end = min($total_pages, 5);
					}
					if ($cp >= $total_pages - 2) {
						$start = max(1, $total_pages - 4);
					}

					if ($start > 1): ?>
						<li class="page-item">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($base_params, array('p' => 1))); ?>">1</a>
						</li>
						<?php if ($start > 2): ?>
						<li class="page-item disabled"><span class="page-link">...</span></li>
						<?php endif; ?>
					<?php endif; ?>

					<?php for ($i = $start; $i <= $end; $i++): ?>
						<li class="page-item<?php echo ($i === $cp) ? ' active' : ''; ?>">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($base_params, array('p' => $i))); ?>"><?php echo $i; ?></a>
						</li>
					<?php endfor; ?>

					<?php if ($end < $total_pages): ?>
						<?php if ($end < $total_pages - 1): ?>
						<li class="page-item disabled"><span class="page-link">...</span></li>
						<?php endif; ?>
						<li class="page-item">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($base_params, array('p' => $total_pages))); ?>"><?php echo $total_pages; ?></a>
						</li>
					<?php endif; ?>

					<?php
					// Next
					$next_disabled = ($pagination['current_page'] >= $total_pages) ? ' disabled' : '';
					$next_params = array_merge($base_params, array('p' => $pagination['current_page'] + 1));
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
