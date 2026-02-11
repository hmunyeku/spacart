<?php
/**
 * SpaCart Admin - Coupons management
 */
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dirname(__DIR__).'/lib/spacart.lib.php';

$langs->loadLangs(array('admin', 'spacart@spacart'));
if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

if ($action === 'add' || $action === 'update') {
    $code = strtoupper(trim(GETPOST('code', 'alphanohtml')));
    $type = GETPOST('type', 'alpha');
    $value = GETPOST('value', 'alpha');
    $minOrder = GETPOST('min_order', 'alpha');
    $maxUses = GETPOST('max_uses', 'int');
    $dateStart = GETPOST('date_start', 'alpha');
    $dateEnd = GETPOST('date_end', 'alpha');
    $active = GETPOST('active', 'int');

    if ($action === 'update' && $id > 0) {
        $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_coupon SET code = '".$db->escape($code)."', type = '".$db->escape($type)."', value = ".(float) $value.", min_order = ".(float) $minOrder.", max_uses = ".(int) $maxUses;
        $sql .= ", date_start = ".($dateStart ? "'".$db->escape($dateStart)."'" : "NULL");
        $sql .= ", date_end = ".($dateEnd ? "'".$db->escape($dateEnd)."'" : "NULL");
        $sql .= ", active = ".(int) $active.", tms = NOW() WHERE rowid = ".(int) $id;
        $db->query($sql);
        setEventMessages('Coupon mis à jour', null, 'mesgs');
    } else {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_coupon (code, type, value, min_order, max_uses, current_uses, date_start, date_end, active, date_creation)";
        $sql .= " VALUES ('".$db->escape($code)."', '".$db->escape($type)."', ".(float) $value.", ".(float) $minOrder.", ".(int) $maxUses.", 0";
        $sql .= ", ".($dateStart ? "'".$db->escape($dateStart)."'" : "NULL");
        $sql .= ", ".($dateEnd ? "'".$db->escape($dateEnd)."'" : "NULL");
        $sql .= ", ".(int) $active.", NOW())";
        $db->query($sql);
        setEventMessages('Coupon créé', null, 'mesgs');
    }
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

if ($action === 'delete' && $id > 0) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_coupon WHERE rowid = ".(int) $id);
    setEventMessages('Coupon supprimé', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

llxHeader('', 'SpaCart - Coupons');
$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'coupons', 'SpaCart', -1, 'spacart@spacart');

if ($action === 'create' || $action === 'edit') {
    $coupon = null;
    if ($action === 'edit' && $id > 0) {
        $res = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_coupon WHERE rowid = ".(int) $id);
        $coupon = $db->fetch_object($res);
    }

    print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="'.($coupon ? 'update' : 'add').'">';
    if ($coupon) print '<input type="hidden" name="id" value="'.$coupon->rowid.'">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">'.($coupon ? 'Modifier' : 'Nouveau').' coupon</td></tr>';
    print '<tr><td>Code</td><td><input type="text" name="code" class="flat" value="'.($coupon ? htmlspecialchars($coupon->code) : strtoupper(substr(md5(uniqid()), 0, 8))).'"></td></tr>';
    print '<tr><td>Type</td><td><select name="type" class="flat"><option value="percent"'.($coupon && $coupon->type === 'percent' ? ' selected' : '').'>Pourcentage (%)</option><option value="fixed"'.($coupon && $coupon->type === 'fixed' ? ' selected' : '').'>Montant fixe</option></select></td></tr>';
    print '<tr><td>Valeur</td><td><input type="text" name="value" class="flat" value="'.($coupon ? $coupon->value : '').'"></td></tr>';
    print '<tr><td>Commande minimum</td><td><input type="text" name="min_order" class="flat" value="'.($coupon ? $coupon->min_order : '0').'"></td></tr>';
    print '<tr><td>Utilisations max (0=illimité)</td><td><input type="number" name="max_uses" class="flat" value="'.($coupon ? $coupon->max_uses : '0').'"></td></tr>';
    print '<tr><td>Date début</td><td><input type="date" name="date_start" class="flat" value="'.($coupon && $coupon->date_start ? substr($coupon->date_start, 0, 10) : '').'"></td></tr>';
    print '<tr><td>Date fin</td><td><input type="date" name="date_end" class="flat" value="'.($coupon && $coupon->date_end ? substr($coupon->date_end, 0, 10) : '').'"></td></tr>';
    print '<tr><td>Actif</td><td><input type="checkbox" name="active" value="1"'.(!$coupon || $coupon->active ? ' checked' : '').'></td></tr>';
    print '</table>';
    print '<div class="center"><input type="submit" class="button" value="Enregistrer"></div></form>';
} else {
    print '<div class="tabsAction"><a class="butAction" href="?action=create">Nouveau coupon</a></div>';

    $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_coupon ORDER BY date_creation DESC");
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>Code</td><td>Type</td><td>Valeur</td><td>Min commande</td><td>Utilisé</td><td>Dates</td><td>Actif</td><td></td></tr>';
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td><code>'.$obj->code.'</code></td>';
            print '<td>'.($obj->type === 'percent' ? '%' : 'Fixe').'</td>';
            print '<td>'.$obj->value.($obj->type === 'percent' ? '%' : ' '.getDolGlobalString('MAIN_MONNAIE', 'EUR')).'</td>';
            print '<td>'.($obj->min_order > 0 ? spacartFormatPrice($obj->min_order) : '-').'</td>';
            print '<td>'.$obj->current_uses.($obj->max_uses > 0 ? '/'.$obj->max_uses : '').'</td>';
            print '<td>'.($obj->date_start ? substr($obj->date_start, 0, 10) : '').($obj->date_end ? ' → '.substr($obj->date_end, 0, 10) : '').'</td>';
            print '<td>'.($obj->active ? 'Oui' : 'Non').'</td>';
            print '<td class="right"><a href="?action=edit&id='.$obj->rowid.'">'.img_edit().'</a> <a href="?action=delete&id='.$obj->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a></td>';
            print '</tr>';
        }
    }
    print '</table>';
}

print dol_get_fiche_end();
llxFooter();
