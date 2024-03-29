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
     * @return void
     */
    public function initContent()
    {
        parent::initContent();

        /** @var Cart */
        $cart = $this->context->cart;

        /** @var Link */
        $link = $this->context->link;

        /** @var Maksuturva */
        $module = $this->module;

        if (!Validate::isLoadedObject($cart) || !$module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $gateway = new MaksuturvaGatewayImplementation($module, $cart);

        /** @var Smarty */
        $smarty = $this->context->smarty;
        $smarty->assign([
            'count_products' => $cart->nbProducts(),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $module->getPath(),
            'this_path_ssl' => $module->getPathSSL(),
            'back_button' => $link->getPageLink('order', true, null, 'step=3'),
            'mt_form_action' => $gateway->getPaymentUrl(),
            'mt_extra_fields' => $gateway->getFieldArray(),
        ]);

        $this->setTemplate('module:maksuturva/views/templates/front/payment_execution_twbs.tpl');
    }
}
