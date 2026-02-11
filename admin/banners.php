<?php
/**
 * SpaCart Admin - Banners management
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
    $subtitle = GETPOST('subtitle', 'alphanohtml');
    $image = GETPOST('image', 'alphanohtml');
    $link = GETPOST('link', 'alphanohtml');
    $position_type = GETPOST('position_type', 'alphanohtml');
    $positionNum = GETPOST('position', 'int');
    $active = GETPOST('active', 'int');

    if ($action === 'update' && $id > 0) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_banner SET title = '".$db->escape($title)."', subtitle = '".$db->escape($subtitle)."'";
        $sql .= ", image = '".$db->escape($image)."', link = '".$db->escape($link)."', position_type = '".$db->escape($position_type)."'";
        $sql .= ", position = ".(int) $positionNum.", active = ".(int) $active.", tms = NOW() WHERE rowid = ".(int) $id;
        $db->query($sql);
        setEventMessages('Bannière mise à jour', null, 'mesgs');
    } else {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_banner (title, subtitle, image, link, position_type, position, active, date_creation, tms)";
        $sql .= " VALUES ('".$db->escape($title)."', '".$db->escape($subtitle)."', '".$db->escape($image)."', '".$db->escape($link)."'";
        $sql .= ", '".$db->escape($position_type)."', ".(int) $positionNum.", ".(int) $active.", NOW(), NOW())";
        $db->query($sql);
        setEventMessages('Bannière créée', null, 'mesgs');
    }
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

if ($action === 'delete' && $id > 0) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_banner WHERE rowid = ".(int) $id);
    setEventMessages('Bannière supprimée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

llxHeader('', 'SpaCart - Bannières');
$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'banners', 'SpaCart', -1, 'spacart@spacart');

if ($action === 'create' || $action === 'edit') {
    $banner = null;
    if ($action === 'edit' && $id > 0) {
        $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_banner WHERE rowid = ".(int) $id);
        $banner = $db->fetch_object($res);
    }

    print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="'.($banner ? 'update' : 'add').'">';
    if ($banner) print '<input type="hidden" name="id" value="'.$banner->rowid.'">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.($banner ? 'Modifier' : 'Nouvelle').' bannière</td></tr>';
    print '<tr><td>Titre</td><td><input type="text" name="title" class="flat minwidth300" value="'.($banner ? htmlspecialchars($banner->title) : '').'"></td></tr>';
    print '<tr><td>Sous-titre</td><td><input type="text" name="subtitle" class="flat minwidth300" value="'.($banner ? htmlspecialchars($banner->subtitle) : '').'"></td></tr>';
    print '<tr><td>Image URL</td><td><input type="text" name="image" class="flat minwidth400" value="'.($banner ? htmlspecialchars($banner->image) : '').'"></td></tr>';
    if ($banner && $banner->image) {
        print '<tr><td></td><td><img src="'.htmlspecialchars($banner->image).'" style="max-width:300px;max-height:100px;"></td></tr>';
    }
    print '<tr><td>Lien</td><td><input type="text" name="link" class="flat minwidth300" value="'.($banner ? htmlspecialchars($banner->link) : '').'" placeholder="#/category/1"></td></tr>';
    print '<tr><td>Emplacement</td><td><select name="position_type" class="flat">';
    $posTypes = array('homepage' => 'Page d\'accueil', 'category' => 'Page catégorie', 'sidebar' => 'Barre latérale');
    foreach ($posTypes as $k => $v) {
        print '<option value="'.$k.'"'.($banner && $banner->position_type === $k ? ' selected' : '').'>'.$v.'</option>';
    }
    print '</select></td></tr>';
    print '<tr><td>Position (ordre)</td><td><input type="number" name="position" class="flat" value="'.($banner ? $banner->position : 0).'"></td></tr>';
    print '<tr><td>Active</td><td><input type="checkbox" name="active" value="1"'.(!$banner || $banner->active ? ' checked' : '').'></td></tr>';
    print '</table>';
    print '<div class="center"><input type="submit" class="button" value="Enregistrer"> <a href="?" class="button">Annuler</a></div></form>';
} else {
    print '<div class="tabsAction"><a class="butAction" href="?action=create">Nouvelle bannière</a></div>';

    $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_banner ORDER BY position_type, position");
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>Aperçu</td><td>Titre</td><td>Emplacement</td><td>Position</td><td>Active</td><td></td></tr>';
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td>'.($obj->image ? '<img src="'.htmlspecialchars($obj->image).'" style="max-width:120px;max-height:50px;">' : '-').'</td>';
            print '<td>'.htmlspecialchars($obj->title).'<br><small>'.htmlspecialchars($obj->subtitle).'</small></td>';
            print '<td>'.($posTypes[$obj->position_type] ?? $obj->position_type).'</td>';
            print '<td>'.$obj->position.'</td>';
            print '<td>'.($obj->active ? 'Oui' : 'Non').'</td>';
            print '<td class="right"><a href="?action=edit&id='.$obj->rowid.'">'.img_edit().'</a> <a href="?action=delete&id='.$obj->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a></td>';
            print '</tr>';
        }
    }
    print '</table>';
}

print dol_get_fiche_end();
llxFooter();
