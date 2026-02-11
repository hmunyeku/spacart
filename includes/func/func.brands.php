<?php
/**
 * SpaCart - Brands functions
 * Brands are stored as categories with a specific parent or tag
 * We use a dedicated config key SPACART_BRAND_CATEGORY_ID to identify the brand root category
 */

/**
 * Get brand root category ID from config
 */
function spacart_get_brand_root_id()
{
    return (int) getDolGlobalString('SPACART_BRAND_CATEGORY_ID', 0);
}

/**
 * Get all brands (child categories of brand root)
 */
function spacart_get_brands()
{
    global $db;
    $brandRootId = spacart_get_brand_root_id();
    if (!$brandRootId) return array();

    $brands = array();

    $sql = "SELECT c.rowid as id, c.label, c.description, c.color";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie c";
    $sql .= " WHERE c.fk_parent = ".(int) $brandRootId;
    $sql .= " AND c.type = 0 AND c.visible = 1";
    $sql .= " ORDER BY c.label ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->product_count = spacart_count_brand_products($obj->id);
            $brands[] = $obj;
        }
    }
    return $brands;
}

/**
 * Get single brand
 */
function spacart_get_brand($id)
{
    global $db;

    $sql = "SELECT c.rowid as id, c.label, c.description, c.color";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie c";
    $sql .= " WHERE c.rowid = ".(int) $id;
    $sql .= " AND c.type = 0";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        return $db->fetch_object($resql);
    }
    return null;
}

/**
 * Count products belonging to a brand category
 */
function spacart_count_brand_products($brandId)
{
    global $db;

    $sql = "SELECT COUNT(DISTINCT cp.fk_product) as cnt";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie_product cp";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cp.fk_product";
    $sql .= " WHERE cp.fk_categorie = ".(int) $brandId;
    $sql .= " AND p.tosell = 1 AND p.fk_product_type = 0";

    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        return (int) $obj->cnt;
    }
    return 0;
}

/**
 * Get brand for a product (first brand category found)
 */
function spacart_get_product_brand($productId)
{
    global $db;
    $brandRootId = spacart_get_brand_root_id();
    if (!$brandRootId) return null;

    $sql = "SELECT c.rowid as id, c.label, c.description";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie c";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."categorie_product cp ON cp.fk_categorie = c.rowid";
    $sql .= " WHERE cp.fk_product = ".(int) $productId;
    $sql .= " AND c.fk_parent = ".(int) $brandRootId;
    $sql .= " LIMIT 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        return $db->fetch_object($resql);
    }
    return null;
}
