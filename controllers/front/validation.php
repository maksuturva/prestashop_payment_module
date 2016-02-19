<?php
/**
 * 2016 Maksuturva Group Oy
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License (LGPLv2.1)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-2.1.html
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
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
 */

/**
 * @property Maksuturva $module
 * @property ContextCore $context
 */
class MaksuturvaValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @inheritdoc
     */
    public function postProcess()
    {
        $this->validatePayment();
    }

    /**
     * @inheritdoc
     */
    public function preProcess()
    {
        $this->validatePayment();
    }

    /**
     * Validates the payment request and redirects to the order confirmation page.
     */
    protected function validatePayment()
    {
        if (!$this->isPaymentMethodValid()) {
            die($this->module->l('This payment method is not available.', 'maksuturva'));
        }

        /** @var CartCore|Cart $cart */
        $cart = $this->context->cart;
        if (!$cart || !$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice) {
            $this->doRedirect('order', array('step' => 1));
        }

        /** @var CustomerCore|Customer $customer */
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->doRedirect('order', array('step' => 1));
        }

        $mks_message = $this->module->validatePayment($cart, $customer, $_GET);

        $this->doRedirect('order-confirmation', array(
            'id_cart' => (int)$cart->id,
            'id_module' => (int)$this->module->id,
            'id_order' => (int)$this->module->currentOrder,
            'key' => $customer->secure_key,
            'mks_msg' => $mks_message,
        ));
    }

    /**
     * Checks that this payment option is still available in case the customer changed his address
     * just before the end of the checkout process.
     *
     * @return bool
     */
    protected function isPaymentMethodValid()
    {
        if (!$this->module->active) {
            return false;
        }

        if (method_exists('Module', 'getPaymentModules')) {
            foreach (Module::getPaymentModules() as $module) {
                if (isset($module['name']) && $module['name'] === $this->module->name) {
                    return true;
                }
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * Redirect the user to specified controller action.
     *
     * Handles the inconsistency between PS versions.
     *
     * @param string $controller
     * @param array $params
     */
    protected function doRedirect($controller, array $params = array())
    {
        $query_string = !empty($params) ? http_build_query($params) : '';
        if (_PS_VERSION_ >= '1.5') {
            Tools::redirect('index.php?controller='. $controller . '&' . $query_string);
        } else {
            Tools::redirectLink(__PS_BASE_URI__ . $controller .'.php?' . $query_string);
        }
    }
}
