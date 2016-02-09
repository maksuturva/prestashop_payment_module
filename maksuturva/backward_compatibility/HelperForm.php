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

/**
 * Simple drop in replacement for the PS 1.5+ HelperForm class that can be used in PS 1.4.
 */
class HelperForm
{
    public $module;
    public $context;
    public $tpl;
    public $base_path = '/views/templates/admin/';
    public $base_folder = 'helpers/form/';
    public $base_tpl = 'form.tpl';
    public $submit_action = 'submit';
    public $tpl_vars = array();
    public $title;
    public $token;
    public $show_toolbar = false;
    public $toolbar_btn;
    public $toolbar_scroll;
    public $currentIndex;
    public $table;
    public $identifier;
    public $name_controller;
    public $languages = array();
    public $default_form_language;
    public $allow_employee_form_lang;
    public $id;
    public $fields_form = array();
    public $fields_value = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->context = Context::getContext();
    }

    /**
     * Generates the from HTML and returns it.
     *
     * @param array $fields_form
     * @return string
     */
    public function generateForm(array $fields_form)
    {
        $this->tpl = $this->createTemplate($this->base_tpl);

        $this->tpl->assign(array(
            'title' => $this->title,
            'toolbar_btn' => $this->toolbar_btn,
            'show_toolbar' => $this->show_toolbar,
            'toolbar_scroll' => $this->toolbar_scroll,
            'submit_action' => $this->submit_action,
            'current' => $this->currentIndex,
            'token' => $this->token,
            'table' => $this->table,
            'identifier' => $this->identifier,
            'name_controller' => $this->name_controller,
            'languages' => $this->languages,
            'defaultFormLanguage' => $this->default_form_language,
            'allowEmployeeFormLang' => $this->allow_employee_form_lang,
            'form_id' => $this->id,
            'fields' => $fields_form,
            'fields_value' => $this->fields_value,
            'required_fields' => $this->hasRequiredFields($fields_form),
            'module_dir' => _MODULE_DIR_,
            'contains_states' => (isset($this->fields_value['id_country']) && isset($this->fields_value['id_state']))
                ? Country::containsStates($this->fields_value['id_country'])
                : null,
        ));

        $this->tpl->assign($this->tpl_vars);
        return $this->tpl->fetch();
    }

    /**
     * Create a smarty template from the template file.
     *
     * @param string $tpl_name
     * @return mixed
     */
    public function createTemplate($tpl_name)
    {
        if ($this->module)
            $tpl_path = _PS_MODULE_DIR_.$this->module->name.$this->base_path.$this->base_folder.$tpl_name;

        if (isset($tpl_path) && file_exists($tpl_path))
            return $this->context->smarty->createTemplate($tpl_path, $this->context->smarty);
        else
            return $this->context->smarty->createTemplate($this->base_folder.$tpl_name, $this->context->smarty);
    }

    /**
     * Checks if there are required fields in the form.
     *
     * @param array $fields_form
     * @return bool
     */
    public function hasRequiredFields(array $fields_form)
    {
        foreach ($fields_form as $data)
            if (isset($data['form']['input']))
                foreach ($data['form']['input'] as $input)
                    if (array_key_exists('required', $input) && $input['required'] && $input['type'] != 'radio')
                        return true;

        return false;
    }
}
