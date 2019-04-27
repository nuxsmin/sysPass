<?php
/**
 * @var  NotificationData $notification
 * @var ThemeIcons        $icons
 * @var ConfigData        $configData
 * @var callable          $_getvar
 * @var Template          $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\NotificationData;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;
use SP\Util\DateUtil;

$notification = $_getvar('notification');
?>
<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmNotices" id="frmNotices" class="form-action"
          data-onsubmit="notification/save"
          data-route="<?php echo $_getvar('route', ''); ?>"
          data-action-next="<?php echo $_getvar('nextAction'); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Type'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="notification_type" name="notification_type"
                               type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $notification->getType(); ?>" <?php echo $_getvar('disabled'); ?>>
                        <label class="mdl-textfield__label"
                               for="notification_type"><?php echo __('Type'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Component'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="notification_component"
                               name="notification_component" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $notification->getComponent(); ?>" <?php echo $_getvar('disabled'); ?>>
                        <label class="mdl-textfield__label"
                               for="notification_component"><?php echo __('Component'); ?></label>
                    </div>
                </td>
            </tr>
            <?php if ($_getvar('isView')): ?>
                <tr>
                    <td class="descField"><?php echo __('Date'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="notification_date"
                                   name="notification_date" type="text"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   value="<?php echo DateUtil::getDateFromUnix($notification->getDate()); ?>" <?php echo $_getvar('disabled'); ?>>
                            <label class="mdl-textfield__label"
                                   for="notification_date"><?php echo __('Date'); ?></label>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="descField"><?php echo __('Description'); ?></td>
                <td class="valField">
                    <?php if ($_getvar('isView') === false): ?>
                        <div class="mdl-textfield mdl-js-textfield">
                            <textarea
                                    class="mdl-textfield__input mdl-color-text--indigo-400"
                                    type="text" rows="3"
                                    id="notification_description"
                                    name="notification_description" <?php echo $_getvar('readonly'); ?>><?php echo $notification->getDescription(); ?></textarea>
                            <label class="mdl-textfield__label"
                                   for="notification_description"><?php echo __('Description'); ?></label>
                        </div>
                    <?php else: ?>
                        <div class="notification-description">
                            <?php echo $notification->getDescription(); ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>

            <?php if (!$_getvar('isView') && $_getvar('ctx_userIsAdminApp')): ?>
                <tr>
                    <td class="descField"><?php echo __('User'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('User'); ?></div>

                        <select id="notification_user" name="notification_user"
                                class="select-box select-box-deselect" <?php echo $_getvar('disabled'); ?>>
                            <option value=""><?php echo __('Select User'); ?></option>
                            <?php /** @var SelectItem $user */
                            foreach ($_getvar('users') as $user): ?>
                                <option
                                        value="<?php echo $user->getId(); ?>" <?php echo $user->isSelected() ? 'selected' : ''; ?>><?php echo $user->getName(); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td class="descField"><?php echo __('Options'); ?></td>
                    <td class="valField checkbox">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="notification_sticky"
                               title="<?php echo __('Global notification'); ?>">
                            <input type="checkbox" id="notification_sticky"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="notification_sticky" <?php echo $notification->isSticky() ? 'checked' : ' '; ?> <?php echo $_getvar('disabled'); ?>/>
                            <span class="mdl-switch__label"><?php echo __('Global'); ?></span>
                        </label>

                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="notification_onlyadmin"
                               title="<?php echo __('Only for application administrators'); ?>">
                            <input type="checkbox" id="notification_onlyadmin"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="notification_onlyadmin" <?php echo $notification->isOnlyAdmin() ? 'checked' : ' '; ?> <?php echo $_getvar('disabled'); ?>/>
                            <span class="mdl-switch__label"><?php echo __('Only Admins'); ?></span>
                        </label>

                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="notification_checkout"
                               title="<?php echo __('Read'); ?>">
                            <input type="checkbox" id="notification_checkout"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="notification_checkout" <?php echo $notification->isChecked() ? 'checked' : ' '; ?> <?php echo $_getvar('disabled'); ?>/>
                            <span class="mdl-switch__label"><?php echo __('Read'); ?></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>

            </tbody>
        </table>
    </form>
    <?php if (!$_getvar('isView')): ?>
        <div class="action-in-box">
            <button
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    form="frmNotices"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>