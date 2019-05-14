<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        htdocs/modulebuilder/template/class/myobject.class.php
 * \ingroup     mymodule
 * \brief       This file is a CRUD class file for MyObject (Create/Read/Update/Delete)
 */
// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class for MyObject
 */
class wpshop_product extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'wpshop_product';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'wpshop_product';

	/**
	 * @var int  Does myobject support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does myobject support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'myobject@mymodule';

	// BEGIN MODULEBUILDER PROPERTIES
	/**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
	public $fields=array(
	    'rowid'         =>array('type'=>'integer',      'label'=>'TechnicalID',      'enabled'=>1, 'visible'=>-2, 'noteditable'=>1, 'notnull'=> 1, 'index'=>1, 'position'=>1, 'comment'=>'Id'),
			'fk_product'    => array( 'type'=>'integer', 'label'=>'DolibarrProductID', 'enabled' => 1, 'visible' => -2, 'noteeditable' => 1, 'notnull' => 1),
			'wp_product'    => array( 'type'=>'integer', 'label'=>'WPProductID', 'enabled' => 1, 'visible' => -2, 'noteeditable' => 1, 'notnull' => 1),
			'sync_date' =>array('type'=>'datetime',     'label'=>'SyncDate',     'enabled'=>1, 'visible'=>-2, 'notnull'=> 1, 'position'=>500),
			'last_sync_date' =>array('type'=>'datetime',     'label'=>'LastDateSync',     'enabled'=>1, 'visible'=>-2, 'notnull'=> 1, 'position'=>500),
	);

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var string FK_Product
	 */
	public $fk_product;
	
	/**
	 * @var string FK_Product
	 */
	public $wp_product;

	/**
     * @var integer|string date_creation
     */
	public $sync_date;
	
	/**
		 * @var integer|string date_creation
		 */
	public $last_sync_date;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs, $user;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible']=0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']=0;

		// Unset fields that are disabled
		foreach($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		foreach($this->fields as $key => $val)
		{
			if (is_array($this->fields['status']['arrayofkeyval']))
			{
				foreach($this->fields['status']['arrayofkeyval'] as $key2 => $val2)
				{
					$this->fields['status']['arrayofkeyval'][$key2]=$langs->trans($val2);
				}
			}
		}
	}

	public function create( User $user, $notrigger = false ) {
		if ( empty( $this->sync_date ) ) {
			$this->sync_date = dol_now( 'tzserver' );
		}
		
		if ( empty( $this->last_sync_date ) ) {
			$this->last_sync_date = dol_now( 'tzserver' );
		}
		
		$this->createCommon($user, $notrigger);
		return $this->last_sync_date;
	}
	
	public function update(User $user, $notrigger = false) {
		$this->last_sync_date = dol_now( 'tzserver' );
		$this->updateCommon($user, $notrigger);
		return $this->last_sync_date;
	}
	
	public function fetch($id) {
		if (empty($id)) return -1;

		$sql = 'SELECT '.$this->getFieldList();
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;

		if (!empty($id))  $sql.= ' WHERE fk_product = '.$id;
		else $sql.=' WHERE 1 = 1';	// usage with empty id and empty ref is very rare
		$sql.=' LIMIT 1';	// This is a fetch, to be sure to get only one record
		$res = $this->db->query($sql);
		if ($res)
		{
			$obj = $this->db->fetch_object($res);
			if ($obj)
			{
				$this->setVarsFromFetchObj($obj);
				return $this;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
	}
}
