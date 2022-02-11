<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a class="maksuturva"
               href="{$link->getModuleLink('maksuturva', 'payment')|escape:'html':'UTF-8'}"
               title="{l s='Pay with Maksuturva' mod='maksuturva'}">
                <img src="{$this_path|escape:'html':'UTF-8'}/views/img/Svea_logo.png"
                     alt="{l s='Pay with Maksuturva' mod='maksuturva'}"
                     class="img-fluid img-responsive"/>
                {l s='Pay with Maksuturva' mod='maksuturva'}
            </a>
        </p>
    </div>
</div>