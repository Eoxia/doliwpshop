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

$page_name = "Utilisateurs/droits";
llxHeader("", $langs->trans("DoliWpshopSetup"));

$linkback = "<a href=\"" . DOL_URL_ROOT . "/admin/modules.php?restore_lastsearch_values=1\">" . $langs->trans("BackToModuleList") . "</a>";
print load_fiche_titre($langs->trans("DoliWpshopSetup"), $linkback, "title_setup");

$head = doliwpshopAdminPrepareHead();
dol_fiche_head($head, "users", $langs->trans("ModuleDoliWPshopName"), -1, "doliwpshop@doliwpshop");

print "<span class=\"opacitymedium\">" . "Page de configuration des utilisateurs et droits du module DoliWPshop" . "</span><br><br>\n";

print "<form method=\"POST\" action=\"" . $_SERVER["PHP_SELF"] . "\">\n";
print "<input type=\"hidden\" name=\"token\" value=\"" . newToken() . "\">\n";
print "<input type=\"hidden\" name=\"action\" value=\"update\">\n";

print "<table class=\"noborder centpercent\">\n";
print "<tr class=\"liste_titre\">\n";
print "<td>" . $langs->trans("Parameter") . "</td>\n";
print "<td>" . $langs->trans("Value") . "</td>\n";
print "</tr>\n";

print "<tr class=\"oddevent\">\n";
print "<td colspan=\"2\" class=\"center\"><em>Aucun paramètre pour le moment</em></td>\n";
print "</tr>\n";

print "</table>\n";

print "<div class=\"center\">\n";
print "<input type=\"submit\" class=\"button button-save\" value=\"" . $langs->trans("Save") . "\">\n";
print "</div>\n";
print "</form>\n";

dol_fiche_end();
llxFooter();
$db->close();

