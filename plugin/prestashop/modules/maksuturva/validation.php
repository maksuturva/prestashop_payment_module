<?php
/**
 * Maksuturva Payment Module
 * Creation date: 01/12/2011
 */
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/maksuturva.php');

$maksuturva = new Maksuturva();

/*
 * Validate the customer, the cart and the module itself
 */
if (!$cart ||
	$cart->id_customer == 0 ||
	$cart->id_address_delivery == 0 ||
	$cart->id_address_invoice == 0 ||
	!$maksuturva->active) {
	die(Tools::displayError('This payment method is not available.'));
}

/*
 * Still available? (if customer's address changed)
 */
$authorized = false;
if (method_exists('Module', 'getPaymentModules')){
	foreach (Module::getPaymentModules() as $module) {
		if ($module['name'] == $maksuturva->name) {
			$authorized = true;
			break;
		}
	}
	if (!$authorized) {
		die(Tools::displayError('This payment method is not available.'));
	}
}

/*
 * Valid customer?
 */
$customer = new Customer((int)$cart->id_customer);
if (!Validate::isLoadedObject($customer)) {
	Tools::redirect('index.php?controller=order&step=1');
}

/*
 * Finally, validate the payment and update orderStatus
 */
$maksuturva->validatePayment($cart, $customer, $_GET);
