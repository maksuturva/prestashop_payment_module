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

<div class="panel">
    <div class="panel-heading">
        <img src="{$this_path|escape:'html':'UTF-8'}/logo.png" width="20" height="20"/>
        {l s='Maksuturva' mod='maksuturva'}
    </div>
    <div class="row">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><span class="title_box ">{l s='Payment ID' mod='maksuturva'}</span></th>
                        <th><span class="title_box ">{l s='Status' mod='maksuturva'}</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{$mt_pmt_id|escape:'html':'UTF-8'}</td>
                        <td>{$mt_pmt_status_message|escape:'html':'UTF-8'}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        {if !empty($mt_pmt_surcharge_message)}
            <br /><br />
            <div class="alert alert-warning">
                {$mt_pmt_surcharge_message|escape:'html':'UTF-8'}
            </div>
        {/if}
    </div>
</div>
