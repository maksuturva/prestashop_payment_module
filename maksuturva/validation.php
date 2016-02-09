<?php
/**
 * 2016 Maksuturva Group Oy
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@maksuturva.fi so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Maksuturva Group Oy <info@maksuturva.fi>
 * @copyright 2016 Maksuturva Group Oy
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/maksuturva.php');

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
