<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/statistics.php
 * \ingroup    spacart
 * \brief      SpaCart Admin - Advanced statistics dashboard
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title   = 'Statistiques';
$current_page = 'statistics';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// ============================================================
// Date range filter
// ============================================================
$range = trim($_GET['range'] ?? '30');
$date_from_custom = trim($_GET['date_from'] ?? '');
$date_to_custom   = trim($_GET['date_to'] ?? '');

// Calculate effective dates based on range
$date_to   = date('Y-m-d');
$date_from = date('Y-m-d', strtotime('-30 days'));

switch ($range) {
	case '7':
		$date_from = date('Y-m-d', strtotime('-7 days'));
		$range_label = '7 derniers jours';
		break;
	case '30':
		$date_from = date('Y-m-d', strtotime('-30 days'));
		$range_label = '30 derniers jours';
		break;
	case '90':
		$date_from = date('Y-m-d', strtotime('-90 days'));
		$range_label = '90 derniers jours';
		break;
	case 'year':
		$date_from = date('Y-01-01');
		$range_label = 'Annee en cours';
		break;
	case 'custom':
		if ($date_from_custom !== '') {
			$date_from = $date_from_custom;
		}
		if ($date_to_custom !== '') {
			$date_to = $date_to_custom;
		}
		$range_label = 'Periode personnalisee';
		break;
	default:
		$range = '30';
		$range_label = '30 derniers jours';
		break;
}

$esc_date_from = $db->escape($date_from);
$esc_date_to   = $db->escape($date_to);

// ============================================================
// KPI Cards
// ============================================================

// Total revenue in date range (confirmed orders: fk_statut > 0)
$sql = "SELECT SUM(total_ttc) as total FROM ".$prefix."commande"
	." WHERE entity = ".$entity
	." AND fk_statut > 0"
	." AND date_commande >= '".$esc_date_from." 00:00:00'"
	." AND date_commande <= '".$esc_date_to." 23:59:59'";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$kpi_revenue = $obj && $obj->total ? (float) $obj->total : 0;

// Total orders in date range
$sql = "SELECT COUNT(*) as nb FROM ".$prefix."commande"
	." WHERE entity = ".$entity
	." AND fk_statut > 0"
	." AND date_commande >= '".$esc_date_from." 00:00:00'"
	." AND date_commande <= '".$esc_date_to." 23:59:59'";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$kpi_orders = $obj ? (int) $obj->nb : 0;

// Average order value
$kpi_avg_order = ($kpi_orders > 0) ? ($kpi_revenue / $kpi_orders) : 0;

// Total new customers in date range
$sql = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_customer"
	." WHERE entity = ".$entity
	." AND date_creation >= '".$esc_date_from." 00:00:00'"
	." AND date_creation <= '".$esc_date_to." 23:59:59'";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$kpi_customers = $obj ? (int) $obj->nb : 0;

// Conversion rate: orders / unique carts in date range
$sql = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_cart"
	." WHERE entity = ".$entity
	." AND date_creation >= '".$esc_date_from." 00:00:00'"
	." AND date_creation <= '".$esc_date_to." 23:59:59'";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$total_carts = $obj ? (int) $obj->nb : 0;
$kpi_conversion = ($total_carts > 0) ? round(($kpi_orders / $total_carts) * 100, 1) : 0;

// ============================================================
// Top 10 selling products in date range
// ============================================================
$top_products = array();
$sql  = "SELECT p.rowid, p.ref, p.label, SUM(d.qty) as total_qty, SUM(d.total_ttc) as total_revenue";
$sql .= " FROM ".$prefix."commandedet as d";
$sql .= " INNER JOIN ".$prefix."commande as c ON c.rowid = d.fk_commande";
$sql .= " LEFT JOIN ".$prefix."product as p ON p.rowid = d.fk_product";
$sql .= " WHERE c.entity = ".$entity;
$sql .= " AND c.fk_statut > 0";
$sql .= " AND c.date_commande >= '".$esc_date_from." 00:00:00'";
$sql .= " AND c.date_commande <= '".$esc_date_to." 23:59:59'";
$sql .= " AND d.fk_product > 0";
$sql .= " GROUP BY p.rowid, p.ref, p.label";
$sql .= " ORDER BY total_qty DESC";
$sql .= " LIMIT 10";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$top_products[] = $obj;
	}
	$db->free($resql);
}

// ============================================================
// Top 10 customers by spending in date range
// ============================================================
$top_customers = array();
$sql  = "SELECT s.rowid, s.nom as customer_name, s.email,";
$sql .= " COUNT(c.rowid) as nb_orders, SUM(c.total_ttc) as total_spent";
$sql .= " FROM ".$prefix."commande as c";
$sql .= " INNER JOIN ".$prefix."societe as s ON s.rowid = c.fk_soc";
$sql .= " WHERE c.entity = ".$entity;
$sql .= " AND c.fk_statut > 0";
$sql .= " AND c.date_commande >= '".$esc_date_from." 00:00:00'";
$sql .= " AND c.date_commande <= '".$esc_date_to." 23:59:59'";
$sql .= " GROUP BY s.rowid, s.nom, s.email";
$sql .= " ORDER BY total_spent DESC";
$sql .= " LIMIT 10";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$top_customers[] = $obj;
	}
	$db->free($resql);
}

// ============================================================
// Revenue by month (last 12 months, independent of filter)
// ============================================================
$revenue_by_month = array();
for ($i = 11; $i >= 0; $i--) {
	$month_start = date('Y-m-01', strtotime("-{$i} months"));
	$month_end   = date('Y-m-t', strtotime("-{$i} months"));
	$month_label = date('m/Y', strtotime($month_start));

	$sql = "SELECT COUNT(*) as nb_orders, SUM(total_ttc) as total"
		." FROM ".$prefix."commande"
		." WHERE entity = ".$entity
		." AND fk_statut > 0"
		." AND date_commande >= '".$db->escape($month_start)." 00:00:00'"
		." AND date_commande <= '".$db->escape($month_end)." 23:59:59'";
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);

	$revenue_by_month[] = (object) array(
		'month'     => $month_label,
		'nb_orders' => $obj ? (int) $obj->nb_orders : 0,
		'total'     => $obj && $obj->total ? (float) $obj->total : 0,
	);
}

// Chart data arrays from revenue_by_month
$chart_month_labels = array();
$chart_month_values = array();
foreach ($revenue_by_month as $rm) {
	$chart_month_labels[] = $rm->month;
	$chart_month_values[] = round($rm->total, 2);
}

// ============================================================
// Orders by status breakdown (all time)
// ============================================================
$status_labels = array(
	-1 => 'Annulee',
	0  => 'Brouillon',
	1  => 'Validee',
	2  => 'En cours',
	3  => 'Livree',
);
$orders_by_status = array();
foreach ($status_labels as $st => $label) {
	$sql = "SELECT COUNT(*) as nb FROM ".$prefix."commande"
		." WHERE entity = ".$entity." AND fk_statut = ".(int) $st;
	$resql = $db->query($sql);
	$obj = $db->fetch_object($resql);
	$orders_by_status[] = (object) array(
		'status_code'  => $st,
		'status_label' => $label,
		'nb'           => $obj ? (int) $obj->nb : 0,
	);
}

// Chart data arrays from orders_by_status
$chart_status_labels = array();
$chart_status_values = array();
foreach ($orders_by_status as $os) {
	$chart_status_labels[] = $os->status_label;
	$chart_status_values[] = $os->nb;
}

// ============================================================
// Include header
// ============================================================
require_once __DIR__.'/../includes/header.php';
?>

<!-- Page header -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between mb-4">
	<h1 class="h3 mb-0"><i class="bi bi-bar-chart-line me-2"></i>Statistiques</h1>
	<span class="text-muted"><?php echo spacartAdminEscape($range_label); ?> : <?php echo spacartAdminFormatDate($date_from, 'd/m/Y'); ?> - <?php echo spacartAdminFormatDate($date_to, 'd/m/Y'); ?></span>
</div>

<!-- ============================================================== -->
<!-- Date range filter -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="statistics">
			<div class="row g-3 align-items-end">
				<!-- Range preset -->
				<div class="col-12 col-md-3">
					<label for="filter_range" class="form-label">Periode</label>
					<select class="form-select" id="filter_range" name="range" onchange="toggleCustomDates(this.value)">
						<option value="7"<?php echo ($range === '7') ? ' selected' : ''; ?>>7 derniers jours</option>
						<option value="30"<?php echo ($range === '30') ? ' selected' : ''; ?>>30 derniers jours</option>
						<option value="90"<?php echo ($range === '90') ? ' selected' : ''; ?>>90 derniers jours</option>
						<option value="year"<?php echo ($range === 'year') ? ' selected' : ''; ?>>Annee en cours</option>
						<option value="custom"<?php echo ($range === 'custom') ? ' selected' : ''; ?>>Personnalise</option>
					</select>
				</div>

				<!-- Custom date from -->
				<div class="col-6 col-md-2" id="custom_date_from_wrap" style="<?php echo ($range !== 'custom') ? 'display:none;' : ''; ?>">
					<label for="filter_date_from" class="form-label">Date debut</label>
					<input type="date" class="form-control" id="filter_date_from" name="date_from"
						   value="<?php echo spacartAdminEscape($date_from_custom); ?>">
				</div>

				<!-- Custom date to -->
				<div class="col-6 col-md-2" id="custom_date_to_wrap" style="<?php echo ($range !== 'custom') ? 'display:none;' : ''; ?>">
					<label for="filter_date_to" class="form-label">Date fin</label>
					<input type="date" class="form-control" id="filter_date_to" name="date_to"
						   value="<?php echo spacartAdminEscape($date_to_custom); ?>">
				</div>

				<!-- Filter button -->
				<div class="col-6 col-md-2">
					<button type="submit" class="btn btn-primary w-100">
						<i class="bi bi-funnel me-1"></i>Appliquer
					</button>
				</div>

				<!-- Reset -->
				<div class="col-6 col-md-1">
					<a href="?page=statistics" class="btn btn-outline-secondary w-100" title="Reinitialiser">
						<i class="bi bi-x-circle"></i>
					</a>
				</div>
			</div>
		</form>
	</div>
</div>

<!-- ============================================================== -->
<!-- KPI Cards -->
<!-- ============================================================== -->
<div class="stats-row">
	<div class="stat-card">
		<div class="stat-icon bg-success">
			<i class="bi bi-currency-euro"></i>
		</div>
		<div class="stat-content">
			<span class="stat-value"><?php echo spacartAdminFormatPrice($kpi_revenue); ?></span>
			<span class="stat-label">Chiffre d'affaires</span>
		</div>
	</div>

	<div class="stat-card">
		<div class="stat-icon bg-primary">
			<i class="bi bi-cart-check"></i>
		</div>
		<div class="stat-content">
			<span class="stat-value"><?php echo (int) $kpi_orders; ?></span>
			<span class="stat-label">Commandes</span>
		</div>
	</div>

	<div class="stat-card">
		<div class="stat-icon bg-info">
			<i class="bi bi-cash-stack"></i>
		</div>
		<div class="stat-content">
			<span class="stat-value"><?php echo spacartAdminFormatPrice($kpi_avg_order); ?></span>
			<span class="stat-label">Panier moyen</span>
		</div>
	</div>

	<div class="stat-card">
		<div class="stat-icon bg-warning">
			<i class="bi bi-people"></i>
		</div>
		<div class="stat-content">
			<span class="stat-value"><?php echo (int) $kpi_customers; ?></span>
			<span class="stat-label">Nouveaux clients</span>
		</div>
	</div>

	<div class="stat-card">
		<div class="stat-icon bg-danger">
			<i class="bi bi-funnel"></i>
		</div>
		<div class="stat-content">
			<span class="stat-value"><?php echo $kpi_conversion; ?> %</span>
			<span class="stat-label">Taux de conversion</span>
		</div>
	</div>
</div>

<!-- ============================================================== -->
<!-- Charts Row -->
<!-- ============================================================== -->
<div class="row mt-4">
	<div class="col-lg-7 mb-4">
		<div class="admin-card">
			<div class="card-header">
				<h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Chiffre d'affaires par mois (12 derniers mois)</h5>
			</div>
			<div class="card-body">
				<canvas id="chart-revenue-monthly" height="300"></canvas>
			</div>
		</div>
	</div>
	<div class="col-lg-5 mb-4">
		<div class="admin-card">
			<div class="card-header">
				<h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Commandes par statut</h5>
			</div>
			<div class="card-body">
				<canvas id="chart-orders-status" height="300"></canvas>
			</div>
		</div>
	</div>
</div>

<!-- ============================================================== -->
<!-- Top 10 selling products -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-header">
		<h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Top 10 produits vendus</h5>
	</div>
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th>#</th>
						<th>Ref.</th>
						<th>Produit</th>
						<th class="text-end">Qte vendue</th>
						<th class="text-end">CA TTC</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($top_products)): ?>
						<tr>
							<td colspan="5" class="text-center text-muted py-4">Aucune vente sur cette periode.</td>
						</tr>
					<?php else: ?>
						<?php $rank = 1; foreach ($top_products as $prod): ?>
						<tr>
							<td><?php echo $rank++; ?></td>
							<td><strong><?php echo spacartAdminEscape($prod->ref); ?></strong></td>
							<td><?php echo spacartAdminEscape($prod->label); ?></td>
							<td class="text-end"><?php echo (int) $prod->total_qty; ?></td>
							<td class="text-end"><?php echo spacartAdminFormatPrice((float) $prod->total_revenue); ?></td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ============================================================== -->
<!-- Top 10 customers by spending -->
<!-- ============================================================== -->
<div class="admin-card mb-4">
	<div class="card-header">
		<h5 class="mb-0"><i class="bi bi-person-check me-2"></i>Top 10 clients par depenses</h5>
	</div>
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table table-hover align-middle mb-0">
				<thead>
					<tr>
						<th>#</th>
						<th>Client</th>
						<th>Email</th>
						<th class="text-end">Commandes</th>
						<th class="text-end">Total depense TTC</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($top_customers)): ?>
						<tr>
							<td colspan="5" class="text-center text-muted py-4">Aucun client sur cette periode.</td>
						</tr>
					<?php else: ?>
						<?php $rank = 1; foreach ($top_customers as $cust): ?>
						<tr>
							<td><?php echo $rank++; ?></td>
							<td><strong><?php echo spacartAdminEscape($cust->customer_name); ?></strong></td>
							<td><?php echo spacartAdminEscape($cust->email); ?></td>
							<td class="text-end"><?php echo (int) $cust->nb_orders; ?></td>
							<td class="text-end"><?php echo spacartAdminFormatPrice((float) $cust->total_spent); ?></td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="row">
	<!-- ============================================================== -->
	<!-- Revenue by month (last 12 months) -->
	<!-- ============================================================== -->
	<div class="col-lg-7 mb-4">
		<div class="admin-card">
			<div class="card-header">
				<h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>CA mensuel (12 derniers mois)</h5>
			</div>
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="admin-table table table-hover align-middle mb-0">
						<thead>
							<tr>
								<th>Mois</th>
								<th class="text-end">Commandes</th>
								<th class="text-end">CA TTC</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($revenue_by_month as $rm): ?>
							<tr>
								<td><?php echo spacartAdminEscape($rm->month); ?></td>
								<td class="text-end"><?php echo (int) $rm->nb_orders; ?></td>
								<td class="text-end"><?php echo spacartAdminFormatPrice($rm->total); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- ============================================================== -->
	<!-- Orders by status breakdown -->
	<!-- ============================================================== -->
	<div class="col-lg-5 mb-4">
		<div class="admin-card">
			<div class="card-header">
				<h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Repartition des commandes par statut</h5>
			</div>
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="admin-table table table-hover align-middle mb-0">
						<thead>
							<tr>
								<th>Statut</th>
								<th class="text-end">Nombre</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($orders_by_status as $os): ?>
							<tr>
								<td><?php echo spacartAdminOrderStatusBadge($os->status_code); ?></td>
								<td class="text-end"><?php echo (int) $os->nb; ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Chart data for future Chart.js integration -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Revenue by month chart data
	var revenueMonthlyData = {
		labels: <?php echo json_encode($chart_month_labels, JSON_UNESCAPED_UNICODE); ?>,
		values: <?php echo json_encode($chart_month_values); ?>
	};
	// Orders by status chart data
	var ordersStatusData = {
		labels: <?php echo json_encode($chart_status_labels, JSON_UNESCAPED_UNICODE); ?>,
		values: <?php echo json_encode($chart_status_values); ?>
	};

	// Toggle custom date fields
	window.toggleCustomDates = function(val) {
		var showCustom = (val === 'custom');
		document.getElementById('custom_date_from_wrap').style.display = showCustom ? '' : 'none';
		document.getElementById('custom_date_to_wrap').style.display = showCustom ? '' : 'none';
	};

	// Placeholder: initialize charts when Chart.js is loaded
	if (typeof window.initStatisticsCharts === 'function') {
		window.initStatisticsCharts(revenueMonthlyData, ordersStatusData);
	}
});
</script>

<?php
// ============================================================
// Include footer
// ============================================================
require_once __DIR__.'/../includes/footer.php';
