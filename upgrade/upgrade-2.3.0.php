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
 * Upgrades the module to version 2.3.0.
 *
 * Registers new hooks used in PrestaShop 8.1+.
 *
 * @param Maksuturva $module
 *
 * @return bool
 */
function upgrade_module_2_3_0($module)
{
    return $module->unregisterHook('Header')
        && $module->registerhook('displayHeader')
        && $module->unregisterHook('paymentReturn')
        && $module->registerhook('displayPaymentReturn')
        && $module->unregisterHook('PDFInvoice')
        && $module->registerhook('displayPDFInvoice')
        ;
}
