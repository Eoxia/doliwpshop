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

dol_include_once('/wpshop/class/wpshop_object.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/api_products.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_thirdparties.class.php';
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
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Wpshop extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array();

	public $wpshop_object;
		
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
		$this->wpshop_object = new wpshop_object($this->db);
    }

    /**
     *  Associate WP with a product object
     *
     * @param array $request_data   Request datas
     * @return int  ID of myobject
     *
     * @url	POST object
     */
    function post($request_data = null)
    {
		if (! DolibarrApiAccess::$user->rights->wpshop->write) {
			throw new RestException(401);
		}

		$result = $this->_validate($request_data);

		foreach($request_data as $field => $value) {
			$this->wpshop_object->$field = $value;
		}

		$class = null;

		$data_sha = array();

		switch( $request_data['type'] ) {
			case 'product':
				$class = new Products();
				$request_data['array_options']['web'] = 1;

				$object = $class->get( $this->wpshop_object->doli_id );

				$data_sha['doli_id']   = $this->wpshop_object->doli_id;
				$data_sha['wp_id']     = $this->wpshop_object->wp_id;
				$data_sha['label']     = $object->label;
				$data_sha['price']     = (float) $object->price;
				$data_sha['price_ttc'] = (float) $object->price_ttc;
				$data_sha['tva_tx']    = (float) $object->tva_tx;

				break;
			case 'propal':
				$class = new Proposals();

				$object = $class->get( $this->wpshop_object->doli_id );

				break;
			case 'order':
				$class = new Orders();

				$object = $class->get( $this->wpshop_object->doli_id );

				$request_data['date_commande'] = dol_now();
				break;
			case 'third_party':
				$class = new Thirdparties();

				$object = $class->get( $this->wpshop_object->doli_id );

				break;
			case 'contact':
			case 'invoice':
			case 'payment':
				$class = 'tmp';
				break;
			default:
				break;
		}

	    $this->wpshop_object->shadata = hash( 'sha256', implode( ',', $data_sha ) );

	    if ( empty( $class ) ) {
			return null;
		}

		unset( $request_data['type'] );

		/*if ( empty( $request_data['doli_id'] ) ) {
			$this->wpshop_object->doli_id = $class->post( $request_data );
		}*/

		$founded = new wpshop_object( $this->db );
		$founded_object = $founded->fetch_exist( (int) $this->wpshop_object->doli_id, $this->wpshop_object->wp_id, $this->wpshop_object->type );
		if ( empty( $founded_object ) || $founded_object == -1 ) {
			$this->wpshop_object->create(DolibarrApiAccess::$user);
		} else {
			if ( $class != 'tmp' ) {

				$products = new $class();

				$product = $products->put($request_data['doli_id'], $request_data);
			}

			$founded->update(DolibarrApiAccess::$user, false, $statut );
		}

		if ( ! empty( $class ) && $class != 'tmp' ) {
			$object->data_sha = $this->wpshop_object->shadata;
		} else {
			$object = new stdClass();
			$object->data_sha = $this->wpshop_object->shadata;
		}

		return $object;
	}
		
	/**
     *  Associate WP with a product object
     *
     * @param array $request_data   Request datas
     * @return int  ID of myobject
     *
     * @url	POST object/statut
     */
	public function post_check_statut($request_data = null) {
		$founded = new wpshop_object( $this->db );
		$founded_object = $founded->fetch_exist( (int) $request_data['doli_id'], $request_data['wp_id'], $request_data['type'] );

		if ( $request_data['sha_256'] === $founded_object->shadata ) {
			return true;
		}

		return false;
	}

	/**
	 * Update product and sync date
	 *
	 * @param  int $id             ID of product
	 * @param  array $request_data [description]
	 *
	 * @url PUT object/{id}
	 */
	function put($id, $request_data = null) {
		if (! DolibarrApiAccess::$user->rights->wpshop->write) {
			throw new RestException(401);
		}

		$result = $this->wpshop_object->fetch($id);

		foreach($request_data as $field => $value) {
			$this->wpshop_object->$field = $value;
		}

		$products = new Products();
		$product = $products->put($id, $request_data);

		$last_sync_date = $this->wpshop_object->update(DolibarrApiAccess::$user, false, $statut );

		if ( $statut == -1 ) {
			$last_sync_date = $this->wpshop_object->create(DolibarrApiAccess::$user);
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
	 * @url GET products
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
	 * @url GET object/get/web
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
     * @param	array		$data   Array of data to validate
     * @return	array
     *
     * @throws	RestException
     */
    private function _validate($data)
    {
        $myobject = array();
        foreach (self::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $myobject[$field] = $data[$field];
        }
        return $myobject;
    }
		
}
