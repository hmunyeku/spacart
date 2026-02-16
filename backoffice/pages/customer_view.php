<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/customer_view.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Single customer detail view
 *
 * Table: llx_spacart_customer (linked to llx_societe via fk_soc)
 * Also displays: orders (llx_commande), addresses (llx_spacart_address)
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

global $db, $conf;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
	spacartAdminFlash('Client introuvable.', 'danger');
	header('Location: ?page=customers');
	exit;
}

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// -------------------------------------------------------------------
// Handle POST actions (toggle status)
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=customer_view&id='.$id);
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	if ($action === 'toggle_status') {
		$new_status = isset($_POST['new_status']) ? (int) $_POST['new_status'] : 0;

		if (in_array($new_status, array(0, 1))) {
			$sql = "UPDATE ".$prefix."spacart_customer";
			$sql .= " SET status = ".$new_status;
			$sql .= " WHERE rowid = ".$id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				$label = ($new_status === 1) ? 'active' : 'desactive';
				spacartAdminFlash('Le client a ete '.$label.' avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour du statut.', 'danger');
			}
		} else {
			spacartAdminFlash('Parametres invalides.', 'danger');
		}

		header('Location: ?page=customer_view&id='.$id);
		exit;
	}
}

// -------------------------------------------------------------------
// Load customer data
// -------------------------------------------------------------------
$sql = "SELECT c.rowid, c.email, c.firstname, c.lastname, c.phone, c.fk_soc,";
$sql .= " c.status, c.date_creation";
$sql .= " FROM ".$prefix."spacart_customer as c";
$sql .= " WHERE c.rowid = ".$id;
$sql .= " AND c.entity = ".$entity;

$customer = null;
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
	$customer = $db->fetch_object($resql);
	$db->free($resql);
} else {
	spacartAdminFlash('Client introuvable.', 'danger');
	header('Location: ?page=customers');
	exit;
}

// -------------------------------------------------------------------
// Load linked Dolibarr third-party info (if fk_soc > 0)
// -------------------------------------------------------------------
$thirdparty = null;
if ((int) $customer->fk_soc > 0) {
	$sql_soc = "SELECT s.rowid, s.nom, s.email, s.address, s.zip, s.town, s.phone, s.siren, s.siret";
	$sql_soc .= " FROM ".$prefix."societe as s";
	$sql_soc .= " WHERE s.rowid = ".(int) $customer->fk_soc;
	$resql_soc = $db->query($sql_soc);
	if ($resql_soc && $db->num_rows($resql_soc) > 0) {
		$thirdparty = $db->fetch_object($resql_soc);
		$db->free($resql_soc);
	}
}

// -------------------------------------------------------------------
// Load orders for this customer (via fk_soc)
// -------------------------------------------------------------------
$orders = array();
if ((int) $customer->fk_soc > 0) {
	$sql_orders = "SELECT co.rowid, co.ref, co.date_commande, co.total_ttc, co.fk_statut";
	$sql_orders .= " FROM ".$prefix."commande as co";
	$sql_orders .= " WHERE co.fk_soc = ".(int) $customer->fk_soc;
	$sql_orders .= " AND co.entity = ".$entity;
	$sql_orders .= " ORDER BY co.date_commande DESC";
	$sql_orders .= " LIMIT 50";

	$resql_orders = $db->query($sql_orders);
	if ($resql_orders) {
		while ($obj = $db->fetch_object($resql_orders)) {
			$orders[] = $obj;
		}
		$db->free($resql_orders);
	}
}

// -------------------------------------------------------------------
// Load addresses
// -------------------------------------------------------------------
$addresses = array();
$sql_addr = "SELECT a.rowid, a.label, a.firstname, a.lastname, a.address, a.zip, a.city,";
$sql_addr .= " a.country, a.phone, a.is_default";
$sql_addr .= " FROM ".$prefix."spacart_address as a";
$sql_addr .= " WHERE a.fk_customer = ".$id;
$sql_addr .= " ORDER BY a.is_default DESC, a.rowid ASC";

$resql_addr = $db->query($sql_addr);
if ($resql_addr) {
	while ($obj = $db->fetch_object($resql_addr)) {
		$addresses[] = $obj;
	}
	$db->free($resql_addr);
}

// Page setup
$customer_fullname = trim($customer->firstname.' '.$customer->lastname);
if ($customer_fullname === '') {
	$customer_fullname = $customer->email;
}
$page_title = 'Detail client';
$current_page = 'customer_view';
$csrf_token = spacartAdminGetCSRFToken();

// -------------------------------------------------------------------
// Include header
// -------------------------------------------------------------------
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between mb-4">
	<div class="d-flex align-items-center gap-3 mb-2 mb-md-0">
		<a href="?page=customers" class="btn btn-outline-secondary btn-sm" title="Retour a la liste" aria-label="Retour a la liste des clients">
			<i class="bi bi-arrow-left"></i>
		</a>
		<?php
		$initials = 'C';
		if (!empty($customer->firstname) && !empty($customer->lastname)) {
			$initials = mb_strtoupper(mb_substr($customer->firstname, 0, 1).mb_substr($customer->lastname, 0, 1));
		} elseif (!empty($customer->firstname)) {
			$initials = mb_strtoupper(mb_substr($customer->firstname, 0, 2));
		} elseif (!empty($customer->email)) {
			$initials = mb_strtoupper(mb_substr($customer->email, 0, 2));
		}
		?>
		<div class="customer-avatar-lg"><?php echo spacartAdminEscape($initials); ?></div>
		<div>
			<h1 class="h3 mb-0">
				<?php echo spacartAdminEscape($customer_fullname); ?>
			</h1>
			<small class="text-muted"><?php echo spacartAdminEscape($customer->email); ?></small>
		</div>
		<?php if ((int) $customer->status === 1): ?>
			<?php echo spacartAdminStatusBadge('active', 'Actif'); ?>
		<?php else: ?>
			<?php echo spacartAdminStatusBadge('inactive', 'Inactif'); ?>
		<?php endif; ?>
	</div>

	<!-- Toggle status -->
	<div>
		<form method="post" action="?page=customer_view&id=<?php echo $id; ?>" class="d-inline">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="toggle_status">
			<input type="hidden" name="new_status" value="<?php echo ((int) $customer->status === 1) ? '0' : '1'; ?>">
			<?php if ((int) $customer->status === 1): ?>
				<button type="submit" class="btn btn-outline-warning btn-sm" data-confirm="Desactiver ce client ?">
					<i class="bi bi-toggle-on me-1"></i>Desactiver
				</button>
			<?php else: ?>
				<button type="submit" class="btn btn-outline-success btn-sm" data-confirm="Activer ce client ?">
					<i class="bi bi-toggle-off me-1"></i>Activer
				</button>
			<?php endif; ?>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- Customer info cards (2 columns) -->
<!-- ============================================================== -->
<div class="row g-4 mb-4">

	<!-- Left: Customer info -->
	<div class="col-12 col-lg-6">
		<div class="admin-card h-100">
			<div class="card-header">
				<h5 class="mb-0"><i class="bi bi-person me-2"></i>Informations client</h5>
			</div>
			<div class="card-body">
				<table class="table table-borderless mb-0">
					<tbody>
						<tr>
							<th class="text-muted" style="width:140px;">Nom complet</th>
							<td><?php echo spacartAdminEscape(trim($customer->firstname.' '.$customer->lastname) ?: '-'); ?></td>
						</tr>
						<tr>
							<th class="text-muted">Email</th>
							<td>
								<a href="mailto:<?php echo spacartAdminEscape($customer->email); ?>">
									<?php echo spacartAdminEscape($customer->email); ?>
								</a>
							</td>
						</tr>
						<tr>
							<th class="text-muted">Telephone</th>
							<td><?php echo !empty($customer->phone) ? spacartAdminEscape($customer->phone) : '<span class="text-muted">-</span>'; ?></td>
						</tr>
						<tr>
							<th class="text-muted">Date inscription</th>
							<td><?php echo spacartAdminFormatDate($customer->date_creation); ?></td>
						</tr>
						<tr>
							<th class="text-muted">Statut</th>
							<td>
								<?php if ((int) $customer->status === 1): ?>
									<span class="badge badge-status status-active">Actif</span>
								<?php else: ?>
									<span class="badge badge-status status-inactive">Inactif</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th class="text-muted">ID Client</th>
							<td><code>#<?php echo (int) $customer->rowid; ?></code></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Right: Dolibarr Third-Party link -->
	<div class="col-12 col-lg-6">
		<div class="admin-card h-100">
			<div class="card-header">
				<h5 class="mb-0"><i class="bi bi-building me-2"></i>Tiers Dolibarr</h5>
			</div>
			<div class="card-body">
				<?php if ($thirdparty): ?>
					<table class="table table-borderless mb-0">
						<tbody>
							<tr>
								<th class="text-muted" style="width:140px;">Societe</th>
								<td>
									<strong><?php echo spacartAdminEscape($thirdparty->nom); ?></strong>
									<?php
									// Build link to Dolibarr third-party card
									$dol_base = '';
									if (defined('DOL_URL_ROOT')) {
										$dol_base = DOL_URL_ROOT;
									}
									if ($dol_base !== ''):
									?>
									<a href="<?php echo $dol_base; ?>/societe/card.php?socid=<?php echo (int) $thirdparty->rowid; ?>"
									   target="_blank" class="ms-2 text-decoration-none" title="Voir dans Dolibarr">
										<i class="bi bi-box-arrow-up-right"></i>
									</a>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th class="text-muted">Email</th>
								<td><?php echo !empty($thirdparty->email) ? spacartAdminEscape($thirdparty->email) : '<span class="text-muted">-</span>'; ?></td>
							</tr>
							<tr>
								<th class="text-muted">Telephone</th>
								<td><?php echo !empty($thirdparty->phone) ? spacartAdminEscape($thirdparty->phone) : '<span class="text-muted">-</span>'; ?></td>
							</tr>
							<tr>
								<th class="text-muted">Adresse</th>
								<td>
									<?php
									$address_parts = array_filter(array(
										$thirdparty->address ?? '',
										trim(($thirdparty->zip ?? '').' '.($thirdparty->town ?? '')),
									));
									echo !empty($address_parts) ? spacartAdminEscape(implode(', ', $address_parts)) : '<span class="text-muted">-</span>';
									?>
								</td>
							</tr>
							<tr>
								<th class="text-muted">ID Tiers</th>
								<td><code>#<?php echo (int) $thirdparty->rowid; ?></code></td>
							</tr>
						</tbody>
					</table>
				<?php else: ?>
					<div class="text-center text-muted py-3">
						<i class="bi bi-building fs-1 d-block mb-2"></i>
						<?php if ((int) $customer->fk_soc > 0): ?>
							Tiers Dolibarr #<?php echo (int) $customer->fk_soc; ?> introuvable.
						<?php else: ?>
							Aucun tiers Dolibarr associe a ce client.
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<!-- ============================================================== -->
<!-- Order history -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-header d-flex align-items-center justify-content-between">
		<h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Historique des commandes</h5>
		<span class="badge bg-secondary"><?php echo count($orders); ?> commande<?php echo count($orders) > 1 ? 's' : ''; ?></span>
	</div>
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th>Reference</th>
						<th>Date</th>
						<th class="text-end">Total TTC</th>
						<th>Statut</th>
						<th style="width:80px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($orders)): ?>
						<tr>
							<td colspan="5" class="text-center text-muted py-4">
								<i class="bi bi-box-seam fs-1 d-block mb-2"></i>
								<?php if ((int) $customer->fk_soc <= 0): ?>
									Aucun tiers associe - impossible de charger les commandes.
								<?php else: ?>
									Aucune commande trouvee pour ce client.
								<?php endif; ?>
							</td>
						</tr>
					<?php else: ?>
						<?php foreach ($orders as $order): ?>
							<tr>
								<td>
									<a href="?page=order_view&amp;id=<?php echo (int) $order->rowid; ?>" class="fw-semibold text-decoration-none">
										<?php echo spacartAdminEscape($order->ref); ?>
									</a>
								</td>
								<td><?php echo spacartAdminFormatDate($order->date_commande); ?></td>
								<td class="text-end"><?php echo spacartAdminFormatPrice($order->total_ttc); ?></td>
								<td><?php echo spacartAdminOrderStatusBadge($order->fk_statut); ?></td>
								<td>
									<a href="?page=order_view&amp;id=<?php echo (int) $order->rowid; ?>"
									   class="btn btn-sm btn-outline-primary" title="Voir la commande" aria-label="Voir la commande">
										<i class="bi bi-eye"></i>
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
<!-- Addresses -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-header d-flex align-items-center justify-content-between">
		<h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Adresses</h5>
		<span class="badge bg-secondary"><?php echo count($addresses); ?> adresse<?php echo count($addresses) > 1 ? 's' : ''; ?></span>
	</div>
	<div class="card-body">
		<?php if (empty($addresses)): ?>
			<div class="text-center text-muted py-3">
				<i class="bi bi-geo-alt fs-1 d-block mb-2"></i>
				Aucune adresse enregistree.
			</div>
		<?php else: ?>
			<div class="row g-3">
				<?php foreach ($addresses as $addr): ?>
					<div class="col-12 col-md-6">
						<div class="border rounded p-3 h-100<?php echo (!empty($addr->is_default) && (int) $addr->is_default === 1) ? ' border-primary' : ''; ?>">
							<?php if (!empty($addr->is_default) && (int) $addr->is_default === 1): ?>
								<span class="badge bg-primary mb-2">Adresse par defaut</span>
							<?php endif; ?>

							<?php if (!empty($addr->label)): ?>
								<div class="fw-bold mb-1"><?php echo spacartAdminEscape($addr->label); ?></div>
							<?php endif; ?>

							<div>
								<?php
								$addr_name = trim(($addr->firstname ?? '').' '.($addr->lastname ?? ''));
								if ($addr_name !== '') {
									echo spacartAdminEscape($addr_name).'<br>';
								}
								if (!empty($addr->address)) {
									echo spacartAdminEscape($addr->address).'<br>';
								}
								$addr_city_line = trim(($addr->zip ?? '').' '.($addr->city ?? ''));
								if ($addr_city_line !== '') {
									echo spacartAdminEscape($addr_city_line).'<br>';
								}
								if (!empty($addr->country)) {
									echo spacartAdminEscape($addr->country).'<br>';
								}
								if (!empty($addr->phone)) {
									echo '<span class="text-muted"><i class="bi bi-telephone me-1"></i>'.spacartAdminEscape($addr->phone).'</span>';
								}
								?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<!-- Back to list -->
<div class="mt-4">
	<a href="?page=customers" class="btn btn-outline-secondary" aria-label="Retour a la liste des clients">
		<i class="bi bi-arrow-left me-1"></i>Retour a la liste des clients
	</a>
</div>

<?php
include __DIR__.'/../includes/footer.php';
