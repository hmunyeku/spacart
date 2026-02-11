<?php
/**
 * SpaCart Admin - Reviews moderation
 */
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dirname(__DIR__).'/lib/spacart.lib.php';

$langs->loadLangs(array('admin', 'spacart@spacart'));
if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

if ($action === 'approve' && $id > 0) {
    $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_review SET status = 1 WHERE rowid = ".(int) $id);
    setEventMessages('Avis approuvé', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
if ($action === 'reject' && $id > 0) {
    $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_review SET status = 2 WHERE rowid = ".(int) $id);
    setEventMessages('Avis rejeté', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}
if ($action === 'delete' && $id > 0) {
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_review WHERE rowid = ".(int) $id);
    setEventMessages('Avis supprimé', null, 'mesgs');
    header('Location: '.$_SERVER['PHP_SELF']); exit;
}

llxHeader('', 'SpaCart - Avis');
$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'reviews', 'SpaCart', -1, 'spacart@spacart');

$statusFilter = GETPOST('status_filter', 'int');
$where = '';
if ($statusFilter !== '') {
    $where = " AND r.status = ".(int) $statusFilter;
}

$sql = "SELECT r.*, p.label as product_label FROM ".MAIN_DB_PREFIX."spacart_review r";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = r.fk_product";
$sql .= " WHERE 1=1".$where;
$sql .= " ORDER BY r.date_creation DESC";
$resql = $db->query($sql);

print '<div style="margin-bottom:10px;">Filtrer : <a href="?">Tous</a> | <a href="?status_filter=0">En attente</a> | <a href="?status_filter=1">Approuvés</a> | <a href="?status_filter=2">Rejetés</a></div>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>Produit</td><td>Auteur</td><td>Note</td><td>Commentaire</td><td>Statut</td><td>Date</td><td></td></tr>';
if ($resql) {
    while ($r = $db->fetch_object($resql)) {
        $statusLabels = array(0 => '<span class="badge badge-status1">En attente</span>', 1 => '<span class="badge badge-status4">Approuvé</span>', 2 => '<span class="badge badge-status8">Rejeté</span>');
        print '<tr class="oddeven">';
        print '<td>'.htmlspecialchars($r->product_label).'</td>';
        print '<td>'.htmlspecialchars($r->customer_name).'</td>';
        print '<td>'.str_repeat('★', $r->rating).str_repeat('☆', 5 - $r->rating).'</td>';
        print '<td>'.htmlspecialchars(substr($r->comment, 0, 100)).'</td>';
        print '<td>'.($statusLabels[$r->status] ?? $r->status).'</td>';
        print '<td>'.dol_print_date(strtotime($r->date_creation), 'dayhour').'</td>';
        print '<td class="right nowraponall">';
        if ($r->status == 0) {
            print '<a href="?action=approve&id='.$r->rowid.'&token='.newToken().'" class="butAction butActionSmall">Approuver</a> ';
            print '<a href="?action=reject&id='.$r->rowid.'&token='.newToken().'" class="butActionDelete butActionSmall">Rejeter</a> ';
        }
        print '<a href="?action=delete&id='.$r->rowid.'&token='.newToken().'" onclick="return confirm(\'Confirmer ?\');">'.img_delete().'</a>';
        print '</td></tr>';
    }
}
print '</table>';

print dol_get_fiche_end();
llxFooter();
