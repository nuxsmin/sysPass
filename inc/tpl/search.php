<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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

$isAdmin = ($_SESSION["uisadminapp"] || $_SESSION["uisadminacc"]);
$globalSearch = SP_Config::getValue('globalsearch',0);
$chkGlobalSearch = SP_Common::parseParams('s', 'accountGlobalSearch', 0);
$searchStart = SP_Common::parseParams('s', 'accountSearchStart', 0);
$searchKey = SP_Common::parseParams('s', 'accountSearchKey', 0);
$searchOrder = SP_Common::parseParams('s', 'accountSearchOrder', 0);
?>
<form method="post" name="frmSearch" id="frmSearch" OnSubmit="return accSearch(0);">
    <table id="tblTools" class="round shadow">
        <tr>
            <td id="toolsLeft">
                <img src="imgs/clear.png" title="<?php echo _('Limpiar'); ?>" class="inputImg" id="btnClear" onClick="Clear('frmSearch', 1); accSearch(0);" />
                <input type="text" name="search" id="txtSearch" onKeyUp="accSearch(1,event)" value="<?php echo SP_Common::parseParams('s', 'accountSearchTxt'); ?>" placeholder="<?php echo _('Texto a buscar'); ?>"/>
                <?php if ( $globalSearch && ! $isAdmin ): ?>
                <input type="checkbox" name="gsearch" id="gsearch" class="checkbox" <?php echo ($chkGlobalSearch) ? 'checked="checked"' : ''; ?>/>
                <label for="gsearch" title="<?php echo _('Búsqueda global');?>"><?php echo ($chkGlobalSearch) ? 'ON' : 'OFF'; ?></label>
                <?php endif; ?>                
                <input type="hidden" name="start" value="<?php echo $searchStart; ?>">
                <input type="hidden" name="skey" value="<?php echo $searchKey; ?>" />
                <input type="hidden" name="sorder" value="<?php echo $searchOrder; ?>" />
                <input type="hidden" name="sk" value="<?php echo SP_Common::getSessionKey(true); ?>">
                <input type="hidden" name="isAjax" value="1">
                <?php
                SP_Html::printSelect(DB::getValuesForSelect('customers', 'customer_id', 'customer_name'), $customersSelProp);
                SP_Html::printSelect(DB::getValuesForSelect('categories', 'category_id', 'category_name'), $categoriesSelProp);
                ?>
                <br>
            </td>
            <td id="toolsRight">
                <input type="text" name="rpp" id="rpp" placeholder="<?php echo _('CPP'); ?>" title="<?php echo _('Cuentas por página'); ?>" value="<?php echo SP_Common::parseParams('s', 'accountSearchLimit', SP_Config::getValue('account_count')); ?>"/>
            </td>
        </tr>
    </table>
</form>
<script>
    accSearch(0);
    mkChosen({id: 'selCustomer', placeholder: '<?php echo _('Seleccionar Cliente'); ?>', noresults: '<?php echo _('Sin resultados'); ?>' });
    mkChosen({id: 'selCategory', placeholder: '<?php echo _('Seleccionar Categoría'); ?>', noresults: '<?php echo _('Sin resultados'); ?>' });
    
    $("#rpp").spinner({step: 3, max: 50, min: 6, numberFormat: "n", stop: function(event, ui) {
            accSearch(0);
        }});
    <?php if ( $globalSearch ): ?>
    $('#tblTools').find('.checkbox').button();
    $('#gsearch').click(function(){
        if ( $(this).next('label').hasClass('ui-state-active') ){
            $(this).next('label').children('span').html('OFF');
        } else{
            $(this).next('label').children('span').html('ON');
        }
        accSearch(0);
    });
    <?php endif; ?>
    $('input:text:visible:first').focus();
</script>

<div id="resBuscar"></div>