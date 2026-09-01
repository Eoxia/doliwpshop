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
	if (isset($_POST['WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES'])) {
		$auto_sync = GETPOST('WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES', 'alpha');
		dolibarr_set_const($db, "WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES", $auto_sync, 'yesno', 0, '', $conf->entity);
	}
	
	$default_status = GETPOST('WPSHOP_DEFAULT_PRODUCT_STATUS', 'alpha');
	if (!empty($default_status)) {
		dolibarr_set_const($db, "WPSHOP_DEFAULT_PRODUCT_STATUS", $default_status, 'chaine', 0, '', $conf->entity);
		
		// Update extrafield default value
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extra_fields = new ExtraFields($db);
		$extra_fields->updateExtraField('_wps_status', 'WPshopStatus', 'select', 999, '', 'product', 0, 0, '', $default_status, array('options' => array('publish'=> 'publish', 'draft' => 'draft')));
	}

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

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td class="titlefield">Paramètre</td><td class="right">Valeur</td></tr>';

print '<tr class="oddevent"><td>Ajouter les Tags/catégories du produit sur WPshop automatiquement</td><td class="right">';
print ajax_constantonoff('WPSHOP_AUTO_SYNC_PRODUCT_CATEGORIES');
print '</td></tr>';

print '<tr class="oddevent"><td>Statut WPshop par défaut lors de la création d\'un produit</td><td class="right">';
$default_status = !empty($conf->global->WPSHOP_DEFAULT_PRODUCT_STATUS) ? $conf->global->WPSHOP_DEFAULT_PRODUCT_STATUS : 'draft';
print '<select name="WPSHOP_DEFAULT_PRODUCT_STATUS" class="flat">';
print '<option value="publish"'.($default_status == 'publish' ? ' selected="selected"' : '').'>publish</option>';
print '<option value="draft"'.($default_status == 'draft' ? ' selected="selected"' : '').'>draft</option>';
print '</select>';
print '</td></tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// Page end
dol_fiche_end();

llxFooter();
$db->close();
