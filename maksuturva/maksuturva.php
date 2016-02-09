<?php
/**
 * 2016 Maksuturva Group Oy
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

/**
 * Payment module for accepts payments using Maksuturva.
 *
 * @property ContextCore|Context $context
 *
 * @method string l($string, $specific = false)
 * @method string displayConfirmation($string)
 * @method string displayWarning($warning)
 * @method string displayError($error)
 */
class Maksuturva extends PaymentModule
{
	/**
	 * @var array
	 */
	public $config = array();

	/**
	 * @var array
	 */
    private $_mandatoryFields = array(
    	"pmt_action",
    	"pmt_version",
    	"pmt_id",
    	"pmt_reference",
    	"pmt_amount",
    	"pmt_currency",
    	"pmt_sellercosts",
    	"pmt_paymentmethod",
    	"pmt_escrow",
    	"pmt_hash"
    );

	/**
	 * Class constructor: assign some configuration values
	 */
	public function __construct()
	{
		$this->name = 'maksuturva';
		$this->tab = 'payments_gateways';
		$this->version = '122';
		$this->author = 'Maksuturva';

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		$this->_checkConfig(false);
		$this->displayName = $this->l('ADMIN: Maksuturva');
		$this->description = $this->l('ADMIN: Accepts payments using Maksuturva');
		$this->_errors = array();

		$this->bootstrap = true;

		parent::__construct();
		$this->confirmUninstall = $this->l('ADMIN: Are you sure you want to delete Maksuturva module?');

		/* For 1.4.3 and less compatibility */
		$updateConfig = array(
			'PS_OS_CHEQUE' => 1,
			'PS_OS_PAYMENT' => 2,
			'PS_OS_PREPARATION' => 3,
			'PS_OS_SHIPPING' => 4,
			'PS_OS_DELIVERED' => 5,
			'PS_OS_CANCELED' => 6,
			'PS_OS_REFUND' => 7,
			'PS_OS_ERROR' => 8,
			'PS_OS_OUTOFSTOCK' => 9,
			'PS_OS_BANKWIRE' => 10,
			'PS_OS_PAYPAL' => 11,
			'PS_OS_WS_PAYMENT' => 12
		);
		foreach ($updateConfig as $u => $v) {
			if (!Configuration::get($u) || (int)Configuration::get($u) < 1) {
				if (defined('_'.$u.'_') && (int)constant('_'.$u.'_') > 0) {
					Configuration::updateValue($u, constant('_'.$u.'_'));
				} else {
					Configuration::updateValue($u, $v);
				}
			}
		}

		$this->_checkConfig();

		/* Backward compatibility */
		if (_PS_VERSION_ < '1.5')
			require_once(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
	}

	/**
	 * Retrieves the configuration parameters and checks the existence of
	 * all required configuration entries.
	 * @return boolean
	 */
	private function _checkConfig($warn = true)
	{
		$fail = false;
		$config = Configuration::getMultiple($this->_getConfigKeys());
		foreach ($this->_getConfigKeys() as $key) {
			if (isset($config[$key])) {
				$this->config[$key] = $config[$key];
			} else {
				if ($warn) {
					$this->warning .= $this->l($key . '_WARNING') . ', ';
				}
				$fail = true;
			}
		}
		if (!sizeof(Currency::checkPaymentCurrencies($this->id))) {
			if ($warn) {
				$this->warning .= $this->l('ADMIN: No currency set for this module') . ', ';
			}
			$fail = true;
		}
		if ($warn) {
			$this->warning = trim($this->warning,  ', ');
		}
		return !$fail;
	}

    /**
     * Module keys are fetched using this method
     * @return array
     */
    private function _getConfigKeys()
    {
    	return array(
	      	'MAKSUTURVA_SELLER_ID',
	      	'MAKSUTURVA_SECRET_KEY',
    		'MAKSUTURVA_SECRET_KEY_VERSION',
	        'MAKSUTURVA_URL',
	      	'MAKSUTURVA_ENCODING',
	      	'MAKSUTURVA_SANDBOX',
    		'MAKSUTURVA_OS_AUTHORIZATION',
    		'MAKSUTURVA_PAYMENT_FEE_ID'    		
      	);
    }

    /**
     * Installs the module (non-PHPdoc)
     * @see PaymentModuleCore::install()
     */
	public function install()
	{
		// hooks
		if (!parent::install()
			OR !$this->registerHook('payment')
			OR !$this->registerHook('paymentReturn')
			OR !$this->registerHook('rightColumn')
			OR !$this->registerHook('adminOrder')) {
			return false;
		}

		// config keys/values
		if (!Configuration::updateValue('MAKSUTURVA_SELLER_ID', '') ||
			!Configuration::updateValue('MAKSUTURVA_SECRET_KEY', '') ||
			!Configuration::updateValue('MAKSUTURVA_SECRET_KEY_VERSION', '001') ||
			!Configuration::updateValue('MAKSUTURVA_URL', 'https://www.maksuturva.fi') ||
			!Configuration::updateValue('MAKSUTURVA_ENCODING', 'UTF-8') ||
			!Configuration::updateValue('MAKSUTURVA_SANDBOX', '1') ||
    		!Configuration::updateValue('MAKSUTURVA_PAYMENT_FEE_ID', '')) {
			return false;
		}

		/* Set database - this table is not removed later (if audit is needed) */
		$dbCreate = Db::getInstance()->execute(
			'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'mk_status` (
			  `id_cart` int(10) unsigned NOT NULL,
			  `id_order` int(10) unsigned DEFAULT NULL,
			  `payment_status` int(10) unsigned NOT NULL DEFAULT 0,
			  PRIMARY KEY (`id_cart`)
			) ENGINE='._MYSQL_ENGINE_.'  DEFAULT CHARSET=utf8;'
		);
		if (!$dbCreate) {
			return false;
		}

		// insert maksuturva's logo to order status for better management
		if (!Configuration::get('MAKSUTURVA_OS_AUTHORIZATION'))
		{
			$orderState = new OrderState();
			$orderState->name = array();
			foreach (Language::getLanguages() AS $language)
			{
				if (strtolower($language['iso_code']) == 'fr') {
					$orderState->name[$language['id_lang']] = 'En attendant la confirmation de Maksuturva';
				} else {
					$orderState->name[$language['id_lang']] = 'Pending confirmation from Maksuturva';
				}
			}
			$orderState->send_email = false;
			$orderState->color = '#DDEEFF';
			$orderState->hidden = false;
			$orderState->delivery = false;
			$orderState->logable = true;
			$orderState->invoice = true;
			if ($orderState->add()) {
				copy(dirname(__FILE__).'/logo.gif', dirname(__FILE__).'/../../img/os/'.(int)$orderState->id.'.gif');
			}
			Configuration::updateValue('MAKSUTURVA_OS_AUTHORIZATION', (int)$orderState->id);
		}
		return true;
	}
	
	public function execPayment($cart)
	{
		global $cookie, $smarty, $customer;
		
		if (!$this->active) {
			return;
		}
		if (!$this->checkCurrency($cart)) {
			Tools::redirectLink(__PS_BASE_URI__.'order.php');
		}

		// build up the "post to pay" form
		require_once dirname(__FILE__) . "/MaksuturvaGatewayImplementation.php";
		$gateway = new MaksuturvaGatewayImplementation($cart->id, $cart, Configuration::get('MAKSUTURVA_ENCODING'), $this);

		// insert the order in mk_status to be verified later
		$this->updateCartInMkStatusByIdCart($cart->id);

		$smarty->assign(
			array(
				'nbProducts' => $cart->nbProducts(),
				'cust_currency' => $cart->id_currency,
				'currencies' => $this->getCurrency((int)$cart->id_currency),
				'total' => $cart->getOrderTotal(true, Cart::BOTH),
				'this_path' => $this->_path,
				'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
				'form_action' => MaksuturvaGatewayImplementation::getPaymentUrl(Configuration::get('MAKSUTURVA_URL')),
				'maksuturva_fields' => $gateway->getFieldArray(),
			)
		);

		return $this->display(__FILE__, 'payment_execution.tpl');
	}
	
	/**
	 * Uninstalls the module (non-PHPdoc)
	 * @see PaymentModuleCore::uninstall()
	 */
	public function uninstall()
	{
		foreach ($this->_getConfigKeys() as $key) {
			if (Configuration::get($key)) {
				if (!Configuration::deleteByName($key)) {
					return false;
				}
			}
		}
		if (!parent::uninstall()) {
			return false;
		}
		return true;
	}

	/**
	 * Administration:
	 * This method is responsible for providing
	 * content to administration area
	 */
	public function getContent()
	{
		$html = '';
		$html .= $this->_postProcess();
		$html .= $this->_displayForm();
		return $html;
	}

	/**
	 * @return string
	 */
	private function _displayForm()
	{
		$form_data = array(
			array(
				'form' => array(
					'legend' => array(
						'title' => $this->l('ADMIN: Account Settings'),
						'icon' => 'icon-user'
					),
					'input' => array(
						array(
							'type' => 'text',
							'label' => $this->l('ADMIN: Seller ID'),
							'name' => 'MAKSUTURVA_SELLER_ID',
							'required' => true
						),
						array(
							'type' => 'text',
							'label' => $this->l('ADMIN: Secret Key'),
							'name' => 'MAKSUTURVA_SECRET_KEY',
							'required' => true
						),
						array(
							'type' => 'text',
							'label' => $this->l('ADMIN: Secret Key Version'),
							'name' => 'MAKSUTURVA_SECRET_KEY_VERSION',
							'required' => true
						),
						array(
							'type' => 'text',
							'label' => $this->l('ADMIN: Additional payment fee product ID'),
							'name' => 'MAKSUTURVA_PAYMENT_FEE_ID',
							'required' => false
						),
					),
					'submit' => array(
						'title' => $this->l('ADMIN: Save'),
						'class' => (_PS_VERSION_ < '1.6') ? 'button' : null,
					)
				),
			),
			array(
				'form' => array(
					'legend' => array(
						'title' => $this->l('ADMIN: Advanced Settings'),
						'icon' => 'icon-cog'
					),
					'input' => array(
						array(
							'type' => 'text',
							'label' => $this->l('ADMIN: Communication URL'),
							'name' => 'MAKSUTURVA_URL',
							'required' => true
						),
						array(
							'type' => 'radio',
							'label' => $this->l('ADMIN: Sandbox mode (tests)'),
							'name' => 'MAKSUTURVA_SANDBOX',
							'class' => 't',
							'required' => true,
							'is_bool' => true,
							'values' => array(
								array(
									'id'    => 'sandbox_mode_0',
									'value' => 0,
									'label' => $this->l('ADMIN: Off')
								),
								array(
									'id'    => 'sandbox_mode_1',
									'value' => 1,
									'label' => $this->l('ADMIN: On')
								),
							),
						),
						array(
							'type' => 'radio',
							'label' => $this->l('ADMIN: Communication Encoding'),
							'name' => 'MAKSUTURVA_ENCODING',
							'class' => 't',
							'required' => true,
							'is_bool' => false,
							'values' => array(
								array(
									'id'    => 'mks_utf',
									'value' => 'UTF-8',
									'label' => 'UTF-8'
								),
								array(
									'id'    => 'mks_iso',
									'value' => 'ISO-8859-1',
									'label' => 'ISO-8859-1'
								),
							),
						),
					),
					'submit' => array(
						'title' => $this->l('ADMIN: Save'),
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
		$helper->submit_action = 'submit'.$this->name;
		$helper->show_toolbar = false;
		$helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
			? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
			: 0;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = $this->getAdminLink('AdminModules', false)
			.'&configure='.$this->name
			.'&tab_module='.$this->tab
			.'&module_name='.$this->name;
		$helper->tpl_vars = array(
			'fields_value' => $this->_getFormConfig(),
		);

		return $helper->generateForm($form_data);
	}

	/**
	 * @return array
	 */
	private function _getFormConfig()
	{
		return array(
			'MAKSUTURVA_SELLER_ID' => Tools::getValue('MAKSUTURVA_SELLER_ID', Configuration::get('MAKSUTURVA_SELLER_ID')),
			'MAKSUTURVA_SECRET_KEY' => Tools::getValue('MAKSUTURVA_SECRET_KEY', Configuration::get('MAKSUTURVA_SECRET_KEY')),
			'MAKSUTURVA_SECRET_KEY_VERSION' => Tools::getValue('MAKSUTURVA_SECRET_KEY_VERSION', Configuration::get('MAKSUTURVA_SECRET_KEY_VERSION')),
			'MAKSUTURVA_PAYMENT_FEE_ID' => Tools::getValue('MAKSUTURVA_PAYMENT_FEE_ID', Configuration::get('MAKSUTURVA_PAYMENT_FEE_ID')),
			'MAKSUTURVA_URL' => Tools::getValue('MAKSUTURVA_URL', Configuration::get('MAKSUTURVA_URL')),
			'MAKSUTURVA_SANDBOX' => Tools::getValue('MAKSUTURVA_SANDBOX', Configuration::get('MAKSUTURVA_SANDBOX')),
			'MAKSUTURVA_ENCODING' => Tools::getValue('MAKSUTURVA_ENCODING', Configuration::get('MAKSUTURVA_ENCODING')),
		);
	}

	/**
	 * Handles the form POSTing
	 * 1) administration area, configurations
	 */
	private function _postProcess()
	{
		$html = '';

		if (Tools::isSubmit('submitmaksuturva')) {
			if (Tools::getValue('MAKSUTURVA_SANDBOX') == "0") {
				if (strlen(Tools::getValue('MAKSUTURVA_SELLER_ID')) > 15 || strlen(Tools::getValue('MAKSUTURVA_SELLER_ID')) == 0) {
					$this->_errors[] = $this->l('ADMIN: Invalid Seller ID');
				}
				if (strlen(Tools::getValue('MAKSUTURVA_SECRET_KEY')) == 0) {
					$this->_errors[] = $this->l('ADMIN: Invalid Secret Key');
				}
			}
			if (!Maksuturva::validateHashGenerationNumber(Tools::getValue('MAKSUTURVA_SECRET_KEY_VERSION'))) {
				$this->_errors[] = $this->l('ADMIN: Invalid Secret Key Version. Should be numeric and 3 digits long, e.g. 001');
			}
			if (Tools::getValue('MAKSUTURVA_ENCODING') != "UTF-8" && Tools::getValue('MAKSUTURVA_ENCODING') != "ISO-8859-1") {
				$this->_errors[] = $this->l('ADMIN: Invalid Encoding');
			}
			if (Tools::getValue('MAKSUTURVA_SANDBOX') != "0" && Tools::getValue('MAKSUTURVA_SANDBOX') != "1") {
				$this->_errors[] = $this->l('ADMIN: Invalid Sandbox flag');
			}
			if (Tools::getValue('MAKSUTURVA_URL') != NULL && !Validate::isUrl(Tools::getValue('MAKSUTURVA_URL'))) {
				$this->_errors[] = $this->l('ADMIN: Communication url is invalid');
			}

            $product = new Product((int)preg_replace("/[^0-9]/", "", Tools::getValue('MAKSUTURVA_PAYMENT_FEE_ID')));
            if (Tools::getValue('MAKSUTURVA_PAYMENT_FEE_ID') != NULL){
                if (!Validate::isLoadedObject($product)) {
                    $this->_errors[] = $this->l('ADMIN: Additional payment fee product id is invalid');
                }
            }

			if (!sizeof($this->_errors)) {
				Configuration::updateValue('MAKSUTURVA_SELLER_ID', Tools::getValue('MAKSUTURVA_SELLER_ID'));
				Configuration::updateValue('MAKSUTURVA_SECRET_KEY', trim(Tools::getValue('MAKSUTURVA_SECRET_KEY')));
				Configuration::updateValue('MAKSUTURVA_SECRET_KEY_VERSION', Tools::getValue('MAKSUTURVA_SECRET_KEY_VERSION'));
				Configuration::updateValue('MAKSUTURVA_ENCODING', trim(Tools::getValue('MAKSUTURVA_ENCODING')));
				Configuration::updateValue('MAKSUTURVA_SANDBOX', trim(Tools::getValue('MAKSUTURVA_SANDBOX')));
				Configuration::updateValue('MAKSUTURVA_URL', trim(Tools::getValue('MAKSUTURVA_URL')));
				Configuration::updateValue('MAKSUTURVA_PAYMENT_FEE_ID', trim(Tools::getValue('MAKSUTURVA_PAYMENT_FEE_ID')));
				$html .= $this->displayConfirmation($this->l('ADMIN: Settings updated'));
			} else {
				$html .= $this->displayError(implode('<br />', $this->_errors));
			}
		}

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
		if (_PS_VERSION_ >= '1.5')
			return $this->context->link->getAdminLink('AdminModules', false);

		$params = array('tab' => $controller);
		if ($with_token)
			$params['token'] = Tools::getAdminTokenLite($controller);

		return 'index.php?'.http_build_query($params);
	}

	/**
	 * Processes the payment
	 * @param array $params
	 */
	public function hookPayment($params)
	{
		global $smarty;
		
		if (!$this->active) {
			return;
		}

		// only EUR is supported - we validate it against
		// 1) shop (if it has EUR)
		// 2) cart (if it has only EUR products within)
		if (!$this->checkCurrency($params['cart'])) {
			return;
		}

		// render the form
		$smarty->assign(
			array(
				'this_path' => $this->_path,
				'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			)
		);

		return $this->display(__FILE__, 'payment.tpl');
	}

	/**
	 * Validates a paid or not-paid order, and redirect the
	 * user to the order confirmation with the updated status (paid, processing, error)
	 * @param Cart $cart
	 * @param Customer $customer
	 * @param array $parameters
	 */
	public function validatePayment($cart, $customer, $parameters)
	{
		// accumulate the errors
		$errors = array();
		$admin_messages = array();
		$action = "ok";
		if (isset($parameters["delayed"]) && $parameters["delayed"] == "1") {
			$action = "delayed";
		} else if (isset($parameters["cancel"]) && $parameters["cancel"] == "1") {
			$action = "cancel";
		} else if (isset($parameters["error"]) && $parameters["error"] == "1") {
			$action = "error";
		}

		// test the currency: EUR only
		if (!$this->checkCurrency($cart)) {
			$errors[] = $this->l('The cart currency is not supported');
		}

		$totalPaid = 0;
		// regular payment
  	    switch ($action) {
  	    	case "cancel":
  	    		break;

  	    	case "error":
  	    		$errors[] = $this->l('There was an error processing your payment. Please, try again or contact Suomen Maksuturva (www.maksuturva.fi)');
  	    		break;

  	    	case "delayed":
  	    		break;

  	    	// the default case tries to validate everything
  	    	case "ok":
  	    	default:
	  	    	$values = array();
	            // fields are mandatory, so we discard the request if it is empty
	            // Also when return through the error url given to maksuturva
	            foreach ($this->_mandatoryFields as $field) {
	            	if (isset($parameters[$field])) {
	            	    $values[$field] = $parameters[$field];
	                } else {
	                	$errors[] = $this->l('Missing payment field in response:') . " " . $field;
	                }
	            }

	  	    	// first, check if the cart id exists with the payment id provided
	      	    if (!isset($values['pmt_id']) || (intval($values['pmt_id']) - 100) != $cart->id) {
	      	    	$errors[] = $this->l('The payment didnt match any order');
	    	    }

	    	    // then, check if the mk_status knows of such cart_id
	    	    if (count($this->getCartInMkStatusByIdCart($cart->id)) != 1) {
	    	    	$errors[] = $this->l('Could not find an order related to payment');
	    	    }

	    		// now, validate the hash
	            require_once dirname(__FILE__) . '/MaksuturvaGatewayImplementation.php';
	            // instantiate the gateway with the original order
	        	$gateway = new MaksuturvaGatewayImplementation($cart->id, $cart, Configuration::get('MAKSUTURVA_ENCODING'), $this);
	    		// calculate the hash for order
	        	$calculatedHash = $gateway->generateReturnHash($values);
	        	// test the hash
	        	if (!($calculatedHash == $values['pmt_hash'])) {
	        		$errors[] = $this->l('The payment verification code does not match');
	        	}

	        	// validate amounts, values, etc
	        	// hash, reference number and sellercosts are validated separately, paymentmethod and escrow are not
	        	$ignore = array("pmt_hash", "pmt_paymentmethod", "pmt_reference", "pmt_sellercosts", "pmt_escrow");
	        	foreach ($values as $key => $value) {
	        		// just pass if ignore is on
	        		if (in_array($key, $ignore)) {
	        			continue;
	        		}
	        		if ($gateway->{$key} != $value) {
	        			$errors[] = $this->l('The following field differs from your order: ') .
	        				$key .
	        				" (" . $this->l('obtained') . " " . $value . ", " . $this->l('expecting') . " " . $gateway->{$key} . ")";
	        		}
	        	}
	        	
	        	//SELLERCOSTS VALIDATION
	        	$original_payment_feeFloat = floatval(str_replace(",", ".", $gateway->{'pmt_sellercosts'}));
	        	$new_payment_feeFloat = floatval(str_replace(",", ".", $values['pmt_sellercosts']));
	        	$customerTotalPaid = $new_payment_feeFloat + floatval(str_replace(",", ".", $values['pmt_amount']));
	        	if($original_payment_feeFloat != $new_payment_feeFloat){
	        		// validate that payment_fee have not dropped
	        		if($new_payment_feeFloat <  $original_payment_feeFloat) {
	        			$errors[] = $this->l('Order is not saved. 	Invalid change in shipping and payment costs') .
        				 " (" . $this->l('obtained') . " " . $values['pmt_sellercosts'] . ", " . $this->l('expecting') . " " . $gateway->{'pmt_sellercosts'} . "  )";
	        		}
	        		else {
	        			// validate additional costs product (given by admin in module configurations)
	        			// Product validation DOES NOT prevent saving the order
	        			$seller_costs_product = new Product((int)Configuration::get('MAKSUTURVA_PAYMENT_FEE_ID'));
						if (!Validate::isLoadedObject($seller_costs_product)) {
							$admin_messages[] = "***ADMIN: ".$this->l('ADMIN: Failed to add payment fee of')." ".number_format($new_payment_feeFloat - $original_payment_feeFloat, 2, ",", " ")." EUR) ";
							$admin_messages[] = $this->l('ADMIN: Payment fee product does not exist');
							$admin_messages[] = $this->l('ADMIN: Customer paid total of')." ".number_format($customerTotalPaid, 2, ",", " ")." EUR) ";
						}
						else {
							// PrestaShop 1.5+
							if(method_exists('Product', 'getProductName')){
								$seller_costs_product_name = Product::getProductName($seller_costs_product->id);
							}
							//PrestaShop 1.4
							else{
								$seller_costs_product_name = $seller_costs_product->name[$id_lang_default = (int)Configuration::get('PS_LANG_DEFAULT')];
							}
							if(Product::getQuantity($seller_costs_product->id) < 1) {
								$admin_messages[] = "***ADMIN: ".$this->l('ADMIN: Failed to add payment fee of')." ".number_format($new_payment_feeFloat - $original_payment_feeFloat, 2, ",", " ")." EUR) ";
								$admin_messages[] = $this->l('ADMIN: Product')." '".$seller_costs_product_name."' ".$this->l('ADMIN: has run out');
								$admin_messages[] = $this->l('ADMIN: Customer paid total of')." ".number_format($customerTotalPaid, 2, ",", " ")." EUR) ";
							}
							else{
								//everything ok, inserting new product row
								$cart->updateQty(1, (int)Configuration::get('MAKSUTURVA_PAYMENT_FEE_ID'));
							}
							if(number_format(floatval($seller_costs_product->getPrice()),2,',','') != number_format($new_payment_feeFloat-$original_payment_feeFloat, 2, ',','')){
								$admin_messages[] = "***ADMIN: ".$this->l('ADMIN: Customer paid additional payment fee')." (".number_format($new_payment_feeFloat - $original_payment_feeFloat, 2, ",", " ")." EUR) ".
										$this->l('ADMIN: differs from the product')." '".$seller_costs_product_name."' ".
										$this->l('ADMIN: price')." (".number_format($seller_costs_product->getPrice(), 2, ",", " ")." EUR)";
								$admin_messages[] = $this->l('ADMIN: Customer paid total of')." ".number_format($customerTotalPaid, 2, ",", " ")." EUR) ";
							}
						}
	        		}
	        	}
	        	
	        	
	        	// pmt_reference is calculated
	        	if ($gateway->calcPmtReferenceCheckNumber() != $values["pmt_reference"]) {
	        		$errors[] = $this->l('One or more verification parameters could not be validated');
	        	}
	        	$totalPaid = (($gateway->pmt_amount != "") ? floatval(str_replace(",", ".", $gateway->pmt_amount)) : 0);
	        	break;
  	    }

  	    $message = "";
  	    // for actions "ok" and "error"
  	    if (count($errors) > 0) {
  	    	$id_order_state = Configuration::get('PS_OS_ERROR');
  	    	// assembly the error message
  	    	foreach ($errors as $error) {
  	    		$message .= $error . ". ";
  	    	}
  	    } else if ($action == "delayed") {
  	    	$id_order_state = Configuration::get('MAKSUTURVA_OS_AUTHORIZATION');
  	    	$message = $this->l('Payment is awaiting confirmation');
  	    	$totalPaid = $cart->getOrderTotal();
  	    } else if ($action == "cancel") {
  	    	$id_order_state = Configuration::get('PS_OS_CANCELED');
  	    	$message = $this->l('Payment was canceled');
  	    } else {
  	    	$id_order_state = Configuration::get('PS_OS_PAYMENT');
  	    	$message = $this->l('Payment was successfully registered');
  	    }
		// Get current reference number
		require_once dirname(__FILE__) . '/MaksuturvaGatewayImplementation.php';
		$gateway = new MaksuturvaGatewayImplementation($cart->id, $cart, Configuration::get('MAKSUTURVA_ENCODING'), $this);
		$this->displayName .= ' PMT: ' . $gateway->getReferenceNumber($cart->id);
		
		// convert the message
		//change in rev 122, umlauts converted to url encoding in redirectLink-request
  	    $message = str_replace('\'', '', $message);
  	    $admin_message_string = "";
  	    if(count($admin_messages) > 0){
  	    	foreach ($admin_messages as $admin_message){
  	    		$admin_message_string .= $admin_message . ". ";
  	    	}
  	    }
  	    // finally, validate the order with error or not
  	    $this->validateOrder($cart->id, $id_order_state, $cart->getOrderTotal(), $this->displayName , $message.' '.$admin_message_string, array(), $cart->id_currency, false, $customer->secure_key);
		// fetch the recent-created order
		$order = new Order((int)($this->currentOrder));
		// attatch to mk_status
		$this->updateCartInMkStatusByIdCart($cart->id, (int)($this->currentOrder), $id_order_state);
		
		// redirect to display messages for this given order
		Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.(int)($cart->id).'&id_module='.(int)$this->id.'&id_order='.(int)($this->currentOrder).'&key='.$customer->secure_key.'&mks_msg=' . rawurlencode($message));
	}

	/**
	 * Used in order-confirmation.tpl to display a payment successful (or pending) message
	 * @param array $params
	 */
	public function hookPaymentReturn($params)
	{
		global $smarty;

		if (!$this->active) {
			return;
		}

		$state = $params['objOrder']->getCurrentState();
		switch ($state) {
			case Configuration::get('MAKSUTURVA_OS_AUTHORIZATION'):
				$status = "pending";
				break;

			case Configuration::get('PS_OS_PAYMENT'):
				$status = "ok";
				break;

			case Configuration::get('PS_OS_CANCELED'):
				$status = "cancel";
				break;

			case Configuration::get('PS_OS_ERROR'):
			default:
				$status = "error";
				break;

		}
		$smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
			'status' => $status,
			'message' => str_replace('. ', '.<br/>', Tools::getValue('mks_msg'))
		));
		return $this->display(__FILE__, 'payment_return.tpl');
	}

	/**
	 * Automatically verifies the order status in maksuturva
	 * @param array $params
	 */
	public function hookAdminOrder($params)
	{
		global $smarty;

		$mkStatus = $this->getCartInMkStatusByIdOrder($params["id_order"]);
		if (!$mkStatus || count($mkStatus) != 1) {
			return;
		}
		
		$order = new Order(intval($params["id_order"]));
		$cart = new Cart(intval($order->id_cart));

		$status = $mkStatus[0];
		$checkAgain = false;
		switch ($status["payment_status"]) {
			// only when not set (NULL or 0) or auth
			case Configuration::get('MAKSUTURVA_OS_AUTHORIZATION'):
			case "":
			case "0":
				require_once dirname(__FILE__) . "/MaksuturvaGatewayImplementation.php";
				$gateway = new MaksuturvaGatewayImplementation($cart->id, $cart, Configuration::get('MAKSUTURVA_ENCODING'), $this);

				$newStatus = $status["payment_status"];
				$messages = array();

				try {
		    		$response = $gateway->statusQuery();
		    	} catch (Exception $e) {
		    		$response = false;
		    	}

		    	// errors
		    	if ($response === false) {
		    		$messages[] = $this->l('ADMIN: Error while communicating with maksuturva: Invalid hash or network error');
		    		$checkAgain = true;
		    	} else {
			    	switch ($response["pmtq_returncode"]) {
			    		// set as paid if not already set
			    		case MaksuturvaGatewayImplementation::STATUS_QUERY_PAID:
			    		case MaksuturvaGatewayImplementation::STATUS_QUERY_PAID_DELIVERY:
			    		case MaksuturvaGatewayImplementation::STATUS_QUERY_COMPENSATED:
			    			$this->updateCartInMkStatusByIdCart($cart->id, $params["id_order"], Configuration::get('PS_OS_PAYMENT'));
			    			// try to change order's status
			    			if (intval($status["id_order"]) != 0) {
			    				$order = new Order(intval($status["id_order"]));
			    				$order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
			    			} else {
			    				$confirmMessage = $this->l('ADMIN: Payment confirmed by Maksuturva');
			    				$this->validateOrder($cart->id, Configuration::get('PS_OS_PAYMENT'), $cart->getOrderTotal(), $this->displayName, $confirmMessage, array(), $cart->id_currency, false, $customer->secure_key);
			    			}
			    			$messages[] = $this->l('ADMIN: The payment confirmation was received - payment accepted');
			    			break;

			    		// set payment cancellation with the notice
			    		// stored in response_text
			    		case MaksuturvaGatewayImplementation::STATUS_QUERY_PAYER_CANCELLED:
		    			case MaksuturvaGatewayImplementation::STATUS_QUERY_PAYER_CANCELLED_PARTIAL:
		    			case MaksuturvaGatewayImplementation::STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN:
		    			case MaksuturvaGatewayImplementation::STATUS_QUERY_PAYER_RECLAMATION:
		    			case MaksuturvaGatewayImplementation::STATUS_QUERY_CANCELLED:
		    				$this->updateCartInMkStatusByIdCart($cart->id, $params["id_order"], Configuration::get('PS_OS_CANCELED'));
					    	// try to change order's status
			    			if (intval($status["id_order"]) != 0) {
			    				$order = new Order(intval($status["id_order"]));
			    				$order->setCurrentState(Configuration::get('PS_OS_CANCELED'));
			    			} else {
			    				$confirmMessage = $this->l('Payment canceled in Maksuturva');
			    				$this->validateOrder($cart->id, Configuration::get('PS_OS_CANCELED'), $cart->getOrderTotal(), $this->displayName, $confirmMessage, array(), $cart->id_currency, false, $customer->secure_key);
			    			}

			    			$messages[] = $this->l('The payment was canceled in Maksuturva');
		    				break;

		    			// this is the case where the buyer changed the payment method while the
		    			// mk_status was created: we stop checking the order
		    			case MaksuturvaGatewayImplementation::STATUS_QUERY_NOT_FOUND:
		    				$messages[] = $this->l('ADMIN: The payment could not be tracked by Maksuturva. Check if the customer selected Maksuturva as payment method');
		    				$this->updateCartInMkStatusByIdCart($cart->id, $params["id_order"], Configuration::get('PS_OS_CANCELED'));
		    				break;

		    	        // no news for buyer and seller
			    		case MaksuturvaGatewayImplementation::STATUS_QUERY_FAILED:
			    		case MaksuturvaGatewayImplementation::STATUS_QUERY_WAITING:
		    			case MaksuturvaGatewayImplementation::STATUS_QUERY_UNPAID:
		    			case MaksuturvaGatewayImplementation::STATUS_QUERY_UNPAID_DELIVERY:
		    			default:
		    				$messages[] = $this->l('ADMIN: The payment is still awaiting for confirmation');
		    				$checkAgain = true;
			    			break;
			    	}
		    	}
				break;

			case Configuration::get('PS_OS_PAYMENT'):
				$messages[] = $this->l('ADMIN: The payment was confirmed by Maksuturva');
				break;

			case Configuration::get('PS_OS_CANCELED'):
				if (intval($status["id_order"]) != 0) {
					$order = new Order(intval($status["id_order"]));
					if ($order->getCurrentState() != Configuration::get('PS_OS_CANCELED')) {
						$messages[] = $this->l('ADMIN: The payment could not be tracked by Maksuturva. Check if the customer selected Maksuturva as payment method');
					} else {
						$messages[] = $this->l('ADMIN: The payment was canceled by the customer');
					}
				} else {
					$messages[] = $this->l('ADMIN: The payment was canceled by the customer');
				}
				break;

			case Configuration::get('PS_OS_ERROR'):
			default:
				$messages[] = $this->l('ADMIN: An error occurred and the payment was not confirmed. Please check manually');
				$checkAgain = true;
				break;
		}

		$messageHtml = "";
		foreach ($messages as $message) {
			$messageHtml .= "<p style='font-weight: bold;'>" . $message . "</p>";
		}
		if ($checkAgain) {
			$messageHtml .= "<p style='text-decoration: underline;'>" . $this->l('ADMIN: Refresh this page to check again') . "</p>";
		}

		$html = "<br/>
		<fieldset>
		    <legend>
		    	<img src='" . $this->_path . "/logo.png' width='20'/>" . $this->l('ADMIN: Payment status update') . "</legend>
		    " . $messageHtml . "
		</fieldset>
		";
		return $html;
	}

	/**
	 * Validates if cart's currency is given in Euros
	 * @param Cart $cart
	 * @return boolean
	 */
	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		// only euro is available
		if (is_array($currencies_module)) {
			foreach ($currencies_module as $currency_module) {
				if ($currency_order->id == $currency_module['id_currency'] && strtoupper($currency_order->iso_code) == "EUR") {
					return true;
				}
			}
		}
		return false;
	}


	/**
	 * Tries to insert or update an order for follow up within mk_status
	 * @param int $id_cart
	 * @param int $id_order
	 * @param int $status
	 */
	public function updateCartInMkStatusByIdCart($id_cart, $id_order = NULL, $status = 0)
	{
		// if already in DB
		if (count($this->getCartInMkStatusByIdCart($id_cart)) == 1) {
			$this->_updateCartInMkStatusByColumn("id_cart", $id_cart, $id_order, $status);
		// create, otherwise
		} else {
			Db::getInstance()->execute(
				'INSERT INTO `'._DB_PREFIX_.'mk_status` (`id_cart`, `id_order`, `payment_status`) ' .
				'VALUES ( ' .
					intval($id_cart) . ', '.
					($id_order == NULL ? "NULL" : intval($id_order)) . ', ' .
					intval($status) .
				' );'
			);
		}
	}

	/**
	 * Updates an entry in mk_status given a cart
	 * @param string $col Column name
	 * @param int $id_cart
	 * @param int $id_order
	 * @param int $status
	 */
	private function _updateCartInMkStatusByColumn($col, $id_cart, $id_order = NULL, $status = 0)
	{
		if ($col == "id_order") {
			$where = 'id_order = ' . intval($id_cart);
		} else {
			$where = 'id_cart = ' . intval($id_cart);
		}

		Db::getInstance()->execute(
			'UPDATE `'._DB_PREFIX_.'mk_status` SET ' .
			 	'id_cart = ' . intval($id_cart) . ', '.
			 	'id_order = ' . ($id_order == NULL ? "NULL" : intval($id_order)) . ', ' .
				'payment_status = ' . intval($status) . ' ' .
			'WHERE ' . $where
		);
	}

	/**
	 * Fetches a follow up item from mk_status given a cart
	 * @param int $id_cart
	 */
	public function getCartInMkStatusByIdCart($id_cart)
	{
		return Db::getInstance()->s('SELECT * FROM `'._DB_PREFIX_.'mk_status` WHERE id_cart = ' . intval($id_cart) . ';');
	}

	/**
	 * Fetches a follow up item from mk_status given an order
	 * @param int $id_cart
	 */
	public function getCartInMkStatusByIdOrder($id_order)
	{
		return Db::getInstance()->s('SELECT * FROM `'._DB_PREFIX_.'mk_status` WHERE id_order = ' . intval($id_order) . ';');
	}

	/**
	 * Fetches the rows with a given status
	 * @param int $status
	 */
	public function getCartsInMkStatusByStatus($status)
	{
		return Db::getInstance()->s('SELECT * FROM `'._DB_PREFIX_.'mk_status` WHERE status = ' . intval($status) . ';');
	}
	
	/**
	 *
	 * @param int $value
	 * @return boolean - matches Maksuturva hash generation format or not
	 */
	public function validateHashGenerationNumber($value){
		return (preg_match('#^[0-9]{3}$#', (string)$value) && $value < 4294967296 && $value >= 0);
	}
}
