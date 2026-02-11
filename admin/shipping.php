<?php
/**
 * SpaCart Admin - Shipping zones & methods management
 */
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dirname(__DIR__).'/lib/spacart.lib.php';

$langs->loadLangs(array('admin', 'spacart@spacart'));
if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

// === Actions: Zones ===
if ($action === 'add_zone') {
    $label = GETPOST('label', 'alphanohtml');
    $db->query("INSERT INTO ".MAIN_DB_PREFIX."spacart_shipping_zone (label, active, date_creation) VALUES ('".$db->escape($label)."', 1, NOW())");
    setEventMessages('Zone créée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

// === Actions: Methods ===
if ($action === 'add_method') {
    $label = GETPOST('label', 'alphanohtml');
    $deliveryTime = GETPOST('delivery_time', 'alphanohtml');
    $freeThreshold = GETPOST('free_threshold', 'alpha');
    $position = GETPOST('position', 'int');

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_shipping_method (label, delivery_time, free_threshold, position, active, date_creation)";
    $sql .= " VALUES ('".$db->escape($label)."', '".$db->escape($deliveryTime)."', ".(float) $freeThreshold.", ".(int) $position.", 1, NOW())";
    $db->query($sql);
    setEventMessages('Méthode créée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

// === Actions: Rates ===
if ($action === 'add_rate') {
    $fkMethod = GETPOST('fk_method', 'int');
    $fkZone = GETPOST('fk_zone', 'int');
    $rateType = GETPOST('rate_type', 'alpha');
    $minValue = GETPOST('min_value', 'alpha');
    $maxValue = GETPOST('max_value', 'alpha');
    $price = GETPOST('price', 'alpha');

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_shipping_rate (fk_method, fk_zone, rate_type, min_value, max_value, price, active, date_creation)";
    $sql .= " VALUES (".(int) $fkMethod.", ".(int) $fkZone.", '".$db->escape($rateType)."', ".(float) $minValue.", ".(float) $maxValue.", ".(float) $price.", 1, NOW())";
    $db->query($sql);
    setEventMessages('Tarif créé', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

if ($action === 'delete_zone' && $id) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_shipping_zone WHERE rowid = ".(int) $id);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
if ($action === 'delete_method' && $id) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_shipping_method WHERE rowid = ".(int) $id);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
if ($action === 'delete_rate' && $id) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_shipping_rate WHERE rowid = ".(int) $id);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

// Display
llxHeader('', 'SpaCart - Livraison');
$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'shipping', 'SpaCart', -1, 'spacart@spacart');

// === Zones ===
print '<h3>Zones de livraison</h3>';
print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="add_zone">';
print '<input type="text" name="label" placeholder="Nom de la zone" class="flat"> <input type="submit" class="button" value="Ajouter">';
print '</form>';

$resZones = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_shipping_zone ORDER BY label");
print '<table class="noborder centpercent" style="margin-top:10px;">';
print '<tr class="liste_titre"><td>ID</td><td>Zone</td><td>Défaut</td><td></td></tr>';
if ($resZones) {
    while ($z = $db->fetch_object($resZones)) {
        print '<tr class="oddeven"><td>'.$z->rowid.'</td><td>'.htmlspecialchars($z->label).'</td><td>'.($z->is_default ? 'Oui' : '').'</td>';
        print '<td class="right"><a href="?action=delete_zone&id='.$z->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a></td></tr>';
    }
}
print '</table>';

// === Methods ===
print '<h3 style="margin-top:30px;">Méthodes de livraison</h3>';
print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="add_method">';
print 'Nom: <input type="text" name="label" class="flat"> ';
print 'Délai: <input type="text" name="delivery_time" class="flat" placeholder="2-5 jours"> ';
print 'Gratuit dès: <input type="text" name="free_threshold" class="flat" placeholder="0"> ';
print 'Position: <input type="number" name="position" class="flat" value="10" style="width:60px"> ';
print '<input type="submit" class="button" value="Ajouter">';
print '</form>';

$resMethods = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_shipping_method ORDER BY position");
print '<table class="noborder centpercent" style="margin-top:10px;">';
print '<tr class="liste_titre"><td>ID</td><td>Méthode</td><td>Délai</td><td>Gratuit dès</td><td></td></tr>';
if ($resMethods) {
    while ($m = $db->fetch_object($resMethods)) {
        print '<tr class="oddeven"><td>'.$m->rowid.'</td><td>'.htmlspecialchars($m->label).'</td><td>'.htmlspecialchars($m->delivery_time).'</td>';
        print '<td>'.($m->free_threshold > 0 ? spacartFormatPrice($m->free_threshold) : '-').'</td>';
        print '<td class="right"><a href="?action=delete_method&id='.$m->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a></td></tr>';
    }
}
print '</table>';

// === Rates ===
print '<h3 style="margin-top:30px;">Tarifs de livraison</h3>';

// Get zones and methods for dropdowns
$zones = array();
$resZ2 = $db->query("SELECT rowid, label FROM ".MAIN_DB_PREFIX."spacart_shipping_zone ORDER BY label");
if ($resZ2) { while ($z = $db->fetch_object($resZ2)) $zones[$z->rowid] = $z->label; }
$methods = array();
$resM2 = $db->query("SELECT rowid, label FROM ".MAIN_DB_PREFIX."spacart_shipping_method ORDER BY position");
if ($resM2) { while ($m = $db->fetch_object($resM2)) $methods[$m->rowid] = $m->label; }

print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="add_rate">';
print 'Méthode: <select name="fk_method" class="flat">';
foreach ($methods as $mid => $mlabel) print '<option value="'.$mid.'">'.htmlspecialchars($mlabel).'</option>';
print '</select> ';
print 'Zone: <select name="fk_zone" class="flat">';
foreach ($zones as $zid => $zlabel) print '<option value="'.$zid.'">'.htmlspecialchars($zlabel).'</option>';
print '</select> ';
print 'Type: <select name="rate_type" class="flat"><option value="amount">Montant</option><option value="weight">Poids</option></select> ';
print 'Min: <input type="text" name="min_value" class="flat" style="width:60px" value="0"> ';
print 'Max: <input type="text" name="max_value" class="flat" style="width:60px" value="0"> ';
print 'Prix: <input type="text" name="price" class="flat" style="width:80px"> ';
print '<input type="submit" class="button" value="Ajouter">';
print '</form>';

$resRates = $db->query("SELECT sr.*, sm.label as method_label, sz.label as zone_label FROM ".MAIN_DB_PREFIX."spacart_shipping_rate sr LEFT JOIN ".MAIN_DB_PREFIX."spacart_shipping_method sm ON sm.rowid = sr.fk_method LEFT JOIN ".MAIN_DB_PREFIX."spacart_shipping_zone sz ON sz.rowid = sr.fk_zone ORDER BY sr.fk_method, sr.fk_zone, sr.min_value");
print '<table class="noborder centpercent" style="margin-top:10px;">';
print '<tr class="liste_titre"><td>Méthode</td><td>Zone</td><td>Type</td><td>Min</td><td>Max</td><td>Prix</td><td></td></tr>';
if ($resRates) {
    while ($r = $db->fetch_object($resRates)) {
        print '<tr class="oddeven"><td>'.htmlspecialchars($r->method_label).'</td><td>'.htmlspecialchars($r->zone_label).'</td>';
        print '<td>'.$r->rate_type.'</td><td>'.$r->min_value.'</td><td>'.$r->max_value.'</td>';
        print '<td>'.spacartFormatPrice($r->price).'</td>';
        print '<td class="right"><a href="?action=delete_rate&id='.$r->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a></td></tr>';
    }
}
print '</table>';

print dol_get_fiche_end();
llxFooter();
