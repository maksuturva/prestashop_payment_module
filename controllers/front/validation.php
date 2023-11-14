<?php

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
            die($this->module->l('This payment method is not available.', 'maksuturva'));
        }

        $cart = $this->context->cart;
        if (!$cart || !$cart->id_customer || !$cart->id_address_delivery || !$cart->id_address_invoice) {
            $this->doRedirect('order', array('step' => 1));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->doRedirect('order', array('step' => 1));
        }

        $mks_message = $this->module->validatePayment($cart, $customer, $_GET);

        if (is_array($mks_message)) {
            if ($mks_message['new_message'] != 'error' AND $mks_message['new_message'] != 'cancel') {
                $this->doRedirect('order-confirmation', array(
                    'id_cart' => (int)$cart->id,
                    'id_module' => (int)$this->module->id,
                    'id_order' => (int)$this->module->currentOrder,
                    'key' => $customer->secure_key,
                    'mks_msg' => $mks_message,
                ));
            } else {
                $this->context->smarty->assign(array(
                    'error_message' => $mks_message['new_message'],
                    'shop_name' => $this->context->shop->name,
                    'this_path' => $this->module->getPath()

                ));

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

    protected function doRedirect($controller, array $params = array())
    {
        $query_string = !empty($params) ? http_build_query($params) : '';

        Tools::redirect('index.php?controller='. $controller . '&' . $query_string);

    }
}