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

/*
 * This file is used to abstract the differences between PrestaShop versions when dealing with module routing.
 * It makes sure the PrestaShop config is found and included and exposes a front controller base class in PrestaShop
 * versions that do not have it. This enables the module to always run the controllers when the "old school" payment
 * scripts are executed.
 */

/*
 * If this file is symlinked, then we need to parse the `SCRIPT_FILENAME` to get the path of the PS root dir.
 * This is because __FILE__ resolves the symlink source path.
 * We cannot use `/../..` on dirname() of `SCRIPT_FILENAME` as that will also resolve the symlink source path.
 * So combining as many dirname() calls as needed to step up the tree to the second parent, which should be the
 * PS root dir, is a solution.
 */
if (!empty($_SERVER['SCRIPT_FILENAME'])) {
    $ps_dir = dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])));
}

/*
 * If the PS dir is not set, or we cannot resolve the config file, try to to get the path relative to this file.
 * This only works if this file is NOT symlinked.
 */
if (!isset($ps_dir) || !file_exists($ps_dir . '/config/config.inc.php')) {
    $ps_dir = dirname(__FILE__) . '/../../..';
}

require_once($ps_dir . '/config/config.inc.php');
$_GET['module'] = 'maksuturva';

/*
 * The "ModuleFrontController" class won't be defined in PS 1.4, so define it.
 */
if (_PS_VERSION_ < '1.5') {
    require_once('ModuleFrontController.php');
}
