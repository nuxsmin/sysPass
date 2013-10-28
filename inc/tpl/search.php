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
    "class" => "",
    "size" => 1,
    "label" => "",
    "selected" => SP_Account::$accountSearchCustomer,
    "default" => "",
    "js" => 'OnChange="accSearch(0)"',
    "attribs" => "");

$categoriesSelProp = array("name" => "category",
    "id" => "selCategory",
    "class" => "",
    "size" => 1,
    "label" => "",
    "selected" => SP_Account::$accountSearchCategory,
    "default" => "",
    "js" => 'OnChange="accSearch(0)"',
    "attribs" => "");
?>
<form method="post" name="frmSearch" id="frmSearch" OnSubmit="return accSearch(0);">
    <table id="tblTools" class="round shadow">
        <tr>
            <td id="toolsLeft">
                <label FOR="txtSearch"></label>
                <input type="text" name="search" id="txtSearch" onKeyUp="accSearch(1)" value="<? echo SP_Account::$accountSearchTxt; ?>" placeholder="<? echo _('Texto a buscar'); ?>"/>
                <img src="imgs/clear.png" title="<? echo _('Limpiar'); ?>" class="inputImg" id="btnLimpiar" onClick="Clear('frmSearch', 1); accSearch(0);" />
                <!--<img src="imgs/search.png" title="<? echo _('Buscar'); ?>" class="inputImg" id="btnBuscar" onClick="accSearch(0);" />-->
                <input type="hidden" name="start" value="0">
                <input type="hidden" name="skey" value="<? echo SP_Account::$accountSearchKey; ?>" />
                <input type="hidden" name="sorder" value="<? echo SP_Account::$accountSearchOrder; ?>" />
                <input type="hidden" name="sk" value="<? echo SP_Common::getSessionKey(TRUE); ?>">
                <input type="hidden" name="is_ajax" value="1">
                <?
                SP_Html::printSelect(SP_Customer::getCustomers(), $customersSelProp);
                SP_Html::printSelect(SP_Category::getCategories(), $categoriesSelProp);
                ?>
            </td>
            <td id="toolsRight">
                <input type="text" name="rpp" id="rpp" placeholder="<? echo _('CPP'); ?>" title="<? echo _('Cuentas por página'); ?>"/>
                
                
            </td>
        </tr>
    </table>
</form>
<script>
    accSearch(0);
    //$("#selCategory").combobox({dosearch: 1, placeholder: "<? //echo _('Seleccionar Categoría'); ?>"});
    //$("#selCustomer").combobox({dosearch: 1, placeholder: "<? //echo _('Seleccionar Cliente'); ?>"});
    $("#selCustomer").chosen({
        allow_single_deselect: true,
        placeholder_text_single: "<? echo _('Seleccionar Cliente'); ?>", 
        disable_search_threshold: 10,
        no_results_text: "<? echo _('Sin resultados'); ?>"});
    $("#selCategory").chosen({
        allow_single_deselect: true,
        placeholder_text_single: "<? echo _('Seleccionar Categoría'); ?>", 
        disable_search_threshold: 10,
        no_results_text: "<? echo _('Sin resultados'); ?>"});
    $("#rpp").spinner({step: 5, max: 50, min: 5, numberFormat: "n", stop: function(event, ui) {
            accSearch(0);
        }});
</script>

<div id="resBuscar"></div>