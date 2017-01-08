<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

/** @var  \SP\DataModel\NoticeData $notice */
/** @var  \SP\Core\UI\ThemeIconsBase $icons */
?>
<div id="box-popup">
    <h2 class="center"><?php echo $header; ?></h2>

    <form method="post" name="frmNotices" id="frmNotices" class="form-action"
          data-onsubmit="appMgmt/save"
          data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
          data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_NOT_USER; ?>">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Tipo'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="type" name="type" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $notice->getNoticeType(); ?>">
                        <label class="mdl-textfield__label"
                               for="type"><?php echo _('Tipo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Componente'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="component" name="component" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $notice->getNoticeComponent(); ?>">
                        <label class="mdl-textfield__label"
                               for="component"><?php echo _('Componente'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Fecha'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="date" name="date" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $notice->getNoticeDate(); ?>">
                        <label class="mdl-textfield__label"
                               for="date"><?php echo _('Fecha'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Descripción'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield">
                        <textarea class="mdl-textfield__input mdl-color-text--indigo-400" type="text" rows="3"
                                  id="description"
                                  name="description" <?php echo $isReadonly; ?>><?php echo $notice->getNoticeDescription(); ?></textarea>
                        <label class="mdl-textfield__label" for="description"><?php echo _('Descripción'); ?></label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <input type="hidden" name="itemId" value="<?php echo $notice->getNoticeId(); ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <?php if (!$isView): ?>
        <div class="action-in-box">
            <button
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    form="frmCategories" title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
<script>
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>