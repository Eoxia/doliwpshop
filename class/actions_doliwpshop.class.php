<?php
/* Copyright (C) 2020 Eoxia
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/custom/doliwpshop/class/actions_doliwpshop.class.php
 * \ingroup doliwpshop
 * \brief   Hook on new actions for connected Dolibarr and WPshop
 */

dol_include_once('/custom/doliwpshop/lib/api_doliwpshop.class.php');
dol_include_once('/custom/doliwpshop/class/product_doliwpshop.class.php');
dol_include_once('/custom/doliwpshop/class/thirdparty_doliwpshop.class.php');
dol_include_once('/custom/doliwpshop/class/category_doliwpshop.class.php');

/**
 * Class ActionsDoliWPshop
 */
class ActionsDoliWPshop
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

	/**
	 * Do new actions on CommonObject
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (third party and product object)
	 * @param   string          $action         Current action (view_on_wpshop or create_on_wpshop)
	 * @return  int                             < 0 on error, 0 on success,
	 */
	public function doActions($parameters, &$object, &$action)
	{
		global $langs;
		
		// Translations
		$langs->load("doliwpshop@doliwpshop");

		$connected = WPshopAPI::get('/wp-json/wpshop/v2/statut');

		if ( ! $connected ) {
			setEventMessages($langs->trans("NotConnectedWPshop"), null, 'errors');
			return -1;
		}
		
		if (in_array('productcard', explode(':', $parameters['context'])))
		{
			$productDoliWPshop = new ProductDoliWPshop();
			
			if ($action == 'view' && $connected === true && ! empty($object->array_options['options__wps_id']))
			{
				$productDoliWPshop->checkProductExistOnWPshop($object);
			}

			if ($action == 'createwp' && $connected === true && empty($object->array_options['options__wps_id']))
			{
				$productDoliWPshop->createProductOnWPshop($object);
			}
		}
		if (in_array('categorycard', explode(':', $parameters['context'])))
		{	
			$categoryDoliWPshop = new CategoryDoliWPshop();
			/*
			if ($action == 'view' && $connected === true && ! empty($object->array_options['options__wps_id']))
			{
				$categoryDoliWPshop->checkCategoryExistOnWPshop($object);
			}
			*/
			if ($action == 'createwp' && $connected === true && empty($object->array_options['options__wps_id']))
			{
				$categoryDoliWPshop->createCategoryOnWPshop($object);
			}

			if ($action == 'updatewp' && $connected === true && ! empty($object->array_options['options__wps_id']))
			{
				$categoryDoliWPshop->createCategoryOnWPshop($object); // This actually triggers a sync/pull from Dolibarr
			}
		}
		if (in_array('thirdpartycard', explode(':', $parameters['context'])))
		{
			$thirdpartyDoliWPshop = new ThirdPartyDoliWPshop();

			if ($action == 'view' && $connected === true && ! empty($object->array_options['options__wps_id']))
			{
				$thirdpartyDoliWPshop->checkThirdPartyExistOnWPshop($object);
			}

			if ($action == 'createwp' && $connected === true && empty($object->array_options['options__wps_id']))
			{
				$thirdpartyDoliWPshop->createThirdPartyOnWPshop($object);
			}
		}

		if (in_array('producttranslationcard', explode(':', $parameters['context'])))
		{
			global $conf, $user;

			if ($action == 'delete'){
				if ($conf->global->WPSHOP_DATA_ARCHIVE_ON_DELETION) {
					$id           = GETPOST('id', 'alpha');
					$langtodelete = GETPOST('langtodelete', 'alpha');

					require_once __DIR__.'/productlang.class.php';

					$productLang = new ProductLang($this->db);
					$productLangData = array_shift($productLang->fetchAll('', 't.rowid', 0, 0, array('t.fk_product'=>$id,'t.lang'=>$langtodelete), 'AND'));
					$productLangDataID = array();
					$productLangDataID['id'] = $productLangData->array_options['options_wpshopidtradmultilangs'];
					WPshopAPI::post('/wp-json/wpshop/v2/wpml_delete_data',$productLangDataID);

					require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
					$now = dol_now();
					$actioncomm = new ActionComm($this->db);

					$actioncomm->elementtype = 'product';
					$actioncomm->code        = 'AC_ARCHIVE_DATA_TRAD';
					$actioncomm->type_code   = 'AC_OTH_AUTO';
					$actioncomm->label       = $langs->trans('TranslateProductArchive');
					$actioncomm->datep       = $now;
					$actioncomm->fk_element  = $productLangData->fk_product;
					$actioncomm->userownerid = $user->id;
					$actioncomm->percentage  = -1;
					$actioncomm->note = 'Label: '.$productLangData->label.'<br>'.'Description: '. $productLangData->description;

					$actioncomm->create($user);
				}
			}
		}

 	return 0;
	}

	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		if (in_array('categorycard', explode(':', $parameters['context']))) {
			if (!empty($object->array_options['options__wps_id'])) {
				$connected = WPshopAPI::get('/wp-json/wpshop/v2/statut');
				if ($connected) {
					$url = '/wp-json/wpshop/v2/category/' . $object->array_options['options__wps_id'];
					$response = WPshopAPI::get($url);
					
					if (!empty($response) && isset($response->slug)) {
						global $conf;
						$slug = $response->slug;
						$wp_url = !empty($conf->global->WPSHOP_URL_WORDPRESS) ? rtrim($conf->global->WPSHOP_URL_WORDPRESS, '/') : '';
						$wps_id = $object->array_options['options__wps_id'];
						$link = $wp_url . '/wp-admin/term.php?taxonomy=wps-product-cat&tag_ID=' . $wps_id . '&post_type=wps-product';
						print '<tr><td>'.$langs->trans("WPshop Slug").'</td><td colspan="3"><a href="'.$link.'" target="_blank">'.$slug.'</a></td></tr>';
					}
				}
			}
		}

		// Inject JS to make WPshop ID extrafield clickable across all object cards
		if (!empty($object->array_options['options__wps_id'])) {
			global $conf;
			$wp_url = !empty($conf->global->WPSHOP_URL_WORDPRESS) ? rtrim($conf->global->WPSHOP_URL_WORDPRESS, '/') : '';
			if ($wp_url) {
				$wps_id = $object->array_options['options__wps_id'];
				$link = '';
				if ($object->element == 'product') {
					$link = $wp_url . '/wp-admin/post.php?post=' . $wps_id . '&action=edit';
				} elseif (isset($object->element) && ($object->element == 'category' || $object->element == 'categorie')) {
					$link = $wp_url . '/wp-admin/term.php?taxonomy=wps-product-cat&tag_ID=' . $wps_id . '&post_type=wps-product';
				}
				
				if ($link) {
					print '<script>';
					print '$(document).ready(function() {';
					print '  $("table td").filter(function() { return $(this).text().trim() === "WPshop ID"; }).next("td").each(function() {';
					print '    var txt = $(this).text().trim();';
					print '    if (txt === "'.$wps_id.'") {';
					print '      $(this).html("<a href=\''.$link.'\' target=\'_blank\' rel=\'noopener noreferrer\'>" + txt + "</a>");';
					print '    }';
					print '  });';
					print '});';
					print '</script>';
				}
			}
		}

		return 0;
	}

	/**
	 * Add new actions buttons on CommonObject
	 *
	 * @param   CommonObject  $object  The object to process (third party and product object)
	 */
    public function addMoreActionsButtons($parameters, &$object, &$action)
	{
		global $conf, $langs;
	
		// Translations
		$langs->load("doliwpshop@doliwpshop");

		$connected = WPshopAPI::get('/wp-json/wpshop/v2/statut');
		
		if ($connected !== true) {
			print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.$langs->trans("NotAvailableDolibarr").'" href="#">'.$langs->trans("CreateOnWPshop").'</a></div>';
			return;
		}

		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( $_SERVER['HTTPS'] == 'on' ) {
			  $server_protocol = 'https';
			} else {
			  $server_protocol = 'http';
			} 
		} else {
			$server_protocol = 'http';
		}
		
		if (empty($object->array_options['options__wps_id'])) {
			$actual_link = $server_protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$actual_link .= '&action=createwp&token='.newToken();
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $actual_link . '">'.$langs->trans("CreateOnWPshop").'</a></div>';

		} else {
			$wp_url = !empty($conf->global->WPSHOP_URL_WORDPRESS) ? rtrim($conf->global->WPSHOP_URL_WORDPRESS, '/') : '';
			if ($object->element == 'product' ) {
				print '<div class="inline-block divButAction"><a class="butAction" title="'.$langs->trans("ViewOnWPshop").'" href="' . $wp_url . '/?post_type=wps-product&p=' . $object->array_options['options__wps_id'] . '" target="_blank" >'.$langs->trans("ViewOnWPshop").'</a></div>';
			} elseif (isset($object->element) && ($object->element == 'category' || $object->element == 'categorie')) {
				print '<div class="inline-block divButAction"><a class="butAction" title="'.$langs->trans("ViewOnWPshop").'" href="' . $wp_url . '/wp-admin/term.php?taxonomy=wps-product-cat&tag_ID=' . $object->array_options['options__wps_id'] . '&post_type=wps-product" target="_blank" >'.$langs->trans("ViewOnWPshop").'</a></div>';
			}

			if (isset($object->element) && ($object->element == 'category' || $object->element == 'categorie')) {
				$actual_link_update = $server_protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				$actual_link_update .= '&action=updatewp&token='.newToken();
				print '<div class="inline-block divButAction"><a class="butAction" title="'.$langs->trans("UpdateOnWPshop").'" href="'.$actual_link_update.'">'.$langs->trans("UpdateOnWPshop").'</a></div>';
			} else {
				print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.$langs->trans("NotAvailableObject").'" href="#">'.$langs->trans("CreateOnWPshop").'</a></div>';
			}
		}
	}
}
