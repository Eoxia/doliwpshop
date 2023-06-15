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
$langs->loadLangs(array("admin", "doliwpshop@doliwpshop", "stocks"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

/*
 * Actions
 */

$reg = array();

if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];

	dolibarr_del_const($db, 'STOCK_CALCULATE_ON_BILL', $conf->entity);
	dolibarr_del_const($db, 'STOCK_CALCULATE_ON_VALIDATE_ORDER', $conf->entity);
	dolibarr_del_const($db, 'STOCK_CALCULATE_ON_SHIPMENT', $conf->entity);
	dolibarr_del_const($db, 'STOCK_CALCULATE_ON_SHIPMENT_CLOSE', $conf->entity);

	if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0) {

		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
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

if (isModEnabled('productbatch')) {
	$langs->load("productbatch");
	$disabled = ' disabled';
	print info_admin($langs->trans("WhenProductBatchModuleOnOptionAreForced"));
}
print load_fiche_titre($langs->trans("StockManagementConfiguration"), '', '');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="warehouse">';

// Title rule for stock decrease
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("RuleForStockManagementDecrease")."</td>\n";
print '<td class="right">'.$langs->trans("Status").'</td>'."\n";
print '</tr>'."\n";

$found = 0;

print '<tr class="oddeven">';
print '<td>'.$langs->trans("DeStockOnBill").'</td>';
print '<td class="right">';
if (isModEnabled('facture')) {
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STOCK_CALCULATE_ON_BILL', array(), null, 0, 0, 0, 2, 1);
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STOCK_CALCULATE_ON_BILL", $arrval, $conf->global->STOCK_CALCULATE_ON_BILL);
	}
} else {
	print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module30Name"));
}
print "</td>\n</tr>\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("DeStockOnValidateOrder").'</td>';
print '<td class="right">';
if (isModEnabled('commande')) {
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STOCK_CALCULATE_ON_VALIDATE_ORDER', array(), null, 0, 0, 0, 2, 1);
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STOCK_CALCULATE_ON_VALIDATE_ORDER", $arrval, $conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER);
	}
} else {
	print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module25Name"));
}
print "</td>\n</tr>\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("DeStockOnShipment").'</td>';
print '<td class="right">';
if (isModEnabled("expedition")) {
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STOCK_CALCULATE_ON_SHIPMENT', array(), null, 0, 0, 0, 2, 1);
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STOCK_CALCULATE_ON_SHIPMENT", $arrval, $conf->global->STOCK_CALCULATE_ON_SHIPMENT);
	}
} else {
	print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module80Name"));
}
print "</td>\n</tr>\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("DeStockOnShipmentOnClosing").'</td>';
print '<td class="right">';
if (isModEnabled("expedition")) {
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('STOCK_CALCULATE_ON_SHIPMENT_CLOSE', array(), null, 0, 0, 0, 2, 1);
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("STOCK_CALCULATE_ON_SHIPMENT_CLOSE", $arrval, $conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE);
	}
} else {
	print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module80Name"));
}
print "</td>\n</tr>\n";

print '</table>';

print '<br>';
print '</form>';

// Page end
print dol_get_fiche_end();

llxFooter();
