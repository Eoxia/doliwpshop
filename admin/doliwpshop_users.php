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

// Actions
if ($action == "create_api_user" || $action == "apply_rights") {
	$db->begin();
	$error = 0;

	$targetUser = new User($db);
	if ($action == "create_api_user") {
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
	} elseif ($fuserid > 0) {
		$targetUser->fetch($fuserid);
	}

	if (!$error && $targetUser->id > 0) {
		$failed = doliwpshop_apply_required_rights($targetUser);
		if ($failed > 0) {
			setEventMessages($langs->trans("DoliWPshopRightsNotApplied", $failed, $targetUser->login), null, "errors");
			$error++;
		} else {
			setEventMessages($langs->trans("DoliWPshopRightsApplied", $targetUser->login), null, "mesgs");
		}
	}

	if ($error) {
		$db->rollback();
	} else {
		$db->commit();
	}
}

$page_name = "Utilisateurs/droits";
llxHeader("", $langs->trans("DoliWpshopSetup"), "", "", 0, 0, "", doliwpshop_header_css(), "", doliwpshop_body_classes());

$linkback = "<a href=\"" . DOL_URL_ROOT . "/admin/modules.php?restore_lastsearch_values=1\">" . $langs->trans("BackToModuleList") . "</a>";
print load_fiche_titre($langs->trans("DoliWpshopSetup"), $linkback, "title_setup");

$head = doliwpshopAdminPrepareHead();
dol_fiche_head($head, "users", $langs->trans("ModuleDoliWPshopName"), -1, "doliwpshop@doliwpshop");

require_once DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php";
$form = new Form($db);

// Rights of a disabled module cannot be granted: the module must be enabled first.
$disabled_modules = doliwpshop_get_disabled_modules();
if (!empty($disabled_modules)) {
	print info_admin($langs->trans("DoliWPshopRightsDisabledModules", implode(", ", $disabled_modules)), 0, 0, "warning");
}

print "<form method=\"POST\" action=\"" . $_SERVER["PHP_SELF"] . "\">\n";
print "<input type=\"hidden\" name=\"token\" value=\"" . newToken() . "\">\n";

print "<strong>" . $langs->trans("DoliWPshopRightsSelectUser") . "</strong><br>\n";
print $form->select_dolusers($fuserid, "fuserid", 1);
print " <button type=\"submit\" name=\"action\" value=\"apply_rights\" class=\"button\">" . $langs->trans("DoliWPshopRightsApply") . "</button>\n";
print " <button type=\"submit\" name=\"action\" value=\"create_api_user\" class=\"button\">" . $langs->trans("DoliWPshopRightsCreateUser") . "</button>\n";
print "<br>\n";
print "<span class=\"opacitymedium\">" . $langs->trans("DoliWPshopRightsCreateUserHelp") . "</span>\n";
print "</form><br><br>\n";

// Render the rights table
if ($fuserid > 0) {
	$targetUser = new User($db);
	$targetUser->fetch($fuserid);
	$targetUser->getrights();

	$advanced_perms = getDolGlobalString("MAIN_USE_ADVANCED_PERMS");

	print load_fiche_titre($langs->trans("DoliWPshopRightsTitle", $targetUser->login), "", "");

	print "<table class=\"noborder centpercent\">\n";

	foreach (doliwpshop_get_required_rights() as $module => $definition) {
		$enabled = isModEnabled($module);

		print "<tr class=\"liste_titre\">\n";
		print "<td>" . $langs->trans($definition["label"]) . "</td>\n";
		print "<td class=\"center\">" . $langs->trans("Status") . "</td>\n";
		print "</tr>\n";

		foreach ($definition["rights"] as $id => $right) {
			print "<tr class=\"oddeven\">\n";
			print "<td>" . dol_escape_htmltag(doliwpshop_get_right_label($id, $right)) . " <span class=\"opacitymedium\">(" . $id . ")</span></td>\n";
			if (!$enabled) {
				print "<td class=\"center\"><span class=\"badge badge-danger\">" . $langs->trans("DoliWPshopRightsModuleDisabled") . "</span></td>\n";
			} elseif (doliwpshop_user_has_right($targetUser, $module, $right)) {
				print "<td class=\"center\"><span class=\"badge badge-success\">" . $langs->trans("DoliWPshopRightsActive") . "</span></td>\n";
			} elseif ($right["advanced"] && !$advanced_perms) {
				print "<td class=\"center\"><span class=\"badge badge-secondary\">" . $langs->trans("DoliWPshopRightsNotEnforced") . "</span></td>\n";
			} else {
				print "<td class=\"center\"><span class=\"badge badge-warning\">" . $langs->trans("DoliWPshopRightsMissing") . "</span></td>\n";
			}
			print "</tr>\n";
		}
	}
	print "</table>\n";
} else {
	print "<div class=\"opacitymedium\">" . $langs->trans("DoliWPshopRightsNoUser") . "</div>\n";
}

dol_fiche_end();
llxFooter();
$db->close();
