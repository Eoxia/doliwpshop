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
 * \file    wpshop/class/actions_wpshop.class.php
 * \ingroup wpshop
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

dol_include_once('/wpshop/class/wpshop_product.class.php');


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



	function addMoreActionsButtons( $parameters, &$object, &$action ) {
		global $conf;
		
		$wpshop_product = new wpshop_product($this->db);
		$product = $wpshop_product->fetch( (int) $object->id );
		?>
		<div class="inline-block divButAction"><a class="butAction" target="_blank" href="<?php echo $conf->global->WPSHOP_URL_WORDPRESS . '/wp-admin/post.php?post=' . $product->wp_product . '&action=edit'; ?>">Edit on WordPress</a></div>
		<?php
		return 0;
	}
}
