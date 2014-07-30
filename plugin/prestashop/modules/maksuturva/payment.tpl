{*
 * Maksuturva Payment Module
 * Creation date: 01/12/2011
*}
<p class="payment_module">
    {if $emaksut == "1"}
	<a href="{$this_path_ssl}payment.php" title="{l s='Pay with eMaksut' mod='maksuturva'}">
		<img src="{$this_path}emaksut.png" alt="{l s='Pay with eMaksut' mod='maksuturva'}" width="144" height="50"/>
		{l s='Pay with eMaksut' mod='maksuturva'}
	</a>
    {else}
    <a href="{$this_path_ssl}payment.php" title="{l s='Pay with Maksuturva' mod='maksuturva'}">
        <img src="{$this_path}maksuturva.gif" alt="{l s='Pay with Maksuturva' mod='maksuturva'}" width="115" height="29"/>
        {l s='Pay with Maksuturva' mod='maksuturva'}
    </a>
    {/if}
</p>