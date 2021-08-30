<?php
/* Copyright (C) 2021 EOXIA <dev@eoxia.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */


/**
 * \file    core/triggers/interface_99_modDoliWPshop_DoliWPshopTriggers.class.php
 * \ingroup doliwpshop
 * \brief   DoliWPshop trigger.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggers for DoliWPshop module
 */
class InterfaceDoliWPshopTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "Doliwpshop triggers.";
		$this->version = '1.1.1';
		$this->picto = 'doliwpshop@doliwpshop';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->doliwpshop->enabled)) return 0; // If module is not enabled, we do nothing

		// Data and type of action are stored into $object and $action

		switch ($action) {
			case 'PAYMENTONLINE_PAYMENT_OK' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

				require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';	// This also set $stripearrayofkeysbyenv
				require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
				require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

				$FULLTAG = GETPOST('fulltag');
				$PAYPALTOKEN = GETPOST('TOKEN');
				if (empty($PAYPALTOKEN)) $PAYPALTOKEN = GETPOST('token');
				$PAYPALPAYERID = GETPOST('PAYERID');
				if (empty($PAYPALPAYERID)) $PAYPALPAYERID = GETPOST('PayerID');

				// Detect $paymentmethod
				$reg = array();
				if (preg_match('/PM=([^\.]+)/', $FULLTAG, $reg))
				{
					$paymentmethod = $reg[1];
				}

				$user = new User($this->db);
				$user->fetch($conf->global->DOLIWPSHOP_USERAPI_SET,'', '',0,$conf->entity);
				$user->getrights();

				$order = new Commande($this->db);
				$checkOrder = false;

				if (!empty($conf->paypal->enabled))
				{
					if ($paymentmethod == 'paypal')							// We call this page only if payment is ok on payment system
					{
						if ($PAYPALTOKEN)
						{
							// Get on url call
							$onlinetoken        = $PAYPALTOKEN;
							$fulltag            = $FULLTAG;
							$payerID            = $PAYPALPAYERID;
							// Set by newpayment.php
							$paymentType        = $_SESSION['PaymentType'];
							$currencyCodeType   = $_SESSION['currencyCodeType'];
							$FinalPaymentAmt    = $_SESSION["FinalPaymentAmt"];
							// From env
							$ipaddress          = $_SESSION['ipaddress'];

							dol_syslog("Call paymentok with token=".$onlinetoken." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag, LOG_DEBUG, 0, '_payment');

							// Validate record
							if (!empty($paymentType))
							{
								dol_syslog("We call GetExpressCheckoutDetails", LOG_DEBUG, 0, '_payment');
								$resArray = getDetails($onlinetoken);

								$ack = strtoupper($resArray["ACK"]);
								if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
								{
									// Nothing to do
									dol_syslog("Call to GetExpressCheckoutDetails return ".$ack, LOG_DEBUG, 0, '_payment');
								} else {
									dol_syslog("Call to GetExpressCheckoutDetails return error: ".json_encode($resArray), LOG_WARNING, '_payment');
								}

								dol_syslog("We call DoExpressCheckoutPayment token=".$onlinetoken." paymentType=".$paymentType." currencyCodeType=".$currencyCodeType." payerID=".$payerID." ipaddress=".$ipaddress." FinalPaymentAmt=".$FinalPaymentAmt." fulltag=".$fulltag, LOG_DEBUG, 0, '_payment');
								$resArray2 = confirmPayment($onlinetoken, $paymentType, $currencyCodeType, $payerID, $ipaddress, $FinalPaymentAmt, $fulltag);

								$ack = strtoupper($resArray2["ACK"]);
								if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
								{
									dol_syslog("Call to GetExpressCheckoutDetails return ".$ack, LOG_DEBUG, 0, '_payment');

									$object->source   = $source;
									$object->ref      = $ref;
									$object->payerID  = $payerID;
									$object->fulltag  = $fulltag;
									$object->resArray = $resArray2;

									// resArray was built from a string like that
									// TOKEN=EC%2d1NJ057703V9359028&TIMESTAMP=2010%2d11%2d01T11%3a40%3a13Z&CORRELATIONID=1efa8c6a36bd8&ACK=Success&VERSION=56&BUILD=1553277&TRANSACTIONID=9B994597K9921420R&TRANSACTIONTYPE=expresscheckout&PAYMENTTYPE=instant&ORDERTIME=2010%2d11%2d01T11%3a40%3a12Z&AMT=155%2e57&FEEAMT=5%2e54&TAXAMT=0%2e00&CURRENCYCODE=EUR&PAYMENTSTATUS=Completed&PENDINGREASON=None&REASONCODE=None
									$PAYMENTSTATUS = urldecode($resArray2["PAYMENTSTATUS"]); // Should contains 'Completed'
									$TRANSACTIONID = urldecode($resArray2["TRANSACTIONID"]);
									$TAXAMT = urldecode($resArray2["TAXAMT"]);
									$NOTE = urldecode($resArray2["NOTE"]);

									if (preg_match('/ORD=([^\.]+)/', $resArray['INVNUM'], $reg))
									{
										$data = $reg[1];
									}
									$order->fetch($data);
									if ($resArray['AMT'] == price2num($order->total_ttc,2)){
										$checkOrder = true;
									}
								} else {
									dol_syslog("Call to DoExpressCheckoutPayment return error: ".json_encode($resArray2), LOG_WARNING, 0, '_payment');

									//Display a user friendly Error on the page using any of the following error information returned by PayPal
									$ErrorCode = urldecode($resArray2["L_ERRORCODE0"]);
									$ErrorShortMsg = urldecode($resArray2["L_SHORTMESSAGE0"]);
									$ErrorLongMsg = urldecode($resArray2["L_LONGMESSAGE0"]);
									$ErrorSeverityCode = urldecode($resArray2["L_SEVERITYCODE0"]);
								}
							} else {
								dol_print_error('', 'Session expired');
							}
						} else {
							dol_print_error('', '$PAYPALTOKEN not defined');
						}
					}
				}

				if (!empty($conf->stripe->enabled))
				{
					if ($paymentmethod == 'stripe') { // We call this page only if payment is ok on payment system
						$TRANSACTIONID = $_SESSION['TRANSACTIONID'];
						if ($TRANSACTIONID)	// Not linked to a stripe customer, we make the link
						{
							global $stripearrayofkeysbyenv;
							\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[0]['secret_key']);

							if (preg_match('/^pi_/', $TRANSACTIONID)) {
								// This may throw an error if not found.
								$data = \Stripe\PaymentIntent::retrieve($TRANSACTIONID);    // payment_intent (pi_...)
							}
						}
						$order->fetch($data['metadata']->dol_id);
						if ( $data['amount'] == $order->total_ttc * 100 ){
							$checkOrder = true;
						}
					}
				}

				if ($checkOrder) {
					$invoice = new Facture($this->db);
						$result = $invoice->createFromOrder($order, $user);
						if ( $result > 0 ) {
							$order->classifyBilled($user);
							$invoice->validate($user);

						$paiement = new Paiement($this->db);
						$paiement->datepaye = dol_now();
						$paiement->amounts = array($invoice->id => $order->total_ttc);
						if (empty($paymentTypeId))
						{
							$paymentType = $_SESSION["paymentType"];
							if (empty($paymentType)) $paymentType = 'CB';
							$paymentTypeId = dol_getIdFromCode($this->db, $paymentType, 'c_paiement', 'code', 'id', 1);
						}
						$paiement->paiementid = $paymentTypeId;
						$paiement->ext_payment_id = $TRANSACTIONID;

						if ($paymentmethod == 'paypal') {
							$paiement->note = $langs->trans('Status') . ' : '. $data['charges']['data'][0]['status'] . '<br>';
							$paiement->note .= $langs->trans('AmountReceived') . ' : '. price($data['amount_received']/100, 0, '', -1, -1, -1, $conf->currency)  . '<br>';
							$paiement->note .= $langs->trans('Id') . ' : '. $data['id'] . '<br>';
							$paiement->note .= $langs->trans('Email') . ' : '. $data['charges']['data'][0]['billing_details']['email'] . '<br>';
							$paiement->note .= $langs->trans('Description') . ' : '. $data['description'] . '<br>';
							$paiement->note .= $langs->trans('RiskLevel') . ' : '. $data['charges']['data'][0]['outcome']['risk_level'] . '<br>';
							$paiement->note .= $langs->trans('RiskScore') . ' : '. $data['charges']['data'][0]['outcome']['risk_score'] . '<br>';
							$paiement->note .= $langs->trans('SellerMessage') . ' : '. $data['charges']['data'][0]['outcome']['seller_message'] . '<br>';
						}

						if ($paymentmethod == 'stripe') {
							$paiement->note = $langs->trans('Status') . ' : '. $data['charges']['data'][0]['status'] . '<br>';
							$paiement->note .= $langs->trans('AmountReceived') . ' : '. price($data['amount_received']/100, 0, '', -1, -1, -1, $conf->currency)  . '<br>';
							$paiement->note .= $langs->trans('Id') . ' : '. $data['id'] . '<br>';
							$paiement->note .= $langs->trans('Email') . ' : '. $data['charges']['data'][0]['billing_details']['email'] . '<br>';
							$paiement->note .= $langs->trans('Description') . ' : '. $data['description'] . '<br>';
							$paiement->note .= $langs->trans('RiskLevel') . ' : '. $data['charges']['data'][0]['outcome']['risk_level'] . '<br>';
							$paiement->note .= $langs->trans('RiskScore') . ' : '. $data['charges']['data'][0]['outcome']['risk_score'] . '<br>';
							$paiement->note .= $langs->trans('SellerMessage') . ' : '. $data['charges']['data'][0]['outcome']['seller_message'] . '<br>';
						}

						$paiement->create($user, 1);

						if (!empty($conf->banque->enabled))
						{
							if ($paymentmethod == 'paypal') {
								$bankaccountid = $bankaccountid = $conf->global->PAYPAL_BANK_ACCOUNT_FOR_PAYMENTS;
							}
							if ($paymentmethod == 'stripe') {
								$bankaccountid = $conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS;
							}
							$label = '(CustomerInvoicePayment)';
							$paiement->addPaymentToBank($user, 'payment', $label, $bankaccountid, '', '');
						}
						$this->db->commit();
					}
				}
				break;

			case 'PAYMENTONLINE_PAYMENT_OK' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

				$object->fetchObjectLinked($object->id, 'commande', null, 'facture');
				$invoice_id = array_shift(array_values($object->linkedObjectsIds['facture']));
				$invoice = new Facture($this->db);
				$invoice->fetch($invoice_id);

				$invoice->total_ht  = $object->total_ht;
				$invoice->total_tva = $object->total_tva;
				$invoice->total_ttc = $object->total_ttc;

				$invoice->update($user, false);
				$invoice->set_paid($user, '', '');

				break;

			case 'PRODUCT_SET_MULTILANGS' :
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

				require_once DOL_DOCUMENT_ROOT.'/product/class/productlang.class.php';

				$productLang = new ProductLang($this->db);

				$arrayProductLangs     = $productLang->fetchAll('', 't.rowid', 0, 0, array('t.fk_product'=>$object->id), '');
				$lastArrayProductLangs = end($arrayProductLangs);

				$lastArrayProductLangsData                = array();
				$lastArrayProductLangsData['lang']        = $lastArrayProductLangs->lang;
				$lastArrayProductLangsData['label']       = $lastArrayProductLangs->label;
				$lastArrayProductLangsData['description'] = $lastArrayProductLangs->description;
				$lastArrayProductLangsData['fk_product']  = $lastArrayProductLangs->fk_product;
				$lastArrayProductLangsData['wpshop_id']   = $object->array_options['options__wps_id'];

				$wpmlPostIdTranslated = WPshopAPI::post('/wp-json/wpshop/v2/wpml_insert_data',$lastArrayProductLangsData);

				$lastArrayProductLangs->array_options['wpshopidtradmultilangs']  = $wpmlPostIdTranslated['data'];
				$lastArrayProductLangs->array_options['wpshopurltradmultilangs'] = $conf->global->WPSHOP_URL_WORDPRESS . '/?post_type=wps-product&p=' . $wpmlPostIdTranslated['data'];

				$lastArrayProductLangs->insertExtraFields();

				break;

			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
