<?php
/**
 * SpaCart Backoffice - Dashboard
 *
 * Main admin dashboard with stats, charts, and recent orders.
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
    define('SPACART_ADMIN', true);
}

$page_title = 'Tableau de bord';
$current_page = 'dashboard';

include __DIR__.'/../includes/header.php';

// -------------------------------------------------------------------
// Gather statistics
// -------------------------------------------------------------------
$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// Products count (active / for sale)
$sql = "SELECT COUNT(*) as nb FROM ".$prefix."product WHERE entity = ".$entity." AND tosell = 1";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$stat_products = $obj ? (int) $obj->nb : 0;

// Orders count (SpaCart origin)
$sql = "SELECT COUNT(*) as nb FROM ".$prefix."commande WHERE entity = ".$entity;
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$stat_orders_all = $obj ? (int) $obj->nb : 0;

// Try with module_source filter
$sql = "SELECT COUNT(*) as nb FROM ".$prefix."commande WHERE entity = ".$entity." AND module_source = 'spacart'";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$stat_orders = $obj ? (int) $obj->nb : 0;
if ($stat_orders == 0) {
    $stat_orders = $stat_orders_all;
}

// Customers count
$sql = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_customer WHERE entity = ".$entity;
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$stat_customers = $obj ? (int) $obj->nb : 0;

// Revenue this month
$first_of_month = date('Y-m-01');
$sql = "SELECT SUM(total_ttc) as total FROM ".$prefix."commande WHERE entity = ".$entity
    ." AND fk_statut > 0 AND date_commande >= '".$db->escape($first_of_month)."'";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$stat_revenue = $obj && $obj->total ? (float) $obj->total : 0;

// Pending reviews
$sql = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_review WHERE entity = ".$entity." AND status = 0";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$stat_reviews = $obj ? (int) $obj->nb : 0;

// Newsletter subscribers
$sql = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_subscriber WHERE entity = ".$entity." AND status = 1";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$stat_subscribers = $obj ? (int) $obj->nb : 0;

// -------------------------------------------------------------------
// Chart data: Sales last 6 months
// -------------------------------------------------------------------
$sales_labels = [];
$sales_values = [];
for ($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-{$i} months"));
    $month_end   = date('Y-m-t', strtotime("-{$i} months"));
    $sales_labels[] = strftime('%b %Y', strtotime($month_start));

    $sql = "SELECT SUM(total_ttc) as total FROM ".$prefix."commande"
        ." WHERE entity = ".$entity
        ." AND fk_statut > 0"
        ." AND date_commande >= '".$db->escape($month_start)."'"
        ." AND date_commande <= '".$db->escape($month_end)."'";
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    $sales_values[] = $obj && $obj->total ? round((float) $obj->total, 2) : 0;
}

// -------------------------------------------------------------------
// Chart data: Orders by status
// -------------------------------------------------------------------
$order_status_labels = [
    0 => 'Brouillon',
    1 => 'Validee',
    2 => 'Envoyee',
    3 => 'Livree',
    -1 => 'Annulee',
];
$order_status_values = [];
foreach ($order_status_labels as $st => $label) {
    $sql = "SELECT COUNT(*) as nb FROM ".$prefix."commande WHERE entity = ".$entity." AND fk_statut = ".(int) $st;
    $resql = $db->query($sql);
    $obj = $db->fetch_object($resql);
    $order_status_values[] = $obj ? (int) $obj->nb : 0;
}

// -------------------------------------------------------------------
// Recent orders (last 10)
// -------------------------------------------------------------------
$recent_orders = [];
$sql = "SELECT c.rowid, c.ref, c.date_commande, c.total_ttc, c.fk_statut,"
    ." s.nom as customer_name"
    ." FROM ".$prefix."commande as c"
    ." LEFT JOIN ".$prefix."societe as s ON s.rowid = c.fk_soc"
    ." WHERE c.entity = ".$entity
    ." ORDER BY c.date_commande DESC LIMIT 10";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $recent_orders[] = $obj;
    }
}
?>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div>
        <h2>Bonjour<?php echo !empty($admin_user->firstname) ? ', '.spacartAdminEscape($admin_user->firstname) : ''; ?> !</h2>
        <p>Voici le resume de votre boutique en ligne.</p>
    </div>
    <div class="welcome-actions">
        <a href="?page=product_edit" class="btn btn-light btn-sm"><i class="bi bi-plus-lg me-1"></i>Nouveau produit</a>
        <a href="?page=orders" class="btn btn-outline-light btn-sm"><i class="bi bi-box-seam me-1"></i>Commandes</a>
        <a href="?page=customers" class="btn btn-outline-light btn-sm"><i class="bi bi-people me-1"></i>Clients</a>
    </div>
</div>

<!-- Stats Row -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="bi bi-box-seam"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo (int) $stat_products; ?></span>
            <span class="stat-label">Produits</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="bi bi-cart-check"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo (int) $stat_orders; ?></span>
            <span class="stat-label">Commandes</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="bi bi-people"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo (int) $stat_customers; ?></span>
            <span class="stat-label">Clients</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-warning">
            <i class="bi bi-currency-euro"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo spacartAdminFormatPrice($stat_revenue); ?></span>
            <span class="stat-label">Chiffre d'affaires du mois</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-danger">
            <i class="bi bi-star-half"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo (int) $stat_reviews; ?></span>
            <span class="stat-label">Avis en attente</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="bi bi-envelope-paper"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?php echo (int) $stat_subscribers; ?></span>
            <span class="stat-label">Abonnes newsletter</span>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mt-4">
    <div class="col-lg-7 mb-4">
        <div class="admin-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Ventes des 6 derniers mois</h5>
            </div>
            <div class="card-body">
                <canvas id="chart-sales" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="admin-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Commandes par statut</h5>
            </div>
            <div class="card-body">
                <canvas id="chart-orders" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="admin-card mt-2">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Commandes recentes</h5>
        <a href="?page=orders" class="btn btn-sm btn-outline-primary">Voir tout</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="admin-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th class="text-end">Total TTC</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state-inline">
                                <div class="empty-state-icon"><i class="bi bi-box-seam"></i></div>
                                <p>Aucune commande pour le moment</p>
                                <small class="text-muted">Les commandes de votre boutique apparaitront ici</small>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>
                                <strong><?php echo spacartAdminEscape($order->ref); ?></strong>
                            </td>
                            <td><?php echo spacartAdminEscape($order->customer_name ?: '-'); ?></td>
                            <td><?php echo spacartAdminFormatDate($order->date_commande); ?></td>
                            <td class="text-end"><?php echo spacartAdminFormatPrice((float) $order->total_ttc); ?></td>
                            <td class="text-center"><?php echo spacartAdminOrderStatusBadge((int) $order->fk_statut); ?></td>
                            <td class="text-center">
                                <a href="?page=orders&action=view&id=<?php echo (int) $order->rowid; ?>" class="btn btn-sm btn-outline-secondary" title="Voir la commande" aria-label="Voir la commande">
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

<!-- Chart initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var salesData = {
        labels: <?php echo json_encode($sales_labels, JSON_UNESCAPED_UNICODE); ?>,
        values: <?php echo json_encode($sales_values); ?>
    };
    var ordersData = {
        labels: <?php echo json_encode(array_values($order_status_labels), JSON_UNESCAPED_UNICODE); ?>,
        values: <?php echo json_encode($order_status_values); ?>
    };
    if (typeof window.initDashboardCharts === 'function') {
        window.initDashboardCharts(salesData, ordersData);
    }
});
</script>

<?php
include __DIR__.'/../includes/footer.php';
