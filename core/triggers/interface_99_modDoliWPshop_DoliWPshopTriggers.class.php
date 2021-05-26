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

				$FULLTAG = GETPOST('fulltag');
				// Detect $paymentmethod
				$reg = array();
				if (preg_match('/PM=([^\.]+)/', $FULLTAG, $reg))
				{
					$paymentmethod = $reg[1];
				}

				require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';	// This also set $stripearrayofkeysbyenv
				require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
				require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

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

				$user = new User($this->db);
				$user->fetch($conf->global->DOLIWPSHOP_USERAPI_SET,'', '',0,$conf->entity);
				$user->getrights();

				$order = new Commande($this->db);
				$order->fetch($data['metadata']->dol_id);

				if ( $data['amount'] == $order->total_ttc * 100) {
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

						$paiement->note = $langs->trans('Status') . ' : '. $data['charges']['data'][0]['status'] . '<br>';
						$paiement->note .= $langs->trans('AmountReceived') . ' : '. price($data['amount_received']/100, 0, '', -1, -1, -1, $conf->currency)  . '<br>';
						$paiement->note .= $langs->trans('Id') . ' : '. $data['id'] . '<br>';
						$paiement->note .= $langs->trans('Email') . ' : '. $data['charges']['data'][0]['billing_details']['email'] . '<br>';
						$paiement->note .= $langs->trans('Description') . ' : '. $data['description'] . '<br>';
						$paiement->note .= $langs->trans('RiskLevel') . ' : '. $data['charges']['data'][0]['outcome']['risk_level'] . '<br>';
						$paiement->note .= $langs->trans('RiskScore') . ' : '. $data['charges']['data'][0]['outcome']['risk_score'] . '<br>';
						$paiement->note .= $langs->trans('SellerMessage') . ' : '. $data['charges']['data'][0]['outcome']['seller_message'] . '<br>';

						$paiement->create($user, 1);

						if (!empty($conf->banque->enabled))
						{
							$bankaccountid = $conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS;
							$label = '(CustomerInvoicePayment)';
							$paiement->addPaymentToBank($user, 'payment', $label, $bankaccountid, '', '');
						}
						$this->db->commit();
					}
				}
				break;

			default:
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}
}
