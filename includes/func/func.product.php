<?php
/**
 * SpaCart - Product functions
 * Read products from Dolibarr llx_product + spacart extras
 */

/**
 * Get product list with filters, sorting and pagination
 */
function spacart_get_products($filters = array(), $sort = 'date_desc', $page = 1, $limit = 12)
{
    global $db;

    $offset = ($page - 1) * $limit;
    $where = array("p.entity IN (".getEntity('product').")");
    $where[] = "p.fk_product_type = 0"; // Only products, not services
    $where[] = "p.tosell = 1";

    $join = '';

    // Category filter
    if (!empty($filters['category_id'])) {
        $catId = (int) $filters['category_id'];
        // Include subcategories
        $catIds = spacart_get_category_ids_recursive($catId);
        $join .= " INNER JOIN ".MAIN_DB_PREFIX."categorie_product cp ON cp.fk_product = p.rowid";
        $where[] = "cp.fk_categorie IN (".implode(',', array_map('intval', $catIds)).")";
    }

    // Brand filter (stored as extrafield or category type)
    if (!empty($filters['brand'])) {
        $brandCat = (int) $filters['brand'];
        $join .= " INNER JOIN ".MAIN_DB_PREFIX."categorie_product cpb ON cpb.fk_product = p.rowid";
        $where[] = "cpb.fk_categorie = ".$brandCat;
    }

    // Price range
    if (!empty($filters['price_min'])) {
        $where[] = "p.price >= ".((float) $filters['price_min']);
    }
    if (!empty($filters['price_max'])) {
        $where[] = "p.price <= ".((float) $filters['price_max']);
    }

    // Search
    if (!empty($filters['search'])) {
        $search = $db->escape($filters['search']);
        $where[] = "(p.ref LIKE '%".$search."%' OR p.label LIKE '%".$search."%' OR p.description LIKE '%".$search."%')";
    }

    // In stock only
    if (!empty($filters['in_stock'])) {
        $where[] = "(p.stock_reel > 0 OR p.fk_default_warehouse IS NULL)";
    }

    // Featured
    if (!empty($filters['featured'])) {
        $join .= " INNER JOIN ".MAIN_DB_PREFIX."spacart_featured sf ON sf.fk_product = p.rowid AND sf.active = 1";
    }

    // Sorting
    $orderBy = 'p.datec DESC';
    switch ($sort) {
        case 'price_asc':
            $orderBy = 'p.price ASC';
            break;
        case 'price_desc':
            $orderBy = 'p.price DESC';
            break;
        case 'name_asc':
            $orderBy = 'p.label ASC';
            break;
        case 'name_desc':
            $orderBy = 'p.label DESC';
            break;
        case 'date_asc':
            $orderBy = 'p.datec ASC';
            break;
        case 'date_desc':
            $orderBy = 'p.datec DESC';
            break;
        case 'popular':
            $orderBy = 'p.nb_views DESC';
            break;
        case 'bestseller':
            $orderBy = 'sold_count DESC';
            break;
    }

    $whereStr = implode(' AND ', $where);

    // Count total
    $sqlCount = "SELECT COUNT(DISTINCT p.rowid) as total";
    $sqlCount .= " FROM ".MAIN_DB_PREFIX."product p";
    $sqlCount .= " ".$join;
    $sqlCount .= " WHERE ".$whereStr;

    $resCount = $db->query($sqlCount);
    $total = 0;
    if ($resCount) {
        $obj = $db->fetch_object($resCount);
        $total = (int) $obj->total;
    }

    // Bestseller subquery
    $soldField = '';
    if ($sort === 'bestseller') {
        $soldField = ", COALESCE((SELECT SUM(cd.qty) FROM ".MAIN_DB_PREFIX."commandedet cd
            INNER JOIN ".MAIN_DB_PREFIX."commande c ON c.rowid = cd.fk_commande
            WHERE cd.fk_product = p.rowid AND c.fk_statut >= 1), 0) as sold_count";
    }

    // Main query
    $sql = "SELECT DISTINCT p.rowid, p.ref, p.label, p.description, p.price, p.price_ttc,";
    $sql .= " p.price_base_type, p.tva_tx, p.stock_reel, p.datec, p.weight, p.weight_units,";
    $sql .= " p.fk_product_type, p.tobuy, p.tosell, p.barcode, p.note_public";
    $sql .= $soldField;
    $sql .= " FROM ".MAIN_DB_PREFIX."product p";
    $sql .= " ".$join;
    $sql .= " WHERE ".$whereStr;
    $sql .= " ORDER BY ".$orderBy;
    $sql .= " LIMIT ".(int) $limit." OFFSET ".(int) $offset;

    $products = array();
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->photo_url = spacart_product_photo_url($obj->rowid, $obj->ref);
            $obj->is_new = (strtotime($obj->datec) > strtotime('-30 days'));
            $obj->in_stock = ($obj->stock_reel > 0 || $obj->stock_reel === null);
            $products[] = $obj;
        }
    }

    return array(
        'items' => $products,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pages' => $limit > 0 ? ceil($total / $limit) : 1
    );
}

/**
 * Get single product with all details
 */
function spacart_get_product($id)
{
    global $db;

    $sql = "SELECT p.rowid, p.ref, p.label, p.description, p.note_public,";
    $sql .= " p.price, p.price_ttc, p.price_base_type, p.tva_tx,";
    $sql .= " p.stock_reel, p.datec, p.weight, p.weight_units,";
    $sql .= " p.barcode, p.fk_product_type, p.tosell";
    $sql .= " FROM ".MAIN_DB_PREFIX."product p";
    $sql .= " WHERE p.rowid = ".(int) $id;
    $sql .= " AND p.entity IN (".getEntity('product').")";

    $resql = $db->query($sql);
    if (!$resql || !$db->num_rows($resql)) {
        return null;
    }

    $product = $db->fetch_object($resql);
    $product->photo_url = spacart_product_photo_url($product->rowid, $product->ref);
    $product->photos = spacart_product_photos($product->ref);
    $product->categories = spacart_get_product_categories($product->rowid);
    $product->variants = spacart_get_product_variants($product->rowid);
    $product->options = spacart_get_product_options($product->rowid);
    $product->reviews = spacart_get_product_reviews($product->rowid);
    $product->avg_rating = spacart_get_product_avg_rating($product->rowid);
    $product->review_count = count($product->reviews);
    $product->related = spacart_get_related_products($product->rowid);
    $product->wholesale_prices = spacart_get_wholesale_prices($product->rowid);
    $product->in_stock = ($product->stock_reel > 0 || $product->stock_reel === null);
    $product->is_new = (strtotime($product->datec) > strtotime('-30 days'));

    // Increment views
    spacart_increment_product_views($product->rowid);

    return $product;
}

/**
 * Get product categories
 */
function spacart_get_product_categories($productId)
{
    global $db;
    $cats = array();
    $sql = "SELECT c.rowid, c.label, c.description";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie c";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."categorie_product cp ON cp.fk_categorie = c.rowid";
    $sql .= " WHERE cp.fk_product = ".(int) $productId;
    $sql .= " AND c.type = 0 AND c.visible = 1";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $cats[] = $obj;
        }
    }
    return $cats;
}

/**
 * Get product variants
 */
function spacart_get_product_variants($productId)
{
    global $db;
    $variants = array();

    $sql = "SELECT v.rowid, v.label, v.sku, v.price, v.weight, v.stock, v.active,";
    $sql .= " v.position, v.fk_product";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_variant v";
    $sql .= " WHERE v.fk_product = ".(int) $productId;
    $sql .= " AND v.active = 1";
    $sql .= " ORDER BY v.position ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            // Get variant items/values
            $obj->items = array();
            $sqlItems = "SELECT vi.rowid, vi.attribute_name, vi.attribute_value";
            $sqlItems .= " FROM ".MAIN_DB_PREFIX."spacart_variant_item vi";
            $sqlItems .= " WHERE vi.fk_variant = ".(int) $obj->rowid;
            $resItems = $db->query($sqlItems);
            if ($resItems) {
                while ($item = $db->fetch_object($resItems)) {
                    $obj->items[] = $item;
                }
            }
            $variants[] = $obj;
        }
    }
    return $variants;
}

/**
 * Get product options (option groups + options)
 */
function spacart_get_product_options($productId)
{
    global $db;
    $groups = array();

    $sql = "SELECT og.rowid, og.label, og.type, og.required, og.position";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_option_group og";
    $sql .= " WHERE og.fk_product = ".(int) $productId;
    $sql .= " AND og.active = 1";
    $sql .= " ORDER BY og.position ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($grp = $db->fetch_object($resql)) {
            $grp->options = array();
            $sqlOpt = "SELECT o.rowid, o.label, o.price_modifier, o.price_modifier_type,";
            $sqlOpt .= " o.weight_modifier, o.position";
            $sqlOpt .= " FROM ".MAIN_DB_PREFIX."spacart_option o";
            $sqlOpt .= " WHERE o.fk_option_group = ".(int) $grp->rowid;
            $sqlOpt .= " AND o.active = 1";
            $sqlOpt .= " ORDER BY o.position ASC";
            $resOpt = $db->query($sqlOpt);
            if ($resOpt) {
                while ($opt = $db->fetch_object($resOpt)) {
                    $grp->options[] = $opt;
                }
            }
            $groups[] = $grp;
        }
    }
    return $groups;
}

/**
 * Get product reviews
 */
function spacart_get_product_reviews($productId, $limit = 20)
{
    global $db;
    $reviews = array();

    $sql = "SELECT r.rowid, r.fk_product, r.fk_customer, r.customer_name,";
    $sql .= " r.rating, r.title, r.comment, r.status, r.date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_review r";
    $sql .= " WHERE r.fk_product = ".(int) $productId;
    $sql .= " AND r.status = 1";
    $sql .= " ORDER BY r.date_creation DESC";
    $sql .= " LIMIT ".(int) $limit;

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $reviews[] = $obj;
        }
    }
    return $reviews;
}

/**
 * Get average rating for a product
 */
function spacart_get_product_avg_rating($productId)
{
    global $db;
    $sql = "SELECT AVG(rating) as avg_rating FROM ".MAIN_DB_PREFIX."spacart_review";
    $sql .= " WHERE fk_product = ".(int) $productId." AND status = 1";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        return round((float) $obj->avg_rating, 1);
    }
    return 0;
}

/**
 * Get related products
 */
function spacart_get_related_products($productId, $limit = 4)
{
    global $db;
    $products = array();

    // First try explicit relations
    $sql = "SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.stock_reel, p.datec";
    $sql .= " FROM ".MAIN_DB_PREFIX."product p";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."spacart_related sr ON sr.fk_product_related = p.rowid";
    $sql .= " WHERE sr.fk_product = ".(int) $productId;
    $sql .= " AND p.tosell = 1";
    $sql .= " ORDER BY sr.position ASC";
    $sql .= " LIMIT ".(int) $limit;

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->photo_url = spacart_product_photo_url($obj->rowid, $obj->ref);
            $products[] = $obj;
        }
    }

    // If not enough, fill with same category products
    if (count($products) < $limit) {
        $excludeIds = array((int) $productId);
        foreach ($products as $p) {
            $excludeIds[] = (int) $p->rowid;
        }
        $remaining = $limit - count($products);

        $sql2 = "SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.stock_reel, p.datec";
        $sql2 .= " FROM ".MAIN_DB_PREFIX."product p";
        $sql2 .= " INNER JOIN ".MAIN_DB_PREFIX."categorie_product cp ON cp.fk_product = p.rowid";
        $sql2 .= " WHERE cp.fk_categorie IN (";
        $sql2 .= "   SELECT fk_categorie FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_product = ".(int) $productId;
        $sql2 .= " )";
        $sql2 .= " AND p.rowid NOT IN (".implode(',', $excludeIds).")";
        $sql2 .= " AND p.tosell = 1";
        $sql2 .= " ORDER BY RAND()";
        $sql2 .= " LIMIT ".(int) $remaining;

        $resql2 = $db->query($sql2);
        if ($resql2) {
            while ($obj = $db->fetch_object($resql2)) {
                $obj->photo_url = spacart_product_photo_url($obj->rowid, $obj->ref);
                $products[] = $obj;
            }
        }
    }

    return $products;
}

/**
 * Get wholesale/tiered prices
 */
function spacart_get_wholesale_prices($productId)
{
    global $db;
    $prices = array();

    $sql = "SELECT wp.rowid, wp.fk_product, wp.fk_variant, wp.min_qty, wp.price,";
    $sql .= " wp.membership_type";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_wholesale_price wp";
    $sql .= " WHERE wp.fk_product = ".(int) $productId;
    $sql .= " AND wp.active = 1";
    $sql .= " ORDER BY wp.min_qty ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $prices[] = $obj;
        }
    }
    return $prices;
}

/**
 * Increment product view counter
 */
function spacart_increment_product_views($productId)
{
    global $db;
    // Use Dolibarr's nb_views field if available or a separate tracker
    $sql = "UPDATE ".MAIN_DB_PREFIX."product SET nb_views = COALESCE(nb_views, 0) + 1";
    $sql .= " WHERE rowid = ".(int) $productId;
    $db->query($sql);
}

/**
 * Get featured products
 */
function spacart_get_featured_products($limit = 8)
{
    return spacart_get_products(array('featured' => true), 'date_desc', 1, $limit);
}

/**
 * Get new products (last 30 days)
 */
function spacart_get_new_products($limit = 8)
{
    global $db;
    $products = array();

    $sql = "SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.stock_reel, p.datec";
    $sql .= " FROM ".MAIN_DB_PREFIX."product p";
    $sql .= " WHERE p.tosell = 1 AND p.fk_product_type = 0";
    $sql .= " AND p.entity IN (".getEntity('product').")";
    $sql .= " AND p.datec >= '".date('Y-m-d', strtotime('-30 days'))."'";
    $sql .= " ORDER BY p.datec DESC";
    $sql .= " LIMIT ".(int) $limit;

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->photo_url = spacart_product_photo_url($obj->rowid, $obj->ref);
            $obj->is_new = true;
            $products[] = $obj;
        }
    }
    return $products;
}

/**
 * Get best selling products
 */
function spacart_get_bestsellers($limit = 8)
{
    global $db;
    $products = array();

    $sql = "SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.stock_reel, p.datec,";
    $sql .= " SUM(cd.qty) as sold_count";
    $sql .= " FROM ".MAIN_DB_PREFIX."product p";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."commandedet cd ON cd.fk_product = p.rowid";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."commande c ON c.rowid = cd.fk_commande AND c.fk_statut >= 1";
    $sql .= " WHERE p.tosell = 1 AND p.fk_product_type = 0";
    $sql .= " AND p.entity IN (".getEntity('product').")";
    $sql .= " GROUP BY p.rowid";
    $sql .= " ORDER BY sold_count DESC";
    $sql .= " LIMIT ".(int) $limit;

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->photo_url = spacart_product_photo_url($obj->rowid, $obj->ref);
            $products[] = $obj;
        }
    }
    return $products;
}

/**
 * Get most viewed products
 */
function spacart_get_most_viewed($limit = 8)
{
    global $db;
    $products = array();

    $sql = "SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.stock_reel, p.datec";
    $sql .= " FROM ".MAIN_DB_PREFIX."product p";
    $sql .= " WHERE p.tosell = 1 AND p.fk_product_type = 0";
    $sql .= " AND p.entity IN (".getEntity('product').")";
    $sql .= " AND COALESCE(p.nb_views, 0) > 0";
    $sql .= " ORDER BY p.nb_views DESC";
    $sql .= " LIMIT ".(int) $limit;

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->photo_url = spacart_product_photo_url($obj->rowid, $obj->ref);
            $products[] = $obj;
        }
    }
    return $products;
}

/**
 * Get recently viewed products (from session)
 */
function spacart_get_recently_viewed($limit = 4)
{
    if (empty($_SESSION['spacart_viewed'])) return array();

    global $db;
    $ids = array_slice(array_reverse($_SESSION['spacart_viewed']), 0, $limit);
    $products = array();

    if (empty($ids)) return $products;

    $sql = "SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.stock_reel";
    $sql .= " FROM ".MAIN_DB_PREFIX."product p";
    $sql .= " WHERE p.rowid IN (".implode(',', array_map('intval', $ids)).")";
    $sql .= " AND p.tosell = 1";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->photo_url = spacart_product_photo_url($obj->rowid, $obj->ref);
            $products[] = $obj;
        }
    }
    return $products;
}

/**
 * Track viewed product in session
 */
function spacart_track_product_view($productId)
{
    if (!isset($_SESSION['spacart_viewed'])) {
        $_SESSION['spacart_viewed'] = array();
    }

    // Remove if already in list
    $key = array_search((int) $productId, $_SESSION['spacart_viewed']);
    if ($key !== false) {
        unset($_SESSION['spacart_viewed'][$key]);
    }

    // Add to end
    $_SESSION['spacart_viewed'][] = (int) $productId;

    // Keep only last 20
    if (count($_SESSION['spacart_viewed']) > 20) {
        $_SESSION['spacart_viewed'] = array_slice($_SESSION['spacart_viewed'], -20);
    }
}

/**
 * Check if product is in wishlist
 */
function spacart_is_in_wishlist($productId, $customerId)
{
    if (!$customerId) return false;

    global $db;
    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_wishlist";
    $sql .= " WHERE fk_product = ".(int) $productId;
    $sql .= " AND fk_customer = ".(int) $customerId;
    $resql = $db->query($sql);
    return ($resql && $db->num_rows($resql) > 0);
}
