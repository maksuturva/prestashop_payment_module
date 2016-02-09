{*
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
*}

{capture name=path}{l s='Maksuturva payment' mod='maksuturva'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='maksuturva'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='maksuturva'}</p>
{else}

<h3>{l s='Maksuturva payment' mod='maksuturva'}</h3>

<form action="{$form_action}" method="post">
<div>
    <img src="{$this_path}maksuturva.gif" alt="{l s='Pay with Maksuturva' mod='maksuturva'}" width="115" height="29" style="float:left; margin: 0px 10px 5px 0px;"/>
    <p>{l s='You have chosen to pay with Maksuturva.' mod='maksuturva'}</p>
	<div style="clear:both"></div>
</div>
<p style="margin-top:20px;">
	{l s='The total amount of your order is' mod='maksuturva'}
	<span id="amount" class="price">{displayPrice price=$total}</span>
	{if $use_taxes == 1}
    	{l s='(tax incl.)' mod='maksuturva'}
    {/if}
</p>
<p>
	{l s='You will be redirected to Maksuturva to perform the payment.' mod='maksuturva'}
	<br /><br />
	<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='maksuturva'}.</b>
</p>
<p class="cart_navigation">
	<a href="{$link->getPageLink('order', true, NULL, "step=3")}" class="button_large">{l s='Other payment methods' mod='maksuturva'}</a>
	<input type="submit" name="submit" value="{l s='I confirm my order' mod='maksuturva'}" class="exclusive_large" />
</p>
    {foreach $maksuturva_fields as $fieldname => $value}
        <input type="hidden" name="{$fieldname}" value="{$value}"/>
    {/foreach}
</form>
{/if}
