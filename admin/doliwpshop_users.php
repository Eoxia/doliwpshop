<?php
$res = @include("../../main.inc.php");
if (! $res) {
	$res = @include("../../../main.inc.php");
}
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once "../lib/doliwpshop.lib.php";

$langs->loadLangs(array("admin", "doliwpshop@doliwpshop"));

if (! $user->admin) accessforbidden();

$action = GETPOST("action", "alpha");

if ((float) DOL_VERSION >= 6) {
	include DOL_DOCUMENT_ROOT."/core/actions_setmoduleoptions.inc.php";
}

$fuserid = GETPOST("fuserid", "int");

$required_rights = array(
	"doliwpshop" => array(8000000, 8000001, 8000002),
	"produit" => array(31, 32, 34),
	"categorie" => array(241, 242, 243),
	"societe" => array(121, 122, 125, 281, 282, 283)
);

// Actions
if ($action == "create_api_user" || $action == "apply_rights") {
	$db->begin();
	$error = 0;
	
	if ($action == "create_api_user") {
		$targetUser = new User($db);
		$targetUser->fetch("", "wpshopapi");
		if ($targetUser->id <= 0) {
			$targetUser->login = "wpshopapi";
			$targetUser->lastname = "API";
			$targetUser->firstname = "WPShop";
			$targetUser->statut = 1;
			$targetUser->entity = $conf->entity;
			$res = $targetUser->create($user);
			if ($res < 0) {
				setEventMessages($targetUser->error, $targetUser->errors, "errors");
				$error++;
			}
		}
		$fuserid = $targetUser->id;
	} else {
		$targetUser = new User($db);
		$targetUser->fetch($fuserid);
	}
	
	if (!$error && $targetUser->id > 0) {
		foreach ($required_rights as $module => $rights) {
			foreach ($rights as $rid) {
				$targetUser->addrights($rid);
			}
		}
		setEventMessages("Droits appliqués avec succès à l'utilisateur " . $targetUser->login, null, "mesgs");
	}
	$db->commit();
}

$page_name = "Utilisateurs/droits";
llxHeader("", $langs->trans("DoliWpshopSetup"));

$linkback = "<a href=\"" . DOL_URL_ROOT . "/admin/modules.php?restore_lastsearch_values=1\">" . $langs->trans("BackToModuleList") . "</a>";
print load_fiche_titre($langs->trans("DoliWpshopSetup"), $linkback, "title_setup");

$head = doliwpshopAdminPrepareHead();
dol_fiche_head($head, "users", $langs->trans("ModuleDoliWPshopName"), -1, "doliwpshop@doliwpshop");

require_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";
$form = new Form($db);

print "<form method=\"POST\" action=\"" . $_SERVER["PHP_SELF"] . "\">\n";
print "<input type=\"hidden\" name=\"token\" value=\"" . newToken() . "\">\n";

print "<strong>sélectionner un utilisateur existant pour lui appliquer les droits requis :</strong><br>\n";
print $form->select_dolusers($fuserid, "fuserid", 1);
print " <button type=\"submit\" name=\"action\" value=\"apply_rights\" class=\"button\">APPLIQUER LES DROITS</button>\n";
print " <button type=\"submit\" name=\"action\" value=\"create_api_user\" class=\"button\">CRÉER WPSHOPAPI</button>\n";
print "<br>\n";
print "<span class=\"opacitymedium\">Création rapide : Vous pouvez créer automatiquement un utilisateur système dédié à l'API (login: <code>wpshopapi</code>) qui recevra tous les droits nécessaires.</span>\n";
print "</form><br><br>\n";

// Render the rights table
if ($fuserid > 0) {
	$targetUser = new User($db);
	$targetUser->fetch($fuserid);
	
	print load_fiche_titre("Droits requis pour DoliWPshop (Utilisateur : " . $targetUser->login . ")", "", "");
	
	print "<table class=\"noborder centpercent\">\n";
	
	foreach ($required_rights as $module => $rights) {
		print "<tr class=\"liste_titre\">\n";
		print "<td>" . ucfirst($module) . "</td>\n";
		print "<td class=\"center\">État</td>\n";
		print "</tr>\n";
		
		foreach ($rights as $rid) {
			$sql = "SELECT id, libelle FROM " . MAIN_DB_PREFIX . "rights_def WHERE id = " . $rid;
			$res = $db->query($sql);
			if ($res) {
				$obj = $db->fetch_object($res);
				if ($obj) {
					$sql2 = "SELECT fk_id FROM " . MAIN_DB_PREFIX . "user_rights WHERE fk_user = " . $targetUser->id . " AND fk_id = " . $rid;
					$res2 = $db->query($sql2);
					$has_right = ($db->num_rows($res2) > 0);
					
					print "<tr class=\"oddevent\">\n";
					print "<td>" . $langs->trans($obj->libelle) . "</td>\n";
					if ($has_right) {
						print "<td class=\"center\"><span class=\"badge badge-success\">Actif</span></td>\n";
					} else {
						print "<td class=\"center\"><span class=\"badge badge-warning\">Manquant</span></td>\n";
					}
					print "</tr>\n";
				}
			}
		}
	}
	print "</table>\n";
} else {
	print "<div class=\"opacitymedium\">Sélectionnez un utilisateur et cliquez sur 'Appliquer les droits' pour voir et attribuer ses droits.</div>\n";
}

dol_fiche_end();
llxFooter();
$db->close();

