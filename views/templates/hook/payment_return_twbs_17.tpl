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

{if $status == 'ok'}
    <p class="alert alert-success">{l s='Your order has been paid.' mod='maksuturva'}</p>
{elseif $status == 'pending'}
    <p class="alert alert-warning">{l s='NOTE: Your order is pending payment confirmation from Svea. Payment status will be updated automatically when Svea verifies the payment.' mod='maksuturva'}</p>

{* cancel actually never comes here, it goes to error.tpl *}
{elseif $status == 'cancel'}
    <p class="alert alert-warning">{l s='You have cancelled the payment.' mod='maksuturva'}</p>

{* this should never happen *}
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
