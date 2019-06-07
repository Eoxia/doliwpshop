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

dol_include_once('/wpshop/class/wpshop_product.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/api_products.class.php';



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
class WpshopApi extends DolibarrApi
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
			$this->wpshop_object = new wpshop_product($this->db);
    }

    /**
     *  Associate WP with a product object
     *
     * @param array $request_data   Request datas
     * @return int  ID of myobject
     *
     * @url	POST associate/product
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
			
			// Le produit existe pas sur Dolibarr le créer
			$products = new Products();
			$request_data['array_options']['web'] = 1;
			$product = $products->post($request_data);
			$product = $products->get($product);
			$product->last_sync_date = $last_sync_date;
			
			return $product;
    }

		/**
		 * Update product and sync date
		 *
		 * @param  int $id             ID of product
		 * @param  array $request_data [description]
		 *
		 * @url PUT update/product/{id}
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
		 * @url GET product/get/web
     */
    function index($sortfield = "t.ref", $sortorder = 'ASC', $limit = 100, $page = 0, $mode = 0, $category = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        $sql = "SELECT t.rowid, t.ref, t.ref_ext";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as t";
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
                    $obj_ret[] = $this->_cleanObjectDatas($product_static);
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
