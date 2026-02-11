<?php
/**
 * SpaCart - Blog & News functions
 */

/**
 * Get blog articles
 */
function spacart_get_blog_articles($page = 1, $limit = 10)
{
    global $db;
    $offset = ($page - 1) * $limit;
    $articles = array();

    $sqlCount = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."spacart_blog WHERE status = 1";
    $resCount = $db->query($sqlCount);
    $total = $resCount ? (int) $db->fetch_object($resCount)->total : 0;

    $sql = "SELECT b.rowid, b.title, b.slug, b.content, b.image, b.author,";
    $sql .= " b.meta_title, b.meta_description, b.status, b.date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_blog b";
    $sql .= " WHERE b.status = 1";
    $sql .= " ORDER BY b.date_creation DESC";
    $sql .= " LIMIT ".(int) $limit." OFFSET ".(int) $offset;

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->excerpt = strip_tags(substr($obj->content, 0, 200)).'...';
            $obj->comment_count = spacart_count_blog_comments($obj->rowid);
            $articles[] = $obj;
        }
    }

    return array('items' => $articles, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit));
}

/**
 * Get single blog article
 */
function spacart_get_blog_article($id)
{
    global $db;

    $sql = "SELECT b.rowid, b.title, b.slug, b.content, b.image, b.author,";
    $sql .= " b.meta_title, b.meta_description, b.status, b.date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_blog b";
    $sql .= " WHERE b.rowid = ".(int) $id." AND b.status = 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        $article = $db->fetch_object($resql);
        $article->comments = spacart_get_blog_comments($id);
        return $article;
    }
    return null;
}

/**
 * Get blog comments
 */
function spacart_get_blog_comments($articleId)
{
    global $db;
    $comments = array();

    $sql = "SELECT bc.rowid, bc.author_name, bc.author_email, bc.content, bc.status, bc.date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_blog_comment bc";
    $sql .= " WHERE bc.fk_blog = ".(int) $articleId." AND bc.status = 1";
    $sql .= " ORDER BY bc.date_creation ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $comments[] = $obj;
        }
    }
    return $comments;
}

function spacart_count_blog_comments($articleId)
{
    global $db;
    $sql = "SELECT COUNT(*) as cnt FROM ".MAIN_DB_PREFIX."spacart_blog_comment WHERE fk_blog = ".(int) $articleId." AND status = 1";
    $res = $db->query($sql);
    return $res ? (int) $db->fetch_object($res)->cnt : 0;
}

/**
 * Get news articles
 */
function spacart_get_news_articles($page = 1, $limit = 10)
{
    global $db;
    $offset = ($page - 1) * $limit;
    $articles = array();

    $sqlCount = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."spacart_news WHERE status = 1";
    $resCount = $db->query($sqlCount);
    $total = $resCount ? (int) $db->fetch_object($resCount)->total : 0;

    $sql = "SELECT n.rowid, n.title, n.slug, n.content, n.image, n.author,";
    $sql .= " n.status, n.date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_news n";
    $sql .= " WHERE n.status = 1";
    $sql .= " ORDER BY n.date_creation DESC";
    $sql .= " LIMIT ".(int) $limit." OFFSET ".(int) $offset;

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->excerpt = strip_tags(substr($obj->content, 0, 200)).'...';
            $articles[] = $obj;
        }
    }

    return array('items' => $articles, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit));
}

/**
 * Get single news article
 */
function spacart_get_news_article($id)
{
    global $db;

    $sql = "SELECT n.rowid, n.title, n.slug, n.content, n.image, n.author, n.status, n.date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_news n";
    $sql .= " WHERE n.rowid = ".(int) $id." AND n.status = 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        $article = $db->fetch_object($resql);
        // Get news comments
        $article->comments = array();
        $sqlC = "SELECT nc.rowid, nc.author_name, nc.content, nc.status, nc.date_creation";
        $sqlC .= " FROM ".MAIN_DB_PREFIX."spacart_news_comment nc";
        $sqlC .= " WHERE nc.fk_news = ".(int) $id." AND nc.status = 1";
        $sqlC .= " ORDER BY nc.date_creation ASC";
        $resC = $db->query($sqlC);
        if ($resC) {
            while ($c = $db->fetch_object($resC)) {
                $article->comments[] = $c;
            }
        }
        return $article;
    }
    return null;
}

/**
 * Get CMS page by slug
 */
function spacart_get_page_by_slug($slug)
{
    global $db;

    $sql = "SELECT p.rowid, p.title, p.slug, p.content, p.meta_title, p.meta_description,";
    $sql .= " p.show_in_menu, p.position, p.status, p.date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_page p";
    $sql .= " WHERE p.slug = '".$db->escape($slug)."' AND p.status = 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        return $db->fetch_object($resql);
    }
    return null;
}

/**
 * Get all active testimonials
 */
function spacart_get_testimonials()
{
    global $db;
    $items = array();

    $sql = "SELECT t.rowid, t.customer_name, t.content, t.rating, t.photo, t.date_creation";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_testimonial t";
    $sql .= " WHERE t.active = 1";
    $sql .= " ORDER BY t.date_creation DESC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $items[] = $obj;
        }
    }
    return $items;
}
