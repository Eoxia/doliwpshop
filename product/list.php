<?php
/* Copyright (C) 2019-2026 Eoxia <dev@eoxia.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$langs->loadLangs(array('doliwpshop@doliwpshop', 'products', 'categories'));

// Security check
if (empty($user->rights->doliwpshop->read)) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

if ($action == 'update_tags' && !empty($user->rights->categorie->creer)) {
	$product_id = GETPOST('product_id', 'int');
	$category_ids = GETPOST('category_ids', 'array');
	if ($product_id > 0) {
		$product = new Product($db);
		$product->id = $product_id;
		$product->element = 'product'; 
		
		$catstatic = new Categorie($db);
		$currents = $catstatic->containing($product_id, Categorie::TYPE_PRODUCT);
		if (is_array($currents)) {
			foreach($currents as $cc) {
				$cc->del_type($product, Categorie::TYPE_PRODUCT);
			}
		}
		
		if (is_array($category_ids)) {
			foreach ($category_ids as $catid) {
				if ($catid > 0) {
					$cat = new Categorie($db);
					$cat->fetch($catid);
					$cat->add_type($product, Categorie::TYPE_PRODUCT);
				}
			}
		}
		
		if (GETPOST('ajax')) {
			header('Content-Type: application/json');
			echo json_encode(array('success' => true));
			exit;
		} else {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		}
	}
}

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield = "p.ref";
if (! $sortorder) $sortorder = "ASC";

$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_category = GETPOST('search_category', 'array');
$search_doli_id = GETPOST('search_doli_id', 'alpha');
$search_wps_status = GETPOST('search_wps_status', 'alpha');
$search_wps_id = GETPOST('search_wps_id', 'alpha');
$search_status = GETPOST('search_status', 'int');

$mainmenu = 'doliwpshop';
$leftmenu = 'doliwpshop_products_list';

llxHeader('', $langs->trans('Products'));

$title = $langs->trans('Products');

$sql = "SELECT p.rowid, p.ref, p.label, p.price, p.price_base_type, p.tva_tx, p.tosell,";
$sql.= " pe._wps_status, pe._wps_id,";
$sql.= " (SELECT GROUP_CONCAT(CONCAT(c.rowid, '::', IFNULL(c.color, ''), '::', c.label) SEPARATOR '||') FROM ".MAIN_DB_PREFIX."categorie_product as cp JOIN ".MAIN_DB_PREFIX."categorie as c ON c.rowid = cp.fk_categorie WHERE cp.fk_product = p.rowid) as categories_data";
$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON p.rowid = pe.fk_object";
$sql.= " WHERE pe._wps_id IS NOT NULL AND pe._wps_id != ''";

if ($search_ref) $sql .= natural_search('p.ref', $search_ref);
if ($search_label) $sql .= natural_search('p.label', $search_label);
if ($search_doli_id) $sql .= natural_search('p.rowid', $search_doli_id, 1);
if ($search_wps_status) $sql .= natural_search('pe._wps_status', $search_wps_status);
if ($search_wps_id) $sql .= natural_search('pe._wps_id', $search_wps_id);
if ($search_status != '' && $search_status >= 0) $sql .= " AND p.tosell = " . ((int) $search_status);
if (!empty($search_category) && is_array($search_category)) {
	$valid_cats = array();
	foreach($search_category as $cat) {
		if ($cat > 0) $valid_cats[] = (int) $cat;
	}
	if (!empty($valid_cats)) {
		$sql .= " AND EXISTS (SELECT cp.fk_product FROM ".MAIN_DB_PREFIX."categorie_product as cp WHERE cp.fk_product = p.rowid AND cp.fk_categorie IN (".$db->sanitize(implode(',', $valid_cats))."))";
	}
}

$sql.= $db->order($sortfield, $sortorder);
$sql.= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param = '';
	if ($search_ref) $param .= '&search_ref=' . urlencode($search_ref);
	if ($search_label) $param .= '&search_label=' . urlencode($search_label);
	if ($search_doli_id) $param .= '&search_doli_id=' . urlencode($search_doli_id);
	if ($search_wps_status) $param .= '&search_wps_status=' . urlencode($search_wps_status);
	if ($search_wps_id) $param .= '&search_wps_id=' . urlencode($search_wps_id);
	if ($search_status != '') $param .= '&search_status=' . urlencode($search_status);
	if (!empty($search_category)) {
		foreach($search_category as $cat) {
			$param .= '&search_category[]=' . urlencode($cat);
		}
	}
	
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $num, 'title_products', 0, '', '', $limit);
	
	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste">'."\n";

	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_label" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';
	
	// Tag filter (multiselect with select2)
	print '<td class="liste_titre maxwidth200">';
	$form = new Form($db);
	$cate_static = new Categorie($db);
	$categories = $cate_static->get_full_arbo(Categorie::TYPE_PRODUCT);
	$catarray = array();
	if (is_array($categories)) {
		foreach($categories as $cat) {
			$catarray[$cat['id']] = $cat['fulllabel'];
		}
	}
	print $form->multiselectarray('search_category', $catarray, $search_category, 0, 0, 'minwidth100 select2', 0, '100%');
	print '</td>';

	print '<td class="liste_titre"></td>'; // SellingPrice
	
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_wps_status" value="'.dol_escape_htmltag($search_wps_status).'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="4" type="text" name="search_doli_id" value="'.dol_escape_htmltag($search_doli_id).'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="4" type="text" name="search_wps_id" value="'.dol_escape_htmltag($search_wps_id).'">';
	print '</td>';
	
	print '<td class="liste_titre">';
	$form->selectarray('search_status', array('-1'=>'', '0'=>$langs->trans('ProductStatusNotOnSell'), '1'=>$langs->trans('ProductStatusOnSell')), $search_status);
	print '</td>';
	
	print '<td class="liste_titre" align="right">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Label', $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Categories', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('SellingPrice', $_SERVER["PHP_SELF"], 'p.price', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('WPshopStatus', $_SERVER["PHP_SELF"], 'pe._wps_status', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Doli ID', $_SERVER["PHP_SELF"], 'p.rowid', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('WPshop ID', $_SERVER["PHP_SELF"], 'pe._wps_id', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('Status', $_SERVER["PHP_SELF"], 'p.tosell', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre('', $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder); // Empty column for search button space
	print '</tr>'."\n";

	$productstatic = new Product($db);
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	$extrafields = new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label('product');

	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);
		
		$productstatic->id = $obj->rowid;
		$productstatic->ref = $obj->ref;
		$productstatic->label = $obj->label;
		$productstatic->status = $obj->tosell;

		print '<tr class="oddeven">';
		print '<td>'.$productstatic->getNomUrl(1).'</td>';
		print '<td>'.dol_escape_htmltag($obj->label).'</td>';
		
		$categoriesHtml = '<div class="tag-display" id="tag-display-'.$obj->rowid.'">';
		$selected_cats = array();
		if (!empty($obj->categories_data)) {
			$cats = explode('||', $obj->categories_data);
			$toprint = array();
			foreach($cats as $catStr) {
				list($cId, $cColor, $cLabel) = explode('::', $catStr);
				$selected_cats[] = $cId;
				
				$url = $_SERVER["PHP_SELF"]."?search_category[]=".urlencode($cId);
				if ($search_ref) $url .= '&search_ref=' . urlencode($search_ref);
				if ($search_label) $url .= '&search_label=' . urlencode($search_label);
				if ($search_doli_id) $url .= '&search_doli_id=' . urlencode($search_doli_id);
				if ($search_wps_status) $url .= '&search_wps_status=' . urlencode($search_wps_status);
				if ($search_wps_id) $url .= '&search_wps_id=' . urlencode($search_wps_id);
				if ($search_status != '') $url .= '&search_status=' . urlencode($search_status);
				
				$sfortag = '<li class="select2-search-choice-dolibarr noborderoncategories'.(empty($toprint) ? ' nomarginleft' : '').'"' . ($cColor ? ' style="background: #' . $cColor . ';"' : ' style="background: #bbb;"') . '>';
				
				$forced_color = 'categtextwhite';
				if ($cColor && function_exists('colorIsLight') && colorIsLight($cColor)) {
					$forced_color = 'categtextblack';
				}
				
				$sfortag .= '<a href="'.$url.'" class="'.$forced_color.'">'.dol_escape_htmltag($cLabel).'</a>';
				$sfortag .= '</li>';
				$toprint[] = $sfortag;
			}
			$categoriesHtml .= '<div class="select2-container-multi-dolibarr"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
		}
		
		$categoriesHtml .= '</div>';

		// Edit mode (multiselect) hidden by default
		if (!empty($user->rights->categorie->creer)) {
			$categoriesHtml .= '<div class="tag-editor" id="tag-editor-'.$obj->rowid.'" style="display:none; min-width: 200px;">';
			$categoriesHtml .= $form->multiselectarray('categories_'.$obj->rowid, $catarray, $selected_cats, 0, 0, 'minwidth100 select2 tag-multiselect', 0, '100%');
			$categoriesHtml .= ' <a href="#" class="close-tags-btn" data-productid="'.$obj->rowid.'" title="'.$langs->trans("Close").'"><span class="fa fa-times"></span></a>';
			$categoriesHtml .= '</div>';
		}

		print '<td class="td-tag-area" data-productid="'.$obj->rowid.'" style="cursor: pointer;">'.$categoriesHtml.'</td>';
		print '<td>'.price($obj->price, 1, $langs, 1, -1, -1, $conf->currency).'</td>';
		
		// Render WPShop status properly using ExtraFields if possible
		$wps_status_html = $extrafields->showOutputField('_wps_status', $obj->_wps_status, '', 'product');
		if (empty($wps_status_html)) $wps_status_html = dol_escape_htmltag($obj->_wps_status);
		print '<td>'.$wps_status_html.'</td>';
		
		print '<td>'.$obj->rowid.'</td>';
		
		$wps_id_text = dol_escape_htmltag($obj->_wps_id);
		if (!empty($conf->global->WPSHOP_URL_WORDPRESS) && $obj->_wps_id > 0) {
			$wp_url = rtrim($conf->global->WPSHOP_URL_WORDPRESS, '/');
			$wps_id_text = '<a href="'.$wp_url.'/wp-admin/post.php?post='.urlencode($obj->_wps_id).'&action=edit" target="_blank" rel="noopener noreferrer">'.$wps_id_text.'</a>';
		}
		print '<td>'.$wps_id_text.'</td>';
		
		print '<td>'.$productstatic->LibStatut($obj->tosell, 5).'</td>';
		print '<td></td>';
		print '</tr>'."\n";
		$i++;
	}
	print '</table>';
	print '</div>';
	print '</form>';
	
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

?>
<script>
$(document).ready(function() {
	$('.td-tag-area').click(function(e) {
		// If clicking a link (badge filter) or if editor is already open, do nothing
		if ($(e.target).is('a')) {
			return true;
		}
		var pid = $(this).data('productid');
		// Only if user has right to create categories (tag-editor div exists)
		if ($('#tag-editor-' + pid).length > 0 && $('#tag-editor-' + pid).is(':hidden')) {
			$('#tag-display-' + pid).hide();
			$('#tag-editor-' + pid).show();
			$('#categories_' + pid).select2('open');
		}
	});

	$('.close-tags-btn').click(function(e) {
		e.preventDefault();
		var pid = $(this).data('productid');
		$('#tag-editor-' + pid).hide();
		$('#tag-display-' + pid).show();
	});

	// Auto-submit search form when tag filter changes
	$('#search_category').on('change', function() {
		$('#searchFormList').submit();
	});

	$('.tag-multiselect').on('change', function(e) {
		var select_name = $(this).attr('name');
		// Handle select2 array name "categories_X[]"
		var match = select_name.match(/categories_(\d+)/);
		if (match) {
			var product_id = match[1];
			var category_ids = $(this).val() || [];
			
			$.ajax({
				url: '<?php echo $_SERVER["PHP_SELF"]; ?>',
				type: 'POST',
				data: {
					token: '<?php echo newToken(); ?>',
					action: 'update_tags',
					ajax: 1,
					product_id: product_id,
					category_ids: category_ids
				},
				success: function(response) {
					window.location.reload();
				},
				error: function() {
					$.jnotify('Error saving tags', 'error');
				}
			});
		}
	});
});
</script>
<?php

llxFooter();
$db->close();
