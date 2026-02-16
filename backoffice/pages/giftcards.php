<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/giftcards.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Gift card management with search, pagination
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title   = 'Cartes cadeaux';
$current_page = 'giftcards';

global $db, $conf;

// ============================================================
// POST actions
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=giftcards');
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Create gift card ---
	if ($action === 'add_giftcard') {
		$initial_amount  = (float) ($_POST['initial_amount'] ?? 0);
		$sender_email    = trim($_POST['sender_email'] ?? '');
		$recipient_email = trim($_POST['recipient_email'] ?? '');
		$date_expiry     = trim($_POST['date_expiry'] ?? '');

		$errors = array();

		if ($initial_amount <= 0) {
			$errors[] = 'Le montant doit etre superieur a 0.';
		}
		if ($sender_email !== '' && !filter_var($sender_email, FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'L\'email de l\'expediteur n\'est pas valide.';
		}
		if ($recipient_email !== '' && !filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'L\'email du destinataire n\'est pas valide.';
		}
		if ($date_expiry !== '' && $date_expiry < date('Y-m-d')) {
			$errors[] = 'La date d\'expiration ne peut pas etre dans le passe.';
		}

		if (!empty($errors)) {
			foreach ($errors as $err) {
				spacartAdminFlash($err, 'danger');
			}
		} else {
			// Auto-generate unique gift card code: GC-XXXX-XXXX-XXXX
			$gc_code = 'GC-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4))
				.'-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4))
				.'-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

			// Ensure uniqueness
			$max_attempts = 10;
			$attempt = 0;
			while ($attempt < $max_attempts) {
				$sql_dup = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_giftcard";
				$sql_dup .= " WHERE code = '".$db->escape($gc_code)."'";
				$sql_dup .= " AND entity = ".(int) $conf->entity;
				$res_dup = $db->query($sql_dup);
				if ($res_dup && $db->num_rows($res_dup) === 0) {
					break;
				}
				$gc_code = 'GC-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4))
					.'-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4))
					.'-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
				$attempt++;
			}

			$sql_ins = "INSERT INTO ".MAIN_DB_PREFIX."spacart_giftcard";
			$sql_ins .= " (code, initial_amount, balance, sender_email, recipient_email, status, entity, date_creation, date_expiry)";
			$sql_ins .= " VALUES (";
			$sql_ins .= "'".$db->escape($gc_code)."',";
			$sql_ins .= " ".(float) $initial_amount.",";
			$sql_ins .= " ".(float) $initial_amount.","; // balance = initial amount on creation
			$sql_ins .= " ".($sender_email !== '' ? "'".$db->escape($sender_email)."'" : "NULL").",";
			$sql_ins .= " ".($recipient_email !== '' ? "'".$db->escape($recipient_email)."'" : "NULL").",";
			$sql_ins .= " 1,";
			$sql_ins .= " ".(int) $conf->entity.",";
			$sql_ins .= " NOW(),";
			$sql_ins .= " ".($date_expiry !== '' ? "'".$db->escape($date_expiry)."'" : "NULL");
			$sql_ins .= ")";

			if ($db->query($sql_ins)) {
				spacartAdminFlash('Carte cadeau "'.$gc_code.'" creee avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la creation de la carte cadeau.', 'danger');
			}
		}

		header('Location: ?page=giftcards');
		exit;
	}

	// --- Toggle gift card status ---
	if ($action === 'toggle_status') {
		$gc_id      = (int) ($_POST['gc_id'] ?? 0);
		$new_status = (int) ($_POST['new_status'] ?? 0);

		if ($gc_id > 0 && in_array($new_status, array(0, 1))) {
			$sql_upd = "UPDATE ".MAIN_DB_PREFIX."spacart_giftcard";
			$sql_upd .= " SET status = ".$new_status;
			$sql_upd .= " WHERE rowid = ".$gc_id;
			$sql_upd .= " AND entity = ".(int) $conf->entity;

			if ($db->query($sql_upd)) {
				$label = ($new_status === 1) ? 'activee' : 'desactivee';
				spacartAdminFlash('La carte cadeau a ete '.$label.'.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour du statut.', 'danger');
			}
		}

		header('Location: ?page=giftcards');
		exit;
	}
}

// ============================================================
// Filters from GET
// ============================================================
$search  = isset($_GET['search']) ? trim($_GET['search']) : '';
$pg      = max(1, (int) ($_GET['pg'] ?? 1));
$per_page = 20;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// ============================================================
// Build WHERE clause
// ============================================================
$where = "g.entity = ".$entity;

if ($search !== '') {
	$esc_search = $db->escape($search);
	$where .= " AND (g.code LIKE '%".$esc_search."%'";
	$where .= " OR g.sender_email LIKE '%".$esc_search."%'";
	$where .= " OR g.recipient_email LIKE '%".$esc_search."%')";
}

// ============================================================
// Pagination
// ============================================================
$sql_count  = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_giftcard as g WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// Total unfiltered for subtitle
$sql_total   = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_giftcard WHERE entity = ".$entity;
$resql_total = $db->query($sql_total);
$obj_total   = $db->fetch_object($resql_total);
$total_giftcards = $obj_total ? (int) $obj_total->nb : 0;

// ============================================================
// Fetch gift cards
// ============================================================
$giftcards = array();
$sql  = "SELECT g.rowid, g.code, g.initial_amount, g.balance, g.sender_email, g.recipient_email,";
$sql .= " g.status, g.date_creation, g.date_expiry";
$sql .= " FROM ".$prefix."spacart_giftcard as g";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY g.date_creation DESC";
$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$giftcards[] = $obj;
	}
	$db->free($resql);
}

// Build filter query string for pagination links
$filter_params = array('page' => 'giftcards');
if ($search !== '') $filter_params['search'] = $search;

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

/**
 * Get status badge HTML for gift card status
 *
 * @param  object $gc Gift card row object
 * @return string HTML badge
 */
function _giftcardStatusBadge($gc)
{
	if ((int) $gc->status === 0) {
		return spacartAdminStatusBadge('disabled', 'Desactivee');
	}
	if (!empty($gc->date_expiry) && $gc->date_expiry < date('Y-m-d')) {
		return spacartAdminStatusBadge('expired', 'Expiree');
	}
	if ((float) $gc->balance <= 0) {
		return spacartAdminStatusBadge('pending', 'Epuisee');
	}
	return spacartAdminStatusBadge('active', 'Active');
}

// ============================================================
// Include header
// ============================================================
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1">Cartes cadeaux</h1>
		<p class="text-muted mb-0"><?php echo (int) $total_giftcards; ?> carte<?php echo $total_giftcards > 1 ? 's' : ''; ?> cadeau<?php echo $total_giftcards > 1 ? 'x' : ''; ?> au total</p>
	</div>
	<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGiftcardModal">
		<i class="bi bi-plus-lg me-1"></i>Creer une carte cadeau
	</button>
</div>

<!-- ============================================================== -->
<!-- Filter bar -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="giftcards">
			<div class="row g-3 align-items-end">
				<!-- Search -->
				<div class="col-12 col-md-6">
					<label for="filter_search" class="form-label">Recherche par code ou email</label>
					<input type="text" class="form-control" id="filter_search" name="search"
						   value="<?php echo spacartAdminEscape($search); ?>"
						   placeholder="Code, email expediteur ou destinataire...">
				</div>

				<!-- Filter button -->
				<div class="col-6 col-md-2">
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>

				<!-- Reset -->
				<?php if ($search !== ''): ?>
				<div class="col-6 col-md-2">
					<a href="?page=giftcards" class="btn btn-outline-secondary w-100">
						<i class="bi bi-x-circle me-1"></i>Reinitialiser
					</a>
				</div>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Gift cards table -->
<!-- ============================================================== -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th>Code</th>
						<th class="text-end">Montant initial</th>
						<th class="text-end">Solde</th>
						<th>Expediteur</th>
						<th>Destinataire</th>
						<th class="text-center">Statut</th>
						<th>Creation</th>
						<th>Expiration</th>
						<th class="text-center" style="width:100px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($giftcards)): ?>
					<tr>
						<td colspan="9">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-gift"></i></div>
								<p>Aucune carte cadeau trouvee</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($giftcards as $gc): ?>
						<tr>
							<td>
								<strong class="font-monospace"><?php echo spacartAdminEscape($gc->code); ?></strong>
							</td>
							<td class="text-end">
								<?php echo spacartAdminFormatPrice($gc->initial_amount); ?>
							</td>
							<td class="text-end">
								<?php
								$balance = (float) $gc->balance;
								$initial = (float) $gc->initial_amount;
								$balance_class = '';
								if ($balance <= 0) {
									$balance_class = 'text-danger fw-bold';
								} elseif ($initial > 0 && $balance < ($initial * 0.25)) {
									$balance_class = 'text-warning';
								}
								?>
								<span class="<?php echo $balance_class; ?>"><?php echo spacartAdminFormatPrice($balance); ?></span>
								<?php if ($initial > 0): ?>
									<br>
									<small class="text-muted">
										<?php
										$pct_used = $initial > 0 ? round((($initial - $balance) / $initial) * 100) : 0;
										echo $pct_used.'% utilise';
										?>
									</small>
								<?php endif; ?>
							</td>
							<td>
								<?php echo !empty($gc->sender_email) ? spacartAdminEscape($gc->sender_email) : '<span class="text-muted">-</span>'; ?>
							</td>
							<td>
								<?php echo !empty($gc->recipient_email) ? spacartAdminEscape($gc->recipient_email) : '<span class="text-muted">-</span>'; ?>
							</td>
							<td class="text-center">
								<?php echo _giftcardStatusBadge($gc); ?>
							</td>
							<td><?php echo spacartAdminFormatDate($gc->date_creation, 'd/m/Y'); ?></td>
							<td>
								<?php if (!empty($gc->date_expiry)): ?>
									<?php
									$is_expired = ($gc->date_expiry < date('Y-m-d'));
									?>
									<span class="<?php echo $is_expired ? 'text-danger' : ''; ?>">
										<?php echo spacartAdminFormatDate($gc->date_expiry, 'd/m/Y'); ?>
									</span>
								<?php else: ?>
									<span class="text-muted">Illimitee</span>
								<?php endif; ?>
							</td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Toggle status -->
									<form method="post" action="?page=giftcards" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_status">
										<input type="hidden" name="gc_id" value="<?php echo (int) $gc->rowid; ?>">
										<input type="hidden" name="new_status" value="<?php echo ((int) $gc->status === 1) ? '0' : '1'; ?>">
										<?php if ((int) $gc->status === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Desactiver" aria-label="Desactiver la carte cadeau"
													data-confirm="Desactiver cette carte cadeau ?">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Activer" aria-label="Activer la carte cadeau"
													data-confirm="Activer cette carte cadeau ?">
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
				sur <?php echo (int) $pagination['total']; ?> carte<?php echo $pagination['total'] > 1 ? 's' : ''; ?>
			</div>
			<nav aria-label="Pagination cartes cadeaux">
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
<!-- Add Gift Card Modal -->
<!-- ============================================================== -->
<div class="modal fade" id="addGiftcardModal" tabindex="-1" aria-labelledby="addGiftcardModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" action="?page=giftcards">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="add_giftcard">

				<div class="modal-header">
					<h5 class="modal-title" id="addGiftcardModalLabel">
						<i class="bi bi-gift me-2"></i>Creer une carte cadeau
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
				</div>

				<div class="modal-body">
					<div class="alert alert-info small mb-3">
						<i class="bi bi-info-circle me-1"></i>
						Un code unique sera genere automatiquement au format <strong>GC-XXXX-XXXX-XXXX</strong>.
					</div>

					<!-- Initial amount -->
					<div class="mb-3">
						<label for="gc_initial_amount" class="form-label">Montant <span class="text-danger">*</span></label>
						<div class="input-group">
							<input type="number" class="form-control" id="gc_initial_amount" name="initial_amount"
								   required min="0.01" step="0.01" placeholder="50.00">
							<span class="input-group-text"><?php echo spacartAdminEscape(getDolGlobalString('SPACART_CURRENCY_SYMBOL', "\xe2\x82\xac")); ?></span>
						</div>
						<div class="form-text">Le solde initial de la carte cadeau.</div>
					</div>

					<!-- Sender email -->
					<div class="mb-3">
						<label for="gc_sender_email" class="form-label">Email expediteur</label>
						<input type="email" class="form-control" id="gc_sender_email" name="sender_email"
							   placeholder="expediteur@email.com">
						<div class="form-text">Adresse email de la personne qui offre la carte (optionnel).</div>
					</div>

					<!-- Recipient email -->
					<div class="mb-3">
						<label for="gc_recipient_email" class="form-label">Email destinataire</label>
						<input type="email" class="form-control" id="gc_recipient_email" name="recipient_email"
							   placeholder="destinataire@email.com">
						<div class="form-text">Adresse email de la personne qui recevra la carte (optionnel).</div>
					</div>

					<!-- Expiry date -->
					<div class="mb-3">
						<label for="gc_date_expiry" class="form-label">Date d'expiration</label>
						<input type="date" class="form-control" id="gc_date_expiry" name="date_expiry"
							   min="<?php echo date('Y-m-d'); ?>">
						<div class="form-text">Laisser vide pour une duree illimitee.</div>
					</div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
					<button type="submit" class="btn btn-primary">
						<i class="bi bi-check-lg me-1"></i>Creer la carte cadeau
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php
include __DIR__.'/../includes/footer.php';
