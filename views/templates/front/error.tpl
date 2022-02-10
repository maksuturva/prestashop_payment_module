{extends $layout}

{block name='content'}

    <h3>{l s='Something went wrong!' mod='maksuturva'}</h3>

    {if isset($error_message)}
        {if $error_message == 'error'}
            <p class="alert alert-warning">{l s='An error occurred while processing the payment.' mod='maksuturva'}</p>
            <div class="box order-confirmation">
                <p>
                    {l s='For any questions or for further information, please contact our' mod='maksuturva'}
                    <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
                </p>
            </div>
        {else}
            <p class="alert alert-warning">{l s='Your order on %s has been canceled.' sprintf=[$shop_name] mod='maksuturva'}</p>
            <div class="box order-confirmation">
                <img src="{$this_path|escape:'html':'UTF-8'}/views/img/Svea_logo.png" class="img-fluid img-responsive" />
                <p>
                    {l s='For any questions or for further information, please contact our' mod='maksuturva'}
                    <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
                </p>
            </div>
        {/if}
    {/if}

{/block}

