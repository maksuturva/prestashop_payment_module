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

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/maksuturva.php');

if (!$cookie->isLogged(true)) {
	Tools::redirect('authentication.php?back=order.php');
}
// check cart
if (!isset($cart)) {
	Tools::redirectLink(__PS_BASE_URI__.'order.php');
}

$module = new Maksuturva();
echo $module->execPayment($cart);

include_once(dirname(__FILE__).'/../../footer.php');