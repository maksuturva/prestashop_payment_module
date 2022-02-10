
{capture name=path}{l s='Maksuturva payment' mod='maksuturva'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='maksuturva'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $count_products <= 0}
	<p class="warning">{l s='Your shopping cart is empty.' mod='maksuturva'}</p>
{else}
	<h3>{l s='Maksuturva payment' mod='maksuturva'}</h3>
	<form action="{$mt_form_action|escape:'html':'UTF-8'}" method="post">
		<img src="{$this_path|escape:'html':'UTF-8'}views/img/Svea_logo.png"
			 alt="{l s='Pay with Maksuturva' mod='maksuturva'}"
			 class="img-fluid img-responsive"/>
		<p>
			{l s='You have chosen to pay with Maksuturva.' mod='maksuturva'}
			{l s='Here is a short summary of your order:' mod='maksuturva'}
		</p>
		<p>
			- {l s='The total amount of your order is' mod='maksuturva'}
			<span id="amount" class="price">{displayPrice price=$total}</span>
			{if $use_taxes == 1}
				{l s='(tax incl.)' mod='maksuturva'}
			{/if}
		</p>
		<p>
			{l s='You will be redirected to Maksuturva to perform the payment.' mod='maksuturva'}
			<br />
			<br />
			<b>{l s='Please confirm your order by clicking "I confirm my order"' mod='maksuturva'}.</b>
		</p>
		<p class="cart_navigation">
			<a href="{$back_button|escape:'html':'UTF-8'}" class="button_large hideOnSubmit">
				{l s='Other payment methods' mod='maksuturva'}
			</a>
			<input type="submit"
				   name="submit"
				   value="{l s='I confirm my order' mod='maksuturva'}"
				   class="exclusive_large hideOnSubmit" />
		</p>
		{foreach $mt_extra_fields as $name => $value}
			<input type="hidden" name="{$name|escape:'html':'UTF-8'}" value="{$value|escape:'html':'UTF-8'}"/>
		{/foreach}
	</form>
{/if}