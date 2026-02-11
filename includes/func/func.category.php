<?php
/**
 * SpaCart - Category functions
 * Read categories from Dolibarr llx_categorie
 */

/**
 * Get all product categories as flat list
 */
function spacart_get_categories($parentId = 0)
{
    global $db;
    $categories = array();

    $sql = "SELECT c.rowid as id, c.label, c.description, c.fk_parent, c.visible,";
    $sql .= " c.position, c.color";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie c";
    $sql .= " WHERE c.type = 0"; // Product categories
    $sql .= " AND c.visible = 1";
    $sql .= " AND c.entity IN (".getEntity('category').")";

    if ($parentId > 0) {
        $sql .= " AND c.fk_parent = ".(int) $parentId;
    }

    $sql .= " ORDER BY c.position ASC, c.label ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $categories[] = array(
                'id' => (int) $obj->id,
                'label' => $obj->label,
                'description' => $obj->description,
                'fk_parent' => (int) $obj->fk_parent,
                'visible' => (int) $obj->visible,
                'position' => (int) $obj->position,
                'color' => $obj->color,
                'children' => array()
            );
        }
    }
    return $categories;
}

/**
 * Get category tree (hierarchical)
 */
function spacart_get_category_tree()
{
    $all = spacart_get_categories();
    return spacart_build_category_tree($all, 0);
}

/**
 * Get single category
 */
function spacart_get_category($id)
{
    global $db;

    $sql = "SELECT c.rowid as id, c.label, c.description, c.fk_parent, c.visible,";
    $sql .= " c.position, c.color";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie c";
    $sql .= " WHERE c.rowid = ".(int) $id;
    $sql .= " AND c.type = 0 AND c.visible = 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        return $db->fetch_object($resql);
    }
    return null;
}

/**
 * Get all IDs for a category + its children (recursive)
 */
function spacart_get_category_ids_recursive($catId)
{
    global $db;
    $ids = array((int) $catId);

    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";
    $sql .= " WHERE fk_parent = ".(int) $catId;
    $sql .= " AND type = 0 AND visible = 1";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $childIds = spacart_get_category_ids_recursive((int) $obj->rowid);
            $ids = array_merge($ids, $childIds);
        }
    }

    return array_unique($ids);
}

/**
 * Get category breadcrumb (parent chain)
 */
function spacart_get_category_breadcrumb($catId)
{
    global $db;
    $chain = array();

    $id = (int) $catId;
    $maxDepth = 10; // prevent infinite loop
    $depth = 0;

    while ($id > 0 && $depth < $maxDepth) {
        $sql = "SELECT rowid, label, fk_parent FROM ".MAIN_DB_PREFIX."categorie";
        $sql .= " WHERE rowid = ".(int) $id;
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql)) {
            $obj = $db->fetch_object($resql);
            array_unshift($chain, array(
                'id' => (int) $obj->rowid,
                'label' => $obj->label,
                'url' => '#/category/'.$obj->rowid
            ));
            $id = (int) $obj->fk_parent;
        } else {
            break;
        }
        $depth++;
    }

    return $chain;
}

/**
 * Count products in a category (including subcategories)
 */
function spacart_count_products_in_category($catId)
{
    global $db;
    $catIds = spacart_get_category_ids_recursive($catId);

    $sql = "SELECT COUNT(DISTINCT cp.fk_product) as cnt";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie_product cp";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = cp.fk_product";
    $sql .= " WHERE cp.fk_categorie IN (".implode(',', array_map('intval', $catIds)).")";
    $sql .= " AND p.tosell = 1 AND p.fk_product_type = 0";

    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        return (int) $obj->cnt;
    }
    return 0;
}

/**
 * Get subcategories of a category
 */
function spacart_get_subcategories($parentId)
{
    global $db;
    $subs = array();

    $sql = "SELECT c.rowid as id, c.label, c.description, c.color";
    $sql .= " FROM ".MAIN_DB_PREFIX."categorie c";
    $sql .= " WHERE c.fk_parent = ".(int) $parentId;
    $sql .= " AND c.type = 0 AND c.visible = 1";
    $sql .= " ORDER BY c.position ASC, c.label ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->product_count = spacart_count_products_in_category($obj->id);
            $subs[] = $obj;
        }
    }
    return $subs;
}
