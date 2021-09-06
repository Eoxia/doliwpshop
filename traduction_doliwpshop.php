<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *    \file       traduction_doliwpshop.php
 *    \ingroup    doliwphop
 *    \brief      Page de traduction des produits lié a WPML/Wordpress/WPshop
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

require_once __DIR__ . '/lib/doliwpshop_function.lib.php';

require_once __DIR__ . '/class/productlang.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'languages'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
    $socid = $user->socid;
}

if ($id > 0 || !empty($ref)) {
    $objectProduct = new Product($db);
    $objectProduct->fetch($id, $ref);
	$objectProduct->multilangs = getMultiLangs($objectProduct);
}

$extrafields = new ExtraFields($db);
$object = new ProductLang($db);
$extrafields->fetch_name_optionals_label($object->table_element);

if ($objectProduct->id > 0) {
    if ($objectProduct->type == $objectProduct::TYPE_PRODUCT) {
        restrictedArea($user, 'produit', $objectProduct->id, 'product&product', '', '');
    }
    if ($objectProduct->type == $objectProduct::TYPE_SERVICE) {
        restrictedArea($user, 'service', $objectProduct->id, 'product&product', '', '');
    }
} else {
    restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('producttranslationcard', 'globalcard'));


/*
 * Actions
 */

$parameters = array('id' => $id, 'ref' => $ref);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
    // retour a l'affichage des traduction si annulation
    if ($cancel == $langs->trans("Cancel")) {
        $action = '';
    }

    if ($action == 'delete' && GETPOST('langtodelete', 'alpha')) {
        $objectProduct = new Product($db);
        $objectProduct->fetch($id);
        $objectProduct->delMultiLangs(GETPOST('langtodelete', 'alpha'), $user);
        setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
        $action = '';
    }

    // Add translation
    if ($action == 'vadd' && $cancel != $langs->trans("Cancel") && ($user->rights->produit->creer || $user->rights->service->creer)) {
        $objectProduct = new Product($db);
        $objectProduct->fetch($id);
		$objectProduct->multilangs = getMultiLangs($objectProduct);
		
        $current_lang = $langs->getDefaultLang();

        // update de l'objet
        if (GETPOST("forcelangprod") == $current_lang) {
            $objectProduct->label = GETPOST("libelle");
            $objectProduct->description = dol_htmlcleanlastbr(GETPOST("desc", 'restricthtml'));
            $objectProduct->other = dol_htmlcleanlastbr(GETPOST("other", 'restricthtml'));

            $objectProduct->update($objectProduct->id, $user);
        } else {
            $objectProduct->multilangs[GETPOST("forcelangprod")]["object"] = new ProductLang($db);
            $objectProduct->multilangs[GETPOST("forcelangprod")]["object"]->label = GETPOST("libelle");
            $objectProduct->multilangs[GETPOST("forcelangprod")]["object"]->description = dol_htmlcleanlastbr(GETPOST("desc", 'restricthtml'));
            $objectProduct->multilangs[GETPOST("forcelangprod")]["object"]->other = dol_htmlcleanlastbr(GETPOST("other", 'restricthtml'));
            //$ret = $extrafields->setOptionalsFromPost(null, $objectProduct->multilangs[GETPOST("forcelangprod")]["object"]);

            $extrafields_value = GETPOST("options_language_code");
        }

        // save in database
        if (GETPOST("forcelangprod")) {
            $result = setMultiLangsByLangs($user, GETPOST("forcelangprod"), $extrafields_value, $objectProduct);
        } else {
            $objectProduct->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Language"));
            $result = -1;
        }

        if ($result > 0) {
            $action = '';
        } else {
            $action = 'add';
            setEventMessages($objectProduct->error, $objectProduct->errors, 'errors');
        }
    }

    // Edit translation
    if ($action == 'vedit' && $cancel != $langs->trans("Cancel") && ($user->rights->produit->creer || $user->rights->service->creer)) {
        $objectProduct = new Product($db);
        $objectProduct->fetch($id);
        $current_lang = $langs->getDefaultLang();

        foreach ($objectProduct->multilangs as $key => $value) { // enregistrement des nouvelles valeurs dans l'objet
            if ($key == $current_lang) {
                $objectProduct->label = GETPOST("libelle-" . $key);
                $objectProduct->description = dol_htmlcleanlastbr(GETPOST("desc-" . $key, 'restricthtml'));
                $objectProduct->other = dol_htmlcleanlastbr(GETPOST("other-" . $key, 'restricthtml'));
            } else {
                $objectProduct->multilangs[$key]["label"] = GETPOST("libelle-" . $key);
                $objectProduct->multilangs[$key]["description"] = dol_htmlcleanlastbr(GETPOST("desc-" . $key, 'restricthtml'));
                $objectProduct->multilangs[$key]["other"] = dol_htmlcleanlastbr(GETPOST("other-" . $key, 'restricthtml'));
            }
        }

        $result = $objectProduct->setMultiLangs($user);
        if ($result > 0) {
            $action = '';
        } else {
            $action = 'edit';
            setEventMessages($objectProduct->error, $objectProduct->errors, 'errors');
        }
    }

    // Delete translation
    if ($action == 'vdelete' && $cancel != $langs->trans("Cancel") && ($user->rights->produit->creer || $user->rights->service->creer)) {
        $objectProduct = new Product($db);
        $objectProduct->fetch($id);
        $langtodelete = GETPOST('langdel', 'alpha');

        $result = $objectProduct->delMultiLangs($langtodelete, $user);
        if ($result > 0) {
            $action = '';
        } else {
            $action = 'edit';
            setEventMessages($objectProduct->error, $objectProduct->errors, 'errors');
        }
    }

    $objectProduct = new Product($db);
    $result = $objectProduct->fetch($id, $ref);
	$objectProduct->multilangs = getMultiLangs($objectProduct);
}

/*
 * View
 */

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($objectProduct->label, 16);
if (GETPOST("type") == '0' || ($objectProduct->type == Product::TYPE_PRODUCT)) {
    $title = $langs->trans('Product') . " " . $shortlabel . " - " . $langs->trans('Translation');
    $helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($objectProduct->type == Product::TYPE_SERVICE)) {
    $title = $langs->trans('Service') . " " . $shortlabel . " - " . $langs->trans('Translation');
    $helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl);

$form = new Form($db);
$formadmin = new FormAdmin($db);

$head = product_prepare_head($objectProduct);
$titre = $langs->trans("CardProduct" . $objectProduct->type);
$picto = ($objectProduct->type == Product::TYPE_SERVICE ? 'service' : 'product');


// Calculate $cnt_trans
$cnt_trans = 0;
if (!empty($objectProduct->multilangs)) {
    foreach ($objectProduct->multilangs as $key => $value) {
        $cnt_trans++;
    }
}


print dol_get_fiche_head($head, 'translate_doliwpshop', $titre, 0, $picto);

$linkback = '<a href="' . DOL_URL_ROOT . '/product/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

$shownav = 1;
if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) {
    $shownav = 0;
}

dol_banner_tab($objectProduct, 'ref', $linkback, $shownav, 'ref', '', '', '', 0, '', '', 1);

print dol_get_fiche_end();


/*
 * Action bar
 */
print "\n" . '<div class="tabsAction">' . "\n";

$parameters = array();
//$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
if (empty($reshook)) {
    if ($action == '') {
        if ($user->rights->produit->creer || $user->rights->service->creer) {
            print '<a class="butAction" href="' . DOL_URL_ROOT . '/custom/doliwpshop/traduction_doliwpshop.php?action=add&token='.newToken().'&id=' . $objectProduct->id . '">' . $langs->trans("Add") . '</a>';
            if ($cnt_trans > 0) {
                print '<a class="butAction" href="' . DOL_URL_ROOT . '/custom/doliwpshop/traduction_doliwpshop.php?action=edit&token='.newToken().'&id=' . $objectProduct->id . '">' . $langs->trans("Update") . '</a>';
            }
        }
    }
}

print "\n" . '</div>' . "\n";

if ($action == 'edit') {
    //WYSIWYG Editor
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

    print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="vedit">';
    print '<input type="hidden" name="id" value="' . $objectProduct->id . '">';

    if (!empty($objectProduct->multilangs)) {
        $i = 0;
        foreach ($objectProduct->multilangs as $key => $value) {
            $i++;

            $s = picto_from_langcode($key);
            print ($i > 1 ? "<br>" : "") . ($s ? $s . ' ' : '') . ' <div class="inline-block margintop marginbottomonly"><b>' . $langs->trans('Language_' . $key) . '</b></div><div class="inline-block floatright"><a href="' . $_SERVER["PHP_SELF"] . '?id=' . $objectProduct->id . '&action=delete&token=' . newToken() . '&langtodelete=' . $key . '">' . img_delete('', 'class="valigntextbottom marginrightonly"') . '</a></div>';

            print '<div class="underbanner clearboth"></div>';
            print '<table class="border centpercent">';
            print '<tr><td class="tdtop titlefieldcreate fieldrequired">' . $langs->trans('Label') . '</td><td><input name="libelle-' . $key . '" size="40" value="' . dol_escape_htmltag($value["object"]->label) . '"></td></tr>';
            print '<tr><td class="tdtop">' . $langs->trans('Description') . '</td><td>';
            $doleditor = new DolEditor("desc-$key", $value["object"]->description, '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
            $doleditor->Create();
            print '</td></tr>';
            if (!empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) {
                print '<tr><td class="tdtop">' . $langs->trans('Other') . ' (' . $langs->trans("NotUsed") . ')</td><td>';
                $doleditor = new DolEditor("other-$key", $value["object"]->other, '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
                $doleditor->Create();
            }
            print '</td></tr>';
            // Other attributes
            $object = $value["object"];
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';
            print '</table>';
        }
    }

    $parameters = array();
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

    print '<br>';

    print '<div class="center">';
    print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

    print '</form>';
} elseif ($action != 'add') {
    if (!empty($objectProduct->multilangs)) {
        $i = 0;

        foreach ($objectProduct->multilangs as $key => $value) {
            $i++;
			$s = picto_from_langcode($key);
			print ($i > 1 ? "<br>" : "") . ($s ? $s . ' ' : '') . ' <div class="inline-block marginbottomonly"><b>' . $langs->trans('Language_' . $key) . '</b></div><div class="inline-block floatright"><a href="' . $_SERVER["PHP_SELF"] . '?id=' . $objectProduct->id . '&action=delete&token=' . newToken() . '&langtodelete=' . $key . '">' . img_delete('', 'class="valigntextbottom marginrightonly"') . '</a></div>';

			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent">';
			print '<tr><td class="titlefieldcreate">' . $langs->trans('Label') . '</td><td>' . $value["object"]->label . '</td></tr>';
			print '<tr><td class="tdtop">' . $langs->trans('Description') . '</td><td>' . $value["object"]->description . '</td></tr>';
			if (!empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) {
				print '<tr><td>' . $langs->trans('Other') . ' (' . $langs->trans("NotUsed") . ')</td><td>' . $value["object"]->other . '</td></tr>';
			}
			$object = $value["object"];
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
            print '</table>';
            print '</div>';
        }
    }
    if (!$cnt_trans && $action != 'add') {
        print '<div class="opacitymedium">' . $langs->trans('NoTranslation') . '</div>';
    }
}


/*
 * Form to add a new translation
 */

if ($action == 'add' && ($user->rights->produit->creer || $user->rights->service->creer)) {
    //WYSIWYG Editor
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

    print '<br>';
    print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="vadd">';
    print '<input type="hidden" name="id" value="' . GETPOST("id", 'int') . '">';

    print dol_get_fiche_head();

    print '<table class="border centpercent">';
    print '<tr><td class="tdtop titlefieldcreate fieldrequired">' . $langs->trans('Language') . '</td><td>';
    print $formadmin->select_language(GETPOST('forcelangprod'), 'forcelangprod', 0, $objectProduct->multilangs, 1);
    print '</td></tr>';
    print '<tr><td class="tdtop fieldrequired">' . $langs->trans('Label') . '</td><td><input name="libelle" size="40"></td></tr>';
    print '<tr><td class="tdtop">' . $langs->trans('Description') . '</td><td>';
    $doleditor = new DolEditor('desc', '', '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
    $doleditor->Create();
    print '</td></tr>';
    // Other field (not used)
    if (!empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) {
        print '<tr><td class="tdtop">' . $langs->trans('Other') . ' (' . $langs->trans("NotUsed") . '</td><td>';
        $doleditor = new DolEditor('other', '', '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
        $doleditor->Create();
        print '</td></tr>';
    }
    $result = $extrafields->fetch_name_optionals_label($object->table_element);
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>';

    $parameters = array();
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

    print dol_get_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

    print '</form>';

    print '<br>';
}

// End of page
llxFooter();
$db->close();
