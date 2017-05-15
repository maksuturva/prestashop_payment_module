<?php
/**
 * 2017 Maksuturva Group Oy
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
 * @copyright 2017 Maksuturva Group Oy
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
 */

/**
 * Payment controller for processing the request after selecting the Maksuturva gateway in the checkout.
 *
 * @property Maksuturva $module
 * @property ContextCore|Context $context
 */
class MaksuturvaPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * @inheritdoc
     */
    public $ssl = true;

    /**
     * @inheritdoc
     */
    public $display_column_left = false;

    /**
     * @inheritdoc
     */
    public function initContent()
    {
        parent::initContent();

        /** @var CartCore|Cart $cart */
        $cart = $this->context->cart;
        /** @var LinkCore|Link $link */
        $link = $this->context->link;

        if (!Validate::isLoadedObject($cart) || !$this->module->checkCurrency($cart)) {
            if (_PS_VERSION_ >= '1.5') {
                Tools::redirect('index.php?controller=order');
            } else {
                Tools::redirect('order.php');
            }
        }

        $gateway = new MaksuturvaGatewayImplementation($this->module, $cart);
        $this->context->smarty->assign(array(
            'count_products' => $cart->nbProducts(),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPath(),
            'this_path_ssl' => $this->module->getPathSSL(),
            'back_button' => (_PS_VERSION_ >= '1.5')
                ? $link->getPageLink('order', true, null, 'step=3')
                : $link->getPageLink('order.php', true) . '?step=3',
            'mt_form_action' => $gateway->getPaymentUrl(),
            'mt_extra_fields' => $gateway->getFieldArray(),
        ));

        if (_PS_VERSION_ >= '1.6') {
            $this->setTemplate('payment_execution_twbs.tpl');
        } else {
            $this->setTemplate('payment_execution.tpl');
        }
    }
}
