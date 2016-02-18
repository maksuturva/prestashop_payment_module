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

{if $status != "error"}
	<img src="{$this_path|escape:'html':'UTF-8'}/views/img/maksuturva.gif" width="115" height="29" />
	<br /><br />
{/if}

{if $status == 'ok'}
	<p>
		{l s='Your order on' mod='maksuturva'}
		<strong>{$shop_name|escape:'html':'UTF-8'}</strong> {l s='is complete.' mod='maksuturva'}
		<br /><br />
		<strong>{l s='Your order will be shipped as soon as possible.' mod='maksuturva'}</strong>
		<br /><br />
		{l s='For any questions or for further information, please contact our' mod='maksuturva'}
		<a href="{$link->getPageLink('contact-form.php', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
	</p>
{elseif $status == 'pending'}
	<p>
		{l s='Your order on' mod='maksuturva'}
		<strong>{$shop_name|escape:'html':'UTF-8'}</strong> {l s='is pending.' mod='maksuturva'}
		<br /><br />
		<strong>{l s='Your order will be shipped as soon as we receive the payment confirmation.' mod='maksuturva'}</strong>
		<br /><br />
		{l s='For any questions or for further information, please contact our' mod='maksuturva'}
		<a href="{$link->getPageLink('contact-form.php', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
	</p>
{elseif $status == 'cancel'}
    <p>
		{l s='Your order on' mod='maksuturva'}
		<strong>{$shop_name|escape:'html':'UTF-8'}</strong> {l s='has been canceled.' mod='maksuturva'}
        <br /><br />
		{l s='For any questions or for further information, please contact our' mod='maksuturva'}
		<a href="{$link->getPageLink('contact-form.php', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
    </p>
{else}
	<p>{l s='An error occurred while processing the payment.' mod='maksuturva'}</p>
	{if !empty($message)}
		<p><strong>{$message|escape:'html':'UTF-8'}</strong></p>
	{/if}
	<p>
		{l s='For further questions you can contact our' mod='maksuturva'}
		<a href="{$link->getPageLink('contact-form.php', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='maksuturva'}</a>.
	</p>
{/if}
