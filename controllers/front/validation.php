<?php
/**
 * Copyright (C) 2023 Svea Payments Oy
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License (LGPLv2.1)
 * that is bundled with this package in the file LICENSE.
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
 * @author    Svea Payments Oy <info@svea.fi>
 * @copyright 2023 Svea Payments Oy
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
 */
class MaksuturvaValidationModuleFrontController extends ModuleFrontController
{
    public $display_column_left = false;
    public $display_column_right = false;

    public function postProcess()
    {
        $this->validatePayment();
    }

    public function preProcess()
    {
        $this->validatePayment();
    }

    protected function validatePayment()
    {
        if (!$this->isPaymentMethodValid()) {
            exit($this->module->l('This payment method is not available.', 'maksuturva'));
        }

        $cart = $this->context->cart;
        if (!$cart || !$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice) {
            $this->doRedirect('order', ['step' => 1]);
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->doRedirect('order', ['step' => 1]);
        }

        $mks_message = $this->module->validatePayment($cart, $customer, $_GET);

        if (is_array($mks_message)) {
            if ($mks_message['new_message'] != 'error' and $mks_message['new_message'] != 'cancel') {
                $this->doRedirect('order-confirmation', [
                    'id_cart' => (int) $cart->id,
                    'id_module' => (int) $this->module->id,
                    'id_order' => (int) $this->module->currentOrder,
                    'key' => $customer->secure_key,
                    'mks_msg' => $mks_message,
                ]);
            } else {
                $this->context->smarty->assign([
                    'error_message' => $mks_message['new_message'],
                    'shop_name' => $this->context->shop->name,
                    'this_path' => $this->module->getPath(),
                ]);

                return $this->setTemplate('module:maksuturva/views/templates/front/error.tpl');
            }
        }
    }

    protected function isPaymentMethodValid()
    {
        if (!$this->module->active) {
            return false;
        }

        foreach (Module::getPaymentModules() as $module) {
            if (isset($module['name']) && $module['name'] === $this->module->name) {
                return true;
            }
        }

        return false;
    }

    protected function doRedirect($controller, array $params = [])
    {
        $query_string = !empty($params) ? http_build_query($params) : '';

        Tools::redirect('index.php?controller=' . $controller . '&' . $query_string);
    }
}
