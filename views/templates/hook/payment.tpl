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

<p class="payment_module">
    <a href="{$this_path_ssl|escape:'html':'UTF-8'}payment.php"
       title="{l s='Pay with Maksuturva' mod='maksuturva'}">
        <img src="{$this_path|escape:'html':'UTF-8'}/views/img/maksuturva.gif"
             alt="{l s='Pay with Maksuturva' mod='maksuturva'}"
             width="115"
             height="29"/>
        {l s='Pay with Maksuturva' mod='maksuturva'}
    </a>
</p>
