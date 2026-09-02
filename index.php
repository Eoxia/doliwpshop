<?php
$res = @include("../main.inc.php");
if (! $res) {
	$res = @include("../../main.inc.php");
}
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once "lib/doliwpshop.lib.php";

$langs->loadLangs(array("admin", "doliwpshop@doliwpshop"));

if (! $user->rights->doliwpshop->read) accessforbidden();

$title = "Tableau de bord WPshop";

$mainmenu = "doliwpshop";
$leftmenu = "doliwpshop_products";

llxHeader("", $title);

print load_fiche_titre($title, "", "object_doliwpshop@doliwpshop");

$sql = "SELECT count(p.rowid) as nb FROM " . MAIN_DB_PREFIX . "product as p";
$sql .= " WHERE p.entity IN (" . getEntity("product") . ")";
$res = $db->query($sql);
$obj = $db->fetch_object($res);
$total_products = $obj->nb;

$sql = "SELECT count(p.rowid) as nb FROM " . MAIN_DB_PREFIX . "product as p INNER JOIN " . MAIN_DB_PREFIX . "product_extrafields as pe ON p.rowid = pe.fk_object WHERE pe._wps_id > 0";
$sql .= " AND p.entity IN (" . getEntity("product") . ")";
$res = $db->query($sql);
$obj = $db->fetch_object($res);
$sync_products = $obj->nb;

$percent = ($total_products > 0) ? round(($sync_products / $total_products) * 100, 2) : 0;

print "<div class=\"fichecenter\">";
print "<div class=\"twocolumns\">";
print "<div class=\"firstcolumn\">";

print "<table class=\"noborder centpercent\">";
print "<tr class=\"liste_titre\">";
print "<th colspan=\"2\">Indicateurs Produits/Services</th>";
print "</tr>";
print "<tr class=\"oddevent\">";
print "<td>Nombre total de produits/services</td>";
print "<td class=\"right\">" . $total_products . "</td>";
print "</tr>";
print "<tr class=\"oddevent\">";
print "<td>Produits/services synchronisés sur WPshop</td>";
print "<td class=\"right\">" . $sync_products . "</td>";
print "</tr>";
print "<tr class=\"oddevent\">";
print "<td>Pourcentage de produits en vente sur le shop</td>";
print "<td class=\"right\">" . $percent . " %</td>";
print "</tr>";
print "</table>";

print "</div>";
print "</div>";
print "</div>";

llxFooter();
$db->close();

