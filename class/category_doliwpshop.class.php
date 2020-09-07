<?php
/* Copyright (C) 2020 Eoxia
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/custom/doliwpshop/class/category_doliwpshop.class.php
 * \ingroup doliwpshop
 * \brief   Hook on dolibarr category.
 */

/**
 * Class CategoryDoliWPshop
 */
class CategoryDoliWPshop {
	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Checks if the category object exists on WPshop
	 *
	 * @param  CommonObject $object  category object
	 *
	 * @return int             <0 if KO, category object exist if OK : 0
	 */
	/*public function checkCategoryExistOnWPshop($object) {
		global $user, $langs;

		// Translations
		$langs->load("doliwpshop@doliwpshop");

		if (! empty($object->array_options['options__wps_id'])) {
			$url = 'wp-json/wpshop/v1/category/' . $object->array_options['options__wps_id'];

			$response = WPshopAPI::get($url);

			if (!$response) {
				// EOFramework API return NULL if category ID is not found. Missing real message from EOFramework.
				//$object->array_options['options__wps_id'] = "";
				$result = $object->update( $user, $object->id, 1, 'update', true);
                
				if (!$result) {
					setEventMessages($langs->trans("ErrorUpdateObject") . $object->id, null, 'errors');
					return -1;
				} else {
					setEventMessages($langs->trans("ErrorWPSCategory"), null, 'errors');
					return -1;
				}
			}
		}

		return 0;
	}*/

	/**
	 * Create the category object on WPshop
	 *
	 * @param  CommonObject $object  category object
	 *
	 * @return int             <0 if KO, category object create if OK : 0
	 */
	public function createCategoryOnWPshop($object) {
		global $user, $langs;
		
		// Translations
		$langs->load("doliwpshop@doliwpshop");

        $url = 'wp-json/wpshop/v2/sync';
        			
		$response = WPshopAPI::post($url, array(
			'doli_id' => $object->id,
			'type'    => 'wps-product-cat',
        ));
		
		if ($response['status']) {
            $object->array_options['options__wps_id'] = $response['data']['wp_object']['data']['id'];
            $object->array_options['options__wps_slug'] = $response['data']['wp_object']['data']['slug'];
            $result = $object->update( $user,$object->id, 1, 'update', true);
			
			if (!$result) {
				setEventMessages($langs->trans("ErrorUpdateObject") . $object->id, null, 'errors');
				return -1;
			}
			else{
				setEventMessages($langs->trans("CreateWPSCategory") . $response['data']['wp_object']['data']['id'], null);
				return 0;
			}
		} else {
			setEventMessages($langs->trans("ErrorPostRequest") . $url . ' "' . $response['error_message'] . '"', null, 'errors');
			return -1;
		}
		
		return 0;
	}
}
