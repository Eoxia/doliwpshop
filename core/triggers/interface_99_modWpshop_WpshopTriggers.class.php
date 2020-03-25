<?php
/* Copyright (C) 2019 SuperAdmin
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
 * \file    core/triggers/interface_99_modWpshop_WpshopTriggers.class.php
 * \ingroup wpshop
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modWpshop_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

dol_include_once('/wpshop/class/wp_api.class.php');
dol_include_once('/wpshop/class/wpshop_connector.class.php');

/**
 *  Class of triggers for Wpshop module
 */
class InterfaceWpshopTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "Wpshop triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'wpshop@wpshop';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		global $conf;
		global $db;

		if (empty($conf->wpshop->enabled)) return 0;

		switch ($action) {
			case 'ECMFILES_CREATE':
			case 'ECMFILES_MODIFY':
				if ( $_REQUEST['action'] != 'confirm_paiement' ) {
					$object_class = null;
					$type = '';
					switch ( $object->src_object_type ) {
						case 'propal':
							$object_class = new Propal($this->db);
							$type = 'propal';
							break;
					}

					if ($object_class != null) {
						$object_class->fetch($object->src_object_id);
						$wpshop_object = new wpshop_object($this->db);
						$object = $wpshop_object->fetch((int) $object->src_object_id, $type);

						if ( ! empty( $object ) && ! empty( $object->wp_id ) && ! empty( $object->doli_id ) ) {
							$object->update( $user );
							$request = WPAPI::post( '/wp-json/wpshop/v2/sync', array(
								'wp_id'   => $object->wp_id,
								'doli_id' => $object->doli_id,
							), 'POST' );
						}
					}
				}
				break;
		    case 'PRODUCT_CREATE':
				if ( empty( $object->wp_id ) && ( ! empty( $object->array_options['options_web'] ) && $object->array_options['options_web'] ) ) {
					$sync_date = dol_now( 'gmt' );

					$request = WPAPI::post( '/wp-json/wpshop/v1/product/', array(
						'title' => $object->label,
						'price' => $object->price,
						'price_ttc' => $object->price_ttc,
						'tva_tx' => $object->tva_tx,
						'date_last_synchro' => date( 'Y-m-d H:i:s', $sync_date ),
						'external_id' => (int) $object->id,
					), 'POST' );

					$wpshop_object = new wpshop_object($this->db);
					$wpshop_object->wp_id = $request['data']['id'];
					$wpshop_object->doli_id = $object->id;
					$wpshop_object->type = "product";
					$wpshop_object->sync_date = $sync_date;
					$wpshop_object->last_sync_date = $sync_date;
					$wpshop_object->create($user);
				}
				break;
		    case 'PRODUCT_MODIFY':
				$wpshop_product = new wpshop_object( $this->db );
				$product = $wpshop_product->fetch( (int) $object->id, 'product' );

				if ( ! empty( $product ) && ( ! empty( $object->array_options['options_web'] ) && $object->array_options['options_web'] ) ) {
					$product->update( $user );
					$request = WPAPI::post( '/wp-json/wpshop/v1/product/' . (int) $product->wp_id, array(
						'title' => $object->label,
						'price' => $object->price,
						'price_ttc' => $object->price_ttc,
						'tva_tx' => $object->tva_tx,
						'date_last_synchro' => date( 'Y-m-d H:i:s', $product->last_sync_date ),
					), 'PUT' );
				}
				break;
			case 'PRODUCT_PRICE_MODIFY':
				$wpshop_product = new wpshop_object( $this->db );
				$product = $wpshop_product->fetch( (int) $object->id, 'product' );

				if ( ! empty( $product ) && ( ! empty( $object->array_options['options_web'] ) && $object->array_options['options_web'] ) ) {
					$product->update( $user );
					$request = WPAPI::post( '/wp-json/wpshop/v1/product/' . (int) $product->wp_id, array(
						'price' => $object->price,
						'price_ttc' => $object->price_ttc,
						'tva_tx' => $object->tva_tx,
						'date_last_synchro' => date( 'Y-m-d H:i:s', $product->last_sync_date ),
					), 'PUT' );
				}
				break;
			// Proposals
			case 'PROPAL_CREATE':
				if ( ! empty( $_REQUEST['action'] ) ) {
				$sync_date = dol_now( 'gmt' );

				$wpshop_product = new wpshop_object( $this->db );
				$product = $wpshop_product->fetch( (int) $object->id, 'propal' );

				if ( empty( $product ) ) {

						$data = array(
							'title' => $object->ref,
							'total_ht' => $object->total_ht,
							'total_ttc' => $object->total_ttc,
							'tva_tx' => $object->total_tva,
							'socid' => $object->socid,
							'datec' => $object->date,
							'date_last_synchro' => date( 'Y-m-d H:i:s', $sync_date ),
							'external_id' => (int) $object->id,
						);

						$request = WPAPI::post( '/wp-json/wpshop/v2/create/propal/', $data, 'POST' );

						$wpshop_object = new wpshop_object($this->db);
						$wpshop_object->wp_id = isset( $request ) ? $request['data']['id'] : $object->wp_id;
						$wpshop_object->doli_id = $object->id;
						$wpshop_object->type = "propal";
						$wpshop_object->sync_date = $sync_date;
						$wpshop_object->last_sync_date = $sync_date;
						$wpshop_object->create($user);
					}
				}
			break;

			case 'PROPAL_CLASSIFY_BILLED':
				 $wpshop_object = new wpshop_object( $this->db );
				 $propal = $wpshop_object->fetch( (int) $object->id, 'propal' );

				 if ( ! empty( $propal ) ) {
					 $propal->update( $user );

					 $request = WPAPI::post( '/wp-json/wpshop/v1/proposal/' . (int) $propal->wp_id, array(
						 'status' => 'wps-billed',
						 'billed' => 1,
					 ), 'PUT' );
				 }
				 break;

			case 'PROPAL_CLASSIFY_UNBILLED':
				 $wpshop_object = new wpshop_object( $this->db );
				 $propal = $wpshop_object->fetch( (int) $object->id, 'propal' );

				 if ( ! empty( $propal ) ) {
					 $propal->update( $user );

					 $request = WPAPI::post( '/wp-json/wpshop/v1/proposal/' . (int) $propal->wp_id, array(
						 'status' => 'publish',
						 'billed' => 0,
					 ), 'PUT' );
				 }
				 break;
		
			case 'PROPAL_REOPEN':
				$wpshop_object = new wpshop_object( $this->db );
				$order = $wpshop_object->fetch( (int) $object->id, 'propal' );

				if ( ! empty( $order ) ) {
					$order->update( $user );

					$request = WPAPI::post( '/wp-json/wpshop/v1/proposal/' . (int) $order->wp_id, array(
						'status' => 'publish',
					), 'PUT' );

				}
				break;
			//case 'PROPAL_SENTBYMAIL':
			case 'PROPAL_CLOSE_SIGNED':
				$wpshop_object = new wpshop_object( $this->db );
				$propal = $wpshop_object->fetch( (int) $object->id, 'propal' );

				if ( ! empty( $propal ) ) {
					$propal->update( $user );

					$request = WPAPI::post( '/wp-json/wpshop/v1/proposal/' . (int) $propal->wp_id, array(
						'status' => 'wps-accepted',
					), 'PUT' );
				}
				break;
			case 'PROPAL_CLOSE_REFUSED':
				$wpshop_object = new wpshop_object( $this->db );
				$propal = $wpshop_object->fetch( (int) $object->id, 'propal' );

				if ( ! empty( $propal ) ) {
					$propal->update( $user );

					$request = WPAPI::post( '/wp-json/wpshop/v1/proposal/' . (int) $propal->wp_id, array(
						'status' => 'wps-refused',
					), 'PUT' );
				}
				break;
			default:
		        dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
		        break;
		    }

		return 0;
	}
}
