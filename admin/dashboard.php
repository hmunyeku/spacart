<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       admin/dashboard.php
 * \ingroup    spacart
 * \brief      SpaCart dashboard - overview of online shop activity
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dirname(__DIR__).'/lib/spacart.lib.php';

if (!$user->admin) accessforbidden();

$langs->loadLangs(array("spacart@spacart"));

/*
 * View
 */

llxHeader('', $langs->trans("Dashboard").' - SpaCart');

$head = spacartAdminPrepareHead();
print dol_get_fiche_head($head, 'dashboard', 'SpaCart', -1, 'spacart@spacart');

// Stats cards
print '<div class="fichecenter">';
print '<div class="fichethirdleft">';

// Total orders from spacart
$sql = "SELECT COUNT(rowid) as nb, COALESCE(SUM(total_ttc), 0) as total";
$sql .= " FROM ".MAIN_DB_PREFIX."commande";
$sql .= " WHERE module_source = 'spacart'";
$sql .= " AND entity IN (".getEntity('commande').")";
$resql = $db->query($sql);
$obj = $db->fetch_object($resql);
$totalOrders = $obj->nb;
$totalRevenue = $obj->total;

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("TotalOrders").'</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("TotalOrders").'</td><td class="right"><strong>'.$totalOrders.'</strong></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("TotalRevenue").'</td><td class="right"><strong>'.price($totalRevenue).' '.getDolGlobalString('SPACART_CURRENCY_SYMBOL', '€').'</strong></td></tr>';
print '</table>';
print '<br>';

// Total customers
$sql2 = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."spacart_customer WHERE entity = ".$conf->entity;
$resql2 = $db->query($sql2);
if ($resql2) {
	$obj2 = $db->fetch_object($resql2);
	$totalCustomers = $obj2 ? $obj2->nb : 0;
} else {
	$totalCustomers = 0;
}

// Active carts
$sql3 = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."spacart_cart WHERE status = 0 AND entity = ".$conf->entity;
$resql3 = $db->query($sql3);
if ($resql3) {
	$obj3 = $db->fetch_object($resql3);
	$activeCarts = $obj3 ? $obj3->nb : 0;
} else {
	$activeCarts = 0;
}

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">Clients & Paniers</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("TotalCustomers").'</td><td class="right"><strong>'.$totalCustomers.'</strong></td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("AbandonedCarts").'</td><td class="right"><strong>'.$activeCarts.'</strong></td></tr>';
print '</table>';

print '</div>';

print '<div class="fichetwothirdright">';

// Recent orders
$sql4 = "SELECT c.rowid, c.ref, c.date_commande, c.total_ttc, c.fk_statut, s.nom as customer_name";
$sql4 .= " FROM ".MAIN_DB_PREFIX."commande as c";
$sql4 .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
$sql4 .= " WHERE c.module_source = 'spacart'";
$sql4 .= " AND c.entity IN (".getEntity('commande').")";
$sql4 .= " ORDER BY c.date_creation DESC LIMIT 20";

$resql4 = $db->query($sql4);

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Ref").'</td>';
print '<td>'.$langs->trans("Customer").'</td>';
print '<td class="center">'.$langs->trans("Date").'</td>';
print '<td class="right">'.$langs->trans("Total").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>';

if ($resql4) {
	$num = $db->num_rows($resql4);
	if ($num > 0) {
		while ($obj4 = $db->fetch_object($resql4)) {
			print '<tr class="oddeven">';
			print '<td><a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$obj4->rowid.'">'.$obj4->ref.'</a></td>';
			print '<td>'.dol_escape_htmltag($obj4->customer_name).'</td>';
			print '<td class="center">'.dol_print_date($db->jdate($obj4->date_commande), 'day').'</td>';
			print '<td class="right">'.price($obj4->total_ttc).'</td>';
			print '<td class="center">'.$obj4->fk_statut.'</td>';
			print '</tr>';
		}
	} else {
		print '<tr class="oddeven"><td colspan="5" class="opacitymedium">Aucune commande en ligne pour le moment.</td></tr>';
	}
}

print '</table>';

print '</div>';
print '</div>';

// Shop link
print '<br><div class="center">';
$shopUrl = DOL_URL_ROOT.'/custom/spacart/public/';
print '<a class="butAction" href="'.$shopUrl.'" target="_blank">'.$langs->trans("ViewShop").'</a>';
print '</div>';

print dol_get_fiche_end();
llxFooter();
$db->close();
