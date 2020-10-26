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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/main.inc.php';

require_once DOL_DOCUMENT_ROOT .'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

require_once DOL_DOCUMENT_ROOT.'/product/class/api_products.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_thirdparties.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/api_categories.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_contacts.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/api_orders.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/api_proposals.class.php';

/**
 * \file     htdocs/custom/doliwpshop/class/api_doliwpshop.class.php
 * \ingroup doliwpshop
 * \brief   File for API management of myobject.
 */

/**
 * API class for doliwpshop myobject
 *
 * @property DoliDB db
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class DoliWPshop extends DolibarrApi
{
	/**
	 * @var Product
	 */
	private $product;

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
		$this->product = new Product($this->db);
		$this->societe = new Societe($this->db);
		$this->category = new Categorie($this->db);
	}

	/**
	 * @param integer $wp_id
	 * @param integer $doli_id
	 *
	 * @url GET /associateProduct
	 */
	public function associateProduct($wp_id, $doli_id) {
		$result = $this->product->fetch($doli_id);

		if (!$result) {
			throw new RestException(404, 'Product not found');
		}

		if (!DolibarrApi::_checkAccessToResource('product', $this->product->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$oldproduct = dol_clone($this->product, 0);

		$this->product->array_options['options__wps_id'] = $wp_id;

		$updatetype = false;

		if ($this->product->type != $oldproduct->type && ($this->product->isProduct() || $this->product->isService())) {
			$updatetype = true;
		}

		$result = $this->product->update($doli_id, DolibarrApiAccess::$user, 1, 'update', $updatetype);

		return $result;
	}

	public function associateCategory($wp_id, $doli_id) {
		$result = $this->category->fetch($doli_id);
		
		if (!$result) {
			throw new RestException(404, 'Category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('category', $this->category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$oldcategory = dol_clone($this->category, 0);

		$this->category->array_options['options__wps_id'] = $wp_id;

		$updatetype = false;

		if ($this->category->type != $oldcategory->type && ($this->category->isCategory())) {
			$updatetype = true;
		}

		$result = $this->category->update($doli_id, DolibarrApiAccess::$user, 1, 'update', $updatetype);

		return $result;
	}

	/**
	 * @param integer $wp_id
	 * @param integer $doli_id
	 *
	 * @url GET /associateThirdparty
	 */
	public function associateThirdparty($wp_id, $doli_id) {
		$result = $this->societe->fetch($doli_id);

		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $this->societe->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$oldsociety = dol_clone($this->societe, 0);

		$this->societe->array_options['options__wps_id'] = $wp_id;

		$updatetype = false;

		if ($this->societe->type != $oldsociety->type) {
			$updatetype = true;
		}

		$result = $this->societe->update($doli_id, DolibarrApiAccess::$user, 1, 'update', $updatetype);

		return $result;
	}
}


