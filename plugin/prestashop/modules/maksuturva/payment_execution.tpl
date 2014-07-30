{*
 * Maksuturva Payment Module
 * Creation date: 01/12/2011
*}

{capture name=path}{l s='Maksuturva payment' mod='maksuturva'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='maksuturva'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
	<p class="warning">{l s='Your shopping cart is empty.'}</p>
{else}

{if $emaksut == "1"}
    <h3>{l s='eMaksut payment' mod='maksuturva'}</h3>
{else}
    <h3>{l s='Maksuturva payment' mod='maksuturva'}</h3>
{/if}

<form action="{$form_action}" method="post">
<p>
    {if $emaksut == "1"}
        <img src="{$this_path}emaksut.png" alt="{l s='Pay with eMaksut' mod='maksuturva'}" width="144" height="50" style="float:left; margin: 0px 10px 5px 0px;"/>
        {l s='You have chosen to pay with eMaksut.' mod='maksuturva'}
    {else}
        <img src="{$this_path}maksuturva.gif" alt="{l s='Pay with Maksuturva' mod='maksuturva'}" width="115" height="29" style="float:left; margin: 0px 10px 5px 0px;"/>
        {l s='You have chosen to pay with Maksuturva.' mod='maksuturva'}
    {/if}
	<br/><br />
	{l s='Here is a short summary of your order:' mod='maksuturva'}
</p>
<p style="margin-top:20px;">
	- {l s='The total amount of your order is' mod='maksuturva'}
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
