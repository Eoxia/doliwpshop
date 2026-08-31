<?php
/* Copyright (C) 2019-2026 Eoxia <dev@eoxia.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->loadLangs(array('doliwpshop@doliwpshop', 'products', 'categories'));

// Security check
if (empty($user->rights->doliwpshop->read)) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield = "p.ref";
if (! $sortorder) $sortorder = "ASC";

$mainmenu = 'doliwpshop';
$leftmenu = 'doliwpshop_products_list';

llxHeader('', $langs->trans('Products'));

$title = $langs->trans('Products');

$sql = "SELECT p.rowid, p.ref, p.label, p.price, p.price_base_type, p.tva_tx, p.tosell,";
$sql.= " pe._wps_status, pe._wps_id,";
$sql.= " (SELECT GROUP_CONCAT(c.label SEPARATOR ', ') FROM ".MAIN_DB_PREFIX."categorie_product as cp JOIN ".MAIN_DB_PREFIX."categorie as c ON c.rowid = cp.fk_categorie WHERE cp.fk_product = p.rowid) as categories";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON p.rowid = pe.fk_object";
$sql.= " WHERE pe._wps_id IS NOT NULL AND pe._wps_id != ''";

$sql.= $db->order($sortfield, $sortorder);
$sql.= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param = '';
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $num, 'title_products', 0, '', '', $limit);
	
	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">'."\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Label', $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Categories', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('SellingPrice', $_SERVER["PHP_SELF"], 'p.price', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('WPshopStatus', $_SERVER["PHP_SELF"], 'pe._wps_status', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('WPshop ID', $_SERVER["PHP_SELF"], 'pe._wps_id', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Status', $_SERVER["PHP_SELF"], 'p.tosell', '', $param, '', $sortfield, $sortorder);
	print '</tr>'."\n";

	$productstatic = new Product($db);

	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);
		
		$productstatic->id = $obj->rowid;
		$productstatic->ref = $obj->ref;
		$productstatic->label = $obj->label;
		$productstatic->status = $obj->tosell;

		print '<tr class="oddeven">';
		print '<td>'.$productstatic->getNomUrl(1).'</td>';
		print '<td>'.dol_escape_htmltag($obj->label).'</td>';
		print '<td>'.dol_escape_htmltag($obj->categories).'</td>';
		print '<td>'.price($obj->price, 1, $langs, 1, -1, -1, $conf->currency).'</td>';
		print '<td>'.dol_escape_htmltag($obj->_wps_status).'</td>';
		print '<td>'.dol_escape_htmltag($obj->_wps_id).'</td>';
		print '<td>'.$productstatic->LibStatut($obj->tosell, 5).'</td>';
		print '</tr>'."\n";
		$i++;
	}
	print '</table>';
	print '</div>';
	
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
