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
$userapi->rights->facture->lire ? 1 : $userapi->addrights(11);
$userapi->rights->facture->creer ? 1 : $userapi->addrights(12);
$userapi->rights->facture->paiment ? 1 : $userapi->addrights(16);
//Rights propals
$userapi->rights->propale->lire ? 1 : $userapi->addrights(21);
$userapi->rights->propale->creer ? 1 : $userapi->addrights(22);
$userapi->rights->propale->cloturer ? 1 : $userapi->addrights(26);
//Rights products
$userapi->rights->produit->lire ? 1 : $userapi->addrights(31);
$userapi->rights->produit->creer ? 1 : $userapi->addrights(32);
//Rights orders
$userapi->rights->commande->lire ? 1 : $userapi->addrights(81);
$userapi->rights->commande->creer ? 1 : $userapi->addrights(82);
//Rights tiers
$userapi->rights->societe->lire ? 1 : $userapi->addrights(121);
$userapi->rights->societe->creer ? 1 : $userapi->addrights(122);
$userapi->rights->societe->supprimer ? 1 : $userapi->addrights(125);
$userapi->rights->societe->exporter ? 1 : $userapi->addrights(126);
$userapi->rights->societe->client->voir ? 1 : $userapi->addrights(262);
$userapi->rights->societe->contact->lire ? 1 : $userapi->addrights(281);
//Rights tags
$userapi->rights->categorie->lire ? 1 : $userapi->addrights(241);
$userapi->rights->categorie->creer ? 1 : $userapi->addrights(242);
//Rights services
$userapi->rights->service->lire ? 1 : $userapi->addrights(531);
$userapi->rights->service->creer ? 1 : $userapi->addrights(532);
//Rights stocks
$userapi->rights->stock->lire ? 1 : $userapi->addrights(1001);
//Rights events
$userapi->rights->agenda->myactions->read ? 1 : $userapi->addrights(2401);
$userapi->rights->propale->myactions->create  ? 1 : $userapi->addrights(2402);
$userapi->rights->propale->myactions->delete  ? 1 : $userapi->addrights(2403);

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
		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key),$langs->trans($key.'Tooltip'));
		print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css'])?'minwidth200':$val['css']).'" value="' . $conf->global->$key . '"></td></tr>';
	}

	print '<tr><td>'.$langs->trans("DataArchiveOnDeletion").'</td><td>';
	print '<input type="checkbox" id="data_archive_on_deletion" name="data_archive_on_deletion" '.($conf->global->WPSHOP_DATA_ARCHIVE_ON_DELETION ? ' checked=""' : '').'';
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
			print '<tr class="oddeven"><td>';
			print $form->textwithpicto($langs->trans($key),$langs->trans($key.'Tooltip'));
			print '</td><td>' . $conf->global->$key . '</td></tr>';
		}

		print '<tr class="oddevent"><td>'.$langs->trans("CommunicationWordPress").'</td><td>';
		
		if ( $connected === true ) {
			echo $langs->trans("ConnectedWordPress");
		} else {
			echo $langs->trans("FailureWordPress");
		}
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("DataArchiveOnDeletion").'</td><td>';
		print '<input type="checkbox" id="data_archive_on_deletion" name="data_archive_on_deletion" '.($conf->global->WPSHOP_DATA_ARCHIVE_ON_DELETION ? ' checked=""' : '').' disabled>';
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
