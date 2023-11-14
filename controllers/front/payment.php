<?php

class MaksuturvaPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;

    public function initContent()
    {
        parent::initContent();

        $cart = $this->context->cart;
        $link = $this->context->link;

        if (!Validate::isLoadedObject($cart) || !$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $gateway = new MaksuturvaGatewayImplementation($this->module, $cart);

        $this->context->smarty->assign(array(
            'count_products' => $cart->nbProducts(),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPath(),
            'this_path_ssl' => $this->module->getPathSSL(),
            'back_button' => $link->getPageLink('order', true, null, 'step=3'),
            'mt_form_action' => $gateway->getPaymentUrl(),
            'mt_extra_fields' => $gateway->getFieldArray(),
        ));

        return $this->setTemplate('module:maksuturva/views/templates/front/payment_execution_twbs.tpl');
    }
}