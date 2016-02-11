{*
* 2016 Maksuturva Group Oy
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
* @copyright 2016 Maksuturva Group Oy
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

{if $status != "error"}
	<img src="{$this_path}/views/img/maksuturva.gif" width="115" height="29" style="float: right;"/>
{/if}
{if $status == 'ok'}
	<p>{l s='Your order on' mod='maksuturva' mod='maksuturva'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='maksuturva'}
		<br /><br /><span class="bold">{l s='Your order will be shipped as soon as possible.' mod='maksuturva'}</span>
		<br /><br />{l s='For any questions or for further information, please contact our' mod='maksuturva'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='maksuturva'}</a>.
	</p>
{elseif $status == 'pending'}
	<p>{l s='Your order on' mod='maksuturva'} <span class="bold">{$shop_name}</span> {l s='is pending.' mod='maksuturva'}
		<br /><br /><span class="bold">{l s='Your order will be shipped as soon as we receive the payment confirmation.' mod='maksuturva'}</span>
		<br /><br />{l s='For any questions or for further information, please contact our' mod='maksuturva'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='maksuturva'}</a>.
	</p>
{elseif $status == 'cancel'}
    <p>{l s='Your order on' mod='maksuturva'} <span class="bold">{$shop_name}</span> {l s='has been canceled.' mod='maksuturva'}
        <br /><br />{l s='For any questions or for further information, please contact our' mod='maksuturva'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='maksuturva'}</a>.
    </p>
{else}
	<p class="warning">{l s='An error occurred while processing the payment.' mod='maksuturva'}</p>
	<p class="warning" style="font-weight: bold;">{$message}</p>
	<p class="warning">
		{l s='For further questions you can contact our' mod='maksuturva'}
		<a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='maksuturva'}</a>.
	</p>
{/if}