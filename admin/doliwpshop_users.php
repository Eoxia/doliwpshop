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

print "<div class=\"info\">\n";
print "<strong>" . $langs->trans("Droits requis pour DoliWPshop") . "</strong><br><br>\n";
print "Pour que le module puisse synchroniser correctement les données entre Dolibarr et WordPress, les utilisateurs concernés (ou l'utilisateur API/Cron) doivent disposer des permissions natives suivantes dans Dolibarr :<br>\n";
print "<ul>\n";
print "<li><strong>DoliWPshop</strong> : Lire, Créer/Modifier, Supprimer</li>\n";
print "<li><strong>Produits/Services</strong> : Lire, Créer/Modifier</li>\n";
print "<li><strong>Tags/Catégories</strong> : Lire, Créer/Modifier</li>\n";
print "<li><strong>Tiers (Clients)</strong> : Lire, Créer/Modifier</li>\n";
print "</ul>\n";
print "<em>Note : Assurez-vous également que la configuration de l'API REST de WordPress autorise l'utilisateur lié à la clé API WPShop à lire et modifier les données (Produits, Catégories, etc.) sur votre boutique.</em>\n";
print "</div>\n";

dol_fiche_end();
llxFooter();
$db->close();

