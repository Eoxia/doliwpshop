<?php
/* Copyright (C) 2019-2020 Eoxia <technique@eoxia.com>
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

/**
 *    \defgroup   wpshop     Module DolibarrModuleWpshop
 *  \brief      WPshop module descriptor.
 *
 *  \file       htdocs/wpshop/core/modules/modWPshop.class.php
 *  \ingroup    wpshop
 *  \brief      Description and activation file for module WPshop
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';


/**
 *  Description and activation class for module WPshop
 */
class modWpshop extends DolibarrModules {
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param   DoliDB  $db  Database handler
	 */
	public function __construct( $db ) {
		global $langs, $conf;

		$this->db = $db;

		$this->numero          = 800000;
		$this->rights_class    = 'wpshop';
		$this->family          = "Connectors";
		$this->module_position = '90';
		$this->name            = preg_replace( '/^mod/i', '', get_class( $this ) );
		$this->description     = 'Module for Dolibarr ERP/CRM to connect WPshop Plugin from WordPress.';
		$this->descriptionlong = 'Module for Dolibarr ERP/CRM to connect WPshop Plugin from WordPress.';
		$this->editor_name     = 'Eoxia';
		$this->editor_url      = 'https://eoxia.com';
		$this->version         = '0.1.0';
		$this->const_name      = 'MAIN_MODULE_' . strtoupper( $this->name );
		$this->picto           = 'generic';

		$this->module_parts = array(
			'triggers'          => 1,
			'login'             => 0,
			'substitutions'     => 1,
			'menus'             => 0,
			'theme'             => 0,
			'tpl'               => 0,
			'barcode'           => 0,
			'models'            => 0,
			'moduleforexternal' => 0,
			'hooks'             => array(
				'productcard',
			)
		);

		$this->dirs = array( "/wpshop/temp" );

		// Config pages. Put here list of php page, stored into wpshop/admin directory, to use to setup module.
		$this->config_page_url = array( "setup.php@wpshop" );

		// Dependencies
		$this->hidden       = false;            // A condition to hide module
		$this->depends      = array();        // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->requiredby   = array();    // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array();    // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->langfiles    = array( "wpshop@wpshop" );
		//$this->phpmin = array(5,4);					// Minimum version of PHP required by module
		$this->need_dolibarr_version   = array( 4, 0 );        // Minimum version of Dolibarr required by module
		$this->warnings_activation     = array();            // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();        // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'WPshopWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		if ( ! isset( $conf->wpshop ) || ! isset( $conf->wpshop->enabled ) ) {
			$conf->wpshop          = new stdClass();
			$conf->wpshop->enabled = 0;
		}


		// Array to add new pages in new tabs
		$this->tabs = array();

		// Permissions
		$this->rights = array();        // Permission array used by this module

		$r                     = 0;
		$this->rights[ $r ][0] = $this->numero + $r;    // Permission id (must not be already used)
		$this->rights[ $r ][1] = 'Read myobject of WPshop';    // Permission label
		$this->rights[ $r ][3] = 1;                    // Permission by default for new user (0/1)
		$this->rights[ $r ][4] = 'read';                // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)
		$this->rights[ $r ][5] = '';                    // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)

		$r ++;
		$this->rights[ $r ][0] = $this->numero + $r;    // Permission id (must not be already used)
		$this->rights[ $r ][1] = 'Create/Update myobject of WPshop';    // Permission label
		$this->rights[ $r ][3] = 1;                    // Permission by default for new user (0/1)
		$this->rights[ $r ][4] = 'write';                // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)
		$this->rights[ $r ][5] = '';                    // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)

		$r ++;
		$this->rights[ $r ][0] = $this->numero + $r;    // Permission id (must not be already used)
		$this->rights[ $r ][1] = 'Delete myobject of WPshop';    // Permission label
		$this->rights[ $r ][3] = 1;                    // Permission by default for new user (0/1)
		$this->rights[ $r ][4] = 'delete';                // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)
		$this->rights[ $r ][5] = '';                    // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)

		// Main menu entries
		$this->menu = array();            // List of menus to add
	}

	/**
	 *    Function called when module is enabled.
	 *    The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *    It also creates data directories
	 *
	 * @param   string  $options  Options when enabling module ('', 'noboxes')
	 *
	 * @return     int                1 if OK, 0 if KO
	 */
	public function init( $options = '' ) {
		$extra_fields = new ExtraFields( $this->db );
		$extra_fields->addExtraField( 'web', 'On the web', 'boolean', 999, '', 'product' );
		$extra_fields->addExtraField( '_wps_id', 'WPShop ID', 'int', 1000, '', 'product', 0, 0,'','', 0,'','0' );

		return $this->_init(null);
	}

	/**
	 *    Function called when module is disabled.
	 *    Remove from database constants, boxes and permissions from Dolibarr database.
	 *    Data directories are not deleted
	 *
	 * @param   string  $options  Options when enabling module ('', 'noboxes')
	 *
	 * @return     int                1 if OK, 0 if KO
	 *
	 * @todo: We have to remove extrafields or not ?
	 */
	public function remove( $options = '' ) {
		return $this->_remove(null);
	}
}
