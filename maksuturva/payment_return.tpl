{*
 * Maksuturva Payment Module
 * Creation date: 01/12/2011
*}

{if $status != "error"}
    {if $emaksut == "1"}
        <img src="{$this_path}emaksut.png" width="144" height="50" style="float: right;"/>
    {else}
        <img src="{$this_path}maksuturva.gif" width="115" height="29" style="float: right;"/>
    {/if}
{/if}
{if $status == 'ok'}
	<p>{l s='Your order on' mod='maksuturva' mod='maksuturva'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='maksuturva'}
		<br /><br /><span class="bold">{l s='Your order will be shipped as soon as possible.' mod='maksuturva'}</span>
		<br /><br />{l s='For any questions or for further information, please contact our' mod='maksuturva'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='maksuturva'}</a>.
	</p>
{elseif $status == 'pending'}
	<p>{l s='Your order on' mod='maksuturva'} <span class="bold">{$shop_name}</span> {l s='is pending.' mod='maksuturva'}
		<br /><br /><span class="bold">{l s='Your order will be shipped as soon as we receive the payment confirmation.' mod='maksuturva'}</span>
		<br /><br />{l s='For any questions or for further information, please contact our' mod='maksuturva'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='maksuturva'}</a>.
	</p>
{elseif $status == 'cancel'}
    <p>{l s='Your order on' mod='maksuturva'} <span class="bold">{$shop_name}</span> {l s='has been canceled.' mod='maksuturva'}
        <br /><br />{l s='For any questions or for further information, please contact our' mod='maksuturva'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='maksuturva'}</a>.
    </p>
{else}
	<p class="warning">{l s='An error occurred while processing the payment.' mod='maksuturva'}</p>
	<p class="warning" style="font-weight: bold;">{$message}</p>
	<p class="warning">
		{l s='For further questions you can contact our' mod='maksuturva'}
		<a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='maksuturva'}</a>.
	</p>
{/if}