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
			
			$last_sync_date = $this->wpshop_object->update(DolibarrApiAccess::$user);
			$product->last_sync_date = $last_sync_date;
			return $product;
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
