<?php

/**
 * Copyright (C) 2026 Svea Payments Oy
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
 * @copyright 2026 Svea Payments Oy
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

    // Check existing table structure
    $columns = $db->executeS('SHOW COLUMNS FROM `' . bqSQL($table) . '`');
    if (!is_array($columns)) {
        return false;
    }

    $has_id_mt_payment = false;
    $has_id_cart = false;
    $has_attempt = false;
    $has_pmt_id = false;
    $has_logs = false;

    foreach ($columns as $column) {
        if ($column['Field'] === 'id_mt_payment') {
            $has_id_mt_payment = true;
        }
        if ($column['Field'] === 'id_cart') {
            $has_id_cart = true;
        }
        if ($column['Field'] === 'attempt') {
            $has_attempt = true;
        }
        if ($column['Field'] === 'pmt_id') {
            $has_pmt_id = true;
        }
        if ($column['Field'] === 'logs') {
            $has_logs = true;
        }
    }

    // Add id_mt_payment as auto-increment primary key
    if (!$has_id_mt_payment) {
        // Add new primary key column
        if (!$db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD COLUMN `id_mt_payment` int(10) unsigned NOT NULL AUTO_INCREMENT FIRST, DROP PRIMARY KEY, ADD PRIMARY KEY (`id_mt_payment`)')) {
            return false;
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

    if (!$has_logs) {
        if (!$db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD COLUMN `logs` LONGTEXT NULL DEFAULT NULL AFTER `data_received`')) {
            return false;
        }
    }

    // Backfill id_cart from orders table
    $db->execute(
        'UPDATE `' . bqSQL($table) . '` p
         INNER JOIN `' . bqSQL(_DB_PREFIX_) . 'orders` o ON o.id_order = p.id_order
         SET p.id_cart = o.id_cart
         WHERE p.id_cart = 0 OR p.id_cart IS NULL'
    );

    // Backfill pmt_id for old records (from v2.3.0)
    // Extract pmt_id from JSON data_sent field
    $db->execute(
        'UPDATE `' . bqSQL($table) . '`
         SET pmt_id = JSON_UNQUOTE(JSON_EXTRACT(data_sent, \'$.pmt_id\'))
         WHERE (pmt_id = \'\' OR pmt_id IS NULL)
           AND data_sent IS NOT NULL
           AND JSON_EXTRACT(data_sent, \'$.pmt_id\') IS NOT NULL'
    );

    // Fallback: for any rows that still don't have pmt_id (malformed JSON or missing field),
    // generate a unique one using id_mt_payment with a recognizable prefix
    $db->execute(
        'UPDATE `' . bqSQL($table) . '`
         SET pmt_id = CONCAT(\'MIGRATED_\', id_mt_payment)
         WHERE pmt_id = \'\' OR pmt_id IS NULL'
    );

    // Add indexes
    $indexes = $db->executeS('SHOW INDEX FROM `' . bqSQL($table) . '`');
    $existing_indexes = [];
    if (is_array($indexes)) {
        foreach ($indexes as $index) {
            $existing_indexes[] = $index['Key_name'];
        }
    }

    if (!in_array('idx_pmt_id', $existing_indexes)) {
        // Use regular index instead of unique since we're dealing with historical data
        $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD INDEX `idx_pmt_id` (`pmt_id`)');
    }

    if (!in_array('idx_id_order', $existing_indexes)) {
        $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD INDEX `idx_id_order` (`id_order`)');
    }

    if (!in_array('idx_id_cart', $existing_indexes)) {
        $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD INDEX `idx_id_cart` (`id_cart`)');
    }

    // Remove deprecated encoding setting
    Configuration::deleteByName('MAKSUTURVA_ENCODING');

    return true;
}
