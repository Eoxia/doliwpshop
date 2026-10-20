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
 *	\file		htdocs/custom/doliwpshop/lib/doliwpshop.lib.php
 *	\ingroup	doliwpshop
 *	\brief		Library files with common functions for DoliWPshop
 */

/**
 *  Prepare admin pages header
 *
 *  @return	array
 */
function doliwpshopAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("doliwpshop@doliwpshop");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/doliwpshop/admin/doliwpshop.php", 1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;
	
	$head[$h][0] = dol_buildpath("/doliwpshop/admin/doliwpshop_products.php", 1);
	$head[$h][1] = "Produits/services"; // We can hardcode or use translation
	$head[$h][2] = 'products';
	$h++;
	
	$head[$h][0] = dol_buildpath("/doliwpshop/admin/doliwpshop_categories.php", 1);
	$head[$h][1] = "Catégories";
	$head[$h][2] = 'categories';
	$h++;

	$head[$h][0] = dol_buildpath("/doliwpshop/admin/doliwpshop_users.php", 1);
	$head[$h][1] = "Utilisateurs/droits";
	$head[$h][2] = 'users';
	$h++;

	$head[$h][0] = dol_buildpath("/doliwpshop/admin/stockmanagement.php", 1);
	$head[$h][1] = $langs->trans("StockManagement");
	$head[$h][2] = 'stock';
	$h++;

	$object = null;
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'doliwpshop');

	return $head;
}

/**
 *  Stylesheets of the DoliWPshop back office pages, to pass to llxHeader()
 *
 *  The module stylesheet is always loaded. DoliWPshop does not depend on Saturne: only when Saturne is enabled
 *  are its stylesheet and the modern back office theme it carries added (see doliwpshop_body_classes()).
 *
 *  @param  array $arrayofcss Stylesheets already needed by the page
 *  @return array             Stylesheets to pass to llxHeader()
 */
function doliwpshop_header_css(array $arrayofcss = array()): array
{
	if (isModEnabled('saturne')) {
		$arrayofcss[] = '/saturne/css/saturne.min.css';
	}
	$arrayofcss[] = '/doliwpshop/css/doliwpshop.css';

	return $arrayofcss;
}

/**
 *  Body classes of the DoliWPshop back office pages, to pass to llxHeader() as $morecssonbody
 *
 *  mod-doliwpshop scopes the module stylesheet. saturne-theme, added only when Saturne is enabled, turns on the
 *  modern back office theme shared by the Saturne modules.
 *
 *  @param  string $morecssonbody Body classes already needed by the page
 *  @return string                Body classes to pass to llxHeader()
 */
function doliwpshop_body_classes(string $morecssonbody = ''): string
{
	$classes = trim($morecssonbody . ' mod-doliwpshop');
	if (isModEnabled('saturne')) {
		$classes .= ' saturne-theme';
	}

	return $classes;
}

