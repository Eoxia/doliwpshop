<?php
/* Copyright (C) 2019-2021 Eoxia <dev@eoxia.com>
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
 *    \file        lib/doliwpshop_function.lib.php
 *    \ingroup    doliwpshop
 *    \brief        Library files with common functions for DoliWPshop
 */

/*    Update or add a translation for a product by lang
*
* @param  User    $user Object        user making update
* @param  string  $lang_code          Language code
* @param  string  $extrafields_value  Value of extrafields for WPML language code
* @return int        <0 if KO, >0 if OK
*/
function setMultiLangsByLangs($user, $lang_code = '', $extrafields_value = '', $objectProduct) {
	global $conf, $db;

	$productlang = new ProductLang($db);

	$resultLang = $productlang->fetchAll('', '', 0, 0, array('t.fk_product' => $objectProduct->id,
		't.lang'       => $objectProduct->db->escape($lang_code)));
	if (!is_array($resultLang) && $resultLang < 0) {
		$objectProduct->error = $productlang->error;
		$objectProduct->errors = $productlang->errors;
		return -1;
	} else {
		if (is_array($resultLang) && count($resultLang) == 0) {
			$productlang->fk_product = $objectProduct->id;
			$productlang->label = $objectProduct->multilangs[$lang_code]["object"]->label;
			$productlang->description = $objectProduct->multilangs[$lang_code]["object"]->description;
			$productlang->lang = $lang_code;
			if (!empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) {
				$productlang->other = $objectProduct->multilangs[$lang_code]["object"]->other;
			}

			$productlang->array_options['options_language_code'] = $extrafields_value;

			$result = $productlang->create($user);
		} else {
			$productlang->fk_product = $objectProduct->id;
			$productlang->label = $objectProduct->multilangs[$lang_code]["object"]->label;
			$productlang->description = $objectProduct->multilangs[$lang_code]["object"]->description;
			$productlang->lang = $lang_code;
			if (!empty($conf->global->PRODUCT_USE_OTHER_FIELD_IN_TRANSLATION)) {
				$productlang->other = $objectProduct->multilangs[$lang_code]["object"]->other;
			}
			$result = $productlang->update($user);
		}
	}

	if ($result < 0) {
		$objectProduct->error = $productlang->error;
		$objectProduct->errors = $productlang->errors;
		return -1;
	} else {
		$result = $objectProduct->call_trigger('PRODUCT_SET_MULTILANGS_WPSHOP', $user);
		if ($result < 0) {
			$objectProduct->error = $objectProduct->db->lasterror();
			return -1;
		}
	}
	return 1;
}

/**
 *    Load array this->multilangs
 *
 * @return int        <0 if KO, >0 if OK
 */
function getMultiLangs($objectProduct)
{
	global $langs, $db;

	$current_lang = $langs->getDefaultLang();

	require_once  __DIR__ . '/../class/productlang.class.php';
	$productlang = new ProductLang($db);

	$result = $productlang->fetchAll('', '', 0, 0, array('t.fk_product'=>$objectProduct->id));

	if (is_array($result) && count($result)>0) {
		foreach ($result as $key => $val) {
			if ($val->lang == $current_lang) {
				$objectProduct->label        = $val->label;
				$objectProduct->description  = $val->description;
				$objectProduct->other        = $val->other;
				//TODO see how to get extrafields val on current lang
			}
			$objectProduct->multilangs[$val->lang]["object"] = $val;

			//For backport compatiblity
			$objectProduct->multilangs[$val->lang]["label"]        = $val->label;
			$objectProduct->multilangs[$val->lang]["description"] = $val->description;
			$objectProduct->multilangs[$val->lang]["other"]        = $val->other;
		}
	} elseif (!is_array($result) && $result<0) {
		$objectProduct->error = implode(',', $productlang->errors);
		$objectProduct->errors[] = array_merge($objectProduct->errors, $productlang->errors);
		return -1;
	}
	return $objectProduct->multilangs;
}

