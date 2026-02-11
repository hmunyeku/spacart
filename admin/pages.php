<?php
/**
 * SpaCart Admin - CMS Pages management
 */
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dirname(__DIR__).'/lib/spacart.lib.php';

$langs->loadLangs(array('admin', 'spacart@spacart'));
if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

if ($action === 'add' || $action === 'update') {
    $title = GETPOST('title', 'alphanohtml');
    $content = GETPOST('content', 'restricthtml');
    $slug = GETPOST('slug', 'alphanohtml') ?: spacartSlugify($title);
    $status = GETPOST('status', 'int');
    $showInMenu = GETPOST('show_in_menu', 'int');
    $position = GETPOST('position', 'int');

    if ($action === 'update' && $id > 0) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_page SET title = '".$db->escape($title)."', slug = '".$db->escape($slug)."', content = '".$db->escape($content)."', status = ".(int) $status.", show_in_menu = ".(int) $showInMenu.", position = ".(int) $position.", tms = NOW() WHERE rowid = ".(int) $id;
        $db->query($sql);
        setEventMessages('Page mise à jour', null, 'mesgs');
    } else {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_page (title, slug, content, status, show_in_menu, position, date_creation, tms) VALUES ('".$db->escape($title)."', '".$db->escape($slug)."', '".$db->escape($content)."', ".(int) $status.", ".(int) $showInMenu.", ".(int) $position.", NOW(), NOW())";
        $db->query($sql);
        setEventMessages('Page créée', null, 'mesgs');
    }
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

if ($action === 'delete' && $id > 0) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_page WHERE rowid = ".(int) $id);
    setEventMessages('Page supprimée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}

llxHeader('', 'SpaCart - Pages');
$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'pages', 'SpaCart', -1, 'spacart@spacart');

if ($action === 'create' || $action === 'edit') {
    $page = null;
    if ($action === 'edit' && $id > 0) {
        $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_page WHERE rowid = ".(int) $id);
        $page = $db->fetch_object($res);
    }

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="'.($page ? 'update' : 'add').'">';
    if ($page) print '<input type="hidden" name="id" value="'.$page->rowid.'">';

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.($page ? 'Modifier' : 'Nouvelle').' page</td></tr>';
    print '<tr><td>Titre</td><td><input type="text" name="title" class="flat minwidth300" value="'.($page ? htmlspecialchars($page->title) : '').'"></td></tr>';
    print '<tr><td>Slug (URL)</td><td><input type="text" name="slug" class="flat minwidth200" value="'.($page ? htmlspecialchars($page->slug) : '').'" placeholder="auto-généré"></td></tr>';
    print '<tr><td>Contenu</td><td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $editor = new DolEditor('content', $page ? $page->content : '', '', 300, 'dolibarr_notes', '', false, true, true, ROWS_6, '90%');
    $editor->Create();
    print '</td></tr>';
    print '<tr><td>Afficher dans le menu</td><td><input type="checkbox" name="show_in_menu" value="1"'.($page && $page->show_in_menu ? ' checked' : '').'></td></tr>';
    print '<tr><td>Position</td><td><input type="number" name="position" class="flat" value="'.($page ? $page->position : 0).'"></td></tr>';
    print '<tr><td>Statut</td><td><select name="status"><option value="1"'.($page && $page->status == 1 ? ' selected' : '').'>Publié</option><option value="0"'.($page && $page->status == 0 ? ' selected' : '').'>Brouillon</option></select></td></tr>';
    print '</table>';
    print '<div class="center"><input type="submit" class="button" value="Enregistrer"></div>';
    print '</form>';
} else {
    print '<div class="tabsAction"><a class="butAction" href="?action=create">Nouvelle page</a></div>';

    $sql = "SELECT p.rowid, p.title, p.slug, p.status, p.show_in_menu, p.position FROM ".MAIN_DB_PREFIX."spacart_page p ORDER BY p.position ASC";
    $resql = $db->query($sql);

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>Titre</td><td>Slug</td><td>Menu</td><td>Statut</td><td>Position</td><td></td></tr>';

    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td>'.htmlspecialchars($obj->title).'</td>';
            print '<td><code>'.$obj->slug.'</code></td>';
            print '<td>'.($obj->show_in_menu ? 'Oui' : 'Non').'</td>';
            print '<td>'.($obj->status ? 'Publié' : 'Brouillon').'</td>';
            print '<td>'.$obj->position.'</td>';
            print '<td class="right"><a href="?action=edit&id='.$obj->rowid.'">'.img_edit().'</a> <a href="?action=delete&id='.$obj->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a></td>';
            print '</tr>';
        }
    }
    print '</table>';
}

print dol_get_fiche_end();
llxFooter();
