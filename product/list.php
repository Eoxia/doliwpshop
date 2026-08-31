<?php
/* Copyright (C) 2019-2026 Eoxia <dev@eoxia.com>
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

require '../../../main.inc.php';

$langs->loadLangs(array('doliwpshop@doliwpshop'));

// Security check
if (empty($user->rights->doliwpshop->read)) {
	accessforbidden();
}

$mainmenu = 'doliwpshop';
$leftmenu = 'doliwpshop_products_list';

llxHeader('', $langs->trans('Products'));

print load_fiche_titre($langs->trans('Products'));

print '<div class="tabBar">';
print 'Liste des produits (à implémenter)...';
print '</div>';

llxFooter();
$db->close();
