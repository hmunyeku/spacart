<?php
if (!defined('SPACART_ADMIN')) {
    define('SPACART_ADMIN', true);
}

$page_title   = 'Prédictions & Intelligence';
$current_page = 'predictions';

global $db, $conf;
$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// ============================================================
// ML ENGINE - Pure PHP Implementation
// ============================================================

/**
 * Simple Linear Regression (Least Squares)
 * Returns [slope, intercept, r_squared]
 */
function ml_linear_regression($x, $y) {
    $n = count($x);
    if ($n < 2) return [0, 0, 0];
    
    $sum_x = array_sum($x);
    $sum_y = array_sum($y);
    $sum_xy = 0;
    $sum_x2 = 0;
    $sum_y2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sum_xy += $x[$i] * $y[$i];
        $sum_x2 += $x[$i] * $x[$i];
        $sum_y2 += $y[$i] * $y[$i];
    }
    
    $denom = ($n * $sum_x2 - $sum_x * $sum_x);
    if ($denom == 0) return [0, $sum_y / $n, 0];
    
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / $denom;
    $intercept = ($sum_y - $slope * $sum_x) / $n;
    
    // R-squared
    $mean_y = $sum_y / $n;
    $ss_tot = 0;
    $ss_res = 0;
    for ($i = 0; $i < $n; $i++) {
        $predicted = $slope * $x[$i] + $intercept;
        $ss_res += pow($y[$i] - $predicted, 2);
        $ss_tot += pow($y[$i] - $mean_y, 2);
    }
    $r_squared = ($ss_tot > 0) ? 1 - ($ss_res / $ss_tot) : 0;
    
    return [$slope, $intercept, max(0, $r_squared)];
}

/**
 * Weighted Moving Average for time series
 */
function ml_weighted_moving_avg($values, $window = 3) {
    $n = count($values);
    if ($n < $window) return end($values);
    
    $recent = array_slice($values, -$window);
    $weights = [];
    $total_weight = 0;
    for ($i = 0; $i < $window; $i++) {
        $w = $i + 1;
        $weights[] = $w;
        $total_weight += $w;
    }
    
    $weighted_sum = 0;
    for ($i = 0; $i < $window; $i++) {
        $weighted_sum += $recent[$i] * $weights[$i];
    }
    
    return $weighted_sum / $total_weight;
}

/**
 * Exponential Smoothing - Holt method for trend
 */
function ml_holt_forecast($values, $periods = 3, $alpha = 0.3, $beta = 0.1) {
    $n = count($values);
    if ($n < 2) return array_fill(0, $periods, end($values));
    
    $level = $values[0];
    $trend = $values[1] - $values[0];
    
    for ($i = 1; $i < $n; $i++) {
        $prev_level = $level;
        $level = $alpha * $values[$i] + (1 - $alpha) * ($prev_level + $trend);
        $trend = $beta * ($level - $prev_level) + (1 - $beta) * $trend;
    }
    
    $forecasts = [];
    for ($i = 1; $i <= $periods; $i++) {
        $forecasts[] = max(0, $level + $i * $trend);
    }
    
    return $forecasts;
}

/**
 * RFM Scoring for customer segmentation
 */
function ml_rfm_score($recency_days, $frequency, $monetary, $thresholds) {
    $r_score = 5;
    foreach ($thresholds['r'] as $thresh) {
        if ($recency_days > $thresh) { $r_score--; }
    }
    $r_score = max(1, $r_score);
    
    $f_score = 1;
    foreach ($thresholds['f'] as $thresh) {
        if ($frequency >= $thresh) { $f_score++; }
    }
    $f_score = min(5, $f_score);
    
    $m_score = 1;
    foreach ($thresholds['m'] as $thresh) {
        if ($monetary >= $thresh) { $m_score++; }
    }
    $m_score = min(5, $m_score);
    
    return [$r_score, $f_score, $m_score, round(($r_score + $f_score + $m_score) / 3, 1)];
}

/**
 * Proposal conversion probability estimator
 */
function ml_conversion_score($amount, $avg_converted_amount, $std_amount, $customer_history_rate, $days_since_proposal) {
    $score = 50;
    
    if ($std_amount > 0 && $avg_converted_amount > 0) {
        $z = abs($amount - $avg_converted_amount) / $std_amount;
        $score += max(-20, 20 - $z * 10);
    }
    
    $score += $customer_history_rate * 30;
    
    if ($days_since_proposal > 90) $score -= 15;
    elseif ($days_since_proposal > 60) $score -= 10;
    elseif ($days_since_proposal > 30) $score -= 5;
    elseif ($days_since_proposal < 7) $score += 5;
    
    return max(5, min(95, round($score)));
}

// ============================================================
// DATA GATHERING
// ============================================================

// --- 1. Monthly Revenue (last 24 months) ---
$sql_revenue = "SELECT DATE_FORMAT(datef, '%Y-%m') as month, 
    COUNT(*) as invoice_count,
    SUM(total_ht) as revenue_ht,
    SUM(total_ttc) as revenue_ttc
    FROM {$prefix}facture 
    WHERE entity = {$entity} AND fk_statut > 0
    AND datef >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
    GROUP BY month ORDER BY month";
$res = $db->query($sql_revenue);
$monthly_revenue = [];
$revenue_months = [];
$revenue_values = [];
while ($row = $db->fetch_object($res)) {
    $monthly_revenue[] = $row;
    $revenue_months[] = $row->month;
    $revenue_values[] = (float) $row->revenue_ttc;
}

// --- 2. Monthly Orders ---
$sql_orders = "SELECT DATE_FORMAT(date_creation, '%Y-%m') as month,
    COUNT(*) as order_count,
    SUM(total_ttc) as order_total
    FROM {$prefix}commande
    WHERE entity = {$entity}
    AND date_creation >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
    GROUP BY month ORDER BY month";
$res = $db->query($sql_orders);
$monthly_orders = [];
$order_months = [];
$order_values = [];
while ($row = $db->fetch_object($res)) {
    $monthly_orders[] = $row;
    $order_months[] = $row->month;
    $order_values[] = (int) $row->order_count;
}

// --- 3. Monthly Proposals ---
$sql_propals = "SELECT DATE_FORMAT(datep, '%Y-%m') as month,
    COUNT(*) as propal_count,
    SUM(total_ttc) as propal_total,
    SUM(CASE WHEN fk_statut IN (2,4) THEN 1 ELSE 0 END) as converted_count
    FROM {$prefix}propal
    WHERE entity = {$entity}
    AND datep >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
    GROUP BY month ORDER BY month";
$res = $db->query($sql_propals);
$monthly_propals = [];
$propal_months = [];
$propal_values = [];
$conversion_rates = [];
while ($row = $db->fetch_object($res)) {
    $monthly_propals[] = $row;
    $propal_months[] = $row->month;
    $propal_values[] = (int) $row->propal_count;
    $conversion_rates[] = $row->propal_count > 0 ? round($row->converted_count / $row->propal_count * 100, 1) : 0;
}

// --- 4. Customer RFM Data ---
$sql_rfm = "SELECT s.rowid, s.nom as name,
    DATEDIFF(NOW(), MAX(f.datef)) as recency_days,
    COUNT(DISTINCT f.rowid) as frequency,
    COALESCE(SUM(f.total_ttc), 0) as monetary
    FROM {$prefix}societe s
    LEFT JOIN {$prefix}facture f ON f.fk_soc = s.rowid AND f.fk_statut > 0 AND f.entity = {$entity}
    WHERE s.entity = {$entity} AND s.client > 0
    GROUP BY s.rowid, s.nom
    HAVING frequency > 0
    ORDER BY monetary DESC";
$res = $db->query($sql_rfm);
$customers_rfm = [];
$all_recency = [];
$all_frequency = [];
$all_monetary = [];
while ($row = $db->fetch_object($res)) {
    $customers_rfm[] = $row;
    $all_recency[] = (int) $row->recency_days;
    $all_frequency[] = (int) $row->frequency;
    $all_monetary[] = (float) $row->monetary;
}

// Calculate RFM thresholds (quartiles)
sort($all_recency);
sort($all_frequency);
sort($all_monetary);
$n_cust = count($customers_rfm);

function get_quartiles($arr) {
    $n = count($arr);
    if ($n < 4) return [$arr[0] ?? 0, $arr[0] ?? 0, $arr[0] ?? 0, $arr[0] ?? 0];
    return [
        $arr[(int)($n * 0.25)],
        $arr[(int)($n * 0.50)],
        $arr[(int)($n * 0.75)],
        $arr[$n - 1]
    ];
}

$rfm_thresholds = [
    'r' => get_quartiles($all_recency),
    'f' => get_quartiles($all_frequency),
    'm' => get_quartiles($all_monetary),
];

// Score each customer
$rfm_segments = ['Champions' => 0, 'Fideles' => 0, 'Potentiels' => 0, 'A_risque' => 0, 'Perdus' => 0];
foreach ($customers_rfm as &$c) {
    list($r, $f, $m, $avg) = ml_rfm_score((int)$c->recency_days, (int)$c->frequency, (float)$c->monetary, $rfm_thresholds);
    $c->r_score = $r;
    $c->f_score = $f;
    $c->m_score = $m;
    $c->rfm_avg = $avg;
    
    if ($r >= 4 && $f >= 4) $c->segment = 'Champions';
    elseif ($f >= 3 && $m >= 3) $c->segment = 'Fideles';
    elseif ($r >= 3 && $f >= 2) $c->segment = 'Potentiels';
    elseif ($r <= 2 && $f >= 2) $c->segment = 'A_risque';
    else $c->segment = 'Perdus';
    
    $rfm_segments[$c->segment]++;
}
unset($c);

// --- 5. Open Proposals with Conversion Scoring ---
$sql_open = "SELECT p.rowid, p.ref, p.total_ttc, p.datep,
    s.nom as customer_name, s.rowid as fk_soc,
    DATEDIFF(NOW(), p.datep) as days_open
    FROM {$prefix}propal p
    LEFT JOIN {$prefix}societe s ON p.fk_soc = s.rowid
    WHERE p.entity = {$entity} AND p.fk_statut IN (0, 1)
    ORDER BY p.datep DESC";
$res = $db->query($sql_open);
$open_propals = [];
while ($row = $db->fetch_object($res)) {
    $open_propals[] = $row;
}

$sql_conv_stats = "SELECT 
    AVG(CASE WHEN fk_statut IN (2,4) THEN total_ttc END) as avg_converted_amount,
    STDDEV(CASE WHEN fk_statut IN (2,4) THEN total_ttc END) as std_converted_amount,
    COUNT(CASE WHEN fk_statut IN (2,4) THEN 1 END) / NULLIF(COUNT(*), 0) as overall_rate
    FROM {$prefix}propal WHERE entity = {$entity}";
$res = $db->query($sql_conv_stats);
$conv_stats = $db->fetch_object($res);

$sql_cust_rates = "SELECT fk_soc,
    COUNT(CASE WHEN fk_statut IN (2,4) THEN 1 END) / NULLIF(COUNT(*), 0) as cust_rate
    FROM {$prefix}propal WHERE entity = {$entity} GROUP BY fk_soc";
$res = $db->query($sql_cust_rates);
$cust_rates = [];
while ($row = $db->fetch_object($res)) {
    $cust_rates[$row->fk_soc] = (float) $row->cust_rate;
}

foreach ($open_propals as &$p) {
    $cust_rate = isset($cust_rates[$p->fk_soc]) ? $cust_rates[$p->fk_soc] : (float) $conv_stats->overall_rate;
    $p->conversion_score = ml_conversion_score(
        (float) $p->total_ttc,
        (float) $conv_stats->avg_converted_amount,
        (float) $conv_stats->std_converted_amount,
        $cust_rate,
        (int) $p->days_open
    );
}
unset($p);

usort($open_propals, function($a, $b) { return $b->conversion_score - $a->conversion_score; });

// ============================================================
// FORECASTS
// ============================================================

$revenue_forecast = [];
$revenue_confidence = 0;
if (count($revenue_values) >= 3) {
    $x = range(1, count($revenue_values));
    list($slope, $intercept, $r2) = ml_linear_regression($x, $revenue_values);
    $revenue_confidence = round($r2 * 100);
    
    $holt = ml_holt_forecast($revenue_values, 3);
    $n = count($revenue_values);
    
    for ($i = 0; $i < 3; $i++) {
        $lr_pred = $slope * ($n + $i + 1) + $intercept;
        $blended = 0.4 * $lr_pred + 0.6 * $holt[$i];
        
        $month_dt = new DateTime(end($revenue_months) . '-01');
        $month_dt->modify('+' . ($i + 1) . ' months');
        
        $revenue_forecast[] = [
            'month' => $month_dt->format('Y-m'),
            'predicted' => max(0, round($blended)),
            'lower' => max(0, round($blended * 0.7)),
            'upper' => round($blended * 1.3),
        ];
    }
}

$order_forecast = [];
if (count($order_values) >= 3) {
    $holt_orders = ml_holt_forecast($order_values, 3);
    $n = count($order_values);
    $x = range(1, $n);
    list($slope, $intercept, $r2) = ml_linear_regression($x, $order_values);
    
    for ($i = 0; $i < 3; $i++) {
        $lr_pred = $slope * ($n + $i + 1) + $intercept;
        $blended = 0.4 * $lr_pred + 0.6 * $holt_orders[$i];
        
        $month_dt = new DateTime(end($order_months) . '-01');
        $month_dt->modify('+' . ($i + 1) . ' months');
        
        $order_forecast[] = [
            'month' => $month_dt->format('Y-m'),
            'predicted' => max(1, round($blended)),
        ];
    }
}

$conv_forecast = [];
if (count($conversion_rates) >= 3) {
    $holt_conv = ml_holt_forecast($conversion_rates, 3, 0.4, 0.15);
    $n = count($conversion_rates);
    
    for ($i = 0; $i < 3; $i++) {
        $month_dt = new DateTime(end($propal_months) . '-01');
        $month_dt->modify('+' . ($i + 1) . ' months');
        
        $conv_forecast[] = [
            'month' => $month_dt->format('Y-m'),
            'predicted' => max(0, min(100, round($holt_conv[$i], 1))),
        ];
    }
}

// ============================================================
// KPI SUMMARY
// ============================================================

$total_pipeline = 0;
$weighted_pipeline = 0;
foreach ($open_propals as $p) {
    $total_pipeline += (float) $p->total_ttc;
    $weighted_pipeline += (float) $p->total_ttc * ($p->conversion_score / 100);
}

$kpis = [
    'pipeline_total' => $total_pipeline,
    'pipeline_weighted' => $weighted_pipeline,
    'open_proposals' => count($open_propals),
    'avg_conversion' => $conv_stats->overall_rate ? round($conv_stats->overall_rate * 100, 1) : 0,
    'active_customers' => $rfm_segments['Champions'] + $rfm_segments['Fideles'],
    'at_risk_customers' => $rfm_segments['A_risque'] + $rfm_segments['Perdus'],
    'revenue_trend' => count($revenue_forecast) > 0 ? $revenue_forecast[0]['predicted'] : 0,
    'confidence' => $revenue_confidence,
];

// Include header
require_once __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-cpu"></i> Prédictions & Intelligence</h1>
        <p class="text-muted mb-0">Analyses prédictives basées sur vos données Dolibarr &mdash; Moteur ML PHP natif</p>
    </div>
    <div>
        <span class="badge bg-info"><i class="bi bi-database"></i> <?= count($revenue_values) ?> mois de données</span>
        <span class="badge bg-success"><i class="bi bi-bullseye"></i> Confiance: <?= $kpis['confidence'] ?>%</span>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="admin-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small mb-1"><i class="bi bi-funnel"></i> Pipeline (pondéré)</div>
                <div class="h4 mb-0 text-primary"><?= number_format($kpis['pipeline_weighted'], 0, ',', ' ') ?></div>
                <div class="text-muted small">sur <?= number_format($kpis['pipeline_total'], 0, ',', ' ') ?> total (<?= $kpis['open_proposals'] ?> devis)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small mb-1"><i class="bi bi-graph-up-arrow"></i> CA prévu (M+1)</div>
                <div class="h4 mb-0 text-success"><?= number_format($kpis['revenue_trend'], 0, ',', ' ') ?></div>
                <div class="text-muted small">Prévision prochaine période</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small mb-1"><i class="bi bi-percent"></i> Taux de conversion</div>
                <div class="h4 mb-0 text-warning"><?= $kpis['avg_conversion'] ?>%</div>
                <div class="text-muted small">Devis &rarr; Commande (historique)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card h-100">
            <div class="card-body text-center">
                <div class="text-muted small mb-1"><i class="bi bi-people"></i> Clients actifs / à risque</div>
                <div class="h4 mb-0">
                    <span class="text-success"><?= $kpis['active_customers'] ?></span>
                    <span class="text-muted">/</span>
                    <span class="text-danger"><?= $kpis['at_risk_customers'] ?></span>
                </div>
                <div class="text-muted small">Champions+Fidèles / À risque+Perdus</div>
            </div>
        </div>
    </div>
</div>

<!-- Row 1: Revenue Forecast + Order Forecast -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="admin-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up"></i> Prévision de chiffre d'affaires</span>
                <span class="badge bg-secondary">Régression linéaire + Lissage exponentiel (Holt)</span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="admin-card h-100">
            <div class="card-header"><i class="bi bi-box-seam"></i> Prévision commandes</div>
            <div class="card-body">
                <canvas id="ordersChart" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Conversion Scoring + RFM -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="admin-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bullseye"></i> Scoring de conversion &mdash; Devis ouverts</span>
                <span class="badge bg-secondary">Scoring multi-facteurs</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Réf.</th>
                                <th>Client</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center">Jours</th>
                                <th class="text-center">Score</th>
                                <th>Probabilité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($open_propals, 0, 15) as $p): 
                                $score = $p->conversion_score;
                                if ($score >= 65) { $badge_class = 'bg-success'; $label = 'Élevée'; }
                                elseif ($score >= 40) { $badge_class = 'bg-warning text-dark'; $label = 'Moyenne'; }
                                else { $badge_class = 'bg-danger'; $label = 'Faible'; }
                            ?>
                            <tr>
                                <td><a href="<?= DOL_URL_ROOT ?>/comm/propal/card.php?id=<?= $p->rowid ?>" target="_blank" class="text-decoration-none"><?= htmlspecialchars($p->ref) ?></a></td>
                                <td><?= htmlspecialchars(mb_strimwidth($p->customer_name, 0, 25, '...')) ?></td>
                                <td class="text-end text-nowrap"><?= number_format((float)$p->total_ttc, 0, ',', ' ') ?></td>
                                <td class="text-center"><?= $p->days_open ?>j</td>
                                <td class="text-center"><strong><?= $score ?>%</strong></td>
                                <td><span class="badge <?= $badge_class ?>"><?= $label ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($open_propals)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Aucun devis ouvert</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($open_propals) > 15): ?>
                <div class="text-center py-2 border-top">
                    <small class="text-muted"><?= count($open_propals) - 15 ?> devis supplémentaires non affichés</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="admin-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pie-chart"></i> Segmentation clients (RFM)</span>
                <span class="badge bg-secondary">Récence × Fréquence × Montant</span>
            </div>
            <div class="card-body">
                <canvas id="rfmChart" height="200"></canvas>
                <div class="mt-3">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Segment</th><th class="text-center">Clients</th><th>Action</th></tr></thead>
                        <tbody>
                            <tr><td><span class="badge bg-success">Champions</span></td><td class="text-center"><?= $rfm_segments['Champions'] ?></td><td class="small text-muted">Fidéliser, upsell</td></tr>
                            <tr><td><span class="badge bg-primary">Fidèles</span></td><td class="text-center"><?= $rfm_segments['Fidèles'] ?></td><td class="small text-muted">Récompenser</td></tr>
                            <tr><td><span class="badge bg-info">Potentiels</span></td><td class="text-center"><?= $rfm_segments['Potentiels'] ?></td><td class="small text-muted">Développer</td></tr>
                            <tr><td><span class="badge bg-warning text-dark">À risque</span></td><td class="text-center"><?= $rfm_segments['À risque'] ?></td><td class="small text-muted">Réactiver !</td></tr>
                            <tr><td><span class="badge bg-danger">Perdus</span></td><td class="text-center"><?= $rfm_segments['Perdus'] ?></td><td class="small text-muted">Campagne reconquête</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Top Customers RFM Detail -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="admin-card">
            <div class="card-header"><i class="bi bi-trophy"></i> Top 20 clients &mdash; Analyse RFM détaillée</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th class="text-center">R</th>
                                <th class="text-center">F</th>
                                <th class="text-center">M</th>
                                <th class="text-center">Score</th>
                                <th>Segment</th>
                                <th class="text-end">CA total</th>
                                <th class="text-center">Factures</th>
                                <th class="text-center">Dernière activité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($customers_rfm, 0, 20) as $i => $c):
                                $seg_colors = ['Champions'=>'success','Fidèles'=>'primary','Potentiels'=>'info','À risque'=>'warning','Perdus'=>'danger'];
                                $seg_color = $seg_colors[$c->segment] ?? 'secondary';
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><a href="<?= DOL_URL_ROOT ?>/societe/card.php?socid=<?= $c->rowid ?>" target="_blank" class="text-decoration-none"><?= htmlspecialchars(mb_strimwidth($c->name, 0, 30, '...')) ?></a></td>
                                <td class="text-center"><span class="badge bg-<?= $c->r_score >= 4 ? 'success' : ($c->r_score >= 3 ? 'warning' : 'danger') ?>"><?= $c->r_score ?></span></td>
                                <td class="text-center"><span class="badge bg-<?= $c->f_score >= 4 ? 'success' : ($c->f_score >= 3 ? 'warning' : 'danger') ?>"><?= $c->f_score ?></span></td>
                                <td class="text-center"><span class="badge bg-<?= $c->m_score >= 4 ? 'success' : ($c->m_score >= 3 ? 'warning' : 'danger') ?>"><?= $c->m_score ?></span></td>
                                <td class="text-center"><strong><?= $c->rfm_avg ?></strong></td>
                                <td><span class="badge bg-<?= $seg_color ?>"><?= $c->segment ?></span></td>
                                <td class="text-end text-nowrap"><?= number_format((float)$c->monetary, 0, ',', ' ') ?></td>
                                <td class="text-center"><?= $c->frequency ?></td>
                                <td class="text-center"><?= $c->recency_days ?>j</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Forecast Details Table -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="admin-card h-100">
            <div class="card-header"><i class="bi bi-calendar3"></i> Prévisions mensuelles détaillées</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Mois</th><th class="text-end">CA prévu</th><th class="text-end">Fourchette</th><th class="text-center">Commandes</th></tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < count($revenue_forecast); $i++): $rf = $revenue_forecast[$i]; ?>
                        <tr>
                            <td><strong><?= $rf['month'] ?></strong></td>
                            <td class="text-end text-nowrap"><?= number_format($rf['predicted'], 0, ',', ' ') ?></td>
                            <td class="text-end text-muted small"><?= number_format($rf['lower'], 0, ',', ' ') ?> &ndash; <?= number_format($rf['upper'], 0, ',', ' ') ?></td>
                            <td class="text-center"><?= isset($order_forecast[$i]) ? $order_forecast[$i]['predicted'] : '—' ?></td>
                        </tr>
                        <?php endfor; ?>
                        <?php if (empty($revenue_forecast)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Pas assez de données pour les prévisions</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="admin-card h-100">
            <div class="card-header"><i class="bi bi-info-circle"></i> Modèles utilisés</div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="fw-bold"><i class="bi bi-graph-up text-primary"></i> Prévision CA & Commandes</h6>
                    <p class="small text-muted mb-1">Combinaison de <strong>régression linéaire</strong> (tendance long terme) et <strong>lissage exponentiel de Holt</strong> (adaptatif aux tendances récentes). Pondération : 40% linéaire + 60% Holt.</p>
                    <div class="progress" style="height:6px;"><div class="progress-bar bg-primary" style="width:<?= $kpis['confidence'] ?>%"></div></div>
                    <small class="text-muted">R² = <?= $kpis['confidence'] ?>% — <?= $kpis['confidence'] >= 60 ? 'Bon ajustement' : ($kpis['confidence'] >= 30 ? 'Ajustement modéré — données volatiles' : 'Ajustement faible — peu de données') ?></small>
                </div>
                <div class="mb-3">
                    <h6 class="fw-bold"><i class="bi bi-bullseye text-success"></i> Scoring de conversion</h6>
                    <p class="small text-muted mb-0">Score multi-facteurs : proximité au montant moyen converti (±20pts), historique client (×30pts), fraîcheur du devis (±15pts). Calibré sur <?= count($monthly_propals) > 0 ? array_sum($propal_values) : 0 ?> devis historiques.</p>
                </div>
                <div>
                    <h6 class="fw-bold"><i class="bi bi-pie-chart text-warning"></i> Segmentation RFM</h6>
                    <p class="small text-muted mb-0"><strong>R</strong>écence (dernière facture), <strong>F</strong>réquence (nombre de factures), <strong>M</strong>onétaire (CA total). Scoring 1-5 par quartiles sur <?= $n_cust ?> clients actifs.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Color palette
    const colors = {
        primary: '#4361ee',
        success: '#2ec4b6',
        warning: '#ff9f1c',
        danger: '#e71d36',
        info: '#3a86a8',
        light: '#f8f9fa',
        gridColor: 'rgba(0,0,0,0.05)'
    };

    // Common chart options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { usePointStyle: true, padding: 12, font: { size: 11 } } }
        },
        scales: {
            x: { grid: { display: false } },
            y: { grid: { color: colors.gridColor }, beginAtZero: true }
        }
    };

    // ---- Revenue Chart ----
    const revenueLabels = <?= json_encode($revenue_months) ?>;
    const revenueData = <?= json_encode($revenue_values) ?>;
    const revForecastLabels = <?= json_encode(array_column($revenue_forecast, 'month')) ?>;
    const revForecastData = <?= json_encode(array_column($revenue_forecast, 'predicted')) ?>;
    const revForecastLower = <?= json_encode(array_column($revenue_forecast, 'lower')) ?>;
    const revForecastUpper = <?= json_encode(array_column($revenue_forecast, 'upper')) ?>;

    const allRevLabels = revenueLabels.concat(revForecastLabels);
    const actualData = revenueData.concat(new Array(revForecastLabels.length).fill(null));
    const forecastData = new Array(revenueData.length - 1).fill(null);
    forecastData.push(revenueData[revenueData.length - 1]);
    const forecastFull = forecastData.concat(revForecastData);
    const lowerBound = new Array(revenueData.length).fill(null).concat(revForecastLower);
    const upperBound = new Array(revenueData.length).fill(null).concat(revForecastUpper);

    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: allRevLabels,
            datasets: [
                {
                    label: 'CA réel',
                    data: actualData,
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3
                },
                {
                    label: 'Prévision',
                    data: forecastFull,
                    borderColor: colors.success,
                    borderDash: [6, 3],
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    pointRadius: 4,
                    pointStyle: 'triangle'
                },
                {
                    label: 'Fourchette haute',
                    data: upperBound,
                    borderColor: 'transparent',
                    backgroundColor: colors.success + '15',
                    fill: '+1',
                    pointRadius: 0
                },
                {
                    label: 'Fourchette basse',
                    data: lowerBound,
                    borderColor: 'transparent',
                    backgroundColor: colors.success + '15',
                    fill: false,
                    pointRadius: 0
                }
            ]
        },
        options: {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            if (ctx.parsed.y === null) return '';
                            return ctx.dataset.label + ': ' + new Intl.NumberFormat('fr-FR').format(ctx.parsed.y);
                        }
                    }
                }
            },
            scales: {
                ...commonOptions.scales,
                y: {
                    ...commonOptions.scales.y,
                    ticks: { callback: v => new Intl.NumberFormat('fr-FR', { notation: 'compact' }).format(v) }
                }
            }
        }
    });

    // ---- Orders Chart ----
    const orderLabels = <?= json_encode($order_months) ?>;
    const orderData = <?= json_encode($order_values) ?>;
    const ordForecastLabels = <?= json_encode(array_column($order_forecast, 'month')) ?>;
    const ordForecastData = <?= json_encode(array_column($order_forecast, 'predicted')) ?>;

    const allOrdLabels = orderLabels.concat(ordForecastLabels);
    const actualOrders = orderData.concat(new Array(ordForecastLabels.length).fill(null));
    const forecastOrders = new Array(orderData.length - 1).fill(null);
    forecastOrders.push(orderData[orderData.length - 1]);
    const forecastOrdFull = forecastOrders.concat(ordForecastData);

    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: allOrdLabels,
            datasets: [
                {
                    label: 'Commandes réelles',
                    data: actualOrders,
                    backgroundColor: colors.primary + '80',
                    borderRadius: 4
                },
                {
                    label: 'Prévision',
                    data: forecastOrdFull,
                    backgroundColor: colors.success + '60',
                    borderRadius: 4,
                    borderWidth: 2,
                    borderColor: colors.success,
                    borderDash: [4, 2]
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: { ...commonOptions.scales.y, ticks: { stepSize: 1 } }
            }
        }
    });

    // ---- RFM Doughnut Chart ----
    const rfmData = <?= json_encode(array_values($rfm_segments)) ?>;
    const rfmLabels = ['Champions', 'Fidèles', 'Potentiels', 'À risque', 'Perdus'];
    
    new Chart(document.getElementById('rfmChart'), {
        type: 'doughnut',
        data: {
            labels: rfmLabels,
            datasets: [{
                data: rfmData,
                backgroundColor: [colors.success, colors.primary, colors.info, colors.warning, colors.danger],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 10, font: { size: 11 } } }
            },
            cutout: '55%'
        }
    });
});
</script>

<?php
require_once __DIR__.'/../includes/footer.php';
