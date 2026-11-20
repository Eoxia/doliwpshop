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

/**
 *  Rights the API user needs so that WPshop works
 *
 *  Single list used to grant the rights (admin pages) and to check them (API checkPermissions). Built from the
 *  routes WPshop calls and the rights the core API checks for each of them.
 *
 *  Each module key is the one isModEnabled() and User::hasRight() expect. Each right gives its permission path
 *  (perms, subperms), whether it is an "advanced" right (only enforced by the core when MAIN_USE_ADVANCED_PERMS is
 *  on) and, for the module rights, the translation key of its label (core rights use "Permission<id>").
 *
 *  @return array<string, array{label: string, rights: array<int, array{perms: string, subperms: string, advanced: bool, label: string}>}>
 */
function doliwpshop_get_required_rights(): array
{
	$right = static function (string $perms, string $subperms = '', bool $advanced = false, string $label = ''): array {
		return array('perms' => $perms, 'subperms' => $subperms, 'advanced' => $advanced, 'label' => $label);
	};

	return array(
		'societe' => array(
			'label'  => 'Module1Name',
			'rights' => array(
				121 => $right('lire'),
				122 => $right('creer'),
				262 => $right('client', 'voir'),
				281 => $right('contact', 'lire'),
				282 => $right('contact', 'creer'),
			),
		),
		'propal' => array(
			'label'  => 'Module20Name',
			'rights' => array(
				21 => $right('lire'),
				22 => $right('creer'),
				24 => $right('propal_advance', 'validate', true),
				26 => $right('propal_advance', 'close', true),
			),
		),
		'commande' => array(
			'label'  => 'Module25Name',
			'rights' => array(
				81 => $right('lire'),
				82 => $right('creer'),
				84 => $right('order_advance', 'validate', true),
				87 => $right('order_advance', 'close', true),
			),
		),
		'facture' => array(
			'label'  => 'Module30Name',
			'rights' => array(
				11 => $right('lire'),
				12 => $right('creer'),
				14 => $right('invoice_advance', 'validate', true),
				16 => $right('paiement'),
			),
		),
		'product' => array(
			'label'  => 'Module50Name',
			'rights' => array(
				31 => $right('lire'),
				32 => $right('creer'),
			),
		),
		'service' => array(
			'label'  => 'Module53Name',
			'rights' => array(
				531 => $right('lire'),
				532 => $right('creer'),
			),
		),
		'categorie' => array(
			'label'  => 'Module1780Name',
			'rights' => array(
				241 => $right('lire'),
				242 => $right('creer'),
			),
		),
		'stock' => array(
			'label'  => 'Module52Name',
			'rights' => array(
				1001 => $right('lire'),
			),
		),
		'doliwpshop' => array(
			'label'  => 'ModuleDoliWPshopName',
			'rights' => array(
				8000000 => $right('read', '', false, 'ReadRightDoliWPshop'),
				8000001 => $right('write', '', false, 'CreateRightDoliWPshop'),
			),
		),
	);
}

/**
 *  Label of a right of doliwpshop_get_required_rights()
 *
 *  @param  int   $id    Right id
 *  @param  array $right Right definition
 *  @return string       Translated label
 */
function doliwpshop_get_right_label(int $id, array $right): string
{
	global $langs;

	$langs->loadLangs(array('admin', 'doliwpshop@doliwpshop'));

	return $langs->transnoentitiesnoconv(!empty($right['label']) ? $right['label'] : 'Permission' . $id);
}

/**
 *  Grants a user every right of doliwpshop_get_required_rights()
 *
 *  Rights already granted are kept, and so are the other rights of the user. Rights of a disabled module cannot be
 *  granted (they do not exist in the rights table): see doliwpshop_get_disabled_modules().
 *
 *  @param  User $targetuser User to grant
 *  @return int              Number of rights that could not be granted
 */
function doliwpshop_apply_required_rights(User $targetuser): int
{
	$errors = 0;

	foreach (doliwpshop_get_required_rights() as $module => $definition) {
		if (!isModEnabled($module)) {
			continue;
		}
		foreach (array_keys($definition['rights']) as $id) {
			if ($targetuser->addrights($id) < 0) {
				$errors++;
			}
		}
	}

	$targetuser->clearrights();
	$targetuser->getrights();

	return $errors;
}

/**
 *  Modules of doliwpshop_get_required_rights() that are disabled
 *
 *  @return array<string, string> Module key => translated module name
 */
function doliwpshop_get_disabled_modules(): array
{
	global $langs;

	$langs->loadLangs(array('admin', 'doliwpshop@doliwpshop'));

	$disabled = array();
	foreach (doliwpshop_get_required_rights() as $module => $definition) {
		if (!isModEnabled($module)) {
			$disabled[$module] = $langs->transnoentitiesnoconv($definition['label']);
		}
	}

	return $disabled;
}

/**
 *  Whether a user has a right of doliwpshop_get_required_rights()
 *
 *  @param  User   $targetuser User to check (rights loaded)
 *  @param  string $module     Module key
 *  @param  array  $right      Right definition
 *  @return bool
 */
function doliwpshop_user_has_right(User $targetuser, string $module, array $right): bool
{
	return (bool) $targetuser->hasRight($module, $right['perms'], $right['subperms']);
}

/**
 *  Required rights the user is missing
 *
 *  Advanced rights are only checked when MAIN_USE_ADVANCED_PERMS is on, as the core only enforces them then.
 *  A right of a disabled module counts as missing.
 *
 *  @param  User $targetuser User to check (rights loaded)
 *  @return array<int, string> Right id => "module->perms[->subperms] (label)"
 */
function doliwpshop_get_missing_rights(User $targetuser): array
{
	$advanced_perms = getDolGlobalString('MAIN_USE_ADVANCED_PERMS');

	$missing = array();
	foreach (doliwpshop_get_required_rights() as $module => $definition) {
		foreach ($definition['rights'] as $id => $right) {
			if ($right['advanced'] && !$advanced_perms) {
				continue;
			}
			if (!doliwpshop_user_has_right($targetuser, $module, $right)) {
				$path          = $module . '->' . $right['perms'] . ($right['subperms'] !== '' ? '->' . $right['subperms'] : '');
				$missing[$id] = $path . ' (' . doliwpshop_get_right_label($id, $right) . ')';
			}
		}
	}

	return $missing;
}

