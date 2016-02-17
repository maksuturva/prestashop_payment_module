{*
* 2016 Maksuturva Group Oy
*
* NOTICE OF LICENSE
*
* This source file is subject to the GNU Lesser General Public License (LGPLv2.1)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
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
* @license   http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
*}

{if $status == 'ok'}
	<p class="alert alert-success">{l s='Your order on %s is complete.' sprintf=$shop_name mod='maksuturva'}</p>
	<div class="box order-confirmation">
		<img src="{$this_path|escape:'html':'UTF-8'}/views/img/maksuturva.gif" width="115" height="29" />
		<p><strong>{l s='Your order will be shipped as soon as possible.' mod='maksuturva'}</strong></p>
		<p>
			{l s='For any questions or for further information, please contact our' mod='maksuturva'}
			<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
		</p>
	</div>
{elseif $status == 'pending'}
	<p class="alert alert-info">{l s='Your order on %s is pending.' sprintf=$shop_name mod='maksuturva'}</p>
	<div class="box order-confirmation">
		<img src="{$this_path|escape:'html':'UTF-8'}/views/img/maksuturva.gif" width="115" height="29" />
		<p><strong>{l s='Your order will be shipped as soon as we receive the payment confirmation.' mod='maksuturva'}</strong></p>
		<p>
			{l s='For any questions or for further information, please contact our' mod='maksuturva'}
			<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
		</p>
	</div>
{elseif $status == 'cancel'}
	<p class="alert alert-warning">{l s='Your order on %s has been canceled.' sprintf=$shop_name mod='maksuturva'}</p>
	<div class="box order-confirmation">
		<img src="{$this_path|escape:'html':'UTF-8'}/views/img/maksuturva.gif" width="115" height="29" />
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
