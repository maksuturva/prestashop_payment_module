<?php

/**
 * Copyright (C) 2026 Svea Payments Oy
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
 * @copyright 2026 Svea Payments Oy
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
     * @var array<string, mixed> Gateway data for successful payment
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

            // Generate CSP nonce for inline styles and scripts
            $csp_nonce = base64_encode(random_bytes(16));

            // Success: store data for initContent()
            $this->payment_init_success = true;
            $this->gateway_data = [
                'gateway_url' => $gateway->getPaymentUrl(),
                'gateway_fields' => $fields,
                'cart_total' => $cart->getOrderTotal(true, Cart::BOTH),
                'shop_name' => $this->context->shop ? $this->context->shop->name : '',
                'csp_nonce' => $csp_nonce,
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
                'error_message' => 'error',
                'error_message_detail' => $module->l('Unable to initialize payment. An error occurred while preparing your payment. Please try again.'),
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
            // Add CSP headers for extra security
            $this->addSecurityHeaders($this->gateway_data['csp_nonce'], $this->gateway_data['gateway_url']);

            $this->context->smarty->assign($this->gateway_data);
            $this->context->smarty->assign([
                'language_iso' => $this->context->language->iso_code,
            ]);
            $this->setTemplate('module:maksuturva/views/templates/front/payment_redirect.tpl');
        } else {
            $this->setTemplate('module:maksuturva/views/templates/front/error.tpl');
        }
    }

    /**
     * Add security headers for payment redirect page
     *
     * @param string $nonce CSP nonce for inline styles and scripts
     * @param string $gateway_url Payment gateway URL for form submission
     */
    protected function addSecurityHeaders(string $nonce, string $gateway_url): void
    {
        // Content Security Policy: restrict all external resources
        // Use nonce for inline styles and scripts
        // Allow form submission to payment gateway only
        $csp = implode('; ', [
            "default-src 'none'",
            "style-src 'nonce-{$nonce}'",
            "script-src 'nonce-{$nonce}'",
            "form-action {$gateway_url}",
            "base-uri 'self'",
            "frame-ancestors 'none'",
        ]);
        header("Content-Security-Policy: {$csp}");

        // Additional security headers
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');

        // Cache control - don't cache this sensitive page
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    /**
     * Redirect to order process page
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
