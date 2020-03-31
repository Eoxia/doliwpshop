<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019 SuperAdmin
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/main.inc.php';

require_once DOL_DOCUMENT_ROOT .'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';

require_once DOL_DOCUMENT_ROOT.'/product/class/api_products.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_thirdparties.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_contacts.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/api_orders.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/api_proposals.class.php';

/**
 * \file    wpshop/class/api_wpshop.class.php
 * \ingroup wpshop
 * \brief   File for API management of myobject.
 */

/**
 * API class for wpshop myobject
 *
 * @property DoliDB db
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Wpshop extends DolibarrApi
{
    /**
     * Constructor
     *
     * @url     GET /
     *
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
    }

	/**
	 *  Associate WP with a product object
	 *
	 * @param   array  $request_data  Request data
	 *
	 * @return mixed
	 *
	 * @url    POST object
	 * @throws RestException
	 */
    function post($request_data = null)
    {
		if (! DolibarrApiAccess::$user->rights->wpshop->write) {
			throw new RestException(401);
		}

		// @todo: Use result for check if request_data is valid.
		$result = $this->_validate($request_data);

		$class = null;

	    // @todo: Create const for type.
		switch( $request_data['type'] ) {
			case 'wps-product':
				$class = new Products();
				$request_data['array_options']['web'] = 1;

				$object = $class->get( $this->wpshop_connector->doliid );

				$data_sha['doli_id']   = $this->wpshop_connector->doliid;
				$data_sha['wp_id']     = $this->wpshop_connector->wpid;
				$data_sha['label']     = $object->label;
				$data_sha['price']     = (float) $object->price;
				$data_sha['price_ttc'] = (float) $object->price_ttc;
				$data_sha['tva_tx']    = (float) $object->tva_tx;
				break;
			case 'wps-proposal':
				$class = new Proposals();

				if ( ! empty( $this->wpshop_connector->doliid ) ) {
					$object = $class->get( $this->wpshop_connector->doliid );

					$data_sha['doli_id']   = (int) $this->wpshop_connector->doliid;
					$data_sha['wp_id']     = (int) $this->wpshop_connector->wpid;
					$data_sha['ref']       = $object->ref;
					$data_sha['total_ht']  = (float) $object->total_ht;
					$data_sha['total_ttc'] = (float) $object->total_ttc;
				}
				break;
			case 'wps-third-party':
				$class = new Thirdparties();

				$object = $class->get( $this->wpshop_connector->doliid );
				$object->db = $this->db;

				$data_sha['doli_id'] = (int) $this->wpshop_connector->doliid;
				$data_sha['wp_id']   = (int) $this->wpshop_connector->wpid;
				$data_sha['title']   = $object->name;
				$data_sha['address'] = $object->address;
				$data_sha['town']    = $object->town;
				$data_sha['zip']     = $object->zip;
				$data_sha['state']   = $object->state;
				$data_sha['country'] = $object->country;
				$data_sha['town']    = $object->town;
				$data_sha['phone']   = $object->phone;
				$data_sha['email']   = $object->email;

				/*$contacts = $object->contact_array_objects();

				if ( ! empty( $contacts ) ) {
					foreach ( $contacts as $key => $contact ) {
						// @todo: L'email est que du coté de WPShop quand elle est généré par WPShop car inexistante dans dolibarr.
						$data_sha['contacts_' . $key . '_doli_id'] = $contact->id;
						$data_sha['contacts_' . $key . '_doli_third_party_id'] = $contact->socid;
						$data_sha['contacts_' . $key . '_wp_third_party_id'] = (int) $this->wpshop_connector->wp_id;
						$data_sha['contacts_' . $key . '_lastname'] = $contact->lastname;
						$data_sha['contacts_' . $key . '_firstname'] = $contact->firstname;
						$data_sha['contacts_' . $key . '_phone_pro'] = $contact->phone_pro;
						$data_sha['contacts_' . $key . '_phone_mobile'] = $contact->phone_mobile;
					}
				}*/

				break;
			case 'wps-user':
				$class = new Contacts();

				if ( ! empty( $this->wpshop_connector->doliid ) ) {
					$object = $class->get( $this->wpshop_connector->doliid );

					$data_sha['doli_id'] = (int) $this->wpshop_connector->doliid;
					$data_sha['wp_id']   = (int) $this->wpshop_connector->wpid;
					$data_sha['lastname']     = $object->lastname;
					$data_sha['phone']        = $object->phone;
					$data_sha['phone_mobile'] = $object->phone_mobile;
					$data_sha['email']        = $object->email;
				}
				break;
			default:
				break;
		}

	    $this->wpshop_connector->shadata = hash( 'sha256', implode( ',', $data_sha ) );

	    if ( empty( $class ) ) {
	    	// @todo: Status code.
			return array(
				'status' => false,
				'status_message' => 'This type ' . $request_data['type'] . ' is not authorized',
				'status_code' => '0x3'
			);
		}

	    $object = null;

		unset($request_data['type']);

		if (empty($request_data['doliid'])) {
			$this->wpshop_connector->doliid = $class->post( $request_data );
			$object = $class->get($this->wpshop_connector->doliid);

			$object->data_sha = $this->wpshop_connector->shadata;
		}

		$founded_object = $founded->fetch_exist((int) $this->wpshop_connector->doliid, (int) $this->wpshop_connector->wpid, $this->wpshop_connector->type);

		if (empty($founded_object) || $founded_object == -1) {
			$this->wpshop_connector->create(DolibarrApiAccess::$user);
			$founded_object = $founded->fetch_exist( (int) $this->wpshop_connector->doliid, $this->wpshop_connector->wpid, $this->wpshop_connector->type );
			$object = $founded_object;
			$status_message = 'Created new connector id ' . $founded_object->id;
		} else {
			$updated_object = new $class();
			$updated_object->put($request_data['doliid'], $request_data);

			$founded_object->shadata = hash( 'sha256', implode( ',', $data_sha ) );
			$founded_object->update(DolibarrApiAccess::$user, false, $statut );

			$object = $founded_object;
			$status_message = 'Updated connector id ' . $founded_object->id;
		}

		return array(
			'status' => true,
			'status_code' => '0x0',
			'status_message' => $status_message,
			'data' => $object,
		);
	}

	/**
	 *  Check SHA256 between WP Object and Doli Object for data consistency.
	 *
	 * @param $id
	 *
	 * @return array            True if SHA256 is equals. Otherwise false.
	 *
	 * @url    GET product/{$id}/checkSha
	 */
	public function product_check_status($id) {
		return true;
		if ($founded_object == 0 || $founded_object == -1) {
			// @todo: Const et voir avec LM pour message.
			return array(
				'status'         => false,
				'status_code'    => '0x1',
				'status_message' => 'No connector.'
			);
		}

		if ($sha256 === $founded_object->shadata) {
			return array(
				'status'         => true,
				'status_code'    => '0x0',
				'status_message' => 'Sync OK'
			);
		}

		return array(
			'status'         => true,
			'status_code'    => '0x2',
			'status_message' => 'SHA256 is not equal.'
		);
	}

	/**
	 *  Check User Status (With Third Party) and SHA256 consistency.
	 *
	 * @param $wp_contact_id
	 * @param $doli_contact_id
	 * @param $sha_contact
	 * @param $wp_third_party_id
	 * @param $doli_third_party_id
	 * @param $sha_third_party
	 *
	 * @return array            True if SHA256 is equals. Otherwise false.
	 *
	 * @url    GET getUserStatus
	 */
	public function get_user_status($wp_contact_id, $doli_contact_id, $sha_contact, $wp_third_party_id, $doli_third_party_id, $sha_third_party) {
		// @todo: Vérifie que le contact que doli est bien relié au third_party_id.

		$founded        = new WPShopConnector($this->db);
		$founded_contact = $founded->fetch_exist($doli_contact_id, $wp_contact_id, 'wps-user');

		if ($founded_contact == 0 || $founded_contact == -1) {
			return array(
				'status'         => false,
				'status_code'    => '0x12',
				'status_message' => 'No connector with WP Contact and Doli Contact in llx_wpshop_connector.'
			);
		}

		if ($sha_contact !== $founded_contact->shadata) {
			return array(
				'status'         => false,
				'status_code'    => '0x13',
				'status_message' => 'SHA256 is not equal for WP Contact and Doli Contact.'
			);
		}

		$founded_third_party = $founded->fetch_exist($doli_third_party_id, $wp_third_party_id, 'wps-third-party');

		if ($founded_third_party == 0 || $founded_third_party == -1) {
			return array(
				'status'         => false,
				'status_code'    => '0x14',
				'status_message' => 'No connector with WP Third Party and Doli Third Party in llx_wpshop_connector.'
			);
		}

		if ($sha_third_party !== $founded_third_party->shadata) {
			return array(
				'status'         => false,
				'status_code'    => '0x15',
				'status_message' => 'SHA256 is not equal for WP Third Party and Doli Third Party.'
			);
		}

		return array(
			'status'         => true,
			'status_code'    => '0x0',
			'status_message' => 'Sync OK'
		);
	}

	/**
	 * Update product and sync date
	 *
	 * @param   int    $id            ID of product
	 * @param   array  $request_data  [description]
	 *
	 * @url PUT object/{id}
	 * @throws RestException
	 */
	function put($id, $request_data = null) {
		if (! DolibarrApiAccess::$user->rights->wpshop->write) {
			throw new RestException(401);
		}

		$result = $this->wpshop_connector->fetch($id);

		foreach($request_data as $field => $value) {
			$this->wpshop_connector->$field = $value;
		}

		$products = new Products();
		$product = $products->put($id, $request_data);

		$last_sync_date = $this->wpshop_connector->update(DolibarrApiAccess::$user, false, $statut );

		if ( $statut == -1 ) {
			$last_sync_date = $this->wpshop_connector->create(DolibarrApiAccess::$user);
		}

		$product->last_sync_date = $last_sync_date;
		return $product;
	}

	/**
	 * Get single product
	 *
	 * @param  int $id ID of product
	 * @return object
	 *
	 * @url GET productsonweb/{id}
	 */
	function get_product_advanced( $id ) {
		global $db, $conf;

		$obj_ret = array();

		$socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

		$sql = "SELECT t.rowid, t.ref, t.ref_ext, pac.fk_product_parent";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as t";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination as pac";
		$sql.= " ON pac.fk_product_child=t.rowid";
		$sql.= ", ".MAIN_DB_PREFIX."product_extrafields as pf";
		$sql.= ' WHERE t.entity IN ('.getEntity('product').')';
		$sql.= ' AND pf.fk_object=t.rowid AND pf.web=1';
		$sql .= " AND t.rowid = " . $db->escape( $id );

		// Add sql filters
		if ($sqlfilters) {
			if (! DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			echo "<pre>"; print_r( preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) ); echo "</pre>";exit;
			$sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

		$sql.= $db->plimit(1, 0);
		
		$result = $db->query($sql);

		if ($result) {
			$obj = $db->fetch_object($result);

			$product_static = new Product($db);
			if($product_static->fetch($obj->rowid)) {
				$tmp_product = $this->_cleanObjectDatas( $product_static );

				$tmp_product->fk_product_parent = 0;

				if ( isset( $obj->fk_product_parent ) ) {
					$tmp_product->fk_product_parent = $obj->fk_product_parent;
				}

				$obj_ret = $tmp_product;
			}
		}
		else {
			throw new RestException(503, 'Error when retrieve product list : '.$db->lasterror());
		}

		return $obj_ret;
	}
		
	/**
     * List products
     *
     * Get a list of products
     *
     * @param  string $sortfield  Sort field
     * @param  string $sortorder  Sort order
     * @param  int    $limit      Limit for list
     * @param  int    $page       Page number
     * @param  int    $mode       Use this param to filter list (0 for all, 1 for only product, 2 for only service)
     * @param  int    $category   Use this param to filter list by category
     * @param  string $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.tobuy:=:0) and (t.tosell:=:1)"
     * @return array                Array of product objects
     *
	 * @url GET productsonweb
     */
    function index($sortfield = "t.ref", $sortorder = 'ASC', $limit = 100, $page = 0, $mode = 0, $category = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        $sql = "SELECT t.rowid, t.ref, t.ref_ext, pac.fk_product_parent";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as t";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination as pac";
        $sql.= " ON pac.fk_product_child=t.rowid";
		$sql.= ", ".MAIN_DB_PREFIX."product_extrafields as pf";
        if ($category > 0) {
            $sql.= ", ".MAIN_DB_PREFIX."categorie_product as c";
        }

        $sql.= ' WHERE t.entity IN ('.getEntity('product').')';
				$sql.= ' AND pf.fk_object=t.rowid AND pf.web=1';
        // Select products of given category
        if ($category > 0) {
            $sql.= " AND c.fk_categorie = ".$db->escape($category);
            $sql.= " AND c.fk_product = t.rowid ";
        }
        if ($mode == 1) {
            // Show only products
            $sql.= " AND t.fk_product_type = 0";
        } elseif ($mode == 2) {
            // Show only services
            $sql.= " AND t.fk_product_type = 1";
        }

        // Add sql filters
        if ($sqlfilters) {
            if (! DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            echo "<pre>"; print_r( preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) ); echo "</pre>";exit;
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);

        if ($result) {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i = 0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $product_static = new Product($db);
                if($product_static->fetch($obj->rowid)) {
                	$tmp_product = $this->_cleanObjectDatas($product_static);

                	$tmp_product->fk_product_parent = 0;

                	if ( isset( $obj->fk_product_parent ) ) {
                		$tmp_product->fk_product_parent = $obj->fk_product_parent;
	                }

                    $obj_ret[] = $tmp_product;

                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve product list : '.$db->lasterror());
        }
        if(! count($obj_ret)) {
            throw new RestException(404, 'No product found');
        }
        return $obj_ret;
    }

	/**
	 * Get attribute by product
	 *
	 *
	 * @param  integer $id  Sort field
	 * @return array                Array of attributes objects
	 *
	 * @url GET product/attribute
	 */
    function get_attribute_by_product( $id ) {
    	global $db;

	    $combination = new ProductCombination($db);
	    return $combination->getUniqueAttributesAndValuesByFkProductParent( $id );
    }

	/**
	 *  Associate WP with a product object
	 *
	 * @param array $request_data   Request datas
	 * @return array
	 *
	 * @url	POST object/get/child
	 */
    function get_by_attribute_and_parent( $request_data = null ) {
	    global $db, $conf;

	    $combination = new ProductCombination($db);

		foreach( $request_data['data'] as $key => $val ) {
			if ( $val == -1 ) {
				unset( $request_data['data'][ $key ] );
			}
		}

	    return $combination->fetchByProductCombination2ValuePairs($request_data['product_id'], $request_data['data']);
    }

    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {
    	$object = parent::_cleanObjectDatas($object);
    	return $object;
    }

	/**
	 * Validate fields before create or update object
	 *
	 * @param   array  $data  Array of data to validate
	 *
	 * @return bool
	 * @todo: Secure request_data.
	 *
	 */
    private function _validate($data)
    {
        return true;
    }
		
}
