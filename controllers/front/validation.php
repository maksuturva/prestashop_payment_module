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
    public function postProcess(): void
    {
        $this->validatePayment();
    }

    protected function validatePayment(): void
    {
        /** @var Maksuturva */
        $module = $this->module;

        /** @var Smarty */
        $smarty = $this->context->smarty;

        /** @var Shop */
        $shop = $this->context->shop;

        if (!$this->isPaymentMethodValid()) {
            exit($module->l('This payment method is not available.', 'maksuturva'));
        }

        // context only has the cart when the return comes from with the users browser
        /** @var Cart */
        $cart = $this->context->cart;

        // no cart loaded, check if this is m2m callback or browser return after m2m processed it
        if (!Validate::isLoadedObject($cart)) {
            // m2m callbacks don't have customer session/cookies, browser returns do
            $hasCustomerSession = isset($this->context->customer)
                && Validate::isLoadedObject($this->context->customer)
                && $this->context->customer->id > 0;

            if (!$hasCustomerSession) {
                // no customer session, this is a genuine m2m callback
                list($status, $msg) = $this->handleMachine2Machine();
                http_response_code($status);
                header('Content-Type: text/plain; charset=utf-8');
                exit((string) $msg);
            }

            // has customer session but no cart - browser return after m2m created order
            // try to load cart from pmt_id to let existing logic handle the redirect
            if (Tools::isSubmit('pmt_id')) {
                try {
                    $paymentAttempt = MaksuturvaPayment::fromPmtId(Tools::getValue('pmt_id'));
                    $cart = new Cart((int) $paymentAttempt->getCartId());
                } catch (Exception $e) {
                    // couldn't load cart, fall through to error handling below
                }
            }
        }

        // check if m2m came before the browser return - order might already be validated
        $orderId = $this->getOrderIdFromCart($cart);
        if ($orderId) {
            $order = new Order($orderId);
            if (Validate::isLoadedObject($order)) {
                $customer = new Customer($cart->id_customer);
                if (Validate::isLoadedObject($customer)) {
                    // order exists and is valid, redirect to confirmation
                    $this->doRedirect('order-confirmation', [
                        'id_cart' => (int) $cart->id,
                        'id_module' => (int) $module->id,
                        'id_order' => (int) $orderId,
                        'key' => $customer->secure_key,
                    ]);
                }
            }
        }

        if (!$cart || !$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice) {
            $this->doRedirect('order', ['step' => 1]);
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->doRedirect('order', ['step' => 1]);
        }

        $mks_message = $module->validatePayment($cart, $customer, $this->getPaymentParams());

        if (is_array($mks_message)) {
            if ($mks_message['new_message'] != 'error' and $mks_message['new_message'] != 'cancel') {
                $this->doRedirect('order-confirmation', [
                    'id_cart' => (int) $cart->id,
                    'id_module' => (int) $module->id,
                    'id_order' => (int) $module->currentOrder,
                    'key' => $customer->secure_key,
                    'mks_msg' => $mks_message,
                ]);
            } else {
                $smarty->assign([
                    'error_message' => $mks_message['new_message'],
                    'error_message_detail' => $mks_message['message'],
                    'shop_name' => $shop->name,
                    'this_path' => $module->getPath(),
                ]);

                $this->setTemplate('module:maksuturva/views/templates/front/error.tpl');
            }
        }
    }

    // handle machine 2 machine callback from svea
    // for OK requests checks the payment and tries to find an order / cart
    // with pmt_id and if it is found marks the payment as ok (after all the checks)
    private function handleMachine2Machine(): array
    {
        /** @var Maksuturva */
        $module = $this->module;

        $params = $this->getPaymentParams();

        // validate required parameters are present
        $required_fields = [
            'pmt_action',
            'pmt_version',
            'pmt_id',
            'pmt_reference',
            'pmt_amount',
            'pmt_currency',
            'pmt_sellercosts',
            'pmt_paymentmethod',
            'pmt_escrow',
            'pmt_hash',
        ];

        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                return [400, 'Missing required fields'];
            }
        }

        // load the payment attempt by pmt_id
        try {
            $paymentAttempt = MaksuturvaPayment::fromPmtId($params['pmt_id']);
        } catch (Exception $e) {
            return [404, 'Payment attempt not found'];
        }

        // load cart from payment attempt
        $cart = new Cart((int) $paymentAttempt->getCartId());
        if (!Validate::isLoadedObject($cart)) {
            return [404, 'Cart not found'];
        }

        // load customer from cart
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            return [404, 'Customer not found'];
        }

        // verify hash
        try {
            $gateway = new MaksuturvaGatewayImplementation($module, $cart, $paymentAttempt);
            $validator = $gateway->validatePayment($params);
            if ($validator->getStatus() === 'error') {
                return [400, 'Validation error'];
            } elseif ($validator->getStatus() === 'delayed') {
                return [200, 'Delayed'];
            } elseif ($validator->getStatus() === 'cancel') {
                return [200, 'Cancelled']; // ok, customer cancelled payment. this actually should never happend for m2m calls
            } elseif ($validator->getStatus() === 'ok') {
                // this is the only valid state for now
            } else {
                return [400, 'Unknown error']; // something wen't really wrong
            }
        } catch (Exception $e) {
            return [500, 'Internal server error'];
        }

        // check if this cart was already converted to order
        $orderId = $this->getOrderIdFromCart($cart);
        if ($orderId) {
            // cart already has an order - update existing order instead of creating new one
            return $this->updateExistingOrder($orderId, $paymentAttempt, $validator, $params);
        }

        // no existing order, proceed with order creation via module->validatePayment
        $mks_message = $module->validatePayment($cart, $customer, $params);

        if (is_array($mks_message)) {
            if ($mks_message['new_message'] === 'error') {
                return [400, $mks_message['message']];
            }

            return [200, 'OK'];
        }

        return [500, 'Unexpected error'];
    }

    /**
     * Update existing order when M2M callback comes after order was already created
     *
     * @param int $orderId
     * @param MaksuturvaPayment $paymentAttempt
     * @param MaksuturvaPaymentValidator $validator
     * @param array $params
     *
     * @return array [status_code, message]
     */
    private function updateExistingOrder(
        int $orderId,
        MaksuturvaPayment $paymentAttempt,
        $validator,
        array $params
    ): array {
        /** @var Maksuturva */
        $module = $this->module;

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            return [404, 'Order not found'];
        }

        // check if order is already in a final state
        if ($this->isPaymentAlreadyProcessed($paymentAttempt, $orderId)) {
            return [200, 'Already processed'];
        }

        // determine new order state based on validation
        // error is left here also, but as far as I know only pending => ok or cancel are possible
        if ($validator->getStatus() === 'error') {
            $new_state = (int) $module->getConfig('PS_OS_ERROR');
        } elseif ($validator->getStatus() === 'cancel') {
            $new_state = (int) $module->getConfig('PS_OS_CANCELED');
        } else {
            // ok/success
            $new_state = (int) $module->getConfig('PS_OS_PAYMENT');
        }

        // record the response
        $paymentAttempt->recordResponse($params, $new_state);

        // update order state if different from current
        if ($order->getCurrentState() != $new_state) {
            $order->setCurrentState($new_state);

            // handle surcharge if payment is successful and surcharge exists
            if ($validator->getStatus() === 'ok' && $paymentAttempt->includesSurcharge()) {
                $surcharge = $paymentAttempt->getSurcharge();
                $order->total_paid += $surcharge;
                $order->total_paid_tax_excl += $surcharge;
                $order->total_paid_tax_incl += $surcharge;
                if (method_exists($order, 'addOrderPayment')) {
                    $order->addOrderPayment((string) $surcharge);
                } else {
                    $order->total_paid_real += $surcharge;
                    $order->update();
                }
            }
        }

        return [200, 'Order updated'];
    }

    /**
     * Check if cart has already been converted to an order
     *
     * @param Cart $cart
     *
     * @return int|false Order ID if found, false otherwise
     */
    private function getOrderIdFromCart(Cart $cart)
    {
        $orderId = Order::getIdByCartId((int) $cart->id);
        if ($orderId) {
            return (int) $orderId;
        }

        return false;
    }

    /**
     * Check if payment has already been processed (order attached and in final state)
     *
     * @param MaksuturvaPayment $paymentAttempt
     * @param int $orderId
     *
     * @return bool
     */
    private function isPaymentAlreadyProcessed(MaksuturvaPayment $paymentAttempt, int $orderId): bool
    {
        /** @var Maksuturva */
        $module = $this->module;

        // check if payment attempt already has this order attached
        $paymentStatus = $paymentAttempt->getStatus();

        if ($paymentStatus > 0) {
            // payment has a status set, likely already processed
            $order = new Order($orderId);
            if (Validate::isLoadedObject($order)) {
                // check if order is in a final state (paid, cancelled, etc)
                $finalStates = [
                    (int) $module->getConfig('PS_OS_PAYMENT'),
                    (int) $module->getConfig('PS_OS_CANCELED'),
                    (int) $module->getConfig('PS_OS_ERROR'),
                ];

                if (in_array($order->getCurrentState(), $finalStates)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract payment parameters from request using PrestaShop Tools
     *
     * @return array
     */
    private function getPaymentParams(): array
    {
        $params = [];

        // All possible payment gateway parameters
        $paymentFields = [
            // Status indicators
            'ok',
            'cancel',
            'error',
            'delayed',
            // Payment data
            'pmt_action',
            'pmt_version',
            'pmt_id',
            'pmt_reference',
            'pmt_amount',
            'pmt_currency',
            'pmt_sellercosts',
            'pmt_paymentmethod',
            'pmt_escrow',
            'pmt_hash',
            'pmt_buyername',
            'pmt_buyeraddress',
            'pmt_buyerpostalcode',
            'pmt_buyercity',
            'pmt_buyercountry',
            'pmt_deliveryname',
            'pmt_deliveryaddress',
            'pmt_deliverypostalcode',
            'pmt_deliverycity',
            'pmt_deliverycountry',
        ];

        foreach ($paymentFields as $field) {
            $value = Tools::getValue($field);
            if ($value !== false && $value !== '') {
                $params[$field] = $value;
            }
        }

        return $params;
    }

    protected function isPaymentMethodValid(): bool
    {
        if (!$this->module->active) {
            return false;
        }

        foreach (PaymentModule::getInstalledPaymentModules() as $module) {
            if (isset($module['name']) && $module['name'] === $this->module->name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $controller
     * @param array<mixed> $params
     */
    protected function doRedirect(string $controller, array $params = []): void
    {
        $query_string = !empty($params) ? http_build_query($params) : '';

        Tools::redirect('index.php?controller=' . $controller . '&' . $query_string);
    }
}
