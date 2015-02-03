<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

$category = SP_Category::getCategoryData($data['itemid']);
$onCloseAction = $data['onCloseAction'];
$activeTab = $data['activeTab'];
?>

<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $data['header']; ?></H2>

    <form method="post" name="frmCategories" id="frmCategories">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <input type="text" id="grpname" name="name" title="<?php echo _('Nombre de la categoría'); ?>"
                           value="<?php echo $category["category_name"] ?>"/>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Descripción'); ?></td>
                <td class="valField"><input type="text" id="grpdesc" name="description"
                                            title="<?php echo _('Descripción de la categoría'); ?>"
                                            value="<?php echo $category["category_description"]; ?>"/>
                </td>
            </tr>
            </tbody>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
        <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
        <input type="hidden" name="id" value="<?php echo $category["category_id"]; ?>"/>
        <input type="hidden" name="action" value="<?php echo $category["action"] ?>"/>
        <input type="hidden" name="type" value="<?php echo $data['itemtype']; ?>"/>
        <input type="hidden" name="sk" value="<?php echo SP_Common::getSessionKey(true) ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <ul>
            <li><img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg"
                     OnClick="appMgmtSave('frmCategories');"/></li>
        </ul>
    </div>
</div>