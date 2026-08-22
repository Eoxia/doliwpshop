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

$arrayofparameters = array(
	'WPSHOP_URL_WORDPRESS'      => array('css'=> 'minwidth500', 'enabled' => 1),
	'WPSHOP_TOKEN'              => array('css'=> 'minwidth500', 'enabled'=> 1),
);

$userapi = new User($db);
$userapi->fetch($conf->global->DOLIWPSHOP_USERAPI_SET,'', '',0,$conf->entity);
$userapi->getrights();
//Rights invoices
empty($userapi->rights->facture->lire) ? $userapi->addrights(11) : 1;
empty($userapi->rights->facture->creer) ? $userapi->addrights(12) : 1;
empty($userapi->rights->facture->paiment) ? $userapi->addrights(16) : 1;
//Rights propals
empty($userapi->rights->propale->lire) ? $userapi->addrights(21) : 1;
empty($userapi->rights->propale->creer) ? $userapi->addrights(22) : 1;
empty($userapi->rights->propale->cloturer) ? $userapi->addrights(26) : 1;
//Rights products
empty($userapi->rights->produit->lire) ? $userapi->addrights(31) : 1;
empty($userapi->rights->produit->creer) ? $userapi->addrights(32) : 1;
//Rights orders
empty($userapi->rights->commande->lire) ? $userapi->addrights(81) : 1;
empty($userapi->rights->commande->creer) ? $userapi->addrights(82) : 1;
//Rights tiers
empty($userapi->rights->societe->lire) ? $userapi->addrights(121) : 1;
empty($userapi->rights->societe->creer) ? $userapi->addrights(122) : 1;
empty($userapi->rights->societe->supprimer) ? $userapi->addrights(125) : 1;
empty($userapi->rights->societe->exporter) ? $userapi->addrights(126) : 1;
empty($userapi->rights->societe->client->voir) ? $userapi->addrights(262) : 1;
empty($userapi->rights->societe->contact->lire) ? $userapi->addrights(281) : 1;
//Rights tags
empty($userapi->rights->categorie->lire) ? $userapi->addrights(241) : 1;
empty($userapi->rights->categorie->creer) ? $userapi->addrights(242) : 1;
//Rights services
empty($userapi->rights->service->lire) ? $userapi->addrights(531) : 1;
empty($userapi->rights->service->creer) ? $userapi->addrights(532) : 1;
//Rights stocks
empty($userapi->rights->stock->lire) ? $userapi->addrights(1001) : 1;
//Rights events
empty($userapi->rights->agenda->myactions->read) ? $userapi->addrights(2401) : 1;
empty($userapi->rights->propale->myactions->create)  ? $userapi->addrights(2402) : 1;
empty($userapi->rights->propale->myactions->delete)  ? $userapi->addrights(2403) : 1;

/*
 * Actions
 */
if ((float) DOL_VERSION >= 6) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}

if (($action == 'update' && !GETPOST("cancel", 'alpha')) || ($action == 'updateedit'))
{
	$WPSHOP_URL_WORDPRESS = GETPOST('WPSHOP_URL_WORDPRESS','alpha');
	$data_archive_on_deletion = GETPOST('data_archive_on_deletion','alpha');

	$link = '<a href="'.$WPSHOP_URL_WORDPRESS.'">'.$langs->trans("PaymentMessage").'</a>';

	dolibarr_set_const($db, "ONLINE_PAYMENT_MESSAGE_OK", $link, 'integer', 0, '', $conf->entity);

	dolibarr_set_const($db, "WPSHOP_DATA_ARCHIVE_ON_DELETION", $data_archive_on_deletion, 'integer', 0, '', $conf->entity);

	if ($action != 'updateedit' && !$error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

// @todo: Statut en status
$connected = WPshopAPI::get('/wp-json/wpshop/v2/statut');

/*
 * View
 */
$page_name = "DoliWPshopSetup";

if (!function_exists('curl_init')) {
	setEventMessages('Attention : l\'extension PHP cURL est nécessaire pour le fonctionnement de DoliWPshop.', null, 'warnings');
}

llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT .'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_doliwpshop@doliwpshop');

// Configuration header
$head = doliwpshopAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "doliwpshop@doliwpshop");

// Setup page goes here
echo $langs->trans("DoliWPshopSetupPage").'<br><br>';

if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?check=true">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach($arrayofparameters as $key => $val) {
		$value = isset($conf->global->$key) ? $conf->global->$key : '';
		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key),$langs->trans($key.'Tooltip'));
		print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css'])?'minwidth200':$val['css']).'" value="' . $value . '"></td></tr>';
	}

	print '<tr><td>'.$langs->trans("DataArchiveOnDeletion").'</td><td>';
	print '<input type="checkbox" id="data_archive_on_deletion" name="data_archive_on_deletion" '.(!empty($conf->global->WPSHOP_DATA_ARCHIVE_ON_DELETION) ? ' checked=""' : '').'';
	print '</td></tr>';


	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	if (! empty($arrayofparameters)) {
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach($arrayofparameters as $key => $val)	{
			$value = isset($conf->global->$key) ? $conf->global->$key : '';
			print '<tr class="oddeven"><td>';
			print $form->textwithpicto($langs->trans($key),$langs->trans($key.'Tooltip'));
			print '</td><td>' . $value . '</td></tr>';
		}

		print '<tr class="oddevent"><td>'.$langs->trans("CommunicationWordPress").'</td><td>';
		
		if ( $connected === true ) {
			echo $langs->trans("ConnectedWordPress");
		} else {
			echo $langs->trans("FailureWordPress");
		}
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("DataArchiveOnDeletion").'</td><td>';
		print '<input type="checkbox" id="data_archive_on_deletion" name="data_archive_on_deletion" '.(!empty($conf->global->WPSHOP_DATA_ARCHIVE_ON_DELETION) ? ' checked=""' : '').' disabled>';
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("ActivateTranslateLink").'</td><td>';
		print '<a href="'.DOL_MAIN_URL_ROOT.'/admin/ihm.php?mainmenu=home" target="_blank">'.DOL_MAIN_URL_ROOT.'/admin/ihm.php?mainmenu=home</a>';
		print '</td></tr>';

		print '</table>';

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
		print '</div>';
	}
	else {
		print '<br>'.$langs->trans("NothingToSetup");
	}
}

// Page end
dol_fiche_end();

llxFooter();
$db->close();
