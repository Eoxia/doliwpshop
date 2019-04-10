<?php
/* Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018	   Nicolas ZABOURI 	<info@inovea-conseil.com>
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

/**
 * 	\defgroup   wpshop     Module Wpshop
 *  \brief      Wpshop module descriptor.
 *
 *  \file       htdocs/wpshop/core/modules/modWpshop.class.php
 *  \ingroup    wpshop
 *  \brief      Description and activation file for module Wpshop
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module Wpshop
 */
class modWpshop extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs,$conf;

		$this->db = $db;

		$this->numero = 500000;		// TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
		$this->rights_class = 'wpshop';
		$this->family = "other";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "WPshopDescription";
		$this->descriptionlong = "WPshop description (Long)";
		$this->editor_name = 'Eoxia';
		$this->editor_url = 'https://eoxia.com';
		$this->version = '0.1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='generic';
		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 1,
			'menus' => 0,
			'theme' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'css' => array('/wpshop/css/wpshop.css.php'),
			'js' => array('/wpshop/js/wpshop.js.php'),
			'hooks' => array('data' => array('productcard')),
			'moduleforexternal' => 0
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/wpshop/temp","/wpshop/subdir");
		$this->dirs = array("/wpshop/temp");

		// Config pages. Put here list of php page, stored into wpshop/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@wpshop");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->requiredby = array();	// List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->langfiles = array("wpshop@wpshop");
		//$this->phpmin = array(5,4);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(4,0);		// Minimum version of Dolibarr required by module
		$this->warnings_activation = array();			// Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();		// Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'WpshopWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		if (! isset($conf->wpshop) || ! isset($conf->wpshop->enabled))
		{
			$conf->wpshop=new stdClass();
			$conf->wpshop->enabled=0;
		}


		// Array to add new pages in new tabs
		$this->tabs = array();

		// Permissions
		$this->rights = array();		// Permission array used by this module

		$r=0;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Read myobject of Wpshop';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update myobject of Wpshop';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';				// In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete myobject of Wpshop';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';				// In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->wpshop->level1->level2)

		// Main menu entries
		$this->menu = array();			// List of menus to add
	}

	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories
	 *
     *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function init($options='')
	{
		$result=$this->_load_tables('/wpshop/sql/');
		if ($result < 0) return -1; // Do not activate module if not allowed errors found on module SQL queries (the _load_table run sql with run_sql with error allowed parameter to 'default')

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
