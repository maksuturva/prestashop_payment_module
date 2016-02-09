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

require_once dirname(__FILE__) . '/MaksuturvaGatewayAbstract.php';

/**
 * Main class for gateway payments
 * @author Maksuturva
 */
class MaksuturvaGatewayImplementation extends MaksuturvaGatewayAbstract
{
    var $sandbox = false;

    /**
     * Builds up the order accordingly to the Cart within
     * @param string $id
     * @param Cart $order
     * @param string $encoding
     * @param PaymentModule $module
     * @param string $url
     */
	function __construct($id, $order, $encoding, $module, $url = 'https://www.maksuturva.fi')
	{
		// we increment the id to avoid pmt_reference errors
		$id = $id + 100;

	    if (Configuration::get('MAKSUTURVA_SANDBOX') == '1') {
	        $this->sandbox = true;
	        $secretKey = '11223344556677889900';
	        $sellerId = 'testikauppias';
	    } else {
	        $secretKey = Configuration::get('MAKSUTURVA_SECRET_KEY');
	        $sellerId = Configuration::get('MAKSUTURVA_SELLER_ID');
	    }
	    $url = Configuration::get('MAKSUTURVA_URL');

	    $orderAmount = 0; // sum of rows of types 1, 4, 5, 6

		//Adding each product from order
		$products_rows = array();
		foreach ($order->getProducts() as $product) {
			//var_dump($product);die;
			$desc = Tools::htmlentitiesDecodeUTF8(strip_tags($product["description_short"]));
			$orderAmount += $product["total_wt"]; // with taxes
		    $row = array(
		        'pmt_row_name' => $this->filterCharacters($product["name"]),                                                        //alphanumeric        max lenght 40
            	'pmt_row_desc' => $this->filterCharacters($desc),                                           //alphanumeric        max lenght 1000      min lenght 1
            	'pmt_row_quantity' => $product["cart_quantity"]						//numeric             max lenght 8         min lenght 1
		    );
		    if($product["reference"] != NULL && isset($product["reference"])){
		    	$row['pmt_row_articlenr'] = $product["reference"];
		    }
		    else if($product["ean13"] != NULL && isset($product["ean13"])){
		    	$row['pmt_row_articlenr'] = $product["ean13"];
		    }
		    $row['pmt_row_deliverydate'] = date("d.m.Y");							//alphanumeric        max lenght 10        min lenght 10        dd.MM.yyyy
		    // vat excluded:
            $row['pmt_row_price_gross'] = str_replace('.', ',', sprintf("%.2f", $product["price_wt"]));          //alphanumeric        max lenght 17        min lenght 4         n,nn                           
		    $row['pmt_row_vat'] = str_replace('.', ',', sprintf("%.2f", $product["rate"]));               //alphanumeric        max lenght 5         min lenght 4         n,nn
            $row['pmt_row_discountpercentage'] = "0,00";                                                    //alphanumeric        max lenght 5         min lenght 4         n,nn
            $row['pmt_row_type'] = 1;
		   
		    array_push($products_rows, $row);
		}

		// retrieve order carrier and some more details
		$customer = new Customer((int)$order->id_customer);
		$carrier = new Carrier($order->id_carrier);
		$order_summary = $order->getSummaryDetails();

		$moduleUrl = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__ . 'modules/'.$module->name.'/validation.php';

		if ($order_summary['total_shipping'] > 0){
			$shippingVat =  (($order_summary['total_shipping'] / $order_summary['total_shipping_tax_exc']) - 1) * 100;
		} else{
			$shippingVat = 0;
		}
		$sellerCosts = $order_summary['total_shipping'];

		// Adding the shipping cost as a row
		$row = array(
		    'pmt_row_name' => (($carrier != NULL && isset($carrier->name)) ? $carrier->name : $module->l('Shipping Costs')),
        	'pmt_row_desc' => (($carrier != NULL && isset($carrier->name)) ? $carrier->name : $module->l('Shipping Costs')),
        	'pmt_row_quantity' => 1,
        	'pmt_row_deliverydate' => date("d.m.Y"),
        	'pmt_row_price_gross' => str_replace('.', ',', sprintf("%.2f", $order_summary['total_shipping'])),
        	'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", $shippingVat)),
        	'pmt_row_discountpercentage' => "0,00",
        	'pmt_row_type' => 2,
		);
		array_push($products_rows, $row);
		
		// if wrapping, add
		if ($order_summary["total_wrapping"] > 0) {
			// services do not sum with the sellercosts: just types 2 and 3 (postal and handling costs)
			$wrappingVat = (($order_summary['total_wrapping'] / $order_summary['total_wrapping_tax_exc']) - 1) * 100;
			$orderAmount += $order_summary["total_wrapping"];
			$row = array(
			    'pmt_row_name' => $module->l('Wrapping Costs'),
	        	'pmt_row_desc' => $module->l('Wrapping Costs'),
	        	'pmt_row_quantity' => 1,
	        	'pmt_row_deliverydate' => date("d.m.Y"),
	        	'pmt_row_price_gross' => str_replace('.', ',', sprintf("%.2f", $order_summary['total_wrapping'])),
	        	'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", $wrappingVat)),
	        	'pmt_row_discountpercentage' => "0,00",
	        	'pmt_row_type' => 5, // service performed
			);
			array_push($products_rows, $row);
		}

		// if discounts, add
		if (abs($order_summary["total_discounts"]) > 0) {
			// services do not sum with the sellercosts: just types 2 and 3 (postal and handling costs)
			$discounts_total = 0;
			$discvalue = 0;
			$row = 0;
			foreach ($order_summary['discounts'] as $discount){
				$discvalue = (-1) * abs($discount['value_real']);
				$discounts_total += $discvalue;
				$orderAmount += $discvalue;
				$row = array(
				    'pmt_row_name' => (trim($discount['name']) ? $discount['name'] : $module->l('Discounts')) ,
		        	'pmt_row_desc' => (trim($discount['description']) ? $discount['description'] : $module->l('Discounts')) ,
		        	'pmt_row_quantity' => 1,
		        	'pmt_row_deliverydate' => date("d.m.Y"),
		        	'pmt_row_price_gross' => str_replace('.', ',', sprintf("%.2f", $discvalue) ),
		        	'pmt_row_vat' => str_replace('.', ',', sprintf("%.2f", 0)), // Order has type DISCOUNT_MONEYAMOUNT_6 and so the VAT percentage should be ZERO
		        	'pmt_row_discountpercentage' => "0,00",
		        	'pmt_row_type' => 6, // discounts
				);
				array_push($products_rows, $row);		
			}
			// check if rounding was right, if not just fix last discount to complete what's missing to discounts_total
			if (abs(round($discounts_total, 2)) != abs($order_summary["total_discounts"])){
				$row = array_pop($products_rows);
				$fixvalue = (-1) * abs($order_summary["total_discounts"]) - $discounts_total;
				$orderAmount += $fixvalue; 
				$row['pmt_row_price_gross'] = str_replace('.', ',', sprintf("%.2f", $discvalue + $fixvalue));
				array_push($products_rows, $row);
			}
		}

		$userlocale = "";
		$fields = $order->getFields();
		if(Language::getIsoById($fields["id_lang"]) != null && Country::getIsoById($order_summary["invoice"]->id_country) != null ){
			$userlocale = Language::getIsoById($fields["id_lang"]).'_'.Country::getIsoById($order_summary["invoice"]->id_country);
		}
		$options = array(
			// key version
			"pmt_keygeneration" => Configuration::get('MAKSUTURVA_SECRET_KEY_VERSION'),
		
			"pmt_id" 		=> $id,
			"pmt_orderid"	=> $id,
			"pmt_reference" => $id,
			"pmt_sellerid" 	=> $sellerId,
			"pmt_duedate" 	=> date("d.m.Y"),
			"pmt_userlocale" => $userlocale,

			"pmt_okreturn"	=> $moduleUrl,
			"pmt_errorreturn"	=> $moduleUrl . "?error=1",
			"pmt_cancelreturn"	=> $moduleUrl . "?cancel=1",
			"pmt_delayedpayreturn"	=> $moduleUrl . "?delayed=1",
			"pmt_amount" 		=> str_replace('.', ',', sprintf("%.2f", $orderAmount)),
			//"pmt_paymentmethod" => "FI03", /* possibility to add pre-selected payment method as a hard-coded parameter. Currently pre-selecting payment method dynamically during checkout is not supported by this module */

			// Customer Information
			"pmt_buyername" 	=> trim($order_summary["invoice"]->firstname . " " . $order_summary["invoice"]->lastname),
		    "pmt_buyeraddress" => trim($order_summary["invoice"]->address1 . ", " . $order_summary["invoice"]->address2, ", "),
			"pmt_buyerpostalcode" => $order_summary["invoice"]->postcode,
			"pmt_buyercity" => $order_summary["invoice"]->city,
			"pmt_buyercountry" => Country::getIsoById($order_summary["invoice"]->id_country),
		    "pmt_buyeremail" => $customer->email,

			// emaksut
			"pmt_escrow" => "Y",

		    // Delivery information
			"pmt_deliveryname" => trim($order_summary["delivery"]->firstname . " " . $order_summary["delivery"]->lastname),
			"pmt_deliveryaddress" => trim($order_summary["delivery"]->address1 . ", " . $order_summary["delivery"]->address2, ", "),
			"pmt_deliverypostalcode" => $order_summary["delivery"]->postcode,
		    "pmt_deliverycity" => $order_summary["delivery"]->city,
			"pmt_deliverycountry" => Country::getIsoById($order_summary["delivery"]->id_country),

			"pmt_sellercosts" => str_replace('.', ',', sprintf("%.2f", $sellerCosts)),

		    "pmt_rows" => count($products_rows),
		    "pmt_rows_data" => $products_rows

		);
		//var_dump($order_summary); exit;
		//var_dump( $options); exit;
		parent::__construct($secretKey, $options, $encoding, $url);
	}


    public function calcPmtReferenceCheckNumber()
    {
        return $this->getPmtReferenceNumber($this->_formData['pmt_reference']);
    }

	public function getReferenceNumber($order_id){
		return $this->getPmtReferenceNumber($order_id+100);
	} 

    public function calcHash()
    {
        return $this->generateHash();
    }

    public function getHashAlgo()
    {
        return $this->_hashAlgoDefined;
    }

}