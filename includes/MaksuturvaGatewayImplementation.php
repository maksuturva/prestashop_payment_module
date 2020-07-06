<?php

class MaksuturvaGatewayImplementation extends MaksuturvaGatewayAbstract
{
    const SANDBOX_SELLER_ID = 'testikauppias';
    const SANDBOX_SECRET_KEY = '11223344556677889900';

    public $module;
    private $order_total = 0.00;
    
    private $seller_costs = 0.00;
    
    public function __construct(Maksuturva $module, Cart $order)
    {
        $this->module = $module;
        $this->setBaseUrl($module->getGatewayUrl());
        $this->seller_id = ($module->isSandbox() ? self::SANDBOX_SELLER_ID : $module->getSellerId());
        $this->secret_key = ($module->isSandbox() ? self::SANDBOX_SECRET_KEY : $module->getSecretKey());
        $this->setEncoding($module->getEncoding());
        $this->setPaymentIdPrefix($module->getPaymentIdPrefix());
        $this->setPaymentData($this->createPaymentData($module, $order));
    }
    
    private function createPaymentData(Maksuturva $module, Cart $order)
    {
        $free_shipping = 0;
        $cart = new Cart($order->id);
        $discounts = $cart->getCartRules();
        foreach ($discounts as $discount) {
            if ($discount['free_shipping'] == 1) {
                $free_shipping = 1;
                break;
            }
        }

        $payment_row_data = $this->createPaymentRowData($module, $order);
        
        $buyer_data = $this->createBuyerData($order);
        $delivery_data = $this->createDeliveryData($order);
        $order_details = $order->getSummaryDetails();
        
        $customer = new Customer($order->id_customer);

        return array(
            'pmt_keygeneration' => $module->getSecretKeyVersion(),
            'pmt_id' => $this->getPaymentId($order),
            'pmt_orderid' => $order->id,
            'pmt_reference' => $this->getInternalPaymentId($order),
            'pmt_sellerid' => $this->seller_id,
            'pmt_duedate' => date('d.m.Y'),
            'pmt_userlocale' => $this->getUserLocale($order),
            'pmt_okreturn' => $module->getPaymentUrl(array('ok' => 1)),
            'pmt_errorreturn' => $module->getPaymentUrl(array('error' => 1)),
            'pmt_cancelreturn' => $module->getPaymentUrl(array('cancel' => 1)),
            'pmt_delayedpayreturn' => $module->getPaymentUrl(array('delayed' => 1)),
            'pmt_amount' => $this->filterPrice($this->order_total),
            'pmt_buyername' => $buyer_data['name'],
            'pmt_buyeraddress' => $buyer_data['address'],
            'pmt_buyerpostalcode' => $buyer_data['postal_code'],
            'pmt_buyercity' => $buyer_data['city'],
            'pmt_buyercountry' => $buyer_data['country'],
            'pmt_buyeremail' => $customer->email,
            'pmt_escrow' => 'Y',
            'pmt_deliveryname' => $delivery_data['name'],
            'pmt_deliveryaddress' => $delivery_data['address'],
            'pmt_deliverypostalcode' => $delivery_data['postal_code'],
            'pmt_deliverycity' => $delivery_data['city'],
            'pmt_deliverycountry' => $delivery_data['country'],
            'pmt_sellercosts' => $this->filterPrice($this->seller_costs),
            'pmt_rows' => count($payment_row_data),
            'pmt_rows_data' => $payment_row_data,
            // Possibility to add pre-selected payment method as a hard-coded parameter.
            // Currently pre-selecting payment method dynamically during checkout is not supported by this module.
            // 'pmt_paymentmethod' => 'FI03',
        );
    }
    
    private function createPaymentRowData(Maksuturva $module, Cart $order)
    {
        $payment_rows = array();

        foreach ($order->getProducts() as $product) {
            $payment_row_product = array();
            $this->order_total += $product['total_wt'];

            $payment_row_product['pmt_row_name'] = $this->filterCharacters($product['name']);
            $payment_row_product['pmt_row_desc'] = $this->filterDescription($product['description_short']);
            $payment_row_product['pmt_row_quantity'] = $product['cart_quantity'];

            if (isset($product['reference']) && !empty($product['reference'])) {
                $payment_row_product['pmt_row_articlenr'] = $product['reference'];
            } elseif (isset($product['ean13']) && !empty($product['ean13'])) {
                $payment_row_product['pmt_row_articlenr'] = $product['ean13'];
            }

            $payment_row_product['pmt_row_deliverydate'] = date('d.m.Y');
            $payment_row_product['pmt_row_price_gross'] = $this->filterPrice($product['price_wt']);
            $payment_row_product['pmt_row_vat'] = $this->filterPrice($product['rate']);
            $payment_row_product['pmt_row_discountpercentage'] = '0,00';
            $payment_row_product['pmt_row_type'] = 1;

            $payment_rows[] = $payment_row_product;
        }

        $payment_row_shipping = $this->createPaymentRowShippingData($module, $order);
        if (is_array($payment_row_shipping)) {
            $payment_rows[] = $payment_row_shipping;
        }

        $payment_row_wrapping = $this->createPaymentRowWrappingData($module, $order);
        if (is_array($payment_row_wrapping)) {
            $payment_rows[] = $payment_row_wrapping;
        }

        $payment_row_discount = $this->createPaymentRowDiscountData($module, $order);
        if (is_array($payment_row_discount)) {
            $payment_rows = array_merge($payment_rows, $payment_row_discount);
        }
        
        return $payment_rows;
    }
    
    private function createPaymentRowShippingData(Maksuturva $module, Cart $order)
    {
        $free_shipping = 0;
        $cart = new Cart($order->id);
        $discounts = $cart->getCartRules();
        foreach ($discounts as $discount) {
            if ($discount['free_shipping'] == 1) {
                $free_shipping = 1;
                break;
            }
        }
        
        $order_details = $order->getSummaryDetails();

        if (isset($order_details['total_shipping']) && $order_details['total_shipping'] > 0) {
            /** @var CarrierCore|Carrier $carrier */
            $carrier = new Carrier($order->id_carrier);
            if (Validate::isLoadedObject($carrier) && !empty($carrier->name)) {
                $row_name = $carrier->name;
            } else {
                $row_name = $module->l('Shipping Costs');
            }

            $this->seller_costs += $order_details['total_shipping'];

            $shipping_vat = (($order_details['total_shipping'] / $order_details['total_shipping_tax_exc']) - 1) * 100;

            return array(
                'pmt_row_name' => trim($row_name),
                'pmt_row_desc' => trim($row_name),
                'pmt_row_quantity' => 1,
                'pmt_row_deliverydate' => date('d.m.Y'),
                'pmt_row_price_gross' => $this->filterPrice($order_details['total_shipping']),
                'pmt_row_vat' => $this->filterPrice($shipping_vat),
                'pmt_row_discountpercentage' => '0,00',
                'pmt_row_type' => 2,
            );
        }

        return null;
    }

    /**
     * @param Maksuturva $module
     * @param CartCore|Cart $order
     * @return array|null
     */
    private function createPaymentRowWrappingData(Maksuturva $module, Cart $order)
    {
        $order_details = $order->getSummaryDetails();

        if (isset($order_details['total_wrapping']) && $order_details['total_wrapping'] > 0) {
            $this->order_total += $order_details['total_wrapping'];
            $row_name = $module->l('Wrapping Costs');
            $wrapping_vat = (($order_details['total_wrapping'] / $order_details['total_wrapping_tax_exc']) - 1) * 100;

            return array(
                'pmt_row_name' => $row_name,
                'pmt_row_desc' => $row_name,
                'pmt_row_quantity' => 1,
                'pmt_row_deliverydate' => date('d.m.Y'),
                'pmt_row_price_gross' => $this->filterPrice($order_details['total_wrapping']),
                'pmt_row_vat' => $this->filterPrice($wrapping_vat),
                'pmt_row_discountpercentage' => '0,00',
                'pmt_row_type' => 5,
            );
        }

        return null;
    }

    /**
     * @param Maksuturva $module
     * @param CartCore|Cart $order
     * @return array|null
     */
    private function createPaymentRowDiscountData(Maksuturva $module, Cart $order)
    {
        $order_details = $order->getSummaryDetails();

        $payment_rows_discount = array();

        if (isset($order_details['total_discounts']) && $order_details['total_discounts'] > 0) {
            $discount_total = 0;
            $discount_value = 0;
            foreach ($order_details['discounts'] as $discount) {
                $discount_value = (-1) * abs($discount['value_real']);
                $discount_total += $discount_value;
                
                if ($discount['free_shipping']) {
                    $this->seller_costs = $this->seller_costs + $discount_value;
					$discount_type = 2;
				} else {
					$this->order_total += $discount_value;
					$discount_type = 6;
                }

                $row_name = (!empty($discount['name']) ? $discount['name'] : $module->l('Discounts'));
                $row_desc = (!empty($discount['description']) ? $discount['description'] : $module->l('Discounts'));

                $payment_rows_discount[] = array(
                    'pmt_row_name' => $row_name,
                    'pmt_row_desc' => $row_desc,
                    'pmt_row_quantity' => 1,
                    'pmt_row_deliverydate' => date('d.m.Y'),
                    'pmt_row_price_gross' => $this->filterPrice($discount_value),
                    'pmt_row_vat' => '0,00',
                    'pmt_row_discountpercentage' => '0,00',
                    'pmt_row_type' => $discount_type
                );
            }

            // Check if rounding was right, if not just fix last discount to complete what's missing to discounts_total.
            if (abs(round($discount_total, 2)) != abs($order_details['total_discounts'])) {
                $fixed_row = array_pop($payment_rows_discount);
                $new_value = (-1) * abs($order_details['total_discounts']) - $discount_total;
                $this->order_total += $new_value;
                $fixed_row['pmt_row_price_gross'] = $this->filterPrice($discount_value + $new_value);
                $payment_rows_discount[] = $fixed_row;
            }
        }

        // Gift products not present in total_discount parameter
        if (isset($order_details['gift_products']) && is_array($order_details['gift_products'])) {
            $gift_products = $order_details['gift_products'];

            $discount_total = 0;
            $discount_value = 0;
            
            foreach ($gift_products as $discount) {
                $discount_value = (-1) * abs($discount['price_with_reduction']);
                $discount_total += $discount_value;
                
                $this->order_total += $discount_value;
                $discount_type = 6;

                $row_name = $module->l('Gift');
                $row_desc = $module->l('Gift product discount');

                $payment_rows_discount[] = array(
                    'pmt_row_name' => $row_name,
                    'pmt_row_desc' => $row_desc,
                    'pmt_row_quantity' => 1,
                    'pmt_row_deliverydate' => date('d.m.Y'),
                    'pmt_row_price_gross' => $this->filterPrice($discount_value),
                    'pmt_row_vat' => '0,00',
                    'pmt_row_discountpercentage' => '0,00',
                    'pmt_row_type' => $discount_type
                );
            }
        }

        if (!empty($payment_rows_discount)) {
            return $payment_rows_discount;
        }
        
        return null;
    }

    /**
     * @param CartCore|Cart $order
     * @return array
     */
    private function createBuyerData(Cart $order)
    {
        $order_details = $order->getSummaryDetails();
        $invoice = $order_details['invoice'];

        return array(
            'name' => trim($invoice->firstname . ' ' . $invoice->lastname),
            'address' => trim($invoice->address1 . ', ' . $invoice->address2, ', '),
            'postal_code' => $invoice->postcode,
            'city' => $invoice->city,
            'country' => Country::getIsoById($invoice->id_country),
        );
    }

    /**
     * @param CartCore|Cart $order
     * @return array
     */
    private function createDeliveryData(Cart $order)
    {
        $order_details = $order->getSummaryDetails();
        $delivery = $order_details['delivery'];

        return array(
            'name' => trim($delivery->firstname . ' ' . $delivery->lastname),
            'address' => trim($delivery->address1 . ', ' . $delivery->address2, ', '),
            'postal_code' => $delivery->postcode,
            'city' => $delivery->city,
            'country' => Country::getIsoById($delivery->id_country),
        );
    }

    /**
     * @param CartCore|Cart $order
     * @return string
     */
    private function getUserLocale(Cart $order)
    {
        $fields = $order->getFields();
        $language = Language::getIsoById($fields['id_lang']);

        if (!in_array($language, array('fi', 'sv', 'en'))) {
            $language = 'en';
        }

        return $language . '_FI';
    }

    /**
     * @param CartCore|Cart $order
     * @return int
     */
    private function getPaymentId(Cart $order)
    {
        $pmt_id = '';
        if (Tools::strlen($this->pmt_id_prefix)) {
            $pmt_id .= $this->pmt_id_prefix;
        }
        
        return $pmt_id . $this->getInternalPaymentId($order);
    }

    /**
     * @param CartCore|Cart $order
     * @return int
     */
    private function getInternalPaymentId(Cart $order)
    {
        return $order->id + 100;
    }

    /**
     * @param array $params
     * @return string
     */
    private function getAction(array $params)
    {
        $action = 'ok';
        if (isset($params['delayed']) && $params['delayed'] == '1') {
            $action = 'delayed';
        } elseif (isset($params['cancel']) && $params['cancel'] == '1') {
            $action = 'cancel';
        } elseif (isset($params['error']) && $params['error'] == '1') {
            $action = 'error';
        }
        return $action;
    }

    /**
     * @param string $description
     * @return string
     */
    protected function filterDescription($description)
    {
        return $this->filterCharacters(Tools::htmlentitiesDecodeUTF8(strip_tags($description)));
    }

    /**
     * @param string|int|float $price
     * @return string
     */
    protected function filterPrice($price)
    {
        return str_replace('.', ',', sprintf('%.2f', $price));
    }

    /**
     * @param string $pmt_reference
     * @return bool
     * @throws MaksuturvaGatewayException
     */
    public function checkPaymentReferenceNumber($pmt_reference)
    {
        return ($pmt_reference == $this->getPaymentReferenceNumber());
    }

    /**
     * @return string
     * @throws MaksuturvaGatewayException
     */
    public function getPaymentReferenceNumber()
    {
        return $this->getPmtReferenceNumber($this->payment_data['pmt_reference']);
    }

    /**
     * @param string $pmt_id
     * @return bool
     */
    public function checkPaymentId($pmt_id)
    {
        if (Tools::strlen($this->pmt_id_prefix)
            && Tools::substr($pmt_id, 0, Tools::strlen($this->pmt_id_prefix)) === $this->pmt_id_prefix
        ) {
            $pmt_id = Tools::substr($pmt_id, Tools::strlen($this->pmt_id_prefix));
        }
        return (((int)$pmt_id - 100) == $this->pmt_orderid);
    }

    /**
     * @param array $params
     * @return MaksuturvaPaymentValidator
     */
    public function validatePayment(array $params)
    {
        $validator = new MaksuturvaPaymentValidator($this);
        return $validator->validate($params);
    }
}