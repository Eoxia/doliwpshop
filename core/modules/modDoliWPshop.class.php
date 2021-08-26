<?php
/* Copyright (C) 2019-2020 Eoxia <dev@eoxia.com>
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
 *  \defgroup   doliwpshop     Module DoliWPshop
 *  \brief      DoliWPshop module descriptor.
 *
 *  \file       htdocs/custom/doliwpshop/core/modules/modDoliWPshop.class.php
 *  \ingroup    doliwpshop
 *  \brief      Description and activation file for module DoliWPshop
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module DoliWPshop
 */
class modDoliWPshop extends DolibarrModules {
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param   DoliDB  $db  Database handler
	 */
	public function __construct( $db ) {
		global $langs, $conf;

		$this->db = $db;

		$this->numero          = 800000;
		$this->rights_class    = 'doliwpshop';
		$this->family          = "Connectors";
		$this->module_position = '90';
		$this->name            = preg_replace( '/^mod/i', '', get_class( $this ) );
		$this->description     = $langs->trans("ModuleDoliWPshopDesc");
		$this->descriptionlong = $langs->trans("ModuleDoliWPshopDescLong");
		$this->editor_name     = 'Eoxia';
		$this->editor_url      = 'https://eoxia.com';
		$this->version         = '1.2.0';
		$this->const_name      = 'MAIN_MODULE_' . strtoupper( $this->name );
		$this->picto           = 'doliwpshop@doliwpshop';

		$this->module_parts = array(
			'triggers'          => 1,
			'login'             => 0,
			'substitutions'     => 0,
			'menus'             => 0,
			'theme'             => 0,
			'tpl'               => 0,
			'barcode'           => 0,
			'models'            => 0,
			'moduleforexternal' => 0,
			'hooks'             => array(
				'productcard',
				'thirdpartycard',
				'categorycard',
				'producttranslationcard',
			)
		);

		$this->dirs = array("/doliwpshop/temp");

		// Config pages.
		$this->config_page_url = array("doliwpshop.php@doliwpshop");

		// Dependencies
		$this->hidden       = false;
		$this->depends      = array("modSociete","modPropale","modCommande","modFacture","modBanque","modProduct","modService","modStock","modAgenda","modCategorie","modApi","modPaypal","modStripe");
		$this->requiredby   = array();
		$this->conflictwith = array();
		$this->langfiles    = array("doliwpshop@doliwpshop");
		$this->phpmin                  = array(5, 4);
		$this->need_dolibarr_version   = array(4, 0);
		$this->warnings_activation     = array();
		$this->warnings_activation_ext = array();
		//$this->automatic_activation = array('FR'=>'WPshopWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		if ( ! isset( $conf->doliwpshop ) || ! isset( $conf->doliwpshop->enabled ) ) {
			$conf->doliwpshop          = new stdClass();
			$conf->doliwpshop->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		
		// Permissions provided by this module
		$this->rights = array();

		$r                     = 0;
		$this->rights[$r][0] = $this->numero.$r;    // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans("ReadRightDoliWPshop");    // Permission label
		$this->rights[$r][3] = 1;                    // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';                // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)
		$this->rights[$r][5] = '';                    // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero.$r;    // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans("CreateRightDoliWPshop");    // Permission label
		$this->rights[$r][3] = 1;                    // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';                // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)
		$this->rights[$r][5] = '';                    // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero.$r;    // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans("DeleteRightDoliWPshop");    // Permission label
		$this->rights[$r][3] = 1;                    // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';                // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)
		$this->rights[$r][5] = '';                    // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)

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
		global $conf, $langs;

		// Translations
		$langs->load("doliwpshop@doliwpshop");

		$this->_load_tables('/doliwpshop/sql/');

		if ( $conf->global->DOLIWPSHOP_USERAPI_SET ==  0 ) {
			require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

			$user = new User($this->db);
			$user->lastname  = 'API';
			$user->firstname = 'REST';
			$user->login     = 'USERAPI';
			$user->setPassword($user, 'test');
			$user->api_key = getRandomPassword(true);

			$user_id = $user->create($user);

			dolibarr_set_const($this->db, 'DOLIWPSHOP_USERAPI_SET', $user_id, 'integer', 0, '', $conf->entity);
		}

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extra_fields = new ExtraFields( $this->db );
		
		$extra_fields->addExtraField( '_wps_status', $langs->trans("WPshopStatus"), 'select', 999, '', 'product', 0, 0, 'publish', array('options' => array('publish'=> 'publish', 'draft' => 'draft') ) );
		$extra_fields->addExtraField( '_wps_id', 'WPshop ID', 'int', 1000, '', 'product', 1, 0,'','', 0,'','1' );

		$extra_fields->addExtraField( 'firstname', 'Firstname', 'varchar', 2, '255', 'thirdparty' );
		$extra_fields->addExtraField( '_wps_id', 'WPshop ID', 'int', 1000, '', 'thirdparty', 1, 0,'','', 0,'','1' );

		$extra_fields->addExtraField( '_wps_id', 'WPshop ID', 'int', 1000, '', 'categorie', 1, 0,'','', 0,'','1' );
		$extra_fields->addExtraField( '_wps_slug', 'WPshop Slug', 'varchar', 1001, '', 'categorie', 2, 0,'','', 0,'','1' );

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
	 */
	public function remove( $options = '' ) {
		return $this->_remove(null);
	}
}
