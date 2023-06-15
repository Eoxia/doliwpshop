<?php
/* Copyright (C) 2019-2020 Eoxia <dev@eoxia.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/custom/doliwpshop/admin/doliwpshop.php
 * \ingroup doliwpshop
 * \brief   Page setup for DoliWpshop module.
 */

// Load Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include("../../../main.inc.php"); // From "custom" directory
}
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/doliwpshop.lib.php';
require_once '../lib/api_doliwpshop.class.php';

global $conf, $langs, $user, $db;
// Translations
$langs->loadLangs(array("admin", "doliwpshop@doliwpshop"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

/*
 * Actions
 */

if ($action == 'toggleStockManagement') {
	$confValue = GETPOST('value');
	dolibarr_set_const($db, 'DOLIWPSHOP_STOCK_MANAGEMENT', $value, 'chaine', 0, '', $conf->entity);
}

if ($action == 'switchAutoUnStockMethod') {
	$confValue = GETPOST('value');
	dolibarr_set_const($db, 'DOLIWPSHOP_UNSTOCK_ON_ORDER_VALIDATION_OR_INVOICE_CREATION', $confValue, 'chaine', 0, '', $conf->entity);
}

/*
 * View
 */

$title    = $langs->trans("StockManagement");

llxHeader('', $title);

// Subheader
$linkback = '<a href="'.($backtopage?:DOL_URL_ROOT .'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'object_doliwpshop@doliwpshop');

// Configuration header
$head = doliwpshopAdminPrepareHead();
print dol_get_fiche_head($head, 'stock', '', -1, "doliwpshop@doliwpshop");


print load_fiche_titre($langs->trans("StockManagementConfiguration"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center">' . $langs->trans("Status") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>';
print $langs->trans('AutoUnStock');
print "</td><td>";
print $langs->trans('AutoUnStockDescription');
print '</td>';

print '<td class="center">';
print '<a href="'.$_SERVER["PHP_SELF"].'?action=toggleStockManagement&value='. ($conf->global->DOLIWPSHOP_STOCK_MANAGEMENT > 0 ? 0 : 1) .'">';
print img_picto($langs->trans("Enabled"), $conf->global->DOLIWPSHOP_STOCK_MANAGEMENT > 0 ? 'switch_on' : 'switch_off');
print '</a>';
print "</td>";
print '</tr>';

if ($conf->global->DOLIWPSHOP_STOCK_MANAGEMENT == 1) {

	print '<tr class="oddeven"><td>';
	print $langs->trans('UnstockOnOrderValidation');
	print "</td><td>";
	print $langs->trans('UnstockOnOrderValidationDescription');
	print '</td>';

	print '<td class="center">';
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=switchAutoUnStockMethod&value='. ($conf->global->DOLIWPSHOP_UNSTOCK_ON_ORDER_VALIDATION_OR_INVOICE_CREATION > 0 ? 0 : 1) .'">';
	print img_picto($langs->trans("Enabled"), $conf->global->DOLIWPSHOP_UNSTOCK_ON_ORDER_VALIDATION_OR_INVOICE_CREATION > 0 ? 'switch_on' : 'switch_off');
	print '</a>';
	print "</td>";
	print '</tr>';

	print '<tr class="oddeven"><td>';
	print $langs->trans('UnstockOnInvoiceCreation');
	print "</td><td>";
	print $langs->trans('UnstockOnInvoiceCreationDescription');
	print '</td>';

	print '<td class="center">';
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=switchAutoUnStockMethod&value='. ($conf->global->DOLIWPSHOP_UNSTOCK_ON_ORDER_VALIDATION_OR_INVOICE_CREATION > 0 ? 0 : 1) .'">';
	print img_picto($langs->trans("Enabled"), $conf->global->DOLIWPSHOP_UNSTOCK_ON_ORDER_VALIDATION_OR_INVOICE_CREATION > 0 ? 'switch_off' : 'switch_on');
	print '</a>';
	print "</td>";
	print '</tr>';
}


// Page end
print dol_get_fiche_end();

llxFooter();
