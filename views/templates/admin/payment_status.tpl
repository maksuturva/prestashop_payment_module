{*
* 2016 Maksuturva Group Oy
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
* @copyright 2016 Maksuturva Group Oy
* @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
*}

<br />
<fieldset {if $ps_version == '1.4'}style="width: 400px;"{/if}>
    <legend>
        <img src="{$this_path|escape:'html':'UTF-8'}/logo.png" width="20" height="20"/>
        {l s='Maksuturva' mod='maksuturva'}
    </legend>
    <table class="table" width="100%;" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th>{l s='Payment ID' mod='maksuturva'}</th>
                <th>{l s='Status' mod='maksuturva'}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{$mt_pmt_id|escape:'html':'UTF-8'}</td>
                <td>{$mt_pmt_status_message|escape:'html':'UTF-8'}</td>
            </tr>
        </tbody>
    </table>
    {if !empty($mt_pmt_surcharge_message)}
        <br /><br />
        <div class="alert warning">
            {$mt_pmt_surcharge_message|escape:'html':'UTF-8'}
        </div>
    {/if}
</fieldset>
