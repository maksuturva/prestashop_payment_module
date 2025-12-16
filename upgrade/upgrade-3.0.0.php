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
function upgrade_module_3_0_0($module)
{
    unset($module);

    $db = Db::getInstance();
    $table = _DB_PREFIX_ . 'mt_payment';

    // Check if id_cart column exists, if not add it
    $columns = $db->executeS('SHOW COLUMNS FROM `' . bqSQL($table) . '`');
    $has_id_cart = false;
    $has_attempt = false;
    $has_pmt_id = false;

    foreach ($columns as $column) {
        if ($column['Field'] === 'id_cart') {
            $has_id_cart = true;
        }
        if ($column['Field'] === 'attempt') {
            $has_attempt = true;
        }
        if ($column['Field'] === 'pmt_id') {
            $has_pmt_id = true;
        }
    }

    // Add missing columns
    if (!$has_id_cart) {
        if (!$db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD COLUMN `id_cart` int(10) unsigned NOT NULL DEFAULT 0 AFTER `id_order`')) {
            return false;
        }
    }

    if (!$has_attempt) {
        if (!$db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD COLUMN `attempt` int(10) unsigned NOT NULL DEFAULT 1 AFTER `id_cart`')) {
            return false;
        }
    }

    if (!$has_pmt_id) {
        if (!$db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD COLUMN `pmt_id` varchar(40) NOT NULL DEFAULT \'\' AFTER `attempt`')) {
            return false;
        }
    }

    // Update id_cart from orders table where it's 0 or NULL
    $db->execute(
        'UPDATE `' . bqSQL($table) . '` p
         INNER JOIN `' . bqSQL(_DB_PREFIX_) . 'orders` o ON o.id_order = p.id_order
         SET p.id_cart = o.id_cart
         WHERE p.id_cart = 0 OR p.id_cart IS NULL'
    );

    // Get all rows to update pmt_id
    $query = new DbQuery();
    $query->select('id_mt_payment, id_order, id_cart, data_sent, pmt_id');
    $query->from('mt_payment');

    $rows = $db->executeS($query);
    if ($rows === false) {
        return false;
    }

    // Update each row with proper pmt_id
    /*
    foreach ($rows as $row) {
        // Skip if pmt_id is already set
        if (!empty($row['pmt_id'])) {
            continue;
        }

        $id_order = (int) $row['id_order'];
        $id_cart = (int) $row['id_cart'];
        $data_sent = isset($row['data_sent']) ? $row['data_sent'] : '';

        // Try to extract pmt_id from data_sent JSON
        $decoded = json_decode($data_sent, true);
        if (is_array($decoded) && isset($decoded['pmt_id'])) {
            $pmt_id = $decoded['pmt_id'];
        } else {
            // Generate pmt_id from cart or order ID
            $prefix = (string) Configuration::get('MAKSUTURVA_PMT_ID_PREFIX');
            $base = $id_cart > 0 ? $id_cart : $id_order;
            $pmt_id = (string) $prefix . ((int) $base + 100);
        }

        // Update the row
        $db->update(
            'mt_payment',
            ['pmt_id' => pSQL($pmt_id)],
            'id_mt_payment = ' . (int) $row['id_mt_payment']
        );
    }
    */

    // Add indexes if they don't exist (ignore errors if they already exist)
    $indexes = $db->executeS('SHOW INDEX FROM `' . bqSQL($table) . '`');
    $existing_indexes = [];
    if (is_array($indexes)) {
        foreach ($indexes as $index) {
            $existing_indexes[] = $index['Key_name'];
        }
    }

    if (!in_array('uniq_pmt_id', $existing_indexes)) {
        $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD UNIQUE KEY `uniq_pmt_id` (`pmt_id`)');
    }

    if (!in_array('idx_id_order', $existing_indexes)) {
        $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD KEY `idx_id_order` (`id_order`)');
    }

    if (!in_array('idx_id_cart', $existing_indexes)) {
        $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD KEY `idx_id_cart` (`id_cart`)');
    }

    // Remove deprecated MAKSUTURVA_ENCODING setting (now hardcoded to UTF-8)
    Configuration::deleteByName('MAKSUTURVA_ENCODING');

    return true;
}
