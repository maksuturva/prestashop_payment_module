{**
 * Copyright (C) 2026 Svea Payments Oy
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License (LGPLv2.1)
 * that is bundled with this package in the file LICENSE.
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
 * @author    Svea Payments Oy <info@svea.fi>
 * @copyright 2026 Svea Payments Oy
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
 *}
{extends $layout}

{block name='content'}

    <h3>{l s='Something went wrong!' mod='maksuturva'}</h3>

    {if isset($error_message)}
        {if $error_message == 'error'}
            <p class="alert alert-warning">{l s='There was an error processing the payment.' mod='maksuturva'}</p>
            {if isset($error_message_detail)}
                <p class="small text-muted">{$error_message_detail|escape:'html':'UTF-8'}</p>
            {/if}
        {elseif $error_message == 'cancel'}
            <p class="alert alert-warning">{l s='You have cancelled the payment.' mod='maksuturva'}</p>
        {else}
            <p class="alert alert-warning">{$error_message|escape:'html':'UTF-8'}</p>
            {if isset($error_message_detail)}
                <p class="small text-muted">{$error_message_detail|escape:'html':'UTF-8'}</p>
            {/if}
        {/if}
    {/if}

    <div class="box order-confirmation">
        <p>
            <a class="btn btn-lg btn-primary" href="{$urls.pages.order}">{l s='Try again or choose another payment method' mod='maksuturva'}</a>
        </p>
        <p>
            {l s='For any questions or for further information, please contact our' mod='maksuturva'}
            <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
        </p>
    </div>

{/block}
