{if $status == 'ok'}
	<p class="alert alert-success">{l s='Your order on %s is complete.' sprintf=$shop_name mod='maksuturva'}</p>
	<div class="box order-confirmation">
		<img src="{$this_path|escape:'html':'UTF-8'}/views/img/Svea_logo.png" class="img-fluid img-responsive" />
		<p><strong>{l s='Your order will be shipped as soon as possible.' mod='maksuturva'}</strong></p>
		<p>
			{l s='For any questions or for further information, please contact our' mod='maksuturva'}
			<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
		</p>
	</div>
{elseif $status == 'pending'}
	<p class="alert alert-info">{l s='Your order on %s is pending.' sprintf=$shop_name mod='maksuturva'}</p>
	<div class="box order-confirmation">
		<img src="{$this_path|escape:'html':'UTF-8'}/views/img/Svea_logo.png" class="img-fluid img-responsive" />
		<p><strong>{l s='Your order will be shipped as soon as we receive the payment confirmation.' mod='maksuturva'}</strong></p>
		<p>
			{l s='For any questions or for further information, please contact our' mod='maksuturva'}
			<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
		</p>
	</div>
{elseif $status == 'cancel'}
	<p class="alert alert-warning">{l s='Your order on %s has been canceled.' sprintf=$shop_name mod='maksuturva'}</p>
	<div class="box order-confirmation">
		<img src="{$this_path|escape:'html':'UTF-8'}/views/img/Svea_logo.png" class="img-fluid img-responsive" />
		<p>
			{l s='For any questions or for further information, please contact our' mod='maksuturva'}
			<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
		</p>
	</div>
{else}
	<p class="alert alert-warning">{l s='An error occurred while processing the payment.' mod='maksuturva'}</p>
	<div class="box order-confirmation">
		{if !empty($message)}
			<p>{$message|escape:'html':'UTF-8'}</p>
		{/if}
		<p>
			{l s='For any questions or for further information, please contact our' mod='maksuturva'}
			<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
		</p>
	</div>
{/if}