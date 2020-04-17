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
 * \file    wpshop/class/product.class.php
 * \ingroup wpshop
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class Product
 */

class ProductWpshop {
	public function __construct() {}

	public function checkProductExistOnWp($object) {
		global $user;

		if (! empty($object->array_options['options__wps_id']))
		{
			$url = '/wp-json/wpshop/v1/product/' . $object->array_options['options__wps_id'];

			$response = WPAPI::get($url);

			if (!$response) {
				// EOFramework API return NULL if product ID is not found. Missing real message from EOFramework.
				$object->array_options['options__wps_id'] = "";
				$result = $object->update($object->id, $user, 1, 'update', true);

				if (!$result)
				{
					setEventMessages('An error occurred to update this object #' . $object->id, null, 'errors');
					return -1;
				} else
				{
					setEventMessages('WP Product was not found, delete wps_id entry.', null);
					return 0;
				}
			}
		}

		return 0;
	}

	public function createProductOnWp($object) {
		global $user;

		$url = '/wp-json/wpshop/v2/sync';

		$response = WPAPI::post($url, array(
			'doli_id' => $object->id,
			'type'    => 'wps-product',
		));

		if ($response['status']) {
			$object->array_options['options__wps_id'] = $response['data']['wp_object']['data']['id'];
			$result = $object->update($object->id, $user, 1, 'update', true);

			if (!$result)
			{
				setEventMessages('An error occurred to update this object ' . $object->id, null, 'errors');
				return -1;
			}
			else
			{
				setEventMessages('WP Product was created, updated Dolibarr Product with wps_id #' . $response['data']['wp_object']['data']['id'], null);
				return 0;
			}
		} else {
			setEventMessages('Request Error: POST ' . $url . ' "' . $response['error_message'] . '"', null, 'errors');
			return -1;
		}

		return 0;
	}
}
