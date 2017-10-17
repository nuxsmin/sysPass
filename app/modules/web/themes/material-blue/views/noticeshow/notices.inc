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
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmNotices" id="frmNotices" class="form-action"
          data-onsubmit="appMgmt/save"
          data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
          data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_NOT_USER; ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Tipo'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="notice_type" name="notice_type" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $notice->getNoticeType(); ?>" <?php echo $isDisabled; ?>>
                        <label class="mdl-textfield__label"
                               for="notice_type"><?php echo __('Tipo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Componente'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="notice_component" name="notice_component" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $notice->getNoticeComponent(); ?>" <?php echo $isDisabled; ?>>
                        <label class="mdl-textfield__label"
                               for="notice_component"><?php echo __('Componente'); ?></label>
                    </div>
                </td>
            </tr>
            <?php if ($isView): ?>
            <tr>
                <td class="descField"><?php echo __('Fecha'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="notice_date" name="notice_date" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $notice->getNoticeDate(); ?>" <?php echo $isDisabled; ?>>
                        <label class="mdl-textfield__label"
                               for="notice_date"><?php echo __('Fecha'); ?></label>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="descField"><?php echo __('Descripción'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield">
                        <textarea class="mdl-textfield__input mdl-color-text--indigo-400" type="text" rows="3"
                                  id="notice_description"
                                  name="notice_description" <?php echo $isReadonly; ?>><?php echo $notice->getNoticeDescription(); ?></textarea>
                        <label class="mdl-textfield__label" for="notice_description"><?php echo __('Descripción'); ?></label>
                    </div>
                </td>
            </tr>

            <?php if (!$isView && ($SessionUserData->isUserIsAdminApp() || $isDemo)): ?>
                <tr>
                    <td class="descField"><?php echo __('Usuario'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Usuario'); ?></div>

                        <select id="notice_user" name="notice_user"
                                class="select-box select-box-deselect" <?php echo $isDisabled; ?>>
                            <option value=""><?php echo __('Seleccionar Usuario'); ?></option>
                            <?php foreach ($users as $user): ?>
                                <option
                                        value="<?php echo $user->id; ?>" <?php echo ($user->id == $notice->getNoticeUserId()) ? 'selected' : ''; ?>><?php echo $user->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Opciones'); ?></td>
                    <td class="valField checkbox">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="notice_sticky"
                               title="<?php echo __('Notificación global'); ?>">
                            <input type="checkbox" id="notice_sticky" class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="notice_sticky" <?php echo $notice->isNoticeSticky() ? 'checked' : ' '; ?> <?php echo $isDisabled; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Global'); ?></span>
                        </label>

<!--                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="notice_onlyadmin"
                               title="<?php /*echo __('Sólo para administradores de la aplicación'); */?>">
                            <input type="checkbox" id="notice_onlyadmin" class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="notice_onlyadmin" <?php /*echo $notice->isNoticeOnlyAdmin() ? 'checked' : ' '; */?> <?php /*echo $isDisabled; */?>/>
                            <span class="mdl-switch__label"><?php /*echo __('Solo Admins'); */?></span>
                        </label>-->
                    </td>
                </tr>
            <?php endif; ?>

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
                    form="frmNotices" title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
<script>
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>