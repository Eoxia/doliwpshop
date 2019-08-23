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

dol_include_once('/wpshop/class/wpshop_object.class.php');


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
		
		$type = '';
		
		switch( $parameters['currentcontext'] ) {
			case 'propalcard':
				$type = 'propal';
				break;
			case 'invoicecard':
				$type = 'invoice';
				break;
			case 'productcard':
				$type = 'product';
				break;
			case 'ordercard':
				$type = 'order';
				break;
			case 'paiementcard':
				$type = 'payment';
				break;
		}
		
		$wpshop_object = new wpshop_object($this->db);
		$wpshop_object = $wpshop_object->fetch( (int) $object->id, $type );
		
		$route = '/wp-admin/post.php?post=';
		
		switch ( $wpshop_object->type ) {
			case 'product':
				break;
			case 'propal':
				$route = '/wp-admin/admin.php?page=wps-proposal&id=';
				break;
			case 'order':
				$route = '/wp-admin/admin.php?page=wps-order&id=';
				break;
			case 'invoice':
				$route = '/wp-admin/admin.php?page=wps-invoice&id=';
				break;
		}
		
		if ( is_object( $wpshop_object ) ) {
			?>
			<div class="inline-block divButAction"><a class="butAction" target="_blank" href="<?php echo $conf->global->WPSHOP_URL_WORDPRESS . $route . $wpshop_object->wp_id . '&action=edit'; ?>">Edit on WordPress</a></div>
			<?php
		} else {
			?>
			<div class="inline-block divButAction"><a class="butActionRefused" title="Non disponible car l'objet n'est pas associé à WordPress" href="#">Edit on WordPress</a></div>
			<?php
		}
		
		return 0;
	}
}
