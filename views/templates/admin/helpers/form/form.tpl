{*
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
*}

{if $show_toolbar}
    {include file="toolbar.tpl" toolbar_btn=$toolbar_btn toolbar_scroll=$toolbar_scroll title=$title}
    <div class="leadin">{block name="leadin"}{/block}</div>
{/if}

{if isset($fields.title)}
    <h2>{$fields.title|escape:'html':'UTF-8'}</h2>
{/if}
{block name="defaultForm"}
    <form id="{if isset($fields.form.form.id_form)}{$fields.form.form.id_form|escape:'html':'UTF-8'}{else}{if $table == null}configuration_form{else}{$table|escape:'html':'UTF-8'}_form{/if}{/if}"
          class="defaultForm {$name_controller|escape:'html':'UTF-8'}"
          action="{$current|escape:'html':'UTF-8'}&{if !empty($submit_action)}{$submit_action|escape:'html':'UTF-8'}=1{/if}&token={$token|escape:'html':'UTF-8'}"
          method="post"
          enctype="multipart/form-data"
          {if isset($style)}style="{$style|escape:'html':'UTF-8'}"{/if}>
        {if $form_id}
            <input type="hidden"
                   name="{$identifier|escape:'html':'UTF-8'}"
                   id="{$identifier|escape:'html':'UTF-8'}"
                   value="{$form_id|escape:'html':'UTF-8'}" />
        {/if}
        {foreach $fields as $f => $fieldset}
            <fieldset id="fieldset_{$f|escape:'html':'UTF-8'}">
                {foreach $fieldset.form as $key => $field}
                    {if $key == 'legend'}
                        <legend>
                            {if isset($field.image)}
                                <img src="{$field.image|escape:'html':'UTF-8'}"
                                     alt="{$field.title|escape:'html':'UTF-8'}" />
                            {/if}
                            {$field.title|escape:'html':'UTF-8'}
                        </legend>
                    {elseif $key == 'description' && $field}
                        <p class="description">{$field|escape:'html':'UTF-8'}</p>
                    {elseif $key == 'input'}
                        {foreach $field as $input}
                            {if $input.type == 'hidden'}
                                <input type="hidden"
                                       name="{$input.name|escape:'html':'UTF-8'}"
                                       id="{$input.name|escape:'html':'UTF-8'}"
                                       value="{$fields_value[$input.name]|escape:'html':'UTF-8'}" />
                            {else}
                                {if $input.name == 'id_state'}
                                    <div id="contains_states" {if !$contains_states}style="display:none;"{/if}>
                                {/if}
                                {block name="label"}
                                    {if isset($input.label)}<label>{$input.label|escape:'html':'UTF-8'} </label>{/if}
                                {/block}
                                {block name="field"}
                                    <div class="margin-form">
                                        {block name="input"}
                                        {if $input.type == 'text'}
                                        {if isset($input.lang) && $input.lang}
                                            <div class="translatable">
                                                {foreach $languages as $language}
                                                    <div class="lang_{$language.id_lang|escape:'html':'UTF-8'}"
                                                         style="display:{if $language.id_lang == $defaultFormLanguage}block{else}none{/if}; float: left;">
                                                        {assign var='value_text' value=$fields_value[$input.name][$language.id_lang]}
                                                        <input type="text"
                                                               name="{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|escape:'html':'UTF-8'}"
                                                               id="{if isset($input.id)}{$input.id|escape:'html':'UTF-8'}_{$language.id_lang|escape:'html':'UTF-8'}{else}{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|escape:'html':'UTF-8'}{/if}"
                                                               value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|escape:'html':'UTF-8'}{/if}"
                                                               class="{if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if}"
                                                               {if isset($input.size)}size="{$input.size|escape:'html':'UTF-8'}"{/if}
                                                                {if isset($input.maxlength)}maxlength="{$input.maxlength|escape:'html':'UTF-8'}"{/if}
                                                                {if isset($input.readonly) && $input.readonly}readonly="readonly"{/if}
                                                                {if isset($input.disabled) && $input.disabled}disabled="disabled"{/if}
                                                                {if isset($input.autocomplete) && !$input.autocomplete}autocomplete="off"{/if} />
                                                        {if !empty($input.hint)}
                                                            <span class="hint" name="help_box">
                                                                {$input.hint|escape:'html':'UTF-8'}
                                                                <span class="hint-pointer">&nbsp;</span>
                                                            </span>
                                                        {/if}
                                                    </div>
                                                {/foreach}
                                            </div>
                                            {else}
                                            {assign var='value_text' value=$fields_value[$input.name]}
                                        <input type="text"
                                               name="{$input.name|escape:'html':'UTF-8'}"
                                               id="{if isset($input.id)}{$input.id|escape:'html':'UTF-8'}{else}{$input.name|escape:'html':'UTF-8'}{/if}"
                                               value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|escape:'html':'UTF-8'}{/if}"
                                               class="{if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if}"
                                               {if isset($input.size)}size="{$input.size|escape:'html':'UTF-8'}"{/if}
                                                {if isset($input.maxlength)}maxlength="{$input.maxlength|escape:'html':'UTF-8'}"{/if}
                                                {if isset($input.class)}class="{$input.class|escape:'html':'UTF-8'}"{/if}
                                                {if isset($input.readonly) && $input.readonly}readonly="readonly"{/if}
                                                {if isset($input.disabled) && $input.disabled}disabled="disabled"{/if}
                                                {if isset($input.autocomplete) && !$input.autocomplete}autocomplete="off"{/if} />
                                            {if isset($input.suffix)}{$input.suffix|escape:'html':'UTF-8'}{/if}
                                        {if !empty($input.hint)}
                                            <span class="hint" name="help_box">
                                                {$input.hint|escape:'html':'UTF-8'}
                                                <span class="hint-pointer">&nbsp;</span>
                                            </span>
                                        {/if}
                                        {/if}
                                            {elseif $input.type == 'select'}
                                        {if isset($input.options.query) && !$input.options.query && isset($input.empty_message)}
                                            {$input.empty_message|escape:'html':'UTF-8'}
                                            {$input.required = false}
                                            {$input.desc = null}
                                            {else}
                                            <select name="{$input.name|escape:'html':'UTF-8'}"
                                                    class="{if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if}"
                                                    id="{if isset($input.id)}{$input.id|escape:'html':'UTF-8'}{else}{$input.name|escape:'html':'UTF-8'}{/if}"
                                                    {if isset($input.multiple)}multiple="multiple" {/if}
                                                    {if isset($input.size)}size="{$input.size|escape:'html':'UTF-8'}"{/if}>
                                                {if isset($input.options.default)}
                                                    <option value="{$input.options.default.value|escape:'html':'UTF-8'}">
                                                        {$input.options.default.label|escape:'html':'UTF-8'}
                                                    </option>
                                                {/if}
                                                {if isset($input.options.optiongroup)}
                                                    {foreach $input.options.optiongroup.query as $optiongroup}
                                                        <optgroup label="{$optiongroup[$input.options.optiongroup.label]|escape:'html':'UTF-8'}">
                                                            {foreach $optiongroup[$input.options.options.query] as $option}
                                                                <option value="{$option[$input.options.options.id]|escape:'html':'UTF-8'}"
                                                                        {if isset($input.multiple)}
                                                                            {foreach $fields_value[$input.name] as $field_value}
                                                                                {if $field_value == $option[$input.options.options.id]}selected="selected"{/if}
                                                                            {/foreach}
                                                                        {else}
                                                                            {if $fields_value[$input.name] == $option[$input.options.options.id]}selected="selected"{/if}
                                                                        {/if}
                                                                >{$option[$input.options.options.name]|escape:'html':'UTF-8'}</option>
                                                            {/foreach}
                                                        </optgroup>
                                                    {/foreach}
                                                {else}
                                                    {foreach $input.options.query AS $option}
                                                        {if is_object($option)}
                                                            <option value="{$option->$input.options.id|escape:'html':'UTF-8'}"
                                                                    {if isset($input.multiple)}
                                                                        {foreach $fields_value[$input.name] as $field_value}
                                                                            {if $field_value == $option->$input.options.id}
                                                                                selected="selected"
                                                                            {/if}
                                                                        {/foreach}
                                                                    {else}
                                                                        {if $fields_value[$input.name] == $option->$input.options.id}
                                                                            selected="selected"
                                                                        {/if}
                                                                    {/if}
                                                            >{$option->$input.options.name|escape:'html':'UTF-8'}</option>
                                                        {elseif $option == "-"}
                                                            <option value="">-</option>
                                                        {else}
                                                            <option value="{$option[$input.options.id]|escape:'html':'UTF-8'}"
                                                                    {if isset($input.multiple)}
                                                                        {foreach $fields_value[$input.name] as $field_value}
                                                                            {if $field_value == $option[$input.options.id]}
                                                                                selected="selected"
                                                                            {/if}
                                                                        {/foreach}
                                                                    {else}
                                                                        {if $fields_value[$input.name] == $option[$input.options.id]}
                                                                            selected="selected"
                                                                        {/if}
                                                                    {/if}
                                                            >{$option[$input.options.name]|escape:'html':'UTF-8'}</option>

                                                        {/if}
                                                    {/foreach}
                                                {/if}
                                            </select>
                                        {if !empty($input.hint)}
                                            <span class="hint" name="help_box">
                                                {$input.hint|escape:'html':'UTF-8'}
                                                <span class="hint-pointer">&nbsp;</span>
                                            </span>
                                        {/if}
                                        {/if}
                                            {elseif $input.type == 'radio'}
                                        {foreach $input.values as $value}
                                        <input type="radio"
                                               name="{$input.name|escape:'html':'UTF-8'}"
                                               id="{$value.id|escape:'html':'UTF-8'}"
                                               value="{$value.value|escape:'html':'UTF-8'}"
                                               {if $fields_value[$input.name] == $value.value}checked="checked"{/if}
                                               {if isset($input.disabled) && $input.disabled}disabled="disabled"{/if} />
                                            <label {if isset($input.class)}class="{$input.class|escape:'html':'UTF-8'}"{/if}
                                                   for="{$value.id|escape:'html':'UTF-8'}">
                                                {if isset($input.is_bool) && $input.is_bool == true}
                                                    {if $value.value == 1}
                                                        <img src="../img/admin/enabled.gif"
                                                             alt="{$value.label|escape:'html':'UTF-8'}"
                                                             title="{$value.label|escape:'html':'UTF-8'}" />
                                                    {else}
                                                        <img src="../img/admin/disabled.gif"
                                                             alt="{$value.label|escape:'html':'UTF-8'}"
                                                             title="{$value.label|escape:'html':'UTF-8'}" />
                                                    {/if}
                                                {else}
                                                    {$value.label|escape:'html':'UTF-8'}
                                                {/if}
                                            </label>
                                        {if isset($input.br) && $input.br}<br />{/if}
                                        {if isset($value.p) && $value.p}<p>{$value.p|escape:'html':'UTF-8'}</p>{/if}
                                        {/foreach}
                                            {elseif $input.type == 'textarea'}
                                        {if isset($input.lang) && $input.lang}
                                            <div class="translatable">
                                                {foreach $languages as $language}
                                                    <div class="lang_{$language.id_lang|escape:'html':'UTF-8'}"
                                                         id="{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|escape:'html':'UTF-8'}"
                                                         style="display:{if $language.id_lang == $defaultFormLanguage}block{else}none{/if}; float: left;">
                                                        <textarea cols="{$input.cols|escape:'html':'UTF-8'}"
                                                                  rows="{$input.rows|escape:'html':'UTF-8'}"
                                                                  name="{$input.name|escape:'html':'UTF-8'}_{$language.id_lang|escape:'html':'UTF-8'}"
                                                                  {if isset($input.autoload_rte) && $input.autoload_rte}class="rte autoload_rte {if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if}"{/if} >
                                                            {$fields_value[$input.name][$language.id_lang]|escape:'html':'UTF-8'}
                                                        </textarea>
                                                    </div>
                                                {/foreach}
                                            </div>
                                            {else}
                                            <textarea name="{$input.name|escape:'html':'UTF-8'}"
                                                      id="{if isset($input.id)}{$input.id|escape:'html':'UTF-8'}{else}{$input.name|escape:'html':'UTF-8'}{/if}"
                                                      cols="{$input.cols|escape:'html':'UTF-8'}"
                                                      rows="{$input.rows|escape:'html':'UTF-8'}"
                                                      {if isset($input.autoload_rte) && $input.autoload_rte}class="rte autoload_rte {if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if}"{/if}>
                                                {$fields_value[$input.name]|escape:'html':'UTF-8'}
                                            </textarea>
                                        {/if}
                                            {elseif $input.type == 'checkbox'}
                                        {foreach $input.values.query as $value}
                                            {assign var=id_checkbox value=$input.name|cat:'_'|cat:$value[$input.values.id]}
                                        <input type="checkbox"
                                               name="{$id_checkbox|escape:'html':'UTF-8'}"
                                               id="{$id_checkbox|escape:'html':'UTF-8'}"
                                               class="{if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if}"
                                               {if isset($value.val)}value="{$value.val|escape:'html':'UTF-8'}"{/if}
                                               {if isset($fields_value[$id_checkbox]) && $fields_value[$id_checkbox]}checked="checked"{/if} />
                                            <label for="{$id_checkbox|escape:'html':'UTF-8'}" class="t">
                                                <strong>{$value[$input.values.name]|escape:'html':'UTF-8'}</strong>
                                            </label>
                                            <br />
                                        {/foreach}
                                            {elseif $input.type == 'free'}
                                            {$fields_value[$input.name]|escape:'html':'UTF-8'}
                                        {/if}
                                        {if isset($input.required) && $input.required && $input.type != 'radio'} <sup>*</sup>{/if}
                                        {/block}{* end block input *}
                                        {block name="description"}
                                            {if isset($input.desc) && !empty($input.desc)}
                                                <p class="preference_description">
                                                    {if is_array($input.desc)}
                                                        {foreach $input.desc as $p}
                                                            {if is_array($p)}
                                                                <span id="{$p.id|escape:'html':'UTF-8'}">
                                                                    {$p.text|escape:'html':'UTF-8'}
                                                                </span>
                                                                <br />
                                                            {else}
                                                                {$p|escape:'html':'UTF-8'}
                                                                <br />
                                                            {/if}
                                                        {/foreach}
                                                    {else}
                                                        {$input.desc|escape:'html':'UTF-8'}
                                                    {/if}
                                                </p>
                                            {/if}
                                        {/block}
                                        {if isset($input.lang) && isset($languages)}<div class="clear"></div>{/if}
                                    </div>
                                    <div class="clear"></div>
                                {/block}{* end block field *}
                                {if $input.name == 'id_state'}
                                    </div>
                                {/if}
                            {/if}
                        {/foreach}
                    {elseif $key == 'submit'}
                        <div class="margin-form">
                            <input type="submit"
                                   id="{if isset($field.id)}{$field.id|escape:'html':'UTF-8'}{else}{$table|escape:'html':'UTF-8'}_form_submit_btn{/if}"
                                   value="{$field.title|escape:'html':'UTF-8'}"
                                   name="{if isset($field.name)}{$field.name|escape:'html':'UTF-8'}{else}{$submit_action|escape:'html':'UTF-8'}{/if}{if isset($field.stay) && $field.stay|escape:'html':'UTF-8'}AndStay{/if}"
                                   {if isset($field.class)}class="{$field.class|escape:'html':'UTF-8'}"{/if} />
                        </div>
                    {elseif $key == 'desc'}
                        <p class="clear">
                            {if is_array($field)}
                                {foreach $field as $k => $p}
                                    {if is_array($p)}
                                        <span id="{$p.id|escape:'html':'UTF-8'}">
                                            {$p.text|escape:'html':'UTF-8'}
                                        </span>
                                        <br />
                                    {else}
                                        {$p|escape:'html':'UTF-8'}
                                        {if isset($field[$k+1])}<br />{/if}
                                    {/if}
                                {/foreach}
                            {else}
                                {$field|escape:'html':'UTF-8'}
                            {/if}
                        </p>
                    {/if}
                    {block name="other_input"}{/block}
                {/foreach}
                {if $required_fields}
                    <div class="small"><sup>*</sup> {l s='Required field' mod='maksuturva'}</div>
                {/if}
            </fieldset>
            {block name="other_fieldsets"}{/block}
            {if isset($fields[$f+1])}<br />{/if}
        {/foreach}
    </form>
{/block}
{block name="after"}{/block}
