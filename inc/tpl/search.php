<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
 *  
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

$customersSelProp = array("name" => "customer",
    "id" => "selCustomer",
    "class" => "select-box",
    "size" => 1,
    "label" => "",
    "selected" => SP_Common::parseParams('s', 'accountSearchCustomer', 0),
    "default" => "",
    "js" => 'OnChange="accSearch(0)"',
    "attribs" => "");

$categoriesSelProp = array("name" => "category",
    "id" => "selCategory",
    "class" => "select-box",
    "size" => 1,
    "label" => "",
    "selected" => SP_Common::parseParams('s', 'accountSearchCategory', 0),
    "default" => "",
    "js" => 'OnChange="accSearch(0)"',
    "attribs" => "");
?>
<form method="post" name="frmSearch" id="frmSearch" OnSubmit="return accSearch(0);">
    <table id="tblTools" class="round shadow">
        <tr>
            <td id="toolsLeft">
                <label FOR="txtSearch"></label>
                <input type="text" name="search" id="txtSearch" onKeyUp="accSearch(1)" value="<? echo SP_Common::parseParams('s', 'accountSearchTxt'); ?>" placeholder="<? echo _('Texto a buscar'); ?>"/>
                <img src="imgs/clear.png" title="<? echo _('Limpiar'); ?>" class="inputImg" id="btnLimpiar" onClick="Clear('frmSearch', 1); accSearch(0);" />
                <input type="hidden" name="start" value="<? echo SP_Common::parseParams('s', 'accountSearchStart', 0); ?>">
                <input type="hidden" name="skey" value="<? echo SP_Common::parseParams('s', 'accountSearchKey', 1); ?>" />
                <input type="hidden" name="sorder" value="<? echo SP_Common::parseParams('s', 'accountSearchOrder', 0); ?>" />
                <input type="hidden" name="sk" value="<? echo SP_Common::getSessionKey(TRUE); ?>">
                <input type="hidden" name="is_ajax" value="1">
                <?
                SP_Html::printSelect(SP_Customer::getCustomers(), $customersSelProp);
                SP_Html::printSelect(SP_Category::getCategories(), $categoriesSelProp);
                ?>
            </td>
            <td id="toolsRight">
                <input type="text" name="rpp" id="rpp" placeholder="<? echo _('CPP'); ?>" title="<? echo _('Cuentas por página'); ?>" value="<? echo SP_Common::parseParams('s', 'accountSearchLimit', SP_Config::getValue('account_count')); ?>"/>
            </td>
        </tr>
    </table>
</form>
<script>
    accSearch(0);
    mkChosen({id: 'selCustomer', placeholder: '<? echo _('Seleccionar Cliente'); ?>', noresults: '<? echo _('Sin resultados'); ?>' });
    mkChosen({id: 'selCategory', placeholder: '<? echo _('Seleccionar Categoría'); ?>', noresults: '<? echo _('Sin resultados'); ?>' });
    
    $("#rpp").spinner({step: 5, max: 50, min: 5, numberFormat: "n", stop: function(event, ui) {
            accSearch(0);
        }});
    $('input:text:visible:first').focus();
</script>

<div id="resBuscar"></div>