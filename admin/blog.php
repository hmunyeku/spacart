<?php
/**
 * SpaCart Admin - Blog management
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dirname(__DIR__).'/lib/spacart.lib.php';

$langs->loadLangs(array('admin', 'spacart@spacart'));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

// Actions
if ($action === 'add' || $action === 'update') {
    $title = GETPOST('title', 'alphanohtml');
    $content = GETPOST('content', 'restricthtml');
    $author = GETPOST('author', 'alphanohtml');
    $status = GETPOST('status', 'int');
    $slug = spacartSlugify($title);

    if ($action === 'update' && $id > 0) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_blog SET title = '".$db->escape($title)."', slug = '".$db->escape($slug)."', content = '".$db->escape($content)."', author = '".$db->escape($author)."', status = ".(int) $status.", tms = NOW() WHERE rowid = ".(int) $id;
        $db->query($sql);
        setEventMessages('Article mis à jour', null, 'mesgs');
    } else {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_blog (title, slug, content, author, status, date_creation, tms) VALUES ('".$db->escape($title)."', '".$db->escape($slug)."', '".$db->escape($content)."', '".$db->escape($author)."', ".(int) $status.", NOW(), NOW())";
        $db->query($sql);
        setEventMessages('Article créé', null, 'mesgs');
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if ($action === 'delete' && $id > 0) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_blog WHERE rowid = ".(int) $id);
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_blog_comment WHERE fk_blog = ".(int) $id);
    setEventMessages('Article supprimé', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

// Display
llxHeader('', 'SpaCart - Blog');
$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'blog', 'SpaCart', -1, 'spacart@spacart');

// Add/Edit form
if ($action === 'create' || $action === 'edit') {
    $article = null;
    if ($action === 'edit' && $id > 0) {
        $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_blog WHERE rowid = ".(int) $id);
        $article = $db->fetch_object($res);
    }

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="'.($article ? 'update' : 'add').'">';
    if ($article) print '<input type="hidden" name="id" value="'.$article->rowid.'">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.($article ? 'Modifier' : 'Nouvel').' article</td></tr>';
    print '<tr><td>Titre</td><td><input type="text" name="title" class="flat minwidth300" value="'.($article ? htmlspecialchars($article->title) : '').'"></td></tr>';
    print '<tr><td>Auteur</td><td><input type="text" name="author" class="flat" value="'.($article ? htmlspecialchars($article->author) : '').'"></td></tr>';
    print '<tr><td>Contenu</td><td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $editor = new DolEditor('content', $article ? $article->content : '', '', 300, 'dolibarr_notes', '', false, true, true, ROWS_6, '90%');
    $editor->Create();
    print '</td></tr>';
    print '<tr><td>Statut</td><td><select name="status"><option value="1"'.($article && $article->status == 1 ? ' selected' : '').'>Publié</option><option value="0"'.($article && $article->status == 0 ? ' selected' : '').'>Brouillon</option></select></td></tr>';
    print '</table>';
    print '<div class="center"><input type="submit" class="button" value="Enregistrer"></div>';
    print '</form>';
} else {
    // List
    print '<div class="tabsAction"><a class="butAction" href="?action=create">Nouvel article</a></div>';

    $sql = "SELECT b.rowid, b.title, b.author, b.status, b.date_creation FROM ".MAIN_DB_PREFIX."spacart_blog b ORDER BY b.date_creation DESC";
    $resql = $db->query($sql);

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>ID</td><td>Titre</td><td>Auteur</td><td>Statut</td><td>Date</td><td></td></tr>';

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td>'.$obj->rowid.'</td>';
            print '<td>'.htmlspecialchars($obj->title).'</td>';
            print '<td>'.htmlspecialchars($obj->author).'</td>';
            print '<td>'.($obj->status ? '<span class="badge badge-status4">Publié</span>' : '<span class="badge badge-status0">Brouillon</span>').'</td>';
            print '<td>'.dol_print_date(strtotime($obj->date_creation), 'day').'</td>';
            print '<td class="right">';
            print '<a href="?action=edit&id='.$obj->rowid.'">'.img_edit().'</a> ';
            print '<a href="?action=delete&id='.$obj->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a>';
            print '</td>';
            print '</tr>';
        }
    }
    print '</table>';
}

print dol_get_fiche_end();
llxFooter();
