<?php
/**
 * Maksuturva Payment Module
 * Creation date: 01/12/2011
 */

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/maksuturva.php');

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