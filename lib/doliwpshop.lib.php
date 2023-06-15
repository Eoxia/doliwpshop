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

	$head[$h][0] = dol_buildpath("/doliwpshop/admin/stockmanagement.php", 1);
	$head[$h][1] = $langs->trans("StockManagement");
	$head[$h][2] = 'stock';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'doliwpshop');

	return $head;
}
