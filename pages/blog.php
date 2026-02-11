<?php
/**
 * SpaCart - Blog page handler
 */
if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.blog.php';

$articleId = !empty($get[1]) ? (int) $get[1] : 0;

if ($articleId) {
    // Single article
    $article = spacart_get_blog_article($articleId);
    if (!$article) {
        $page_html = '<div class="spacart-empty-state"><i class="material-icons large grey-text">error</i><p>Article non trouvé</p></div>';
        $page_title = 'Article non trouvé';
        $breadcrumbs_html = '';
        return;
    }

    $page_title = htmlspecialchars($article->meta_title ?: $article->title).' - '.$spacart_config['title'];
    $bc_items = array(
        array('label' => 'Accueil', 'url' => '#/'),
        array('label' => 'Blog', 'url' => '#/blog'),
        array('label' => $article->title, 'url' => '')
    );
    $breadcrumbs_html = spacart_breadcrumbs($bc_items);

    $tpl_vars = array('article' => $article, 'type' => 'blog', 'config' => $spacart_config);
    $page_html = spacart_render(SPACART_TPL_PATH.'/blog/article.php', $tpl_vars);
} else {
    // Article list
    $pageNum = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $result = spacart_get_blog_articles($pageNum, 9);

    $page_title = 'Blog - '.$spacart_config['title'];
    $bc_items = array(array('label' => 'Accueil', 'url' => '#/'), array('label' => 'Blog', 'url' => ''));
    $breadcrumbs_html = spacart_breadcrumbs($bc_items);

    $tpl_vars = array(
        'articles' => $result['items'],
        'total' => $result['total'],
        'total_pages' => $result['pages'],
        'current_page' => $pageNum,
        'type' => 'blog',
        'config' => $spacart_config
    );
    $page_html = spacart_render(SPACART_TPL_PATH.'/blog/body.php', $tpl_vars);
}
