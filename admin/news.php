<?php
/**
 * SpaCart Admin - News / Actualités management
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
    $excerpt = GETPOST('excerpt', 'alphanohtml');
    $status = GETPOST('status', 'int');
    $image = GETPOST('image', 'alphanohtml');

    if ($action === 'update' && $id > 0) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_news SET title = '".$db->escape($title)."', content = '".$db->escape($content)."', excerpt = '".$db->escape($excerpt)."'";
        $sql .= ", image = '".$db->escape($image)."', status = ".(int) $status.", tms = NOW() WHERE rowid = ".(int) $id;
        $db->query($sql);
        setEventMessages('Actualité mise à jour', null, 'mesgs');
    } else {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_news (title, content, excerpt, image, status, date_creation, tms) VALUES ('".$db->escape($title)."', '".$db->escape($content)."', '".$db->escape($excerpt)."', '".$db->escape($image)."', ".(int) $status.", NOW(), NOW())";
        $db->query($sql);
        setEventMessages('Actualité créée', null, 'mesgs');
    }
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

if ($action === 'delete' && $id > 0) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_news_comment WHERE fk_news = ".(int) $id);
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_news WHERE rowid = ".(int) $id);
    setEventMessages('Actualité supprimée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

llxHeader('', 'SpaCart - Actualités');
$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'news', 'SpaCart', -1, 'spacart@spacart');

if ($action === 'create' || $action === 'edit') {
    $news = null;
    if ($action === 'edit' && $id > 0) {
        $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_news WHERE rowid = ".(int) $id);
        $news = $db->fetch_object($res);
    }

    print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="'.($news ? 'update' : 'add').'">';
    if ($news) print '<input type="hidden" name="id" value="'.$news->rowid.'">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.($news ? 'Modifier' : 'Nouvelle').' actualité</td></tr>';
    print '<tr><td>Titre</td><td><input type="text" name="title" class="flat minwidth300" value="'.($news ? htmlspecialchars($news->title) : '').'"></td></tr>';
    print '<tr><td>Extrait</td><td><textarea name="excerpt" class="flat" rows="2" style="width:80%">'.($news ? htmlspecialchars($news->excerpt) : '').'</textarea></td></tr>';
    print '<tr><td>Image URL</td><td><input type="text" name="image" class="flat minwidth300" value="'.($news ? htmlspecialchars($news->image) : '').'"></td></tr>';
    print '<tr><td>Contenu</td><td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $editor = new DolEditor('content', $news ? $news->content : '', '', 300, 'dolibarr_notes', '', false, true, true, ROWS_6, '90%');
    $editor->Create();
    print '</td></tr>';
    print '<tr><td>Statut</td><td><select name="status"><option value="1"'.($news && $news->status == 1 ? ' selected' : '').'>Publié</option><option value="0"'.($news && $news->status == 0 ? ' selected' : '').'>Brouillon</option></select></td></tr>';
    print '</table>';
    print '<div class="center"><input type="submit" class="button" value="Enregistrer"> <a href="?" class="button">Annuler</a></div></form>';
} else {
    print '<div class="tabsAction"><a class="butAction" href="?action=create">Nouvelle actualité</a></div>';

    $resql = $db->query("SELECT n.*, (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."spacart_news_comment WHERE fk_news = n.rowid) as nb_comments FROM ".MAIN_DB_PREFIX."spacart_news n ORDER BY n.date_creation DESC");
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>Titre</td><td>Extrait</td><td>Commentaires</td><td>Statut</td><td>Date</td><td></td></tr>';
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td>'.htmlspecialchars($obj->title).'</td>';
            print '<td>'.htmlspecialchars(substr($obj->excerpt, 0, 80)).'</td>';
            print '<td>'.$obj->nb_comments.'</td>';
            print '<td>'.($obj->status ? 'Publié' : 'Brouillon').'</td>';
            print '<td>'.dol_print_date(strtotime($obj->date_creation), 'dayhour').'</td>';
            print '<td class="right"><a href="?action=edit&id='.$obj->rowid.'">'.img_edit().'</a> <a href="?action=delete&id='.$obj->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a></td>';
            print '</tr>';
        }
    }
    print '</table>';
}

print dol_get_fiche_end();
llxFooter();
