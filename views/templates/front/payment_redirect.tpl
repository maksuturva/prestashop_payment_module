{**
 * Copyright (C) 2023 Svea Payments Oy
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
 * @copyright 2023 Svea Payments Oy
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
 *}

{extends file='page.tpl'}

{block name='page_content'}
<div class="maksuturva-payment-redirect">
    <style>
        .maksuturva-payment-redirect {
            text-align: center;
            padding: 60px 20px;
            min-height: 400px;
        }
        .maksuturva-redirect-spinner {
            width: 60px;
            height: 60px;
            margin: 0 auto 30px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: maksuturva-spin 1s linear infinite;
        }
        @keyframes maksuturva-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .maksuturva-redirect-logo {
            max-width: 200px;
            margin: 0 auto 30px;
        }
        .maksuturva-redirect-message {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 20px;
        }
        .maksuturva-redirect-details {
            color: #666;
            margin-bottom: 30px;
        }
        .maksuturva-redirect-manual {
            margin-top: 40px;
        }
        .maksuturva-redirect-manual button {
            padding: 12px 30px;
            font-size: 1.1em;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .maksuturva-redirect-manual button:hover {
            background-color: #2980b9;
        }
    </style>

    <div class="maksuturva-redirect-spinner"></div>

    <div class="maksuturva-redirect-logo">
        <img src="{$urls.base_url}modules/maksuturva/views/img/Svea_logo.png" alt="Maksuturva" class="img-fluid">
    </div>

    <div class="maksuturva-redirect-message">
        {l s='Redirecting to Maksuturva payment gateway...' mod='maksuturva'}
    </div>

    <div class="maksuturva-redirect-details">
        {l s='Please wait while we redirect you to complete your payment.' mod='maksuturva'}<br>
        {l s='Total amount:' mod='maksuturva'} <strong>{$cart_total|escape:'html':'UTF-8'}</strong>
    </div>

    {* Hidden form that will auto-submit to payment gateway *}
    <form method="POST" action="{$gateway_url|escape:'html':'UTF-8'}" id="maksuturva-payment-form">
        {foreach from=$gateway_fields key=field_name item=field_value}
            <input type="hidden" name="{$field_name|escape:'html':'UTF-8'}" value="{$field_value|escape:'html':'UTF-8'}">
        {/foreach}
    </form>

    {* Auto-submit with JavaScript *}
    <script>
        // Auto-submit the form immediately
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('maksuturva-payment-form').submit();
        });
    </script>

    {* Fallback for users without JavaScript *}
    <noscript>
        <div class="maksuturva-redirect-manual">
            <p>{l s='JavaScript is disabled. Please click the button below to continue to payment.' mod='maksuturva'}</p>
            <button type="submit" form="maksuturva-payment-form">
                {l s='Continue to Payment' mod='maksuturva'}
            </button>
        </div>
    </noscript>
</div>
{/block}
