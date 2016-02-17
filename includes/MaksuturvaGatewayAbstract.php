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
 * Payment gateway abstract.
 *
 * Provides the core payment gateway implementation and should be extended with app specific behavior.
 *
 * @property int $pmt_orderid
 */
abstract class MaksuturvaGatewayAbstract
{
    const STATUS_QUERY_NOT_FOUND = '00';
    const STATUS_QUERY_FAILED = '01';
    const STATUS_QUERY_WAITING = '10';
    const STATUS_QUERY_UNPAID = '11';
    const STATUS_QUERY_UNPAID_DELIVERY = '15';
    const STATUS_QUERY_PAID = '20';
    const STATUS_QUERY_PAID_DELIVERY = '30';
    const STATUS_QUERY_COMPENSATED = '40';
    const STATUS_QUERY_PAYER_CANCELLED = '91';
    const STATUS_QUERY_PAYER_CANCELLED_PARTIAL = '92';
    const STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN = '93';
    const STATUS_QUERY_PAYER_RECLAMATION = '95';
    const STATUS_QUERY_CANCELLED = '99';

    const EXCEPTION_CODE_ALGORITHMS_NOT_SUPPORTED = '00';
    const EXCEPTION_CODE_URL_GENERATION_ERRORS = '01';
    const EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS = '02';
    const EXCEPTION_CODE_REFERENCE_NUMBER_UNDER_100 = '03';
    const EXCEPTION_CODE_FIELD_MISSING = '04';
    const EXCEPTION_CODE_INVALID_ITEM = '05';
    const EXCEPTION_CODE_PHP_CURL_NOT_INSTALLED = '06';
    const EXCEPTION_CODE_HASHES_DONT_MATCH = '07';

    const ROUTE_PAYMENT = '/NewPaymentExtended.pmt';
    const ROUTE_STATUS_QUERY = '/PaymentStatusQuery.pmt';

    /**
     * @var string gateway URL for making new payments.
     */
    protected $base_url = 'https://www.maksuturva.fi/NewPaymentExtended.pmt';

    /**
     * @var string gateway URL for checking payment statuses.
     */
    protected $base_url_status_query = 'https://www.maksuturva.fi/PaymentStatusQuery.pmt';

    /**
     * @var string seller ID to use for identification when calling the gateway.
     */
    protected $seller_id;

    /**
     * @var string seller secret key to use for identification when calling the gateway.
     */
    protected $secret_key;

    /**
     * @var string charset for the payment data.
     */
    protected $charset = 'UTF-8';

    /**
     * @var string charset for the payment http data.
     */
    protected $charset_http = 'UTF-8';

    /**
     * @var mixed prefix used for the `pmt_id` field.
     */
    protected $pmt_id_prefix;

    /**
     * @var string algorithm used for hashing (sha512, sha256, sha1 or md5).
     */
    protected $hash_algorithm;

    /**
     * @var array the payment data.
     */
    protected $payment_data = array(
        'pmt_action' => 'NEW_PAYMENT_EXTENDED',
        'pmt_version' => '0004',
        'pmt_escrow' => 'Y',
        'pmt_keygeneration' => '001',
        'pmt_currency' => 'EUR',
        'pmt_escrowchangeallowed' => 'N',
        'pmt_charset' => 'UTF-8',
        'pmt_charsethttp' => 'UTF-8',
    );

    /**
     * @var array payment query status data.
     */
    protected $status_query_data = array();

    /**
     * @var array mandatory properties in the payment data.
     */
    private static $mandatory_data = array(
        'pmt_action',               //alphanumeric  max-length: 50   min-length: 4   NEW_PAYMENT_EXTENDED
        'pmt_version',              //alphanumeric  max-length: 4    min-length: 4   0004
        'pmt_sellerid',             //alphanumeric  max-length: 15   -
        'pmt_id',                   //alphanumeric  max-length: 20   -
        'pmt_orderid',              //alphanumeric  max-length: 50   -
        'pmt_reference',            //numeric       max-length: 20   min-length: 4   Reference number + check digit
        'pmt_duedate',              //alphanumeric  max-length: 10   min-length: 10  dd.MM.yyyy
        'pmt_amount',               //alphanumeric  max-length: 17   min-length: 4
        'pmt_currency',             //alphanumeric  max-length: 3    min-length: 3   EUR
        'pmt_okreturn',             //alphanumeric  max-length: 200  -
        'pmt_errorreturn',          //alphanumeric  max-length: 200  -
        'pmt_cancelreturn',         //alphanumeric  max-length: 200  -
        'pmt_delayedpayreturn',     //alphanumeric  max-length: 200  -
        'pmt_escrow',               //alpha         max-length: 1    min-length: 1   Maksuturva=Y, eMaksut=N
        'pmt_escrowchangeallowed',  //alpha         max-length: 1    min-length: 1   N
        'pmt_buyername',            //alphanumeric  max-length: 40   -
        'pmt_buyeraddress',         //alphanumeric  max-length: 40   -
        'pmt_buyerpostalcode',      //numeric       max-length: 5    -
        'pmt_buyercity',            //alphanumeric  max-length: 40   -
        'pmt_buyercountry',         //alpha         max-length: 2    -               Respecting the ISO 3166
        'pmt_deliveryname',         //alphanumeric  max-length: 40   -
        'pmt_deliveryaddress',      //alphanumeric  max-length: 40   -
        'pmt_deliverypostalcode',   //numeric       max-length: 5    -
        'pmt_deliverycountry',      //alpha         max-length: 2    -               Respecting the ISO 3166
        'pmt_sellercosts',          //alphanumeric  max-length: 17   min-length: 4   n,nn
        'pmt_rows',                 //numeric       max-length: 4    min-length: 1
        'pmt_charset',              //alphanumeric  max-length: 15   -               {ISO-8859-1, ISO-8859-15, UTF-8}
        'pmt_charsethttp',          //alphanumeric  max-length: 15   -               {ISO-8859-1, ISO-8859-15, UTF-8}
        'pmt_hashversion',          //alphanumeric  max-length: 10   -               {SHA-512, SHA-256, SHA-1, MD5}
        'pmt_keygeneration',        //numeric       max-length: 3    -
//        'pmt_hash',                 //alphanumeric  max-length: 128  min-length: 32
    );

    /**
     * @var array optional properties in the payment data.
     */
    private static $optional_data = array(
        'pmt_selleriban',
        'pmt_userlocale',
        'pmt_invoicefromseller',
        'pmt_paymentmethod',
        'pmt_buyeridentificationcode',
        'pmt_buyerphone',
        'pmt_buyeremail',
    );

    /**
     * @var array mandatory properties for the payment data rows.
     */
    private static $row_mandatory_data = array(
        'pmt_row_name',                  //alphanumeric  max-length: 40    -
        'pmt_row_desc',                  //alphanumeric  max-length: 1000  min-length: 1
        'pmt_row_quantity',              //numeric       max-length: 8     min-length: 1
        'pmt_row_deliverydate',          //alphanumeric  max-length: 10    min-length: 10  dd.MM.yyyy
        'pmt_row_price_gross',           //alphanumeric  max-length: 17    min-length: 4   n,nn
        'pmt_row_price_net',             //alphanumeric  max-length: 17    min-length: 4   n,nn
        'pmt_row_vat',                   //alphanumeric  max-length: 5     min-length: 4   n,nn
        'pmt_row_discountpercentage',    //alphanumeric  max-length: 5     min-length: 4   n,nn
        'pmt_row_type',                  //numeric       max-length: 5     min-length: 1
    );

    /**
     * @var array optional properties for the payment data rows.
     */
    private static $row_optional_data = array(
        'pmt_row_articlenr',
        'pmt_row_unit',
    );

    /**
     * @var array properties used for hashing.
     */
    private static $hash_data = array(
        'pmt_action',
        'pmt_version',
        'pmt_selleriban',
        'pmt_id',
        'pmt_orderid',
        'pmt_reference',
        'pmt_duedate',
        'pmt_amount',
        'pmt_currency',
        'pmt_okreturn',
        'pmt_errorreturn',
        'pmt_cancelreturn',
        'pmt_delayedpayreturn',
        'pmt_escrow',
        'pmt_escrowchangeallowed',
        'pmt_invoicefromseller',
        'pmt_paymentmethod',
        'pmt_buyeridentificationcode',
        'pmt_buyername',
        'pmt_buyeraddress',
        'pmt_buyerpostalcode',
        'pmt_buyercity',
        'pmt_buyercountry',
        'pmt_deliveryname',
        'pmt_deliveryaddress',
        'pmt_deliverypostalcode',
        'pmt_deliverycity',
        'pmt_deliverycountry',
        'pmt_sellercosts',
        //'pmt_row_* fields in specified order, one row at a time',
        // '<merchant’s secret key >'
    );

    /**
     * @var array field filters (min, max).
     */
    private static $field_filters = array(
        'pmt_action' => array(4, 50),
        'pmt_version' => array(4, 4),
        'pmt_sellerid' => array(1, 15),
        'pmt_selleriban' => array(18, 30), // optional
        'pmt_id' => array(1, 20),
        'pmt_orderid' => array(1, 50),
        'pmt_reference' => array(3, 20), // > 100
        'pmt_duedate' => array(10, 10),
        'pmt_userlocale' => array(5, 5), // optional
        'pmt_amount' => array(4, 17),
        'pmt_currency' => array(3, 3),
        'pmt_okreturn' => array(1, 200),
        'pmt_errorreturn' => array(1, 200),
        'pmt_cancelreturn' => array(1, 200),
        'pmt_delayedpayreturn' => array(1, 200),
        'pmt_escrow' => array(1, 1),
        'pmt_escrowchangeallowed' => array(1, 1),
        'pmt_invoicefromseller' => array(1, 1),    // opt
        'pmt_paymentmethod' => array(4, 4), // opt
        'pmt_buyeridentificationcode' => array(9, 11),    // opt
        'pmt_buyername' => array(1, 40),
        'pmt_buyeraddress' => array(1, 40),
        'pmt_buyerpostalcode' => array(1, 5),
        'pmt_buyercity' => array(1, 40),
        'pmt_buyercountry' => array(1, 2),
        'pmt_buyerphone' => array(0, 40),    // opt
        'pmt_buyeremail' => array(0, 40),    // opt
        'pmt_deliveryname' => array(1, 40),
        'pmt_deliveryaddress' => array(1, 40),
        'pmt_deliverypostalcode' => array(1, 5),
        'pmt_deliverycity' => array(1, 40),
        'pmt_deliverycountry' => array(1, 2),
        'pmt_sellercosts' => array(4, 17),
        'pmt_rows' => array(1, 4),
        'pmt_row_name' => array(1, 40),
        'pmt_row_desc' => array(1, 1000),
        'pmt_row_quantity' => array(1, 8),
        'pmt_row_deliverydate' => array(10, 10),
        'pmt_row_price_gross' => array(4, 17),
        'pmt_row_price_net' => array(4, 17),
        'pmt_row_vat' => array(4, 5),
        'pmt_row_discountpercentage' => array(4, 5),
        'pmt_row_type' => array(1, 5),
        'pmt_charset' => array(1, 15),
        'pmt_charsethttp' => array(1, 15),
        'pmt_hashversion' => array(1, 10),
        'pmt_keygeneration' => array(1, 3),
    );

    /**
     * Checks if the payment data is valid.
     *
     * @throws MaksuturvaGatewayException
     */
    protected function validatePaymentData()
    {
        $delivery_fields = array(
            'pmt_deliveryname' => 'pmt_buyername',
            'pmt_deliveryaddress' => 'pmt_buyeraddress',
            'pmt_deliverypostalcode' => 'pmt_buyerpostalcode',
            'pmt_deliverycity' => 'pmt_buyercity',
            'pmt_deliverycountry' => 'pmt_buyercountry'
        );

        foreach ($delivery_fields as $k => $v) {
            if ((!isset($this->payment_data[$k])) || mb_strlen(trim($this->payment_data[$k])) == 0 || is_null($this->payment_data[$k])) {
                $this->payment_data[$k] = $this->payment_data[$v];
            }
        }

        foreach (self::$mandatory_data as $field) {
            if (!array_key_exists($field, $this->payment_data)) {
                throw new MaksuturvaGatewayException(
                    sprintf('Field "%s" is mandatory', $field),
                    self::EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS
                );
            }

            if ($field === 'pmt_reference') {
                if (mb_strlen((string)$this->payment_data['pmt_reference']) < 3) {
                    throw new MaksuturvaGatewayException(
                        sprintf('Field "%s" needs to have at least 3 digits', $field),
                        self::EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS
                    );
                }
            }
        }

        $count_rows = 0;
        if (array_key_exists('pmt_rows_data', $this->payment_data)) {
            foreach ($this->payment_data['pmt_rows_data'] as $row_data) {
                $this->validatePaymentDataItem($row_data, $count_rows);
                $count_rows++;
            }
        }

        if ($count_rows != $this->payment_data['pmt_rows']) {
            throw new MaksuturvaGatewayException(
                sprintf(
                    'The amount of items (%s) passed in field "pmt_rows" does not match with real amount(%s)',
                    $this->payment_data['pmt_rows'],
                    $count_rows
                ),
                self::EXCEPTION_CODE_FIELD_ARRAY_GENERATION_ERRORS
            );
        }

        $this->filterFields();
    }

    /**
     * Checks if an payment data row item is valid.
     *
     * @param array $data
     * @param mixed $count_rows
     *
     * @throws MaksuturvaGatewayException
     */
    protected function validatePaymentDataItem(array $data, $count_rows = null)
    {
        foreach (self::$row_mandatory_data as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'pmt_row_price_gross' && array_key_exists('pmt_row_price_net', $data)) {
                    throw new MaksuturvaGatewayException(
                        sprintf(
                            'pmt_row_price_net%d and pmt_row_price_gross%d are both supplied, when only one of them should be',
                            $count_rows,
                            $count_rows
                        )
                    );
                }
            } else {
                if ($field === 'pmt_row_price_gross' && array_key_exists('pmt_row_price_net', $data)) {
                    continue;
                } elseif ($field === 'pmt_row_price_net' && array_key_exists('pmt_row_price_gross', $data)) {
                    continue;
                }

                throw new MaksuturvaGatewayException(sprintf('Field %s%d is mandatory', $field, $count_rows));
            }
        }
    }

    /**
     * Creates a hash of the payment data.
     *
     * @return string
     */
    protected function createPaymentHash()
    {
        $hash_data = array();
        foreach (self::$hash_data as $field) {
            switch ($field) {
                case 'pmt_selleriban':
                case 'pmt_invoicefromseller':
                case 'pmt_paymentmethod':
                case 'pmt_buyeridentificationcode':
                    if (in_array($field, array_keys($this->payment_data))) {
                        $hash_data[$field] = $this->payment_data[$field];
                    }
                    break;

                default:
                    $hash_data[$field] = $this->payment_data[$field];
                    break;
            }
        }

        foreach ($this->payment_data['pmt_rows_data'] as $i => $row) {
            foreach ($row as $k => $v) {
                $hash_data[$k . $i] = $v;
            }
        }

        return $this->createHash($hash_data);
    }

    /**
     * Turn the given reference number into a Maksuturva reference number.
     *
     * @param int $number
     * @return string
     * @throws MaksuturvaGatewayException
     */
    protected function getPmtReferenceNumber($number)
    {
        if ($number < 100) {
            throw new MaksuturvaGatewayException(
                'Cannot generate reference numbers for an ID smaller than 100',
                self::EXCEPTION_CODE_REFERENCE_NUMBER_UNDER_100
            );
        }

        $multiples = array(7, 3, 1);
        $str = (string)$number;
        $sum = 0;
        $j = 0;
        for ($i = mb_strlen($str) - 1; $i >= 0; $i--) {
            $sum += (int)mb_substr($str, $i, 1) * (int)($multiples[$j % 3]);
            $j++;
        }

        $next_ten = ceil((int)$sum / 10) * 10;
        return $str . (string)(abs($next_ten - $sum));
    }

    /**
     * Validates the consistency of maksuturva responses for a given status query.
     *
     * @param array $data
     * @return boolean
     */
    private function verifyStatusQueryResponse($data)
    {
        $hash_fields = array(
            'pmtq_action',
            'pmtq_version',
            'pmtq_sellerid',
            'pmtq_id',
            'pmtq_amount',
            'pmtq_returncode',
            'pmtq_returntext',
            'pmtq_sellercosts',
            'pmtq_paymentmethod',
            'pmtq_escrow'
        );

        $optional_hash_fields = array(
            'pmtq_sellercosts',
            'pmtq_paymentmethod',
            'pmtq_escrow'
        );

        $hash_data = array();
        foreach ($hash_fields as $field) {
            if (!isset($data[$field]) && !in_array($field, $optional_hash_fields)) {
                return false;
            } elseif (!isset($data[$field])) {
                continue;
            }

            // Test the validity of data as well, when the field exists.
            if (isset($this->status_query_data[$field]) &&
                ($data[$field] != $this->status_query_data[$field])
            ) {
                return false;
            }

            $hash_data[$field] = $data[$field];
        }

        if ($this->createHash($hash_data) != $data['pmtq_hash']) {
            return false;
        }

        return true;
    }

    /**
     * Traverses the payment data and filters/trims them as needed.
     *
     * If a required field is missing or with length below required, throws an exception.
     *
     * @throws MaksuturvaGatewayException
     */
    private function filterFields()
    {
        foreach ($this->payment_data as $k => $value) {
            if ((array_key_exists($k, self::$field_filters) && in_array($k, self::$mandatory_data))
                || array_key_exists($k, self::$field_filters) && in_array($k, self::$row_mandatory_data)
            ) {
                if (mb_strlen($value) < self::$field_filters[$k][0]) {
                    throw new MaksuturvaGatewayException(
                        sprintf('Field "%s" should be at least %d characters long.', $k, self::$field_filters[$k][0])
                    );
                }

                if (mb_strlen($value) > self::$field_filters[$k][1]) {
                    // Auto trim.
                    $this->payment_data[$k] = mb_substr($value, 0, self::$field_filters[$k][1]);
                    $this->payment_data[$k] = $this->encode($this->payment_data[$k]);
                }
                continue;
            } elseif ((array_key_exists($k, self::$field_filters) && in_array($k, self::$optional_data) && mb_strlen($value))
                || (array_key_exists($k, self::$field_filters) && in_array($k, self::$row_optional_data) && mb_strlen($value))
            ) {
                if (mb_strlen($value) < self::$field_filters[$k][0]) {
                    throw new MaksuturvaGatewayException(
                        sprintf('Field "%s" should be at least %d characters long.', $k, self::$field_filters[$k][0])
                    );
                }

                if (mb_strlen($value) > self::$field_filters[$k][1]) {
                    // Auto trim.
                    $this->payment_data[$k] = mb_substr($value, 0, self::$field_filters[$k][1]);
                    $this->payment_data[$k] = $this->encode($this->payment_data[$k]);
                }
                continue;
            }
        }

        foreach ($this->payment_data['pmt_rows_data'] as $i => $p) {
            // Putting desc or title to not be blank.
            if (array_key_exists('pmt_row_name', $p) && array_key_exists('pmt_row_desc', $p)) {
                if (!trim($p['pmt_row_name'])) {
                    $this->payment_data['pmt_rows_data'][$i]['pmt_row_name'] = $p['pmt_row_name'] = $p['pmt_row_desc'];
                } elseif (!trim($p['pmt_row_desc'])) {
                    $this->payment_data['pmt_rows_data'][$i]['pmt_row_desc'] = $p['pmt_row_desc'] = $p['pmt_row_name'];
                }
            }

            foreach ($p as $k => $value) {
                if ((array_key_exists($k, self::$field_filters) && in_array($k, self::$mandatory_data))
                    || array_key_exists($k, self::$field_filters) && in_array($k, self::$row_mandatory_data)
                ) {
                    if (mb_strlen($value) < self::$field_filters[$k][0]) {
                        throw new MaksuturvaGatewayException(
                            sprintf('Field "%s" should be at least %d characters long.', $k,
                                self::$field_filters[$k][0])
                        );
                    }

                    if (mb_strlen($value) > self::$field_filters[$k][1]) {
                        // Auto trim.
                        $this->payment_data['pmt_rows_data'][$i][$k] = mb_substr($value, 0,
                            self::$field_filters[$k][1]);
                        $this->payment_data['pmt_rows_data'][$i][$k] = $this->encode($this->payment_data['pmt_rows_data'][$i][$k]);
                    }
                    continue;
                } elseif ((array_key_exists($k, self::$field_filters) && in_array($k, self::$optional_data) && mb_strlen($value))
                    || (array_key_exists($k, self::$field_filters) && in_array($k, self::$row_optional_data) && mb_strlen($value))
                ) {
                    if (mb_strlen($value) < self::$field_filters[$k][0]) {
                        throw new MaksuturvaGatewayException(
                            sprintf('Field "%s" should be at least %d characters long.', $k,
                                self::$field_filters[$k][0])
                        );
                    }

                    if (mb_strlen($value) > self::$field_filters[$k][1]) {
                        // Auto trim.
                        $this->payment_data['pmt_rows_data'][$i][$k] = mb_substr($value, 0,
                            self::$field_filters[$k][1]);
                        $this->payment_data['pmt_rows_data'][$i][$k] = $this->encode($this->payment_data['pmt_rows_data'][$i][$k]);
                    }
                    continue;
                }
            }
        }
    }

    /**
     * Helper function to filter out problematic characters.
     *
     * So far only quotation marks have been needed to filter out.
     *
     * @param string $string
     * @return string
     */
    public function filterCharacters($string)
    {
        $new_string = str_replace('"', "", $string);
        if (!is_null($new_string) && mb_strlen($new_string) > 0) {
            return $new_string;
        }

        return ' ';
    }

    /**
     * Magic get for fetching payment data fields.
     *
     * @param string $name
     * @return null
     */
    public function __get($name)
    {
        if (in_array($name, self::$mandatory_data) || in_array($name,
                self::$optional_data) || $name == 'pmt_rows_data'
        ) {
            return $this->payment_data[$name];
        }

        return null;
    }

    /**
     * Calculate a hash for given data.
     *
     * @param array $hash_data
     * @return string
     */
    public function createHash(array $hash_data)
    {
        $hash_string = '';
        foreach ($hash_data as $key => $data) {
            if ($key != 'pmt_hash') {
                $hash_string .= $data . '&';
            }
        }

        $hash_string .= $this->secret_key . '&';
        return strtoupper(hash($this->hash_algorithm, $hash_string));
    }

    /**
     * Perform a status query to maksuturva's server using the current payment data.
     *
     * <code>
     * array(
     *        "pmtq_action",
     *        "pmtq_version",
     *        "pmtq_sellerid",
     *        "pmtq_id",
     *        "pmtq_resptype",
     *        "pmtq_return",
     *        "pmtq_hashversion",
     *        "pmtq_keygeneration"
     * );
     * </code>
     *
     * The return data is an array if the order is successfully organized;
     * Otherwise, possible situations of errors:
     *
     * 1) Exceptions in case of not having curl in PHP - exception
     * 2) Network problems (cannot connect, etc) - exception
     * 3) Invalid returned data (hash or consistency) - return false
     *
     * @param array $data Configuration values to be used
     * @return array|bool
     * @throws MaksuturvaGatewayException
     */
    public function statusQuery($data = array())
    {
        if (!function_exists('curl_init')) {
            throw new MaksuturvaGatewayException(
                'cURL is needed in order to communicate with the maksuturva server. Check your PHP installation.',
                self::EXCEPTION_CODE_PHP_CURL_NOT_INSTALLED
            );
        }

        $default_fields = array(
            'pmtq_action' => 'PAYMENT_STATUS_QUERY',
            'pmtq_version' => '0004',
            'pmtq_sellerid' => $this->payment_data['pmt_sellerid'],
            'pmtq_id' => $this->payment_data['pmt_id'],
            'pmtq_resptype' => 'XML',
            'pmtq_return' => '',
            'pmtq_hashversion' => $this->payment_data['pmt_hashversion'],
            'pmtq_keygeneration' => '001'
        );

        // Overrides with user-defined fields.
        $this->status_query_data = array_merge($default_fields, $data);

        // Last step: the hash is placed correctly.
        $hash_fields = array(
            'pmtq_action',
            'pmtq_version',
            'pmtq_sellerid',
            'pmtq_id'
        );
        $hash_data = array();
        foreach ($hash_fields as $field) {
            $hash_data[$field] = $this->status_query_data[$field];
        }
        $this->status_query_data['pmtq_hash'] = $this->createHash($hash_data);

        // Now the request is made to maksuturva.
        $request = curl_init($this->base_url_status_query);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_FRESH_CONNECT, 1);
        curl_setopt($request, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($request, CURLOPT_POSTFIELDS, $this->status_query_data);

        $res = curl_exec($request);
        if ($res === false) {
            throw new MaksuturvaGatewayException(
                'Failed to communicate with maksuturva. Please check the network connection.'
            );
        }
        curl_close($request);

        // We will not rely on xml parsing - instead,
        // the fields are going to be collected by means of regular expression.
        $parsed_response = array();
        $response_fields = array(
            'pmtq_action',
            'pmtq_version',
            'pmtq_sellerid',
            'pmtq_id',
            'pmtq_amount',
            'pmtq_returncode',
            'pmtq_returntext',
            'pmtq_trackingcodes',
            'pmtq_sellercosts',
            'pmtq_paymentmethod',
            'pmtq_escrow',
            'pmtq_buyername',
            'pmtq_buyeraddress1',
            'pmtq_buyeraddress2',
            'pmtq_buyerpostalcode',
            'pmtq_buyercity',
            'pmtq_hash'
        );
        foreach ($response_fields as $field) {
            preg_match("/<$field>(.*)?<\/$field>/i", $res, $matches);
            if (count($matches) == 2) {
                $parsed_response[$field] = $matches[1];
            }
        }

        // Do not provide a response which is not valid.
        if (!$this->verifyStatusQueryResponse($parsed_response)) {
            throw new MaksuturvaGatewayException(
                'The authenticity of the answer could not be verified. Hashes did not match.',
                self::EXCEPTION_CODE_HASHES_DONT_MATCH
            );
        }

        // Return the response - verified.
        return $parsed_response;
    }

    /**
     * Turns the payment data into a single level associative array.
     *
     * @return array
     * @throws MaksuturvaGatewayException
     */
    public function getFieldArray()
    {
        $this->validatePaymentData();

        $return_array = array();
        $this->payment_data['pmt_reference'] = $this->getPmtReferenceNumber($this->payment_data['pmt_reference']);
        foreach ($this->payment_data as $key => $data) {
            if ($key == 'pmt_rows_data') {
                $row_count = 1;
                foreach ($data as $row) {
                    foreach ($row as $k => $v) {
                        $return_array[$this->httpEncode($k . $row_count)] = $this->httpEncode($v);
                    }

                    $row_count++;
                }
            } else {
                $return_array[$this->httpEncode($key)] = $this->httpEncode($data);
            }
        }
        $return_array[$this->httpEncode('pmt_hash')] = $this->encode($this->createPaymentHash(), $this->charset);

        return $return_array;
    }

    /**
     * Encodes the data to the defined "http encoding".
     *
     * @param string $data
     * @param null|string $from_encoding
     * @return string
     */
    public function httpEncode($data, $from_encoding = null)
    {
        return $this->encode($data, $this->charset_http, $from_encoding);
    }

    /**
     * Encodes the data.
     *
     * By default both `to` and `from` encoding is the one defined in `$this->charset`.
     *
     * @param mixed $data
     * @param null|string $to_encoding
     * @param null|string $from_encoding
     * @return string
     */
    public function encode($data, $to_encoding = null, $from_encoding = null)
    {
        if (is_null($to_encoding)) {
            $to_encoding = $this->charset;
        }

        if (is_null($from_encoding)) {
            $from_encoding = $this->charset;
        }

        return mb_convert_encoding($data, $to_encoding, $from_encoding);
    }

    /**
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->base_url;
    }

    /**
     * @return string
     */
    public function getStatusQueryUrl()
    {
        return $this->base_url_status_query;
    }

    /**
     * @param string $base_url
     */
    public function setBaseUrl($base_url)
    {
        $this->base_url = rtrim($base_url, '/') . self::ROUTE_PAYMENT;
        $this->base_url_status_query = rtrim($base_url, '/') . self::ROUTE_STATUS_QUERY;
    }

    /**
     * @param string $encoding
     */
    public function setEncoding($encoding)
    {
        $this->charset = $encoding;
        $this->charset_http = $encoding;
    }

    /**
     * @param mixed $prefix
     */
    public function setPaymentIdPrefix($prefix)
    {
        $this->pmt_id_prefix = $prefix;
    }

    /**
     * @param array $payment_data
     *
     * @throws MaksuturvaGatewayException
     */
    public function setPaymentData(array $payment_data)
    {
        foreach ($payment_data as $key => $value) {
            if ($key === 'pmt_rows_data') {
                foreach ($value as $k => $v) {
                    $this->payment_data[$key][$k] = str_replace('&amp;', '', $v);
                }
            } else {
                $this->payment_data[$key] = str_replace('&amp;', '', $value);
            }
        }

        $hashing_algorithms = hash_algos();
        if (in_array('sha512', $hashing_algorithms)) {
            $this->payment_data['pmt_hashversion'] = 'SHA-512';
            $this->hash_algorithm = 'sha512';
        } elseif (in_array('sha256', $hashing_algorithms)) {
            $this->payment_data['pmt_hashversion'] = 'SHA-256';
            $this->hash_algorithm = 'sha256';
        } elseif (in_array('sha1', $hashing_algorithms)) {
            $this->payment_data['pmt_hashversion'] = 'SHA-1';
            $this->hash_algorithm = 'sha1';
        } elseif (in_array('md5', $hashing_algorithms)) {
            $this->payment_data['pmt_hashversion'] = 'MD5';
            $this->hash_algorithm = 'md5';
        } else {
            throw new MaksuturvaGatewayException(
                'the hash algorithms SHA-512, SHA-256, SHA-1 and MD5 are not supported!',
                self::EXCEPTION_CODE_ALGORITHMS_NOT_SUPPORTED
            );
        }
    }
}
