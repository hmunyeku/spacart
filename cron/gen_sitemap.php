<?php
$conn = new mysqli("localhost", "spacart_user", "SpAcArT2026xCoex", "erp_main");
if ($conn->connect_error) die("DB error: ".$conn->connect_error);
$conn->set_charset("utf8");

// Get public URL
$base_url = "https://erp.coexdis.com/custom/spacart";
$res = $conn->query("SELECT value FROM llx_const WHERE name='SPACART_PUBLIC_URL' AND entity=1 AND value != '' LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $base_url = rtrim($row["value"], "/");
}

$urls = array();

// Homepage
$urls[] = array('loc' => $base_url . '/', 'freq' => 'daily', 'pri' => '1.0');

// Static pages
$statics = array("products", "brands", "sale", "about", "contact", "terms", "privacy", "delivery");
foreach ($statics as $p) {
    $urls[] = array('loc' => $base_url . '/' . $p, 'freq' => 'weekly', 'pri' => '0.7');
}

// Categories
$res = $conn->query("SELECT DISTINCT c.rowid, c.label FROM llx_categorie c WHERE c.type=0 AND c.visible=1 ORDER BY c.label");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $urls[] = array('loc' => $base_url . '/category/' . $row['rowid'], 'freq' => 'weekly', 'pri' => '0.6');
    }
}

// Products (active, price > 0)
$res = $conn->query("SELECT p.rowid, p.tms FROM llx_product p WHERE p.tosell=1 AND p.price > 0 AND p.entity=1 ORDER BY p.tms DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $lastmod = date("Y-m-d", strtotime($row["tms"]));
        $urls[] = array('loc' => $base_url . '/product/' . $row['rowid'], 'freq' => 'weekly', 'pri' => '0.5', 'lastmod' => $lastmod);
    }
}

// Build XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
    if (!empty($u['lastmod'])) $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
    $xml .= "    <changefreq>" . $u['freq'] . "</changefreq>\n";
    $xml .= "    <priority>" . $u['pri'] . "</priority>\n";
    $xml .= "  </url>\n";
}
$xml .= "</urlset>\n";

$dest = "/var/www/vhosts/coexdis.com/erp/htdocs/custom/spacart/sitemap.xml";
file_put_contents($dest, $xml);
echo "Sitemap generated: " . count($urls) . " URLs, " . strlen($xml) . " bytes\n";
$conn->close();
