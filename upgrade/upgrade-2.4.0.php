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
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param Maksuturva $module
 *
 * @return bool
 */
function upgrade_module_2_4_0($module)
{
    unset($module);

    $db = Db::getInstance();
    $old_table = _DB_PREFIX_ . 'mt_payment';
    $new_table = _DB_PREFIX_ . 'mt_payment_tmp';

    $queries = [
        'DROP TABLE IF EXISTS `' . $new_table . '`',
        sprintf(
            'CREATE TABLE `%s` (
			  `id_mt_payment` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `id_order` int(10) unsigned DEFAULT NULL,
			  `id_cart` int(10) unsigned NOT NULL,
			  `attempt` int(10) unsigned NOT NULL DEFAULT 1,
			  `pmt_id` varchar(40) NOT NULL,
			  `status` int(10) unsigned NOT NULL DEFAULT 0,
			  `data_sent` LONGBLOB NULL DEFAULT NULL,
			  `data_received` LONGBLOB NULL DEFAULT NULL,
			  `logs` LONGTEXT NULL DEFAULT NULL,
			  `date_add` DATETIME NOT NULL,
			  `date_upd` DATETIME NULL DEFAULT NULL,
			  PRIMARY KEY (`id_mt_payment`),
			  UNIQUE KEY `uniq_pmt_id` (`pmt_id`),
			  KEY `idx_id_order` (`id_order`),
			  KEY `idx_id_cart` (`id_cart`)
			) ENGINE=%s DEFAULT CHARSET=utf8;',
            $new_table,
            _MYSQL_ENGINE_
        ),
    ];

    foreach ($queries as $sql) {
        if (!$db->execute($sql)) {
            return false;
        }
    }

    $rows = $db->executeS(sprintf(
        'SELECT p.*, o.id_cart
           FROM `%s` p
           LEFT JOIN `%sorders` o ON (o.id_order = p.id_order)',
        $old_table,
        _DB_PREFIX_
    ));
    if ($rows === false) {
        return false;
    }

    foreach ($rows as $row) {
        $id_order = isset($row['id_order']) ? (int) $row['id_order'] : 0;
        $id_cart = isset($row['id_cart']) ? (int) $row['id_cart'] : 0;

        $data_sent = isset($row['data_sent']) ? $row['data_sent'] : '';
        $decoded = json_decode($data_sent, true);

        if (is_array($decoded) && isset($decoded['pmt_id'])) {
            $pmt_id = $decoded['pmt_id'];
        } else {
            $prefix = (string) Configuration::get('MAKSUTURVA_PMT_ID_PREFIX');
            $base = $id_cart > 0 ? $id_cart : $id_order;
            $pmt_id = (string) $prefix . ((int) $base + 100);
        }

        $data_sent_value = Tools::strlen((string) $data_sent) ? $data_sent : json_encode([]);
        $data_received_value = Tools::strlen((string) $row['data_received'])
            ? $row['data_received']
            : json_encode([]);

        $date_upd = isset($row['date_upd']) && Tools::strlen($row['date_upd'])
            ? "'" . pSQL($row['date_upd']) . "'"
            : 'NULL';

        $insert = sprintf(
            "INSERT INTO `%s`
                (`id_order`, `id_cart`, `attempt`, `pmt_id`, `status`, `data_sent`, `data_received`, `logs`, `date_add`, `date_upd`)
             VALUES (%s, %d, 1, '%s', %d, '%s', '%s', '%s', '%s', %s);",
            $new_table,
            $id_order > 0 ? (int) $id_order : 'NULL',
            $id_cart,
            pSQL($pmt_id),
            isset($row['status']) ? (int) $row['status'] : 0,
            pSQL($data_sent_value),
            pSQL($data_received_value),
            pSQL(json_encode([])),
            pSQL($row['date_add']),
            $date_upd
        );

        if (!$db->execute($insert)) {
            return false;
        }
    }

    if (!$db->execute('DROP TABLE IF EXISTS `' . $old_table . '`')) {
        return false;
    }

    if (!$db->execute(sprintf('RENAME TABLE `%s` TO `%s`', $new_table, $old_table))) {
        return false;
    }

    return true;
}
