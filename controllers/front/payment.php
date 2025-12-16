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
class MaksuturvaPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @var bool Payment initialization success flag
     */
    protected $payment_init_success = false;

    /**
     * @var array Gateway data for successful payment
     */
    protected $gateway_data = [];

    /**
     * Process payment initialization
     */
    public function postProcess(): void
    {
        /** @var Cart */
        $cart = $this->context->cart;

        /** @var Maksuturva */
        $module = $this->module;

        // Validate cart and currency before creating payment attempt
        if (!Validate::isLoadedObject($cart)) {
            $this->redirectToOrder();
            return;
        }

        if (!$module->checkCurrency($cart)) {
            $this->redirectToOrder();
            return;
        }

        // Validate customer
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->redirectToOrder();
            return;
        }

        // Create payment attempt and prepare gateway data
        try {
            $paymentAttempt = MaksuturvaPayment::startForCart($module, $cart);
            $gateway = new MaksuturvaGatewayImplementation($module, $cart, $paymentAttempt);
            $fields = $gateway->getFieldArray();
            $paymentAttempt->recordRequest($fields);

            // Success: store data for initContent()
            $this->payment_init_success = true;
            $this->gateway_data = [
                'gateway_url' => $gateway->getPaymentUrl(),
                'gateway_fields' => $fields,
                'cart_total' => $cart->getOrderTotal(true, Cart::BOTH),
                'shop_name' => $this->context->shop->name,
            ];
        } catch (Exception $e) {
            PrestaShopLogger::addLog(sprintf(
                '[Maksuturva] Payment initialization failed for cart %d: %s',
                (int) $cart->id,
                $e->getMessage()
            ), 3);

            // Error: store error data for initContent()
            $this->payment_init_success = false;
            $this->context->smarty->assign([
                'error_message' => $module->l('Unable to initialize payment'),
                'error_message_detail' => $module->l('An error occurred while preparing your payment. Please try again.'),
                'shop_name' => $this->context->shop->name,
                'this_path' => $module->getPath(),
            ]);
        }
    }

    /**
     * Initialize content for showing either redirect or error page
     */
    public function initContent(): void
    {
        parent::initContent();

        if ($this->payment_init_success) {
            $this->context->smarty->assign($this->gateway_data);
            $this->setTemplate('module:maksuturva/views/templates/front/payment_redirect.tpl');
        } else {
            $this->setTemplate('module:maksuturva/views/templates/front/error.tpl');
        }
    }

    /**
     * Redirect to order process page (step 1)
     */
    protected function redirectToOrder(): void
    {
        $this->redirect_after = $this->context->link->getPageLink(
            'order',
            true,
            null,
            []
        );
    }
}
