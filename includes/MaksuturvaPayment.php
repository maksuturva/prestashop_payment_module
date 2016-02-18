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

/**
 * Maksuturva payment container.
 */
class MaksuturvaPayment
{
    /**
     * @var int the ID of the PS order object this payment is for.
     */
    protected $id_order;

    /**
     * @var string the payment status.
     */
    protected $status;

    /**
     * @var array the payment data sent to the gateway.
     */
    protected $data_sent = array();

    /**
     * @var array the payment data received from the gateway.
     */
    protected $data_received = array();

    /**
     * @var string the datetime when the payment was added.
     */
    protected $date_add;

    /**
     * @var string the datetime when the payment was last updated.
     */
    protected $date_upd;

    /**
     * Create or load a Maksuturva payment.
     *
     * @param int|bool $id_order
     */
    public function __construct($id_order = false)
    {
        if ($id_order > 0) {
            $this->load($id_order);
        }
    }

    /**
     * Creates a new payment and stores it in the database.
     *
     * @param array $data
     * @return MaksuturvaPayment
     *
     * @throws MaksuturvaException
     */
    public static function create(array $data)
    {
        $created = Db::getInstance()->execute(sprintf(
            "INSERT INTO `%smt_payment` (`id_order`, `status`, `data_sent`, `data_received`, `date_add`)
              VALUES (%d, %d, '%s', '%s', NOW());",
            _DB_PREFIX_,
            (int)$data['id_order'],
            (int)$data['status'],
            pSQL(Tools::jsonEncode($data['data_sent'])),
            pSQL(Tools::jsonEncode($data['data_received']))
        ));
        if (!$created) {
            throw new MaksuturvaException('Failed to create Maksuturva payment');
        }

        return new self((int)$data['id_order']);
    }

    /**
     * Returns the payment status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
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
            $sent_seller_cost = str_replace(',', '.', $this->data_sent['pmt_sellercosts']);
            $received_seller_cost = str_replace(',', '.', $this->data_received['pmt_sellercosts']);
            if ($received_seller_cost > $sent_seller_cost) {
                return number_format($received_seller_cost - $sent_seller_cost, 2, '.', '');
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
        return ($this->getSurcharge() > 0);
    }

    /**
     * Cancels the payment, i.e. sets it's status to 'PS_OS_PAYMENT';
     */
    public function cancel()
    {
        $this->status = (int)Configuration::get('PS_OS_PAYMENT');
        $this->update();
    }

    /**
     * Completes the payment, i.e. sets it's status to 'PS_OS_CANCELED';
     */
    public function complete()
    {
        $this->status = (int)Configuration::get('PS_OS_CANCELED');
        $this->update();
    }

    /**
     * @param int $id_order
     *
     * @throws MaksuturvaException
     */
    protected function load($id_order)
    {
        $query = sprintf('SELECT * FROM `%smt_payment` WHERE id_order = %d LIMIT 1;', _DB_PREFIX_, (int)$id_order);
        $data = Db::getInstance()->s($query);
        if (!(is_array($data) && count($data) === 1)) {
            throw new MaksuturvaException('Failed to load Maksuturva payment');
        }

        $this->id_order = (int)$data[0]['id_order'];
        $this->status = (int)$data[0]['status'];
        $this->data_sent = Tools::jsonDecode($data[0]['data_sent'], true);
        $this->data_received = Tools::jsonDecode($data[0]['data_received'], true);
        $this->date_add = $data[0]['date_add'];
        $this->date_upd = $data[0]['date_upd'];
    }

    /**
     * Updates the payment in the db. This only updates the current status and the `date_upd` datetime,
     * everything else can never change.
     */
    protected function update()
    {
        Db::getInstance()->execute(sprintf(
            'UPDATE `%smt_payment` SET `status` = %d, `date_upd` = NOW() WHERE `id_order` = %d',
            _DB_PREFIX_,
            (int)$this->status,
            (int)$this->id_order
        ));
    }
}
