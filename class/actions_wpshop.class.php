<?php
/* Copyright (C) 2020 Eoxia
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT . '/custom/wpshop/lib/wp_api.class.php';

/**
 * \file    wpshop/class/actions_wpshop.class.php
 * \ingroup wpshop
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsWpshop
 */
class ActionsWpshop
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();


    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;


    /**
     * Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $user;

		$error = 0; // Error counter
		$resprints = '';
		$results = array();
		$replace = 0;
		$errors = array();

		$connected = WPAPI::get( '/wp-json/wpshop/v2/statut' );

		if (in_array('productcard', explode(':', $parameters['context'])))
		{
			if ($action == 'view' && $connected === true && ! empty($object->array_options['options__wps_id']))
			{
				$url = '/wp-json/wpshop/v1/product/' . $object->array_options['options__wps_id'];

				$response = WPAPI::get($url);

				if (!$response) {
					// @todo: How debug is working in Dolibarr ? doli_log
					// EOFramework API return NULL if product ID is not found. Missing real message from EOFramework.
					$object->array_options['options__wps_id'] = "";
					$result = $object->update($object->id, $user, 1, 'update', true);

					if (!$result)
					{
						setEventMessages('An error occurred to update this object #' . $object->id, null, 'errors');
					} else
					{
						setEventMessages('WP Product was not found, delete wps_id entry.', null);
					}
				}
			}

			if ($action == 'createwp' && $connected === true && empty($object->array_options['options__wps_id']))
			{
				$url = '/wp-json/wpshop/v2/sync';

				$response = WPAPI::post($url, array(
					'doli_id' => $object->id,
					'type'    => 'wps-product', // @todo: Générique.
				));

				if ($response['status']) {
					$object->array_options['options__wps_id'] = $response['data']['wp_object']['data']['id'];
					$result = $object->update($object->id, $user, 1, 'update', true);

					if (!$result)
					{
						setEventMessages('An error occurred to update this object ' . $object->id, null, 'errors');
					}
					else
					{
						setEventMessages('WP Product was created, updated Dolibarr Product with wps_id #' . $response['data']['wp_object']['data']['id'], null);
					}
				} else {
					setEventMessages('Request Error: POST ' . $url . ' "' . $response['error_message'] . '"', null, 'errors');
				}
			}
		}

		if (! $error)
		{
			$this->results = $results;
			$this->resprints = $resprints;

			return $replace; // 0 or return 1 to replace standard code
		}
		else
		{
			array_merge($this->errors, $errors);
			return -1;
		}
	}

    public function addMoreActionsButtons($parameters, &$object, &$action)
	{

		$connected = WPAPI::get( '/wp-json/wpshop/v2/statut' );

		if ($connected !== true)
		{
			?>
			<div class="inline-block divButAction"><a class="butActionRefused" title="Non disponible car Dolibarr n'est pas connecté à WPShop" href="#">Create on WP</a></div>
			<?php
			return;
		}

		if (empty($object->array_options['options__wps_id']))
		{
			$actual_link  = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$actual_link .= '&action=createwp';
			?>
			<div class="inline-block divButAction"><a class="butAction" href="<?php echo $actual_link; ?>">Create on WP</a></div>
			<?php
		}
		else
		{
			?>
			<div class="inline-block divButAction"><a class="butActionRefused" title="Non disponible car l'objet est déjà associée" href="#">Create on WP</a></div>
			<?php
		}
	}
}
