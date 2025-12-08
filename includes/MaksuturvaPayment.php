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

/**
 * Maksuturva payment container.
 */
class MaksuturvaPayment
{
    /**
     * @var int identifier of the payment record
     */
    protected $id;

    /**
     * @var int the ID of the PS order object this payment is for
     */
    protected $id_order = 0;

    /**
     * @var int the ID of the PS cart the payment originates from
     */
    protected $id_cart = 0;

    /**
     * @var int sequence number for this payment attempt
     */
    protected $attempt = 1;

    /**
     * @var string identifier sent to Svea (with prefix if configured)
     */
    protected $pmt_id = '';

    /**
     * @var int the payment status
     */
    protected $status = 0;

    /**
     * @var array the payment data sent to the gateway
     */
    protected $data_sent = [];

    /**
     * @var array the payment data received from the gateway
     */
    protected $data_received = [];

    /**
     * @var array list of log entries
     */
    protected $logs = [];

    /**
     * @var string the datetime when the payment was added
     */
    protected $date_add;

    /**
     * @var string the datetime when the payment was last updated
     */
    protected $date_upd;

    /**
     * Create or load a Maksuturva payment.
     *
     * @param int|string|bool $value
     * @param string $field
     */
    public function __construct($value = false, $field = 'id_order')
    {
        if ($value) {
            $this->load($value, $field);
        }
    }

    /**
     * Starts a new payment attempt for the given cart.
     *
     * @param Maksuturva $module
     * @param Cart $cart
     *
     * @return MaksuturvaPayment
     *
     * @throws MaksuturvaException
     */
    public static function startForCart(Maksuturva $module, Cart $cart)
    {
        $db = Db::getInstance();
        $created = $db->execute(sprintf(
            "INSERT INTO `%smt_payment` (`id_order`, `id_cart`, `status`, `logs`, `date_add`, `date_upd`)
             VALUES (NULL, %d, 0, '%s', NOW(), NOW());",
            _DB_PREFIX_,
            (int) $cart->id,
            pSQL(json_encode([]))
        ));
        if (!$created) {
            throw new MaksuturvaException('Failed to initialize Maksuturva payment attempt');
        }

        $id = (int) $db->Insert_ID();
        $attempt = self::getAttemptNumber((int) $cart->id, $id);
        $pmt_id = self::buildPaymentId($module->getPaymentIdPrefix(), (int) $cart->id, $attempt);

        $updated = $db->execute(sprintf(
            "UPDATE `%smt_payment`
                SET `attempt` = %d, `pmt_id` = '%s', `date_upd` = NOW()
              WHERE `id_mt_payment` = %d",
            _DB_PREFIX_,
            (int) $attempt,
            pSQL($pmt_id),
            (int) $id
        ));
        if (!$updated) {
            throw new MaksuturvaException('Failed to finalize Maksuturva payment attempt');
        }

        return new self($id, 'id_mt_payment');
    }

    /**
     * Returns a payment attempt loaded by its `pmt_id`.
     *
     * @param string $pmt_id
     *
     * @return MaksuturvaPayment
     *
     * @throws MaksuturvaException
     */
    public static function fromPmtId($pmt_id)
    {
        return new self($pmt_id, 'pmt_id');
    }

    /**
     * Returns the number of attempts for the cart up until the given row.
     *
     * @param int $cart_id
     * @param int $entry_id
     *
     * @return int
     */
    protected static function getAttemptNumber($cart_id, $entry_id)
    {
        $query = sprintf(
            'SELECT COUNT(*) FROM `%smt_payment` WHERE `id_cart` = %d AND `id_mt_payment` <= %d',
            _DB_PREFIX_,
            (int) $cart_id,
            (int) $entry_id
        );

        return (int) Db::getInstance()->getValue($query);
    }

    /**
     * Builds a new payment ID using the cart id and attempt number.
     *
     * @param string $prefix
     * @param int $cart_id
     * @param int $attempt
     *
     * @return string
     *
     * @throws MaksuturvaException
     */
    protected static function buildPaymentId($prefix, $cart_id, $attempt)
    {
        $raw = (string) ((((int) $cart_id + 100) * 10000) + (int) $attempt);
        $pmt_id = (string) $prefix . $raw;

        if (Tools::strlen($pmt_id) > 20) {
            throw new MaksuturvaException('Generated payment identifier exceeds the maximum length');
        }

        return $pmt_id;
    }

    /**
     * Returns the payment status.
     *
     * @return int
     */
    public function getStatus()
    {
        return (int) $this->status;
    }

    /**
     * Returns the payment reference number.
     *
     * @return string|null
     */
    public function getPmtReference()
    {
        return isset($this->data_sent['pmt_reference']) ? $this->data_sent['pmt_reference'] : null;
    }

    /**
     * Returns the monetary amount for the payments surcharge if it was included, zero otherwise.
     *
     * @return float|int
     */
    public function getSurcharge()
    {
        if (isset($this->data_sent['pmt_sellercosts'], $this->data_received['pmt_sellercosts'])) {
            $sent_seller_cost = (float) str_replace(',', '.', $this->data_sent['pmt_sellercosts']);
            $received_seller_cost = (float) str_replace(',', '.', $this->data_received['pmt_sellercosts']);
            if ($received_seller_cost > $sent_seller_cost) {
                return $received_seller_cost - $sent_seller_cost;
            }
        }

        return 0;
    }

    /**
     * Checks if the payment includes surcharges, i.e. if the used payment method charged an additional fee.
     *
     * @return bool
     */
    public function includesSurcharge()
    {
        return $this->getSurcharge() > 0;
    }

    /**
     * Cancels the payment, i.e. sets it's status to 'PS_OS_PAYMENT';
     */
    public function cancel()
    {
        $this->status = (int) Configuration::get('PS_OS_PAYMENT');
        $this->update();
    }

    /**
     * Completes the payment, i.e. sets it's status to 'PS_OS_CANCELED';
     */
    public function complete()
    {
        $this->status = (int) Configuration::get('PS_OS_CANCELED');
        $this->update();
    }

    /**
     * Loads a payment attempt using the provided lookup field.
     *
     * @param int|string $value
     * @param string $field
     *
     * @throws MaksuturvaException
     */
    protected function load($value, $field = 'id_order')
    {
        $allowed_fields = [
            'id_order',
            'id_mt_payment',
            'id_cart',
            'pmt_id',
        ];
        if (!in_array($field, $allowed_fields, true)) {
            throw new MaksuturvaException('Unsupported Maksuturva payment lookup field');
        }

        switch ($field) {
            case 'pmt_id':
                $where = sprintf("`pmt_id` = '%s'", pSQL($value));
                break;

            case 'id_mt_payment':
                $where = sprintf('`id_mt_payment` = %d', (int) $value);
                break;

            default:
                $where = sprintf('`%s` = %d', bqSQL($field), (int) $value);
                break;
        }

        $query = sprintf(
            'SELECT * FROM `%smt_payment` WHERE %s ORDER BY `id_mt_payment` DESC LIMIT 1;',
            _DB_PREFIX_,
            $where
        );
        $data = Db::getInstance()->executeS($query);
        if (!(is_array($data) && count($data) === 1)) {
            throw new MaksuturvaException('Failed to load Maksuturva payment');
        }

        $row = $data[0];
        $this->id = (int) $row['id_mt_payment'];
        $this->id_order = (int) $row['id_order'];
        $this->id_cart = (int) $row['id_cart'];
        $this->attempt = (int) $row['attempt'];
        $this->pmt_id = $row['pmt_id'];
        $this->status = (int) $row['status'];
        $this->data_sent = $this->decodeData($row['data_sent']);
        $this->data_received = $this->decodeData($row['data_received']);
        $this->logs = $this->decodeData($row['logs']);
        $this->date_add = $row['date_add'];
        $this->date_upd = $row['date_upd'];
    }

    /**
     * @param string $data
     *
     * @return array
     */
    protected function decodeData($data)
    {
        if (!isset($data) || !Tools::strlen((string) $data)) {
            return [];
        }

        $decoded = json_decode((string) $data, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Stores the sent payload for the attempt.
     *
     * @param array $data
     */
    public function recordRequest(array $data)
    {
        $this->data_sent = $data;
        $this->addLogEntry('request', $data);
        $this->update();
    }

    /**
     * Stores the gateway response for the attempt.
     *
     * @param array $data
     * @param int|null $status
     */
    public function recordResponse(array $data, $status = null)
    {
        $this->data_received = $data;
        if (!is_null($status)) {
            $this->status = (int) $status;
        }
        $this->addLogEntry('response', $data);
        $this->update();
    }

    /**
     * Links the attempt to a created order.
     *
     * @param int $id_order
     */
    public function attachOrder($id_order)
    {
        $this->id_order = (int) $id_order;
        $this->update();
    }

    /**
     * Returns the ID of the cart this attempt belongs to.
     *
     * @return int
     */
    public function getCartId()
    {
        return (int) $this->id_cart;
    }

    /**
     * Returns the attempt payment id.
     *
     * @return string
     */
    public function getPmtId()
    {
        return $this->pmt_id;
    }

    /**
     * Returns the attempt sequence number.
     *
     * @return int
     */
    public function getAttempt()
    {
        return (int) $this->attempt;
    }

    /**
     * Adds a log entry for the attempt.
     *
     * @param string $type
     * @param array $data
     */
    protected function addLogEntry($type, array $data)
    {
        $this->logs[] = [
            'type' => $type,
            'timestamp' => date(DATE_ATOM),
            'payload' => $data,
        ];
    }

    /**
     * Updates the payment in the db.
     */
    protected function update()
    {
        Db::getInstance()->execute(sprintf(
            'UPDATE `%smt_payment`
                SET `id_order` = %s,
                    `status` = %d,
                    `data_sent` = %s,
                    `data_received` = %s,
                    `logs` = %s,
                    `date_upd` = NOW()
              WHERE `id_mt_payment` = %d',
            _DB_PREFIX_,
            $this->id_order > 0 ? (int) $this->id_order : 'NULL',
            (int) $this->status,
            $this->encodeData($this->data_sent),
            $this->encodeData($this->data_received),
            $this->encodeData($this->logs),
            (int) $this->id
        ));
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function encodeData(array $data)
    {
        $json = json_encode($data);
        if ($json === false) {
            $json = '[]';
        }

        return "'" . pSQL($json) . "'";
    }
}
