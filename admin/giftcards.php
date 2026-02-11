<?php
/**
 * SpaCart Admin - Gift cards management
 */
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dirname(__DIR__).'/lib/spacart.lib.php';

$langs->loadLangs(array('admin', 'spacart@spacart'));
if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

if ($action === 'add') {
    $code = strtoupper(trim(GETPOST('code', 'alphanohtml')));
    if (empty($code)) $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
    $initialBalance = (float) GETPOST('initial_balance', 'alpha');
    $expiresAt = GETPOST('expires_at', 'alpha');
    $recipientEmail = GETPOST('recipient_email', 'alphanohtml');
    $recipientName = GETPOST('recipient_name', 'alphanohtml');
    $message = GETPOST('message', 'alphanohtml');

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_giftcard (code, initial_balance, current_balance, recipient_email, recipient_name, message, expires_at, active, date_creation)";
    $sql .= " VALUES ('".$db->escape($code)."', ".$initialBalance.", ".$initialBalance.", '".$db->escape($recipientEmail)."', '".$db->escape($recipientName)."'";
    $sql .= ", '".$db->escape($message)."'";
    $sql .= ", ".($expiresAt ? "'".$db->escape($expiresAt)."'" : "NULL");
    $sql .= ", 1, NOW())";
    $db->query($sql);
    setEventMessages('Carte cadeau créée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

if ($action === 'toggle' && $id > 0) {
    $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_giftcard SET active = IF(active=1, 0, 1), tms = NOW() WHERE rowid = ".(int) $id);
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

if ($action === 'delete' && $id > 0) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_giftcard WHERE rowid = ".(int) $id);
    setEventMessages('Carte cadeau supprimée', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

llxHeader('', 'SpaCart - Cartes cadeaux');
$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'giftcards', 'SpaCart', -1, 'spacart@spacart');

if ($action === 'create') {
    print '<form method="POST"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="add">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td colspan="2">Nouvelle carte cadeau</td></tr>';
    print '<tr><td>Code (vide = auto)</td><td><input type="text" name="code" class="flat" placeholder="auto-généré"></td></tr>';
    print '<tr><td>Montant initial</td><td><input type="text" name="initial_balance" class="flat" value="50"></td></tr>';
    print '<tr><td>Email destinataire</td><td><input type="email" name="recipient_email" class="flat"></td></tr>';
    print '<tr><td>Nom destinataire</td><td><input type="text" name="recipient_name" class="flat"></td></tr>';
    print '<tr><td>Message</td><td><textarea name="message" class="flat" rows="3" style="width:80%"></textarea></td></tr>';
    print '<tr><td>Date d\'expiration</td><td><input type="date" name="expires_at" class="flat"></td></tr>';
    print '</table>';
    print '<div class="center"><input type="submit" class="button" value="Créer"> <a href="?" class="button">Annuler</a></div></form>';
} else {
    print '<div class="tabsAction"><a class="butAction" href="?action=create">Nouvelle carte cadeau</a></div>';

    $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."spacart_giftcard ORDER BY date_creation DESC");
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>Code</td><td>Solde initial</td><td>Solde actuel</td><td>Destinataire</td><td>Expiration</td><td>Active</td><td></td></tr>';
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $pct = $obj->initial_balance > 0 ? round($obj->current_balance / $obj->initial_balance * 100) : 0;
            print '<tr class="oddeven">';
            print '<td><code>'.$obj->code.'</code></td>';
            print '<td>'.spacartFormatPrice($obj->initial_balance).'</td>';
            print '<td>'.spacartFormatPrice($obj->current_balance).' <small>('.$pct.'%)</small></td>';
            print '<td>'.htmlspecialchars($obj->recipient_name).($obj->recipient_email ? ' &lt;'.$obj->recipient_email.'&gt;' : '').'</td>';
            print '<td>'.($obj->expires_at ? substr($obj->expires_at, 0, 10) : 'Jamais').'</td>';
            print '<td>'.($obj->active ? '<span class="badge badge-status4">Oui</span>' : '<span class="badge badge-status8">Non</span>').'</td>';
            print '<td class="right">';
            print '<a href="?action=toggle&id='.$obj->rowid.'&token='.newToken().'">'.($obj->active ? 'Désactiver' : 'Activer').'</a> ';
            print '<a href="?action=delete&id='.$obj->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a>';
            print '</td></tr>';
        }
    }
    print '</table>';
}

print dol_get_fiche_end();
llxFooter();
