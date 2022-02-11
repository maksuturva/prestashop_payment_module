{*
* 2017 Maksuturva Group Oy
*
* NOTICE OF LICENSE
*
* This source file is subject to the GNU Lesser General Public License (LGPLv2.1)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* https://www.gnu.org/licenses/lgpl-2.1.html
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
* @copyright 2017 Maksuturva Group Oy
* @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
*}

{capture name=path}
    <a href="{$link->getPageLink('order', true, null, 'step=3')|escape:'html':'UTF-8'}"
       title="{l s='Go back to the Checkout' mod='maksuturva'}">
        {l s='Checkout' mod='maksuturva'}</a>
    <span class="navigation-pipe">{$navigationPipe|escape:'html':'UTF-8'}</span>
    {l s='Maksuturva payment' mod='maksuturva'}
{/capture}

<h1 class="page-heading">{l s='Order summary' mod='maksuturva'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $count_products <= 0}
    <p class="alert alert-warning">{l s='Your shopping cart is empty.' mod='maksuturva'}</p>
{else}
    <form action="{$mt_form_action|escape:'html':'UTF-8'}" method="post">
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {l s='Maksuturva payment' mod='maksuturva'}
            </h3>
            <img src="{$this_path|escape:'html':'UTF-8'}views/img/Svea_logo.png"
                 alt="{l s='Pay with Maksuturva' mod='maksuturva'}"
                 class="img-fluid img-responsive"/>
            <p class="cheque-indent">
                <strong class="dark">
                    {l s='You have chosen to pay with Maksuturva.' mod='maksuturva'}
                    {l s='Here is a short summary of your order:' mod='maksuturva'}
                </strong>
            </p>
            <p class="cheque-indent">
                - {l s='The total amount of your order is' mod='maksuturva'}
                <span id="amount" class="price">{displayPrice price=$total}</span>
                {if $use_taxes == 1}
                    {l s='(tax incl.)' mod='maksuturva'}
                {/if}
            </p>
            <p>
                {l s='You will be redirected to Maksuturva to perform the payment.' mod='maksuturva'}
                <br />
                <b>{l s='Please confirm your order by clicking "I confirm my order".' mod='maksuturva'}</b>
            </p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a class="button-exclusive btn btn-default"
               href="{$link->getPageLink('order', true, null, 'step=3')|escape:'html':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='maksuturva'}
            </a>
            <button class="button btn btn-default button-medium" type="submit">
                <span>{l s='I confirm my order' mod='maksuturva'}<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
        {foreach $mt_extra_fields as $name => $value}
            <input type="hidden" name="{$name|escape:'html':'UTF-8'}" value="{$value|escape:'html':'UTF-8'}"/>
        {/foreach}
    </form>
{/if}