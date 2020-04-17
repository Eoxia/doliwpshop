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
require_once DOL_DOCUMENT_ROOT . '/custom/wpshop/class/product_wpshop.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/wpshop/class/thirdparty_wpshop.class.php';

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

		if ( ! $connected ) {
			setEventMessages('Not connected to WPshop.', null, 'errors');
			return -1;
		}

		if (in_array('productcard', explode(':', $parameters['context'])))
		{
			$productWpshop = new ProductWpshop();

			if ($action == 'view' && $connected === true && ! empty($object->array_options['options__wps_id']))
			{
				$productWpshop->checkProductExistOnWp($object);
			}

			if ($action == 'createwp' && $connected === true && empty($object->array_options['options__wps_id']))
			{
				$productWpshop->createProductOnWp($object);
			}
		}

		if (in_array('thirdpartycard', explode(':', $parameters['context'])))
		{
			$thirdpartyWpshop = new ThirdPartyWpshop();

			if ($action == 'view' && $connected === true && ! empty($object->array_options['options__wps_id']))
			{
				$thirdpartyWpshop->checkThirdPartyExistOnWp($object);
			}

			if ($action == 'createwp' && $connected === true && empty($object->array_options['options__wps_id']))
			{
				$thirdpartyWpshop->createThirdPartyOnWp($object);
			}
		}

		return 0;
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
