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

<div class="panel card">
    <div class="panel-heading card-header">
        <img src="{$this_path|escape:'html':'UTF-8'}/logo.png" width="20" height="20"/>
        {l s='Svea Payments' mod='maksuturva'}
    </div>
    <div class="{$mt_pmt_class}">
        <div class="alert alert-info">
            <strong>{l s='Payment ID' mod='maksuturva'}:</strong> {$mt_pmt_id|escape:'html':'UTF-8'}<br>
            <strong>{l s='Status' mod='maksuturva'}:</strong> {$mt_pmt_status_message|escape:'html':'UTF-8'}
        </div>

        {if !empty($mt_pmt_surcharge_message)}
            <div class="alert alert-warning">
                {$mt_pmt_surcharge_message|escape:'html':'UTF-8'}
            </div>
        {/if}

        {if isset($mt_payment_attempts)}
            <h4>{l s='Payment Attempts' mod='maksuturva'}</h4>
            <p class="text-muted">{l s='This order had multiple payment attempts:' mod='maksuturva'}</p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{l s='Attempt' mod='maksuturva'}</th>
                            <th>{l s='Payment ID' mod='maksuturva'}</th>
                            <th>{l s='Status' mod='maksuturva'}</th>
                            <th>{l s='Date' mod='maksuturva'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$mt_payment_attempts item=attempt}
                            <tr>
                                <td>#{$attempt.attempt|escape:'html':'UTF-8'}</td>
                                <td>{$attempt.pmt_id|escape:'html':'UTF-8'}</td>
                                <td>{$attempt.status|escape:'html':'UTF-8'}</td>
                                <td>{$attempt.date|escape:'html':'UTF-8'}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
    </div>
</div>
