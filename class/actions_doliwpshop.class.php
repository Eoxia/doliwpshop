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

/**
 * \file    htdocs/custom/doliwpshop/class/actions_doliwpshop.class.php
 * \ingroup doliwpshop
 * \brief   Hook on new actions for connected Dolibarr and WPshop
 */

require_once DOL_DOCUMENT_ROOT . '/custom/doliwpshop/class/api_doliwpshop.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/doliwpshop/class/product_doliwpshop.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/doliwpshop/class/thirdparty_doliwpshop.class.php';

/**
 * Class ActionsDoliWPshop
 */
class ActionsDoliWPshop
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

	/**
	 * Do new actions on CommonObject
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (third party and product object)
	 * @param   string          $action         Current action (view_on_wpshop or create_on_wpshop)
	 * @return  int                             < 0 on error, 0 on success,
	 */
	public function doActions($parameters, &$object, &$action)
	{
		global $langs;

		// Translations
		$langs->load("doliwpshop@doliwpshop");

		$connected = WPshopAPI::get('/wp-json/wpshop/v2/statut');

		if ( ! $connected ) {
			setEventMessages($langs->trans("NotConnectedWPshop"), null, 'errors');
			return -1;
		}

		if (in_array('productcard', explode(':', $parameters['context'])))
		{
			$productDoliWPshop = new ProductDoliWPshop();

			if ($action == 'view' && $connected === true && ! empty($object->array_options['options__wps_id']))
			{
				$productDoliWPshop->checkProductExistOnWPshop($object);
			}

			if ($action == 'createwp' && $connected === true && empty($object->array_options['options__wps_id']))
			{
				$productDoliWPshop->createProductOnWPshop($object);
			}
		}

		if (in_array('thirdpartycard', explode(':', $parameters['context'])))
		{
			$thirdpartyDoliWPshop = new ThirdPartyDoliWPshop();

			if ($action == 'view' && $connected === true && ! empty($object->array_options['options__wps_id']))
			{
				$thirdpartyDoliWPshop->checkThirdPartyExistOnWPshop($object);
			}

			if ($action == 'createwp' && $connected === true && empty($object->array_options['options__wps_id']))
			{
				$thirdpartyDoliWPshop->createThirdPartyOnWPshop($object);
			}
		}

		return 0;
	}

	/**
	 * Add new actions buttons on CommonObject
	 *
	 * @param   CommonObject  $object  The object to process (third party and product object)
	 */
    public function addMoreActionsButtons($parameters, &$object, &$action)
	{
		global $conf, $langs;

		// Translations
		$langs->load("doliwpshop@doliwpshop");

		$connected = WPshopAPI::get('/wp-json/wpshop/v2/statut');

		if ($connected !== true) {
			print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.$langs->trans("NotAvailableDolibarr").'" href="#">'.$langs->trans("CreateOnWPshop").'</a></div>';
			return;
		}

		if (empty($object->array_options['options__wps_id'])) {
			$actual_link  = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$actual_link .= '&action=createwp';
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $actual_link . '">'.$langs->trans("CreateOnWPshop").'</a></div>';
		}
		else {
			if ($object->element == 'product') {
				print '<div class="inline-block divButAction"><a class="butAction" title="'.$langs->trans("ViewOnWPshop").'" href="' . $conf->global->WPSHOP_URL_WORDPRESS . '/?post_type=wps-product&p=' . $object->array_options['options__wps_id'] . '">'.$langs->trans("ViewOnWPshop").'</a></div>';
			}
			print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.$langs->trans("NotAvailableObject").'" href="#">'.$langs->trans("CreateOnWPshop").'</a></div>';
		}
	}
}
