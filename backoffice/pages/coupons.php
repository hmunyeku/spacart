<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/coupons.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Coupon management with search, filters, pagination
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title   = 'Coupons';
$current_page = 'coupons';

global $db, $conf;

// ============================================================
// POST actions
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=coupons');
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Add new coupon ---
	if ($action === 'add_coupon') {
		$code       = strtoupper(trim($_POST['code'] ?? ''));
		$type       = in_array($_POST['type'] ?? '', array('percent', 'amount')) ? $_POST['type'] : 'percent';
		$value      = (float) ($_POST['value'] ?? 0);
		$min_order  = (float) ($_POST['min_order'] ?? 0);
		$max_uses   = (int) ($_POST['max_uses'] ?? 0);
		$date_start = trim($_POST['date_start'] ?? '');
		$date_end   = trim($_POST['date_end'] ?? '');

		$errors = array();

		if ($code === '') {
			$errors[] = 'Le code coupon est obligatoire.';
		}
		if ($value <= 0) {
			$errors[] = 'La valeur doit etre superieure a 0.';
		}
		if ($type === 'percent' && $value > 100) {
			$errors[] = 'Un pourcentage ne peut pas depasser 100%.';
		}
		if ($date_start !== '' && $date_end !== '' && $date_start > $date_end) {
			$errors[] = 'La date de fin doit etre posterieure a la date de debut.';
		}

		// Check for duplicate code within entity
		if (empty($errors)) {
			$sql_dup = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_coupon";
			$sql_dup .= " WHERE code = '".$db->escape($code)."'";
			$sql_dup .= " AND entity = ".(int) $conf->entity;
			$res_dup = $db->query($sql_dup);
			if ($res_dup && $db->num_rows($res_dup) > 0) {
				$errors[] = 'Un coupon avec ce code existe deja.';
			}
		}

		if (!empty($errors)) {
			foreach ($errors as $err) {
				spacartAdminFlash($err, 'danger');
			}
		} else {
			$sql_ins = "INSERT INTO ".MAIN_DB_PREFIX."spacart_coupon";
			$sql_ins .= " (code, type, value, min_order, max_uses, used_count, date_start, date_end, status, entity, date_creation)";
			$sql_ins .= " VALUES (";
			$sql_ins .= "'".$db->escape($code)."',";
			$sql_ins .= " '".$db->escape($type)."',";
			$sql_ins .= " ".(float) $value.",";
			$sql_ins .= " ".(float) $min_order.",";
			$sql_ins .= " ".(int) $max_uses.",";
			$sql_ins .= " 0,";
			$sql_ins .= " ".($date_start !== '' ? "'".$db->escape($date_start)."'" : "NULL").",";
			$sql_ins .= " ".($date_end !== '' ? "'".$db->escape($date_end)."'" : "NULL").",";
			$sql_ins .= " 1,";
			$sql_ins .= " ".(int) $conf->entity.",";
			$sql_ins .= " NOW()";
			$sql_ins .= ")";

			if ($db->query($sql_ins)) {
				spacartAdminFlash('Coupon "'.$code.'" cree avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la creation du coupon.', 'danger');
			}
		}

		header('Location: ?page=coupons');
		exit;
	}

	// --- Toggle coupon status ---
	if ($action === 'toggle_status') {
		$coupon_id  = (int) ($_POST['coupon_id'] ?? 0);
		$new_status = (int) ($_POST['new_status'] ?? 0);

		if ($coupon_id > 0 && in_array($new_status, array(0, 1))) {
			$sql_upd = "UPDATE ".MAIN_DB_PREFIX."spacart_coupon";
			$sql_upd .= " SET status = ".$new_status;
			$sql_upd .= " WHERE rowid = ".$coupon_id;
			$sql_upd .= " AND entity = ".(int) $conf->entity;

			if ($db->query($sql_upd)) {
				$label = ($new_status === 1) ? 'active' : 'desactive';
				spacartAdminFlash('Le coupon a ete '.$label.'.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour du statut.', 'danger');
			}
		}

		header('Location: ?page=coupons');
		exit;
	}

	// --- Delete coupon ---
	if ($action === 'delete_coupon') {
		$coupon_id = (int) ($_POST['coupon_id'] ?? 0);

		if ($coupon_id > 0) {
			$sql_del = "DELETE FROM ".MAIN_DB_PREFIX."spacart_coupon";
			$sql_del .= " WHERE rowid = ".$coupon_id;
			$sql_del .= " AND entity = ".(int) $conf->entity;

			if ($db->query($sql_del)) {
				spacartAdminFlash('Coupon supprime avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la suppression du coupon.', 'danger');
			}
		}

		header('Location: ?page=coupons');
		exit;
	}
}

// ============================================================
// Filters from GET
// ============================================================
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : '';
$pg            = max(1, (int) ($_GET['pg'] ?? 1));
$per_page      = 20;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// ============================================================
// Build WHERE clause
// ============================================================
$where = "c.entity = ".$entity;

if ($search !== '') {
	$esc_search = $db->escape($search);
	$where .= " AND c.code LIKE '%".$esc_search."%'";
}

if ($filter_status !== '') {
	if ($filter_status === 'active') {
		// Active: status = 1 AND (date_end IS NULL OR date_end >= CURDATE()) AND (date_start IS NULL OR date_start <= CURDATE())
		$where .= " AND c.status = 1";
		$where .= " AND (c.date_end IS NULL OR c.date_end >= CURDATE())";
		$where .= " AND (c.date_start IS NULL OR c.date_start <= CURDATE())";
	} elseif ($filter_status === 'expired') {
		// Expired: date_end < CURDATE() and status still 1
		$where .= " AND c.date_end IS NOT NULL AND c.date_end < CURDATE()";
	} elseif ($filter_status === 'disabled') {
		$where .= " AND c.status = 0";
	}
}

// ============================================================
// Pagination
// ============================================================
$sql_count  = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_coupon as c WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// Total unfiltered for subtitle
$sql_total   = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_coupon WHERE entity = ".$entity;
$resql_total = $db->query($sql_total);
$obj_total   = $db->fetch_object($resql_total);
$total_coupons = $obj_total ? (int) $obj_total->nb : 0;

// ============================================================
// Fetch coupons
// ============================================================
$coupons = array();
$sql  = "SELECT c.rowid, c.code, c.type, c.value, c.min_order, c.max_uses, c.used_count,";
$sql .= " c.date_start, c.date_end, c.status, c.date_creation";
$sql .= " FROM ".$prefix."spacart_coupon as c";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY c.date_creation DESC";
$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$coupons[] = $obj;
	}
	$db->free($resql);
}

// Build filter query string for pagination links
$filter_params = array('page' => 'coupons');
if ($search !== '')        $filter_params['search'] = $search;
if ($filter_status !== '') $filter_params['status'] = $filter_status;

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

/**
 * Determine effective coupon status for display
 *
 * @param  object $coupon Coupon row object
 * @return string 'active', 'expired', or 'disabled'
 */
function _couponEffectiveStatus($coupon)
{
	if ((int) $coupon->status === 0) {
		return 'disabled';
	}
	if (!empty($coupon->date_end) && $coupon->date_end < date('Y-m-d')) {
		return 'expired';
	}
	if (!empty($coupon->date_start) && $coupon->date_start > date('Y-m-d')) {
		return 'scheduled';
	}
	return 'active';
}

/**
 * Get status badge HTML for coupon status
 *
 * @param  string $status Effective status
 * @return string HTML badge
 */
function _couponStatusBadge($status)
{
	$map = array(
		'active'    => array('active', 'Actif'),
		'expired'   => array('expired', 'Expire'),
		'disabled'  => array('disabled', 'Desactive'),
		'scheduled' => array('info', 'Planifie'),
	);
	if (isset($map[$status])) {
		return spacartAdminStatusBadge($map[$status][0], $map[$status][1]);
	}
	return spacartAdminStatusBadge('inactive', 'Inconnu');
}

// ============================================================
// Include header
// ============================================================
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1">Coupons</h1>
		<p class="text-muted mb-0"><?php echo (int) $total_coupons; ?> coupon<?php echo $total_coupons > 1 ? 's' : ''; ?> au total</p>
	</div>
	<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCouponModal">
		<i class="bi bi-plus-lg me-1"></i>Ajouter un coupon
	</button>
</div>

<!-- ============================================================== -->
<!-- Filter bar -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="coupons">
			<div class="row g-3 align-items-end">
				<!-- Search -->
				<div class="col-12 col-md-4">
					<label for="filter_search" class="form-label">Recherche par code</label>
					<input type="text" class="form-control" id="filter_search" name="search"
						   value="<?php echo spacartAdminEscape($search); ?>"
						   placeholder="Code coupon...">
				</div>

				<!-- Status filter -->
				<div class="col-6 col-md-3">
					<label for="filter_status" class="form-label">Statut</label>
					<select class="form-select" id="filter_status" name="status">
						<option value="">Tous</option>
						<option value="active"<?php echo ($filter_status === 'active') ? ' selected' : ''; ?>>Actif</option>
						<option value="expired"<?php echo ($filter_status === 'expired') ? ' selected' : ''; ?>>Expire</option>
						<option value="disabled"<?php echo ($filter_status === 'disabled') ? ' selected' : ''; ?>>Desactive</option>
					</select>
				</div>

				<!-- Filter button -->
				<div class="col-6 col-md-2">
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>

				<!-- Reset -->
				<?php if ($search !== '' || $filter_status !== ''): ?>
				<div class="col-12 col-md-2">
					<a href="?page=coupons" class="btn btn-outline-secondary w-100">
						<i class="bi bi-x-circle me-1"></i>Reinitialiser
					</a>
				</div>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Coupons table -->
<!-- ============================================================== -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th>Code</th>
						<th>Type</th>
						<th class="text-end">Valeur</th>
						<th class="text-end">Commande min.</th>
						<th class="text-center">Utilisation</th>
						<th>Debut</th>
						<th>Fin</th>
						<th class="text-center">Statut</th>
						<th>Creation</th>
						<th class="text-center" style="width:140px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($coupons)): ?>
					<tr>
						<td colspan="10">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-ticket-perforated"></i></div>
								<p>Aucun coupon trouve</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($coupons as $coupon):
							$eff_status = _couponEffectiveStatus($coupon);
						?>
						<tr>
							<td>
								<strong class="font-monospace"><?php echo spacartAdminEscape($coupon->code); ?></strong>
							</td>
							<td>
								<?php if ($coupon->type === 'percent'): ?>
									<span class="badge bg-info text-dark">Pourcentage</span>
								<?php else: ?>
									<span class="badge bg-warning text-dark">Montant fixe</span>
								<?php endif; ?>
							</td>
							<td class="text-end">
								<?php if ($coupon->type === 'percent'): ?>
									<?php echo number_format((float) $coupon->value, 2, ',', ' '); ?>%
								<?php else: ?>
									<?php echo spacartAdminFormatPrice($coupon->value); ?>
								<?php endif; ?>
							</td>
							<td class="text-end">
								<?php echo (float) $coupon->min_order > 0 ? spacartAdminFormatPrice($coupon->min_order) : '<span class="text-muted">-</span>'; ?>
							</td>
							<td class="text-center">
								<?php
								$used  = (int) $coupon->used_count;
								$max   = (int) $coupon->max_uses;
								$usage = $used.($max > 0 ? ' / '.$max : ' / illimite');
								// Color warning if close to limit
								$usage_class = '';
								if ($max > 0 && $used >= $max) {
									$usage_class = 'text-danger fw-bold';
								} elseif ($max > 0 && $used >= ($max * 0.8)) {
									$usage_class = 'text-warning';
								}
								?>
								<span class="<?php echo $usage_class; ?>"><?php echo $usage; ?></span>
							</td>
							<td><?php echo !empty($coupon->date_start) ? spacartAdminFormatDate($coupon->date_start, 'd/m/Y') : '<span class="text-muted">-</span>'; ?></td>
							<td><?php echo !empty($coupon->date_end) ? spacartAdminFormatDate($coupon->date_end, 'd/m/Y') : '<span class="text-muted">-</span>'; ?></td>
							<td class="text-center">
								<?php echo _couponStatusBadge($eff_status); ?>
							</td>
							<td><?php echo spacartAdminFormatDate($coupon->date_creation, 'd/m/Y'); ?></td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Toggle status -->
									<form method="post" action="?page=coupons" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_status">
										<input type="hidden" name="coupon_id" value="<?php echo (int) $coupon->rowid; ?>">
										<input type="hidden" name="new_status" value="<?php echo ((int) $coupon->status === 1) ? '0' : '1'; ?>">
										<?php if ((int) $coupon->status === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Desactiver" aria-label="Desactiver le coupon"
													data-confirm="Desactiver ce coupon ?">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Activer" aria-label="Activer le coupon"
													data-confirm="Activer ce coupon ?">
												<i class="bi bi-toggle-off"></i>
											</button>
										<?php endif; ?>
									</form>

									<!-- Delete -->
									<form method="post" action="?page=coupons" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete_coupon">
										<input type="hidden" name="coupon_id" value="<?php echo (int) $coupon->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer le coupon"
												data-confirm="Supprimer definitivement ce coupon ?">
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
				sur <?php echo (int) $pagination['total']; ?> coupon<?php echo $pagination['total'] > 1 ? 's' : ''; ?>
			</div>
			<nav aria-label="Pagination coupons">
				<ul class="pagination pagination-sm mb-0">
					<?php
					$base_params = $filter_params;
					$total_pages = $pagination['total_pages'];
					$cp          = $pagination['current_page'];

					// Previous
					$prev_disabled = ($cp <= 1) ? ' disabled' : '';
					$prev_params   = array_merge($base_params, array('pg' => $cp - 1));
					?>
					<li class="page-item<?php echo $prev_disabled; ?>">
						<a class="page-link" href="?<?php echo http_build_query($prev_params); ?>" aria-label="Precedent">
							<i class="bi bi-chevron-left"></i>
						</a>
					</li>

					<?php
					// Page range
					$start = max(1, $cp - 2);
					$end   = min($total_pages, $cp + 2);
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
					// Next
					$next_disabled = ($cp >= $total_pages) ? ' disabled' : '';
					$next_params   = array_merge($base_params, array('pg' => $cp + 1));
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

<!-- ============================================================== -->
<!-- Add Coupon Modal -->
<!-- ============================================================== -->
<div class="modal fade" id="addCouponModal" tabindex="-1" aria-labelledby="addCouponModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" action="?page=coupons">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="add_coupon">

				<div class="modal-header">
					<h5 class="modal-title" id="addCouponModalLabel">
						<i class="bi bi-ticket-perforated me-2"></i>Ajouter un coupon
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
				</div>

				<div class="modal-body">
					<!-- Code -->
					<div class="mb-3">
						<label for="coupon_code" class="form-label">Code <span class="text-danger">*</span></label>
						<input type="text" class="form-control font-monospace" id="coupon_code" name="code"
							   required maxlength="64" placeholder="ex: PROMO2026"
							   style="text-transform: uppercase;">
						<div class="form-text">Le code sera converti en majuscules automatiquement.</div>
					</div>

					<!-- Type + Value side by side -->
					<div class="row mb-3">
						<div class="col-6">
							<label for="coupon_type" class="form-label">Type <span class="text-danger">*</span></label>
							<select class="form-select" id="coupon_type" name="type" required>
								<option value="percent">Pourcentage (%)</option>
								<option value="amount">Montant fixe</option>
							</select>
						</div>
						<div class="col-6">
							<label for="coupon_value" class="form-label">Valeur <span class="text-danger">*</span></label>
							<input type="number" class="form-control" id="coupon_value" name="value"
								   required min="0.01" step="0.01" placeholder="10">
						</div>
					</div>

					<!-- Min order -->
					<div class="mb-3">
						<label for="coupon_min_order" class="form-label">Commande minimum</label>
						<input type="number" class="form-control" id="coupon_min_order" name="min_order"
							   min="0" step="0.01" value="0" placeholder="0">
						<div class="form-text">Montant minimum du panier pour appliquer ce coupon (0 = pas de minimum).</div>
					</div>

					<!-- Max uses -->
					<div class="mb-3">
						<label for="coupon_max_uses" class="form-label">Nombre maximum d'utilisations</label>
						<input type="number" class="form-control" id="coupon_max_uses" name="max_uses"
							   min="0" step="1" value="0" placeholder="0">
						<div class="form-text">0 = utilisations illimitees.</div>
					</div>

					<!-- Date range -->
					<div class="row mb-3">
						<div class="col-6">
							<label for="coupon_date_start" class="form-label">Date debut</label>
							<input type="date" class="form-control" id="coupon_date_start" name="date_start">
						</div>
						<div class="col-6">
							<label for="coupon_date_end" class="form-label">Date fin</label>
							<input type="date" class="form-control" id="coupon_date_end" name="date_end">
						</div>
					</div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
					<button type="submit" class="btn btn-primary">
						<i class="bi bi-check-lg me-1"></i>Creer le coupon
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
include __DIR__.'/../includes/footer.php';
