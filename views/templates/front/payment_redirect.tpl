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
<!DOCTYPE html>
<html lang="{$language_iso|escape:'html':'UTF-8'}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{l s='Redirecting to payment...' mod='maksuturva'} - {$shop_name|escape:'html':'UTF-8'}</title>
    <style nonce="{$csp_nonce|escape:'html':'UTF-8'}">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .redirect-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .spinner {
            width: 60px;
            height: 60px;
            margin: 0 auto 30px;
            border: 4px solid #e0e0e0;
            border-top: 4px solid #00598c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .logo {
            margin-bottom: 30px;
        }
        .logo-text {
            font-size: 32px;
            font-weight: bold;
            color: #00598c;
            letter-spacing: 2px;
        }
        h1 {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .details {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .amount {
            font-weight: 600;
            color: #333;
        }
        .manual-submit {
            margin-top: 40px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            font-size: 16px;
            background-color: #00598c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: #004570;
        }
        .manual-submit p {
            margin-bottom: 15px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="redirect-container">
        <div class="spinner"></div>

        <div class="logo">
            <div class="logo-text">SVEA</div>
        </div>

        <h1>{l s='Redirecting to Maksuturva payment gateway...' mod='maksuturva'}</h1>

        <div class="details">
            {l s='Please wait while we redirect you to complete your payment.' mod='maksuturva'}<br>
            {l s='Total amount:' mod='maksuturva'} <span class="amount">{$cart_total|escape:'html':'UTF-8'}</span>
        </div>

        <form method="POST" action="{$gateway_url|escape:'html':'UTF-8'}" id="payment-form">
            {foreach from=$gateway_fields key=field_name item=field_value}
                <input type="hidden" name="{$field_name|escape:'html':'UTF-8'}" value="{$field_value|escape:'html':'UTF-8'}">
            {/foreach}
        </form>

        <script nonce="{$csp_nonce|escape:'html':'UTF-8'}">
            document.getElementById('payment-form').submit();
        </script>

        <noscript>
            <div class="manual-submit">
                <p>{l s='JavaScript is disabled. Please click the button below to continue to payment.' mod='maksuturva'}</p>
                <button type="submit" form="payment-form" class="btn">
                    {l s='Continue to Payment' mod='maksuturva'}
                </button>
            </div>
        </noscript>
    </div>
</body>
</html>
