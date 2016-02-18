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

if (!class_exists('ModuleFrontController')) {
    /**
     * Front controller base class for modules.
     * This is a drop in replacement in PrestaShop 1.4 where this does not exist.
     */
    abstract class ModuleFrontController extends FrontController
    {
        /**
         * @var ContextCore|Context the PS context.
         */
        protected $context;

        /**
         * @var Module the module instance.
         */
        protected $module;

        /**
         * @var string
         */
        protected $template;

        /**
         * @inheritdoc
         */
        public function init()
        {
            parent::init();

            $this->module = Module::getInstanceByName(Tools::getValue('module'));
            $this->context = Context::getContext();
            if (!$this->module->active) {
                Tools::redirect('index.php');
            }

            $this->initContent();
        }

        /**
         * @inheritdoc
         */
        public function displayContent()
        {
            parent::displayContent();
            $this->context->smarty->display($this->template);
        }

        /**
         * Assign module template.
         *
         * @param string $tmpl
         * @throws Exception
         */
        public function setTemplate($tmpl)
        {
            $m = $this->module->name;
            if (file_exists(_PS_THEME_DIR_ . 'modules/' . $m . '/' . $tmpl)) {
                $this->template = _PS_THEME_DIR_ . 'modules/' . $m . '/' . $tmpl;
            } elseif (file_exists(_PS_THEME_DIR_ . 'modules/' . $m . '/views/templates/front/' . $tmpl)) {
                $this->template = _PS_THEME_DIR_ . 'modules/' . $m . '/views/templates/front/' . $tmpl;
            } elseif (file_exists(_PS_MODULE_DIR_ . $m . '/views/templates/front/' . $tmpl)) {
                $this->template = _PS_MODULE_DIR_ . $m . '/views/templates/front/' . $tmpl;
            } else {
                throw new Exception(sprintf('Template "%s" not found', $tmpl));
            }
        }

        /**
         * Initializes the content.
         */
        public function initContent()
        {
        }
    }
}
