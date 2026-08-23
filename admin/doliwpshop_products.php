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

// Translations
$langs->loadLangs(array("admin", "doliwpshop@doliwpshop"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action     = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$value      = GETPOST('value', 'alpha');

if ((float) DOL_VERSION >= 6) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}

if ($action == 'update' && !GETPOST("cancel", 'alpha'))
{
	$auto_sync = GETPOST('WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES', 'alpha');
	dolibarr_set_const($db, "WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES", $auto_sync, 'yesno', 0, '', $conf->entity);
	
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

$page_name = "Produits/services";

if (!function_exists('curl_init')) {
	setEventMessages('Attention : l\'extension PHP cURL est nécessaire pour le fonctionnement de DoliWPshop.', null, 'warnings');
}

llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT .'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_doliwpshop@doliwpshop');

// Configuration header
$head = doliwpshopAdminPrepareHead();
dol_fiche_head($head, 'products', '', -1, "doliwpshop@doliwpshop");

// Setup page goes here
echo 'Configuration de la synchronisation des produits et services.<br><br>';

if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">Paramètre</td><td>Valeur</td></tr>';

	print '<tr><td>Ajouter les Tags/catégories du produit sur WPshop automatiquement</td><td>';
	print '<input type="checkbox" id="WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES" name="WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES" value="1" '.(getDolGlobalInt('WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES') ? ' checked="checked"' : '').'>';
	print '</td></tr>';

	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">Paramètre</td><td>Valeur</td></tr>';

	print '<tr class="oddevent"><td>Ajouter les Tags/catégories du produit sur WPshop automatiquement</td><td>';
	print getDolGlobalInt('WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES') ? $langs->trans("Yes") : $langs->trans("No");
	print '</td></tr>';

	print '</table>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
	print '</div>';
}

// Page end
dol_fiche_end();

llxFooter();
$db->close();
