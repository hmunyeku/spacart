<?php
/**
 * SpaCart Admin - Testimonials management
 */
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dirname(__DIR__).'/lib/spacart.lib.php';

$langs->loadLangs(array('admin', 'spacart@spacart'));
if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

if ($action === 'add' || $action === 'update') {
    $customerName = GETPOST('customer_name', 'alphanohtml');
    $content = GETPOST('content', 'alphanohtml');
    $rating = GETPOST('rating', 'int');
    $photo = GETPOST('photo', 'alphanohtml');
    $active = GETPOST('active', 'int');
    $position = GETPOST('position', 'int');

    if ($action === 'update' && $id > 0) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_testimonial SET customer_name = '".$db->escape($customerName)."', content = '".$db->escape($content)."'";
        $sql .= ", rating = ".(int) $rating.", photo = '".$db->escape($photo)."', active = ".(int) $active.", position = ".(int) $position;
        $sql .= ", tms = NOW() WHERE rowid = ".(int) $id;
        $db->query($sql);
        setEventMessages('Témoignage mis à jour', null, 'mesgs');
    } else {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_testimonial (customer_name, content, rating, photo, active, position, date_creation, tms)";
        $sql .= " VALUES ('".$db->escape($customerName)."', '".$db->escape($content)."', ".(int) $rating.", '".$db->escape($photo)."', ".(int) $active.", ".(int) $position.", NOW(), NOW())";
        $db->query($sql);
        setEventMessages('Témoignage créé', null, 'mesgs');
    }
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

if ($action === 'delete' && $id > 0) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_testimonial WHERE rowid = ".(int) $id);
    setEventMessages('Témoignage supprimé', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

llxHeader('', 'SpaCart - Témoignages');
$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'testimonials', 'SpaCart', -1, 'spacart@spacart');

if ($action === 'create' || $action === 'edit') {
    $testi = null;
    if ($action === 'edit' && $id > 0) {
        $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_testimonial WHERE rowid = ".(int) $id);
        $testi = $db->fetch_object($res);
    }

    print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="'.($testi ? 'update' : 'add').'">';
    if ($testi) print '<input type="hidden" name="id" value="'.$testi->rowid.'">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.($testi ? 'Modifier' : 'Nouveau').' témoignage</td></tr>';
    print '<tr><td>Nom du client</td><td><input type="text" name="customer_name" class="flat minwidth200" value="'.($testi ? htmlspecialchars($testi->customer_name) : '').'"></td></tr>';
    print '<tr><td>Témoignage</td><td><textarea name="content" class="flat" rows="4" style="width:80%">'.($testi ? htmlspecialchars($testi->content) : '').'</textarea></td></tr>';
    print '<tr><td>Note (1-5)</td><td><select name="rating" class="flat">';
    for ($i = 5; $i >= 1; $i--) {
        print '<option value="'.$i.'"'.($testi && $testi->rating == $i ? ' selected' : '').'>'.$i.' '.str_repeat('★', $i).'</option>';
    }
    print '</select></td></tr>';
    print '<tr><td>Photo URL</td><td><input type="text" name="photo" class="flat minwidth300" value="'.($testi ? htmlspecialchars($testi->photo) : '').'"></td></tr>';
    print '<tr><td>Position</td><td><input type="number" name="position" class="flat" value="'.($testi ? $testi->position : 0).'"></td></tr>';
    print '<tr><td>Actif</td><td><input type="checkbox" name="active" value="1"'.(!$testi || $testi->active ? ' checked' : '').'></td></tr>';
    print '</table>';
    print '<div class="center"><input type="submit" class="button" value="Enregistrer"> <a href="?" class="button">Annuler</a></div></form>';
} else {
    print '<div class="tabsAction"><a class="butAction" href="?action=create">Nouveau témoignage</a></div>';

    $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_testimonial ORDER BY position, date_creation DESC");
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>Client</td><td>Témoignage</td><td>Note</td><td>Actif</td><td></td></tr>';
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td>'.htmlspecialchars($obj->customer_name).'</td>';
            print '<td>'.htmlspecialchars(substr($obj->content, 0, 100)).'</td>';
            print '<td>'.str_repeat('★', $obj->rating).str_repeat('☆', 5 - $obj->rating).'</td>';
            print '<td>'.($obj->active ? 'Oui' : 'Non').'</td>';
            print '<td class="right"><a href="?action=edit&id='.$obj->rowid.'">'.img_edit().'</a> <a href="?action=delete&id='.$obj->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a></td>';
            print '</tr>';
        }
    }
    print '</table>';
}

print dol_get_fiche_end();
llxFooter();
