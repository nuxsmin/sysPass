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

$action = $data['action'];
$activeTab = $data['active'];

SP_Users::checkUserAccess($action) || SP_Html::showCommonError('unavailable');

$categoriesSelProp1 = array ( "name" => "categoryId",
                        "id" => "sel-edit_categories",
                        "class" => "",
                        "size" => 1,
                        "label" => "",
                        "selected" => "",
                        "default" => "",
                        "js" => "",
                        "attribs" => "");

$categoriesSelProp2 = array ( "name" => "categoryId",
                        "id" => "sel-del_categories",
                        "class" => "",
                        "size" => 1,
                        "label" => "",
                        "selected" => "",
                        "default" => "",
                        "js" => "",
                        "attribs" => "");

$skey = SP_Common::getSessionKey(TRUE);
?>
<table class="data tblConfig round">
    
        <tr>
            <td class="descField">
                <?php echo _('Nueva categoría'); ?>
            </td>
            <td class="valField">
                <form OnSubmit="return configMgmt('addcat');" method="post" name="frmAddCategory" id="frmAddCategory">
                    <input type="text" name="categoryName" maxlength="50">
                    <input type="image" src="imgs/add.png" title="<?php echo _('Nueva categoría'); ?>" class="inputImg" id="btnAdd" />
                    <input type="hidden" name="active" value="<?php echo $activeTab ?>" />
                    <input type="hidden" name="categoryFunction" value="1">
                    <input type="hidden" name="sk" value="<?php echo $skey; ?>">
                </form>
            </td>
        </tr>
    <tr>
        <td class="descField">
            <?php echo _('Modificar categoría'); ?>
        </td>
        <td  class="valField">
            <form OnSubmit="return configMgmt('editcat');" method="post" name="frmEditCategory" id="frmEditCategory">
                <?php SP_Html::printSelect(SP_Category::getCategories(), $categoriesSelProp1); ?>
                <br>
                <br>
                <input type="hidden" name="active" value="<?php echo $activeTab ?>" />
                <input type="text" name="categoryNameNew" maxlength="50" >
                <input type="hidden" name="categoryFunction" value="2">
                <input type="hidden" name="sk" value="<?php echo $skey; ?>">
                <input type="image" src="imgs/save.png" title="<?php echo _('Guardar'); ?>" class="inputImg" id="btnGuardar" />
            </form>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Borrar categoría'); ?>
        </td>
        <td  class="valField">
            <form OnSubmit="return configMgmt('delcat');" method="post" name="frmDelCategory" id="frmDelCategory">
                <?php SP_Html::printSelect(SP_Category::getCategories(), $categoriesSelProp2); ?>
                <input type="hidden" name="active" value="<?php echo $activeTab ?>" />
                <input type="hidden" name="categoryFunction" value="3">
                <input type="hidden" name="sk" value="<?php echo $skey; ?>">
                <input type="image" src="imgs/delete.png" title="<?php echo _('Borrar categoría'); ?>" class="inputImg" />
            </form>
        </td>
    </tr>
</table>

<script>
    $("#sel-edit_categories").chosen({
        placeholder_text_single: "<?php echo _('Seleccionar Categoría'); ?>", 
        disable_search_threshold: 10,
        no_results_text: "<?php echo _('Sin resultados'); ?>"});
    $("#sel-del_categories").chosen({
        placeholder_text_single: "<?php echo _('Seleccionar Categoría'); ?>", 
        disable_search_threshold: 10,
        no_results_text: "<?php echo _('Sin resultados'); ?>"});
</script>