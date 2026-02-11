<?php
/**
 * SpaCart Page - XML Sitemap
 * Generates sitemap.xml for SEO
 */

header('Content-Type: application/xml; charset=utf-8');

$baseUrl = DOL_MAIN_URL_ROOT.'/custom/spacart/public/';

$urls = array();

// Homepage
$urls[] = array('loc' => $baseUrl.'#/', 'priority' => '1.0', 'changefreq' => 'daily');

// Categories
$sqlCat = "SELECT c.rowid, c.label FROM ".MAIN_DB_PREFIX."categorie c WHERE c.type = 0 AND c.visible = 1";
$resCat = $db->query($sqlCat);
if ($resCat) {
    while ($cat = $db->fetch_object($resCat)) {
        $urls[] = array('loc' => $baseUrl.'#/category/'.$cat->rowid, 'priority' => '0.8', 'changefreq' => 'weekly');
    }
}

// Products
$sqlProd = "SELECT p.rowid, p.tms FROM ".MAIN_DB_PREFIX."product p WHERE p.tosell = 1 AND p.fk_product_type = 0";
$resProd = $db->query($sqlProd);
if ($resProd) {
    while ($prod = $db->fetch_object($resProd)) {
        $urls[] = array('loc' => $baseUrl.'#/product/'.$prod->rowid, 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => substr($prod->tms, 0, 10));
    }
}

// CMS Pages
$sqlPage = "SELECT slug, tms FROM ".MAIN_DB_PREFIX."spacart_page WHERE status = 1";
$resPage = $db->query($sqlPage);
if ($resPage) {
    while ($page = $db->fetch_object($resPage)) {
        $urls[] = array('loc' => $baseUrl.'#/page/'.$page->slug, 'priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => substr($page->tms, 0, 10));
    }
}

// Blog articles
$sqlBlog = "SELECT rowid, tms FROM ".MAIN_DB_PREFIX."spacart_blog WHERE status = 1";
$resBlog = $db->query($sqlBlog);
if ($resBlog) {
    while ($blog = $db->fetch_object($resBlog)) {
        $urls[] = array('loc' => $baseUrl.'#/blog/'.$blog->rowid, 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => substr($blog->tms, 0, 10));
    }
}

// News articles
$sqlNews = "SELECT rowid, tms FROM ".MAIN_DB_PREFIX."spacart_news WHERE status = 1";
$resNews = $db->query($sqlNews);
if ($resNews) {
    while ($news = $db->fetch_object($resNews)) {
        $urls[] = array('loc' => $baseUrl.'#/news/'.$news->rowid, 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => substr($news->tms, 0, 10));
    }
}

// Static pages
$staticPages = array('cart', 'login', 'register', 'brands', 'testimonials', 'gift_cards');
foreach ($staticPages as $sp) {
    $urls[] = array('loc' => $baseUrl.'#/'.$sp, 'priority' => '0.4', 'changefreq' => 'monthly');
}

// Output XML
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

foreach ($urls as $u) {
    echo "  <url>\n";
    echo "    <loc>".htmlspecialchars($u['loc'])."</loc>\n";
    if (!empty($u['lastmod'])) echo "    <lastmod>".$u['lastmod']."</lastmod>\n";
    echo "    <changefreq>".$u['changefreq']."</changefreq>\n";
    echo "    <priority>".$u['priority']."</priority>\n";
    echo "  </url>\n";
}

echo "</urlset>\n";
exit;
