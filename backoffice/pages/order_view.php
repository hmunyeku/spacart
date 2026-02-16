<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/order_view.php
 * \ingroup    spacart
 * \brief      SpaCart Admin - Single order detail view
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

global $db, $conf;

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
	spacartAdminFlash('Commande introuvable.', 'danger');
	header('Location: ?page=orders');
	exit;
}

// ============================================================
// POST actions (status change)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=order_view&id='.$id);
		exit;
	}

	$action = $_POST['action'] ?? '';

	if ($action === 'update_status' && isset($_POST['new_status'])) {
		$new_status = (int) $_POST['new_status'];
		$allowed    = array(-1, 0, 1, 2, 3);
		if (in_array($new_status, $allowed)) {
			$sql_upd  = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_statut = ".$new_status;
			$sql_upd .= " WHERE rowid = ".$id;
			$sql_upd .= " AND entity = ".(int) $conf->entity;
			if ($db->query($sql_upd)) {
				spacartAdminFlash('Statut mis a jour : '.spacartAdminOrderStatusLabel($new_status).'.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour du statut.', 'danger');
			}
		} else {
			spacartAdminFlash('Statut invalide.', 'warning');
		}
		header('Location: ?page=order_view&id='.$id);
		exit;
	}
}

// ============================================================
// Load order + customer data
// ============================================================
$sql  = "SELECT c.*, s.nom as customer_name, s.email as customer_email,";
$sql .= " s.address as customer_address, s.zip as customer_zip,";
$sql .= " s.town as customer_town, s.phone as customer_phone";
$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
$sql .= " WHERE c.rowid = ".$id;
$sql .= " AND c.entity = ".(int) $conf->entity;

$order = null;
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
	$order = $db->fetch_object($resql);
	$db->free($resql);
} else {
	spacartAdminFlash('Commande introuvable.', 'danger');
	header('Location: ?page=orders');
	exit;
}

// ============================================================
// Load order lines
// ============================================================
$sql_lines  = "SELECT d.*, p.ref as product_ref, p.label as product_label";
$sql_lines .= " FROM ".MAIN_DB_PREFIX."commandedet as d";
$sql_lines .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = d.fk_product";
$sql_lines .= " WHERE d.fk_commande = ".$id;
$sql_lines .= " ORDER BY d.rang, d.rowid";

$lines = array();
$resql_lines = $db->query($sql_lines);
if ($resql_lines) {
	while ($obj = $db->fetch_object($resql_lines)) {
		$lines[] = $obj;
	}
	$db->free($resql_lines);
}

// Compute VAT total
$total_vat = (float) $order->total_ttc - (float) $order->total_ht;

// Page setup
$page_title   = 'Commande #'.spacartAdminEscape($order->ref);
$current_page = 'orders';
$csrf_token   = spacartAdminGetCSRFToken();

// ============================================================
// Include header
// ============================================================
require_once __DIR__.'/../includes/header.php';
?>

<!-- Page header -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between mb-4">
	<div class="d-flex align-items-center gap-3 mb-2 mb-md-0">
		<a href="?page=orders" class="btn btn-outline-secondary btn-sm" title="Retour a la liste" aria-label="Retour a la liste des commandes">
			<i class="bi bi-arrow-left"></i>
		</a>
		<h1 class="h3 mb-0">
			Commande <span class="text-primary">#<?php echo spacartAdminEscape($order->ref); ?></span>
		</h1>
		<?php echo spacartAdminOrderStatusBadge($order->fk_statut); ?>
	</div>

	<!-- Status change actions -->
	<div class="d-flex align-items-center gap-2">
		<form method="post" class="d-inline-flex align-items-center gap-2" id="statusForm">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="update_status">
			<select name="new_status" class="form-select form-select-sm" style="width:auto;">
				<option value="" disabled selected>Changer le statut</option>
				<option value="0"<?php echo ((int) $order->fk_statut === 0) ? ' disabled' : ''; ?>>Brouillon</option>
				<option value="1"<?php echo ((int) $order->fk_statut === 1) ? ' disabled' : ''; ?>>Valider</option>
				<option value="2"<?php echo ((int) $order->fk_statut === 2) ? ' disabled' : ''; ?>>En cours</option>
				<option value="3"<?php echo ((int) $order->fk_statut === 3) ? ' disabled' : ''; ?>>Livree</option>
				<option value="-1"<?php echo ((int) $order->fk_statut === -1) ? ' disabled' : ''; ?>>Annuler</option>
			</select>
			<button type="submit" class="btn btn-primary btn-sm"
					onclick="return this.form.new_status.value !== '' || (alert('Veuillez choisir un statut.'), false);">
				<i class="bi bi-check-lg me-1"></i>Appliquer
			</button>
		</form>
		<a href="javascript:window.print()" class="btn btn-outline-secondary btn-sm" title="Imprimer" aria-label="Imprimer la commande">
			<i class="bi bi-printer me-1"></i>Imprimer
		</a>
	</div>
</div>

<!-- ============================================================== -->
<!-- Order info cards (2 columns) -->
<!-- ============================================================== -->
<div class="row g-4 mb-4">

	<!-- Left card: Customer info -->
	<div class="col-12 col-lg-6">
		<div class="admin-card h-100">
			<div class="card-header">
				<h5 class="mb-0"><i class="bi bi-person me-2"></i>Client</h5>
			</div>
			<div class="card-body">
				<table class="table table-borderless mb-0">
					<tbody>
						<tr>
							<th class="text-muted" style="width:120px;">Nom</th>
							<td><?php echo spacartAdminEscape($order->customer_name); ?></td>
						</tr>
						<tr>
							<th class="text-muted">Email</th>
							<td>
								<?php if (!empty($order->customer_email)): ?>
									<a href="mailto:<?php echo spacartAdminEscape($order->customer_email); ?>">
										<?php echo spacartAdminEscape($order->customer_email); ?>
									</a>
								<?php else: ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th class="text-muted">Telephone</th>
							<td><?php echo !empty($order->customer_phone) ? spacartAdminEscape($order->customer_phone) : '<span class="text-muted">-</span>'; ?></td>
						</tr>
						<tr>
							<th class="text-muted">Adresse</th>
							<td>
								<?php
								$address_parts = array_filter(array(
									$order->customer_address ?? '',
									trim(($order->customer_zip ?? '').' '.($order->customer_town ?? '')),
								));
								echo !empty($address_parts) ? spacartAdminEscape(implode(', ', $address_parts)) : '<span class="text-muted">-</span>';
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Right card: Order summary -->
	<div class="col-12 col-lg-6">
		<div class="admin-card h-100">
			<div class="card-header">
				<h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Recapitulatif</h5>
			</div>
			<div class="card-body">
				<table class="table table-borderless mb-0">
					<tbody>
						<tr>
							<th class="text-muted" style="width:140px;">Date commande</th>
							<td><?php echo spacartAdminFormatDate($order->date_commande); ?></td>
						</tr>
						<tr>
							<th class="text-muted">Total HT</th>
							<td class="fw-semibold"><?php echo spacartAdminFormatPrice($order->total_ht); ?></td>
						</tr>
						<tr>
							<th class="text-muted">TVA</th>
							<td><?php echo spacartAdminFormatPrice($total_vat); ?></td>
						</tr>
						<tr>
							<th class="text-muted">Total TTC</th>
							<td class="fw-bold fs-5"><?php echo spacartAdminFormatPrice($order->total_ttc); ?></td>
						</tr>
						<tr>
							<th class="text-muted">Mode de paiement</th>
							<td>
								<?php
								$payment_mode = '';
								if (!empty($order->fk_mode_reglement)) {
									$sql_pay = "SELECT libelle FROM ".MAIN_DB_PREFIX."c_paiement WHERE id = ".(int) $order->fk_mode_reglement;
									$res_pay = $db->query($sql_pay);
									if ($res_pay && $db->num_rows($res_pay) > 0) {
										$pay_obj = $db->fetch_object($res_pay);
										$payment_mode = $pay_obj->libelle;
									}
								}
								echo !empty($payment_mode) ? spacartAdminEscape($payment_mode) : '<span class="text-muted">-</span>';
								?>
							</td>
						</tr>
						<tr>
							<th class="text-muted">Source</th>
							<td>
								<?php
								$source = !empty($order->module_source) ? $order->module_source : '-';
								?>
								<span class="badge bg-light text-dark border"><?php echo spacartAdminEscape($source); ?></span>
							</td>
						</tr>
						<tr>
							<th class="text-muted">Statut</th>
							<td><?php echo spacartAdminOrderStatusBadge($order->fk_statut); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<!-- ============================================================== -->
<!-- Order lines table -->
<!-- ============================================================== -->
<div class="admin-card">
	<div class="card-header d-flex align-items-center justify-content-between">
		<h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lignes de commande</h5>
		<span class="badge bg-secondary"><?php echo count($lines); ?> ligne(s)</span>
	</div>
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th>Produit</th>
						<th class="text-center" style="width:80px;">Qte</th>
						<th class="text-end" style="width:130px;">Prix unit. HT</th>
						<th class="text-end" style="width:80px;">TVA%</th>
						<th class="text-end" style="width:130px;">Total HT</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($lines)): ?>
						<tr>
							<td colspan="5">
								<div class="empty-state-inline">
									<div class="empty-state-icon"><i class="bi bi-list-ul"></i></div>
									<p>Aucune ligne de commande</p>
								</div>
							</td>
						</tr>
					<?php else: ?>
						<?php foreach ($lines as $line): ?>
							<tr>
								<td>
									<?php if (!empty($line->product_ref)): ?>
										<span class="fw-semibold"><?php echo spacartAdminEscape($line->product_ref); ?></span>
										<br>
									<?php endif; ?>
									<?php if (!empty($line->product_label)): ?>
										<span class="text-muted"><?php echo spacartAdminEscape($line->product_label); ?></span>
									<?php elseif (!empty($line->description)): ?>
										<span class="text-muted"><?php echo spacartAdminEscape(spacartAdminTruncate(strip_tags($line->description), 80)); ?></span>
									<?php else: ?>
										<span class="text-muted">-</span>
									<?php endif; ?>
								</td>
								<td class="text-center"><?php echo (float) $line->qty; ?></td>
								<td class="text-end"><?php echo spacartAdminFormatPrice($line->subprice); ?></td>
								<td class="text-end"><?php echo number_format((float) $line->tva_tx, 1, ',', ''); ?>%</td>
								<td class="text-end"><?php echo spacartAdminFormatPrice($line->total_ht); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				<?php if (!empty($lines)): ?>
				<tfoot class="table-light">
					<tr>
						<td colspan="4" class="text-end fw-semibold">Total HT</td>
						<td class="text-end fw-semibold"><?php echo spacartAdminFormatPrice($order->total_ht); ?></td>
					</tr>
					<tr>
						<td colspan="4" class="text-end text-muted">TVA</td>
						<td class="text-end text-muted"><?php echo spacartAdminFormatPrice($total_vat); ?></td>
					</tr>
					<tr>
						<td colspan="4" class="text-end fw-bold">Total TTC</td>
						<td class="text-end fw-bold"><?php echo spacartAdminFormatPrice($order->total_ttc); ?></td>
					</tr>
				</tfoot>
				<?php endif; ?>
			</table>
		</div>
	</div>
</div>

<!-- Back to list -->
<div class="mt-4">
	<a href="?page=orders" class="btn btn-outline-secondary" aria-label="Retour a la liste des commandes">
		<i class="bi bi-arrow-left me-1"></i>Retour a la liste des commandes
	</a>
</div>

<?php
// ============================================================
// Include footer
// ============================================================
require_once __DIR__.'/../includes/footer.php';
