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

if (!defined('_PS_VERSION_')) {
    exit;
}

/*
 * Only try to load class files if we can resolve the __FILE__ global to the current file.
 * We need to do this as this module file is parsed with eval() on the modules page, and eval() messes up the __FILE__.
 */
if ((basename(__FILE__) === 'maksuturva.php')) {
    $module_dir = dirname(__FILE__);
    require_once($module_dir . '/includes/MaksuturvaException.php');
    require_once($module_dir . '/includes/MaksuturvaGatewayException.php');
    require_once($module_dir . '/includes/MaksuturvaGatewayAbstract.php');
    require_once($module_dir . '/includes/MaksuturvaGatewayImplementation.php');
    require_once($module_dir . '/includes/MaksuturvaPayment.php');
    require_once($module_dir . '/includes/MaksuturvaPaymentValidator.php');
}

/**
 * Payment module for accepts payments using Maksuturva.
 *
 * @property bool $active
 * @property int $id
 * @property string $_path
 * @property string $table
 * @property string $identifier
 * @property ContextCore|Context $context
 *
 * @property int currentOrder
 *
 * @method string l($string, $specific = false)
 * @method string displayConfirmation($string)
 * @method string displayWarning($warning)
 * @method string displayError($error)
 * @method string display($file, $template, $cacheId = null, $compileId = null)
 * @method bool registerHook($hook_name)
 * @method bool validateOrder()
 */
class Maksuturva extends PaymentModule
{
    /**
     * @var array
     */
    private static $config_keys = array(
        'MAKSUTURVA_SELLER_ID',
        'MAKSUTURVA_SECRET_KEY',
        'MAKSUTURVA_SECRET_KEY_VERSION',
        'MAKSUTURVA_URL',
        'MAKSUTURVA_PMT_ID_PREFIX',
        'MAKSUTURVA_ENCODING',
        'MAKSUTURVA_SANDBOX',
        'MAKSUTURVA_OS_AUTHORIZATION',
    );

    /**
     * Class constructor: assign some configuration values
     */
    public function __construct()
    {
        $this->name = 'maksuturva';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.0';
        $this->author = 'Maksuturva';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Maksuturva');
        $this->description = $this->l('Accepts payments using Maksuturva');
        $this->confirmUninstall = $this->l('Are you sure you want to delete the Maksuturva module?');

        /* Backward compatibility */
        if (_PS_VERSION_ < '1.5') {
            require_once(_PS_MODULE_DIR_ . $this->name . '/backward_compatibility/backward.php');
        }
    }

    /**
     * @inheritdoc
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHooks()
            || !$this->createConfig()
            || !$this->createTables()
            || !$this->createOrderStates()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function uninstall()
    {
        if (!$this->deleteConfig()
            || !parent::uninstall()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Displays and handles the module administration page.
     *
     * @return string
     */
    public function getContent()
    {
        $html = '';
        $html .= $this->postProcess();
        $html .= $this->displayForm();
        return $html;
    }

    /**
     * Create a admin link URL.
     *
     * This is a copy of the same method in the "LinkCore" class with added support for PS 1.4.
     *
     * @param string $controller the name of the controller to link to.
     * @param boolean $with_token include or not the token in the url.
     * @return string the URL.
     */
    public function getAdminLink($controller, $with_token = true)
    {
        if (_PS_VERSION_ >= '1.5') {
            return $this->context->link->getAdminLink('AdminModules', false);
        }

        $params = array('tab' => $controller);
        if ($with_token) {
            $params['token'] = Tools::getAdminTokenLite($controller);
        }

        return 'index.php?' . http_build_query($params);
    }

    /**
     * Callback for the `header` hook.
     *
     * Used to add CSS to the shop frontend.
     */
    public function hookHeader()
    {
        if (_PS_VERSION_ >= '1.6') {
            $this->context->controller->addCSS($this->_path . '/views/css/maksuturva.css', 'all');
        }
    }

    /**
     * Callback for the `payment` hook.
     *
     * Used to display the payment gateway in the checkout.
     *
     * @param array $params
     * @return string
     */
    public function hookPayment($params)
    {
        // only EUR is supported - we validate it against
        // 1) shop (if it has EUR)
        // 2) cart (if it has only EUR products within)
        if (!$this->checkCurrency($params['cart'])) {
            return '';
        }

        $this->context->smarty->assign(
            array(
                'this_path' => $this->getPath(),
                'this_path_ssl' => $this->getPathSSL(),
            )
        );

        if (_PS_VERSION_ >= '1.6') {
            return $this->display(__FILE__, 'views/templates/hook/payment_twbs.tpl');
        } else {
            return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
        }
    }

    /**
     * Callback for the `paymentReturn` hook.
     *
     * Used to display the order confirmation page.
     *
     * @param array $params
     * @return string
     */
    public function hookPaymentReturn($params)
    {
        if (!isset($params['objOrder'])) {
            return '';
        }

        /** @var OrderCore|Order $order */
        $order = $params['objOrder'];

        $status_map = array(
            $this->getConfig('PS_OS_PAYMENT') => 'ok',
            $this->getConfig('PS_OS_OUTOFSTOCK') => 'ok',
            $this->getConfig('PS_OS_OUTOFSTOCK_PAID') => 'ok',
            $this->getConfig('MAKSUTURVA_OS_AUTHORIZATION') => 'pending',
            $this->getConfig('PS_OS_CANCELED') => 'cancel',
        );
        $status = isset($status_map[$order->getCurrentState()])
            ? $status_map[$order->getCurrentState()]
            : 'error';

        $this->context->smarty->assign(array(
            'this_path' => $this->getPath(),
            'this_path_ssl' => $this->getPathSSL(),
            'status' => $status,
            'message' => Tools::getValue('mks_msg')
        ));

        if (_PS_VERSION_ >= '1.6') {
            return $this->display(__FILE__, 'views/templates/hook/payment_return_twbs.tpl');
        } else {
            return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
        }
    }

    /**
     * Callback for the `adminOrder` hook.
     *
     * Automatically verifies the order status in Maksuturva and displays the result on the order administration page.
     *
     * @param array $params
     * @return string
     */
    public function hookAdminOrder($params)
    {
        if (!isset($params['id_order'])) {
            return '';
        }

        try {
            $payment = new MaksuturvaPayment((int)$params['id_order']);
        } catch (Exception $e) {
            // The order was not payed using Maksuturva.
            return '';
        }

        /** @var OrderCore|Order $order */
        $order = new Order((int)$params['id_order']);
        /** @var CartCore|Cart $cart */
        $cart = new Cart((int)$order->id_cart);

        switch ($payment->getStatus()) {
            case (int)$this->getConfig('PS_OS_PAYMENT'):
                $msg = $this->l('The payment was confirmed by Maksuturva');
                break;

            case (int)$this->getConfig('PS_OS_CANCELED'):
                if ($order->getCurrentState() != $this->getConfig('PS_OS_CANCELED')) {
                    $msg = $this->l('The payment could not be tracked by Maksuturva, please check manually');
                } else {
                    $msg = $this->l('The payment was canceled by the customer');
                }
                break;

            case (int)$this->getConfig('PS_OS_ERROR'):
                $msg = $this->l('An error occurred and the payment was not confirmed, please check manually');
                break;

            case (int)$this->getConfig('MAKSUTURVA_OS_AUTHORIZATION'):
            default:
                try {
                    $gateway = new MaksuturvaGatewayImplementation($this, $cart);
                    $response = $gateway->statusQuery();
                    switch ($response['pmtq_returncode']) {
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_PAID:
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_PAID_DELIVERY:
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_COMPENSATED:
                            if ($order->getCurrentState() !== $this->getConfig('PS_OS_PAYMENT')) {
                                $order->setCurrentState($this->getConfig('PS_OS_PAYMENT'));
                            }
                            (new MaksuturvaPayment((int)$order->id))->complete();
                            $msg = $this->l('The payment confirmation was received - payment accepted');
                            break;

                        case MaksuturvaGatewayImplementation::STATUS_QUERY_PAYER_CANCELLED:
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_PAYER_CANCELLED_PARTIAL:
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN:
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_PAYER_RECLAMATION:
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_CANCELLED:
                            if ($order->getCurrentState() !== $this->getConfig('PS_OS_CANCELED')) {
                                $order->setCurrentState($this->getConfig('PS_OS_CANCELED'));
                            }
                            (new MaksuturvaPayment((int)$order->id))->cancel();
                            $msg = $this->l('The payment was canceled in Maksuturva');
                            break;

                        case MaksuturvaGatewayImplementation::STATUS_QUERY_NOT_FOUND:
                            (new MaksuturvaPayment((int)$order->id))->cancel();
                            $msg = $this->l('The payment could not be tracked by Maksuturva, please check manually');
                            break;

                        case MaksuturvaGatewayImplementation::STATUS_QUERY_FAILED:
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_WAITING:
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_UNPAID:
                        case MaksuturvaGatewayImplementation::STATUS_QUERY_UNPAID_DELIVERY:
                        default:
                            $msg = $this->l('The payment is still waiting for confirmation');
                            break;
                    }
                } catch (MaksuturvaGatewayException $e) {
                    $msg = $this->l('Error while communicating with maksuturva: Invalid hash or network error');
                }
                break;
        }

        $this->context->smarty->assign(array(
            'this_path' => $this->getPath(),
            'ps_version' => Tools::substr(_PS_VERSION_, 0, 3),
            'mt_pmt_id' => $payment->getPmtReference(),
            'mt_pmt_status_message' => $msg,
        ));
        if ($payment->includesSurcharge()) {
            $this->context->smarty->assign(array(
                'mt_pmt_surcharge_message' => sprintf(
                    'This order has been subject to a payment surcharge of %s EUR',
                    $payment->getSurcharge()
                ),
            ));
        }

        if (_PS_VERSION_ >= '1.6') {
            return $this->display(__FILE__, 'views/templates/admin/payment_status_twbs.tpl');
        } else {
            return $this->display(__FILE__, 'views/templates/admin/payment_status.tpl');
        }
    }

    /**
     * Callback for the `orderDetailDisplayed` hook.
     *
     * Adds information about payment surcharges that may have been applied to the order.
     *
     * @param array $params
     * @return string
     */
    public function hookOrderDetailDisplayed($params)
    {
        if (!isset($params['order'])) {
            return '';
        }

        try {
            $payment = new MaksuturvaPayment((int)$params['order']->id);
        } catch (Exception $e) {
            // The order was not payed using Maksuturva.
            return '';
        }

        if (!$payment->includesSurcharge()) {
            return '';
        }

        $this->context->smarty->assign(array(
            'this_path' => $this->getPath(),
            'mt_pmt_surcharge_message' => sprintf(
                'This order has been subject to a payment surcharge of %s EUR',
                $payment->getSurcharge()
            ),
        ));

        if (_PS_VERSION_ >= '1.6') {
            return $this->display(__FILE__, 'views/templates/hook/order_details_twbs.tpl');
        } else {
            return $this->display(__FILE__, 'views/templates/hook/order_details.tpl');
        }
    }

    /**
     * Callback for the `displayPDFInvoice` hook.
     *
     * Adds information about payment surcharges that may have been applied to the order.
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayPDFInvoice($params)
    {
        if (!isset($params['object']) || !($params['object'] instanceof OrderInvoice)) {
            return '';
        }

        try {
            $payment = new MaksuturvaPayment((int)$params['object']->id_order);
        } catch (Exception $e) {
            // The order was not payed using Maksuturva.
            return '';
        }

        if (!$payment->includesSurcharge()) {
            return '';
        }

        $notice = sprintf('This order has been subject to a payment surcharge of %s EUR', $payment->getSurcharge());
        return 'Maksuturva - ' . $notice;
    }

    /**
     * Callback for the `PDFInvoice` hook.
     *
     * Adds information about payment surcharges that may have been applied to the order.
     *
     * @param array $params
     * @return string
     */
    public function hookPDFInvoice($params)
    {
        if (!isset($params['pdf'], $params['id_order'])) {
            return '';
        }

        try {
            $payment = new MaksuturvaPayment((int)$params['id_order']);
        } catch (Exception $e) {
            // The order was not payed using Maksuturva.
            return '';
        }

        if (!$payment->includesSurcharge()) {
            return '';
        }

        $notice = sprintf('This order has been subject to a payment surcharge of %s EUR', $payment->getSurcharge());

        $params['pdf']->Ln(6);
        $params['pdf']->Cell(0, 0, 'Maksuturva - ' . $notice, 0, 0, 'R');
        $params['pdf']->Ln(4);

        return $notice;
    }

    /**
     * Validates a payment and registers the order in PrestaShop.
     *
     * @param CartCore|Cart $cart
     * @param CustomerCore|Customer $customer
     * @param array $params
     * @return string
     */
    public function validatePayment(Cart $cart, Customer $customer, array $params)
    {
        if (!$this->checkCurrency($cart)) {
            return $this->l('The cart currency is not supported');
        }

        $gateway = new MaksuturvaGatewayImplementation($this, $cart);
        $validator = $gateway->validatePayment($params);

        if ($validator->getStatus() === 'error') {
            $id_order_state = $this->getConfig('PS_OS_ERROR');
            $message = implode(', ', $validator->getErrors());
        } elseif ($validator->getStatus() === 'delayed') {
            $id_order_state = $this->getConfig('MAKSUTURVA_OS_AUTHORIZATION');
            $message = $this->l('Payment is awaiting confirmation');
        } elseif ($validator->getStatus() === 'cancel') {
            $id_order_state = $this->getConfig('PS_OS_CANCELED');
            $message = $this->l('Payment was canceled');
        } else {
            $id_order_state = $this->getConfig('PS_OS_PAYMENT');
            $message = $this->l('Payment was successfully registered');
        }

        $this->validateOrder(
            (int)$cart->id,
            (int)$id_order_state,
            $cart->getOrderTotal(),
            $this->displayName,
            $message,
            array(),
            (int)$cart->id_currency,
            false,
            $customer->secure_key
        );

        /** @var OrderCore|Order $order */
        $order = new Order((int)$this->currentOrder);
        if (!Validate::isLoadedObject($order)) {
            return $this->l('Failed to find order');
        }

        $payment = MaksuturvaPayment::create(array(
            'id_order' => (int)$order->id,
            'status' => (int)$id_order_state,
            'data_sent' => $gateway->getFieldArray(),
            'data_received' => $params,
        ));

        if ($payment->includesSurcharge()) {
            $surcharge = $payment->getSurcharge();
            $order->total_paid += $surcharge;
            $order->total_paid_tax_excl += $surcharge;
            $order->total_paid_tax_incl += $surcharge;
            if (method_exists($order, 'addOrderPayment')) {
                $order->addOrderPayment($surcharge);
            } else {
                $order->total_paid_real += $surcharge;
                $order->update();
            }
        }

        return $message;
    }

    /**
     * Checks if the cart currency is one of the modules supported currencies.
     *
     * Currently, only EUR is supported.
     *
     * @param CartCore|Cart $cart
     * @return boolean
     */
    public function checkCurrency(Cart $cart)
    {
        /** @var CurrencyCore|Currency $check_currency */
        $check_currency = new Currency($cart->id_currency);
        $supported_currencies = array('EUR');
        $currencies = $this->getCurrency($cart->id_currency);

        if (is_array($currencies) && count($currencies) > 0) {
            foreach ($currencies as $currency) {
                if ($check_currency->id == $currency['id_currency']
                    && in_array($check_currency->iso_code, $supported_currencies)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @return string
     */
    public function getPathSSL()
    {
        return Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/';
    }

    /**
     * @param array $params
     * @return string
     */
    public function getPaymentUrl(array $params = array())
    {
        if (_PS_VERSION_ >= '1.5') {
            /** @var LinkCore|Link $link */
            $link = $this->context->link;
            return $link->getModuleLink($this->name, 'validation', $params, true/* SSL */);
        } else {
            return $this->getPathSSL() . 'validation.php' . (!empty($params) ? ('?' . http_build_query($params)) : '');
        }
    }

    /**
     * @return mixed
     */
    public function getSellerId()
    {
        return $this->getConfig('MAKSUTURVA_SELLER_ID');
    }

    /**
     * @return bool
     */
    public function isSandbox()
    {
        return ((int)$this->getConfig('MAKSUTURVA_SANDBOX') === 1);
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->getConfig('MAKSUTURVA_SECRET_KEY');
    }

    /**
     * @return mixed
     */
    public function getSecretKeyVersion()
    {
        return $this->getConfig('MAKSUTURVA_SECRET_KEY_VERSION');
    }

    /**
     * @return mixed
     */
    public function getGatewayUrl()
    {
        return $this->getConfig('MAKSUTURVA_URL');
    }

    /**
     * @return mixed
     */
    public function getEncoding()
    {
        return $this->getConfig('MAKSUTURVA_ENCODING');
    }

    /**
     * @return mixed
     */
    public function getPaymentIdPrefix()
    {
        return $this->getConfig('MAKSUTURVA_PMT_ID_PREFIX');
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getConfig($key)
    {
        return Configuration::get($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    private function setConfig($key, $value)
    {
        return Configuration::updateValue($key, $value);
    }

    /**
     * Registers all the hooks used by the module.
     *
     * @return bool
     */
    private function registerHooks()
    {
        return ($this->registerHook('header')
            && $this->registerHook('payment')
            && $this->registerHook('paymentReturn')
            && $this->registerHook('adminOrder')
            && $this->registerHook('orderDetailDisplayed')
            && $this->registerHook('PDFInvoice')
        );
    }

    /**
     * Creates the initial config for the module.
     *
     * @return bool
     */
    private function createConfig()
    {
        return ($this->setConfig('MAKSUTURVA_SELLER_ID', '')
            && $this->setConfig('MAKSUTURVA_SECRET_KEY', '')
            && $this->setConfig('MAKSUTURVA_SECRET_KEY_VERSION', '001')
            && $this->setConfig('MAKSUTURVA_URL', 'https://www.maksuturva.fi')
            && $this->setConfig('MAKSUTURVA_PMT_ID_PREFIX', '')
            && $this->setConfig('MAKSUTURVA_ENCODING', 'UTF-8')
            && $this->setConfig('MAKSUTURVA_SANDBOX', '1'));
    }

    /**
     * Deletes the config variables for the module.
     *
     * @return bool
     */
    private function deleteConfig()
    {
        foreach (self::$config_keys as $key) {
            if (Configuration::get($key) && !Configuration::deleteByName($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates any additional db tables used by the module.
     *
     * @return bool
     */
    private function createTables()
    {
        return Db::getInstance()->execute(sprintf(
            'CREATE TABLE IF NOT EXISTS `%smt_payment` (
			  `id_order` int(10) unsigned NOT NULL,
			  `status` int(10) unsigned NOT NULL DEFAULT 0,
			  `data_sent` LONGBLOB NULL DEFAULT NULL,
			  `data_received` LONGBLOB NULL DEFAULT NULL,
			  `date_add` DATETIME NOT NULL,
			  `date_upd` DATETIME NULL DEFAULT NULL,
			  PRIMARY KEY (`id_order`)
			) ENGINE=%s DEFAULT CHARSET=utf8;',
            _DB_PREFIX_,
            _MYSQL_ENGINE_
        ));
    }

    /**
     * Creates additional order states used by the module.
     *
     * @return bool
     */
    private function createOrderStates()
    {
        $translations = array(
            'en' => 'Pending confirmation from Maksuturva',
            'fr' => 'En attendant la confirmation de Maksuturva',
            'fi' => 'Odottaa vahvistusta Maksuturvalta',
        );

        $states = OrderState::getOrderStates($this->getConfig('PS_LANG_DEFAULT'));
        foreach ($states as $state) {
            if (isset($state['name']) && in_array($state['name'], $translations)) {
                return $this->setConfig('MAKSUTURVA_OS_AUTHORIZATION', (int)$state['id_order_state']);
            }
        }

        /** @var OrderStateCore|OrderState $state */
        $state = new OrderState();
        $state->name = array();
        foreach (Language::getLanguages() as $language) {
            if (isset($translations[$language['iso_code']])) {
                $state->name[$language['id_lang']] = $translations[$language['iso_code']];
            } else {
                $state->name[$language['id_lang']] = $translations['en'];
            }
        }
        $state->send_email = false;
        $state->color = '#DDEEFF';
        $state->hidden = false;
        $state->delivery = false;
        $state->logable = true;
        $state->invoice = true;
        if (_PS_VERSION_ >= '1.5') {
            $state->module_name = $this->name;
        }
        if ($state->add()) {
            copy(_PS_MODULE_DIR_ . $this->name . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $state->id . '.gif');
        }

        if (!$this->setConfig('MAKSUTURVA_OS_AUTHORIZATION', (int)$state->id)) {
            return false;
        }

        // Older PS versions will not have these order states in the configuration table, but only as constants.
        // Enter the states into the configuration table with the value of the constant for easier use later on.
        $order_states = array('PS_OS_PAYMENT', 'PS_OS_CANCELED', 'PS_OS_ERROR', 'PS_OS_OUTOFSTOCK');
        foreach ($order_states as $os) {
            if (!$this->getConfig($os)) {
                $const_os = '_' . $os . '_';
                if (defined($const_os)) {
                    if (!$this->setConfig($os, (int)constant($const_os))) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @return string
     */
    private function displayForm()
    {
        $form_data = array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Account Settings'),
                        'icon' => 'icon-user'
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Seller ID'),
                            'name' => 'MAKSUTURVA_SELLER_ID',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Secret Key'),
                            'name' => 'MAKSUTURVA_SECRET_KEY',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Secret Key Version'),
                            'name' => 'MAKSUTURVA_SECRET_KEY_VERSION',
                            'required' => true
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => (_PS_VERSION_ < '1.6') ? 'button' : null,
                    )
                ),
            ),
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Advanced Settings'),
                        'icon' => 'icon-cog'
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => $this->l('Communication URL'),
                            'name' => 'MAKSUTURVA_URL',
                            'required' => true
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Payment Prefix'),
                            'name' => 'MAKSUTURVA_PMT_ID_PREFIX',
                            'required' => false
                        ),
                        array(
                            'type' => 'radio',
                            'label' => $this->l('Sandbox mode (tests)'),
                            'name' => 'MAKSUTURVA_SANDBOX',
                            'class' => 't',
                            'required' => true,
                            'is_bool' => true,
                            'values' => array(
                                array(
                                    'id' => 'sandbox_mode_0',
                                    'value' => 0,
                                    'label' => $this->l('Off')
                                ),
                                array(
                                    'id' => 'sandbox_mode_1',
                                    'value' => 1,
                                    'label' => $this->l('On')
                                ),
                            ),
                        ),
                        array(
                            'type' => 'radio',
                            'label' => $this->l('Communication Encoding'),
                            'name' => 'MAKSUTURVA_ENCODING',
                            'class' => 't',
                            'required' => true,
                            'is_bool' => false,
                            'values' => array(
                                array(
                                    'id' => 'mks_utf',
                                    'value' => 'UTF-8',
                                    'label' => 'UTF-8'
                                ),
                                array(
                                    'id' => 'mks_iso',
                                    'value' => 'ISO-8859-1',
                                    'label' => 'ISO-8859-1'
                                ),
                            ),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
                        'class' => (_PS_VERSION_ < '1.6') ? 'button' : null,
                    )
                ),
            )
        );

        /** @var HelperFormCore|HelperForm $helper */
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->title = $this->displayName;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->table = $this->table;
        $helper->submit_action = 'submit' . $this->name;
        $helper->show_toolbar = false;
        $helper->default_form_language = (int)$this->getConfig('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = $this->getConfig('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? $this->getConfig('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->tpl_vars = array(
            'fields_value' => $this->getFormConfig(),
        );

        return $helper->generateForm($form_data);
    }

    /**
     * @return array
     */
    private function getFormConfig()
    {
        return array(
            'MAKSUTURVA_SELLER_ID' => Tools::getValue(
                'MAKSUTURVA_SELLER_ID',
                $this->getConfig('MAKSUTURVA_SELLER_ID')
            ),
            'MAKSUTURVA_SECRET_KEY' => Tools::getValue(
                'MAKSUTURVA_SECRET_KEY',
                $this->getConfig('MAKSUTURVA_SECRET_KEY')
            ),
            'MAKSUTURVA_SECRET_KEY_VERSION' => Tools::getValue(
                'MAKSUTURVA_SECRET_KEY_VERSION',
                $this->getConfig('MAKSUTURVA_SECRET_KEY_VERSION')
            ),
            'MAKSUTURVA_URL' => Tools::getValue(
                'MAKSUTURVA_URL',
                $this->getConfig('MAKSUTURVA_URL')
            ),
            'MAKSUTURVA_PMT_ID_PREFIX' => Tools::getValue(
                'MAKSUTURVA_PMT_ID_PREFIX',
                $this->getConfig('MAKSUTURVA_PMT_ID_PREFIX')
            ),
            'MAKSUTURVA_SANDBOX' => Tools::getValue(
                'MAKSUTURVA_SANDBOX',
                $this->getConfig('MAKSUTURVA_SANDBOX')
            ),
            'MAKSUTURVA_ENCODING' => Tools::getValue(
                'MAKSUTURVA_ENCODING',
                $this->getConfig('MAKSUTURVA_ENCODING')
            ),
        );
    }

    /**
     * Handles the form POSTing
     * 1) administration area, configurations
     */
    private function postProcess()
    {
        $html = '';
        $errors = array();

        if (Tools::isSubmit('submitmaksuturva')) {
            $sandbox = Tools::getValue('MAKSUTURVA_SANDBOX');
            $seller_id = Tools::getValue('MAKSUTURVA_SELLER_ID');
            $secret_key = Tools::getValue('MAKSUTURVA_SECRET_KEY');
            $secret_key_version = Tools::getValue('MAKSUTURVA_SECRET_KEY_VERSION');
            $encoding = Tools::getValue('MAKSUTURVA_ENCODING');
            $url = Tools::getValue('MAKSUTURVA_URL');
            $pmt_prefix = Tools::getValue('MAKSUTURVA_PMT_ID_PREFIX');

            if ($sandbox == '0') {
                if (empty($seller_id) || Tools::strlen($seller_id) > 15) {
                    $errors[] = $this->l('Invalid Seller ID');
                }
                if (empty($secret_key)) {
                    $errors[] = $this->l('Invalid Secret Key');
                }
            }
            if (!preg_match('/^[0-9]{3}$/', (string)$secret_key_version)) {
                $errors[] = $this->l('Invalid Secret Key Version. Should be numeric and 3 digits long, e.g. 001');
            }
            if ($encoding != 'UTF-8' && $encoding != 'ISO-8859-1') {
                $errors[] = $this->l('Invalid Encoding');
            }
            if ($sandbox != '0' && $sandbox != '1') {
                $errors[] = $this->l('Invalid Sandbox flag');
            }
            if (!Validate::isUrl($url)) {
                $errors[] = $this->l('Communication url is invalid');
            }
            if (!empty($pmt_prefix) && !preg_match('/^[a-z_\-0-9]+/i', $pmt_prefix)) {
                $errors[] = $this->l('Payment prefix is invalid');
            }

            if (!sizeof($errors)) {
                $this->setConfig('MAKSUTURVA_SELLER_ID', trim($seller_id));
                $this->setConfig('MAKSUTURVA_SECRET_KEY', trim($secret_key));
                $this->setConfig('MAKSUTURVA_SECRET_KEY_VERSION', $secret_key_version);
                $this->setConfig('MAKSUTURVA_ENCODING', $encoding);
                $this->setConfig('MAKSUTURVA_SANDBOX', $sandbox);
                $this->setConfig('MAKSUTURVA_URL', $url);
                $this->setConfig('MAKSUTURVA_PMT_ID_PREFIX', $pmt_prefix);
                $html .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $html .= $this->displayError(implode('<br />', $errors));
            }
        }

        return $html;
    }
}
