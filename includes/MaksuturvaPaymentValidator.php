<?php
/**
 * 2016 Maksuturva Group Oy
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License (LGPLv2.1)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
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
 * @license   http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
 */

/**
 * Maksuturva payment validator.
 */
class MaksuturvaPaymentValidator
{
    const ACTION_OK = 'ok';
    const ACTION_DELAYED = 'delayed';
    const ACTION_CANCEL = 'cancel';
    const ACTION_ERROR = 'error';

    const STATUS_OK = 'ok';
    const STATUS_DELAYED = 'delayed';
    const STATUS_CANCEL = 'cancel';
    const STATUS_ERROR = 'error';

    /**
     * @var array all mandatory fields that must exist in the validated params.
     */
    private static $mandatory_fields = array(
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
    );

    /**
     * @var array fields that can be ignored during data consistency checks.
     */
    private static $ignored_consistency_check_fields = array(
        'pmt_hash',
        'pmt_paymentmethod',
        'pmt_reference',
        'pmt_sellercosts',
        'pmt_escrow'
    );

    /**
     * @var MaksuturvaGatewayImplementation|null the payment gateway.
     */
    protected $gateway;

    /**
     * @var string the status of the validation.
     */
    protected $status;

    /**
     * @var array validation errors encountered during validation.
     */
    protected $errors = array();

    /**
     * Constructor.
     *
     * @param MaksuturvaGatewayImplementation $gateway
     */
    public function __construct(MaksuturvaGatewayImplementation $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Validates a payment requests.
     *
     * If the payment gateway return an 'ok' response, only then will the entire request be validated.
     * In other cases, we rely on the gateway status code.
     *
     * @param array $params
     * @return MaksuturvaPaymentValidator
     */
    public function validate(array $params = array())
    {
        switch ($this->getAction($params)) {
            case self::ACTION_CANCEL:
                $this->status = self::STATUS_CANCEL;
                break;

            case self::ACTION_DELAYED:
                $this->status = self::STATUS_DELAYED;
                break;

            case self::ACTION_ERROR:
                $this->status = self::STATUS_ERROR;
                $this->error($this->gateway->module->l('An error occurred and the payment was not confirmed.'));
                break;

            case self::ACTION_OK:
            default:
                $values = $this->validateMandatoryFields($params);
                $this->validatePaymentId($values);
                $this->validateChecksum($values);
                $this->validateConsistency($values);
                $this->validateSellerCosts($values);
                $this->validateReferenceNumber($values);
                if (!empty($this->errors)) {
                    $this->status = self::STATUS_ERROR;
                } else {
                    $this->status = self::STATUS_OK;
                }
                break;
        }

        return $this;
    }

    /**
     * Returns the validation status (one of the STATUS_* constants).
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the validation errors if any.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Adds a new validation error message.
     *
     * @param string $message
     */
    protected function error($message)
    {
        $this->errors[] = $message;
    }

    /**
     * Parses out the `action` from the passed params.
     *
     * The `action` is the status of the payment as passed by the payment gateway.
     * Possible actions are:
     * - ok
     * - cancel
     * - error
     * - delayed
     *
     * @param array $params
     * @return string
     */
    protected function getAction(array $params = array())
    {
        $action = self::ACTION_OK;

        if (isset($params[self::ACTION_DELAYED]) && $params[self::ACTION_DELAYED] == '1') {
            $action = self::ACTION_DELAYED;
        } elseif (isset($params[self::ACTION_CANCEL]) && $params[self::ACTION_CANCEL] == '1') {
            $action = self::ACTION_CANCEL;
        } elseif (isset($params[self::ACTION_ERROR]) && $params[self::ACTION_ERROR] == '1') {
            $action = self::ACTION_ERROR;
        }

        return $action;
    }

    /**
     * Validates that all mandatory fields are present in the params and returns them.
     *
     * If a mandatory field is not set in the params list, a validation error will issued.
     *
     * @param array $params
     * @return array
     */
    protected function validateMandatoryFields(array $params)
    {
        $values = array();
        $missing_fields = array();

        foreach (self::$mandatory_fields as $field) {
            if (isset($params[$field])) {
                $values[$field] = $params[$field];
            } else {
                $missing_fields[] = $field;
            }
        }

        if (count($missing_fields) > 0) {
            $this->error(sprintf(
                $this->gateway->module->l('Missing payment field(s) in response: "%s"'),
                implode('", "', $missing_fields)
            ));
        }

        return $values;
    }

    /**
     * Validates that the 'pmt_id' passed in the params matches the order we are validating.
     *
     * @param array $values
     */
    protected function validatePaymentId(array $values)
    {
        if (!isset($values['pmt_id']) || !$this->gateway->checkPaymentId($values['pmt_id'])) {
            $this->error($this->gateway->module->l('The payment did not match any order'));
        }
    }

    /**
     * Validates that the checksum of the passed params matches the hash passed which is passed along.
     *
     * @param array $values
     */
    protected function validateChecksum(array $values)
    {
        if (!isset($values['pmt_hash']) || $this->gateway->createHash($values) != $values['pmt_hash']) {
            $this->error($this->gateway->module->l('Payment verification checksum does not match'));
        }
    }

    /**
     * Validates that the reference number matches the reference number of the order information.
     *
     * @param array $values
     */
    protected function validateReferenceNumber(array $values)
    {
        if (!isset($values['pmt_reference']) || !$this->gateway->checkPaymentReferenceNumber($values['pmt_reference'])) {
            $this->error($this->gateway->module->l('Payment reference number could not be verified'));
        }
    }

    /**
     * Validates that the passed values matches those of the gateways internal order information.
     *
     * The info should not have changed between creating the order and returning from the payment gateway.
     *
     * @param array $values
     */
    protected function validateConsistency(array $values)
    {
        $not_matching_fields = array();

        foreach ($values as $key => $value) {
            if (in_array($key, self::$ignored_consistency_check_fields)) {
                continue;
            }

            if (isset($this->gateway->{$key}) && $this->gateway->{$key} !== $value) {
                $not_matching_fields[] = sprintf(
                    $this->gateway->module->l('%s (obtained %s, expected %s)'),
                    $key,
                    $value,
                    $this->gateway->{$key}
                );
            }
        }

        if (count($not_matching_fields) > 0) {
            $this->error(sprintf(
                $this->gateway->module->l('The following field(s) differs from order: %s'),
                implode(', ', $not_matching_fields)
            ));
        }
    }

    /**
     * Validates if the payed amounts differ from the amounts of the created order.
     *
     * This can happen if the payment option (credit card, bank transfer etc.) charged a surcharge.
     * If this is the case, the surcharge is added to the order so it will show up in PS later.
     *
     * @param array $values
     */
    protected function validateSellerCosts(array $values)
    {
        $sent_seller_cost = str_replace(',', '.', $this->gateway->{'pmt_sellercosts'});
        $received_seller_cost = str_replace(',', '.', $values['pmt_sellercosts']);

        if ($sent_seller_cost > $received_seller_cost) {
            $this->error(sprintf(
                $this->gateway->module->l('Invalid payment amount (obtained %s, expected %s)'),
                $values['pmt_sellercosts'],
                $this->gateway->{'pmt_sellercosts'}
            ));
        } elseif ($sent_seller_cost < $received_seller_cost) {
            // todo: surcharge
            $surcharge = ($received_seller_cost - $sent_seller_cost);
            // todo: how to add to cart/order
        }

        /*

        $original_payment_feeFloat = floatval(str_replace(",", ".", $this->gateway->{'pmt_sellercosts'}));
        $new_payment_feeFloat = floatval(str_replace(",", ".", $values['pmt_sellercosts']));
        $customerTotalPaid = $new_payment_feeFloat + floatval(str_replace(",", ".", $values['pmt_amount']));
        if($original_payment_feeFloat != $new_payment_feeFloat){
            // validate that payment_fee have not dropped
            if($new_payment_feeFloat < $original_payment_feeFloat) {
                $this->error(sprintf(
                    $this->module->l('Order not saved. Invalid change in shipping and payment costs (obtained %s, expecting %s)'),
                    $values['pmt_sellercosts'],
                    $this->gateway->{'pmt_sellercosts'}
                ));
            }
            else {
                // validate additional costs product (given by admin in module configurations)
                // Product validation DOES NOT prevent saving the order
                $seller_costs_product = new Product((int)Configuration::get('MAKSUTURVA_PAYMENT_FEE_ID'));
                if (!Validate::isLoadedObject($seller_costs_product)) {
                    $this->log(
                        sprintf(
                            $this->module->l('Failed to add payment fee of %s EUR'),
                            number_format($new_payment_feeFloat - $original_payment_feeFloat, 2, ",", " ")
                        )
                    );
                    $this->log($this->module->l('Payment fee product does not exist'));
                    $this->log(
                        sprintf(
                            $this->module->l('Customer paid total of %s EUR'),
                            number_format($customerTotalPaid, 2, ",", " ")
                        )
                    );
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
                        $this->log(
                            sprintf(
                                $this->module->l('Failed to add payment fee of %s EUR'),
                                number_format($new_payment_feeFloat - $original_payment_feeFloat, 2, ",", " ")
                            )
                        );
                        $this->log(
                            sprintf(
                                $this->module->l('Product "%s" has run out'),
                                $seller_costs_product_name
                            )
                        );
                        $this->log(
                            sprintf(
                                $this->module->l('Customer paid total of %s EUR'),
                                number_format($customerTotalPaid, 2, ",", " ")
                            )
                        );
                    }
                    else{
                        //everything ok, inserting new product row
                        $cart->updateQty(1, (int)Configuration::get('MAKSUTURVA_PAYMENT_FEE_ID'));
                    }
                    if(number_format(floatval($seller_costs_product->getPrice()),2,',','') != number_format($new_payment_feeFloat-$original_payment_feeFloat, 2, ',','')){
                        $this->log(
                            sprintf(
                                $this->module->l('Customer paid additional payment fee of %s EUR differs from the product "%s" price %s EUR'),
                                number_format($new_payment_feeFloat - $original_payment_feeFloat, 2, ",", " "),
                                $seller_costs_product_name,
                                number_format($seller_costs_product->getPrice(), 2, ",", " ")
                            )
                        );
                        $this->log(
                            sprintf(
                                $this->module->l('Customer paid total of %s EUR'),
                                number_format($customerTotalPaid, 2, ",", " ")
                            )
                        );
                    }
                }
            }
        }

        */
    }
}
