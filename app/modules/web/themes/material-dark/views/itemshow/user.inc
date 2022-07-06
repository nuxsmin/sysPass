<?php
/**
 * @var UserData   $user
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\UserData;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;

$user = $_getvar('user');
?>

<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
        <div class="mdl-tabs__tab-bar">
            <a href="#user-panel"
               class="mdl-tabs__tab is-active"><?php echo __('User'); ?></a>
            <a href="#options-panel"
               class="mdl-tabs__tab"><?php echo __('Options'); ?></a>

            <?php if ($_getvar('isView')): ?>
                <a href="#details-panel"
                   class="mdl-tabs__tab"><?php echo __('Details'); ?></a>
            <?php endif; ?>
        </div>

        <form method="post" name="frmUsers" id="frmUsers" class="form-action"
              data-onsubmit="appMgmt/save"
              data-action-route="<?php echo $_getvar('route', ''); ?>"
              data-hash="">

            <div class="mdl-tabs__panel is-active" id="user-panel">
                <table class="popup-data">
                    <tbody>
                    <tr>
                        <td class="descField"><?php echo __('Name') ?></td>
                        <td class="valField">

                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="name" name="name" type="text"
                                       required
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       value="<?php echo $user->getName(); ?>"
                                       maxlength="80" <?php echo $_getvar('readonly'); ?>>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('Full username'); ?></label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Login'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="login" name="login" type="text"
                                       required
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       value="<?php echo $user->getLogin(); ?>"
                                       maxlength="80" <?php echo $user->isLdap() ? 'readonly' : $_getvar('readonly'); ?>>
                                <label class="mdl-textfield__label"
                                       for="login"><?php echo __('Session login'); ?></label>
                            </div>
                            <?php if ($user->isLdap()): ?>
                                <i class="material-icons <?php echo $icons->getIconLdapUser()->getClass(); ?>"
                                   title="<?php echo $icons->getIconLdapUser()->getTitle(); ?>"><?php echo $icons->getIconLdapUser()->getIcon(); ?></i>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if ($_getvar('isUseSSO')): ?>
                        <tr>
                            <td class="descField"><?php echo __('SSO Login '); ?></td>
                            <td class="valField">
                                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                    <input id="login_sso" name="login_sso"
                                           type="text"
                                           class="mdl-textfield__input mdl-color-text--indigo-400"
                                           value="<?php echo $user->getSsoLogin(); ?>"
                                           maxlength="100" <?php echo $_getvar('readonly'); ?>>
                                    <label class="mdl-textfield__label"
                                           for="login_sso"><?php echo __('Session login with SSO'); ?></label>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="descField"><?php echo __('Email'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="email" name="email" type="email"
                                       required
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       value="<?php echo $user->getEmail(); ?>"
                                       maxlength="50" <?php echo $_getvar('readonly'); ?>>
                                <label class="mdl-textfield__label"
                                       for="email"><?php echo __('Email address'); ?></label>
                            </div
                        </td>

                    </tr>

                    <?php if ($_getvar('createAction')): ?>
                        <tr>
                            <td class="descField"><?php echo __('Password'); ?></td>
                            <td class="valField">
                                <div class="form-control">
                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                        <input id="password" name="password"
                                               type="password" required
                                               class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                                               maxlength="50">
                                        <label class="mdl-textfield__label"
                                               for="password"><?php echo __('Password'); ?></label>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Password (repeat)'); ?></td>
                            <td class="valField">
                                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                    <input id="password_repeat"
                                           name="password_repeat"
                                           type="password" required
                                           class="mdl-textfield__input mdl-color-text--indigo-400"
                                           maxlength="50">
                                    <label class="mdl-textfield__label"
                                           for="password_repeat"><?php echo __('Password (repeat)'); ?></label>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="descField"><?php echo __('Profile'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Profile'); ?></div>

                            <select id="userprofile_id" name="userprofile_id"
                                    class="select-box"
                                    required <?php echo $_getvar('disabled'); ?>>
                                <option value=""><?php echo __('Select Profile'); ?></option>
                                <?php /** @var SelectItem $profile */
                                foreach ($_getvar('profiles') as $profile): ?>
                                    <option
                                            value="<?php echo $profile->getId(); ?>" <?php echo ($profile->getId() === $user->getUserProfileId()) ? 'selected' : ''; ?>><?php echo $profile->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Group'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Group'); ?></div>

                            <select id="usergroup_id" name="usergroup_id"
                                    class="select-box"
                                    required <?php echo $_getvar('disabled'); ?>>
                                <option value=""><?php echo __('Select Group'); ?></option>
                                <?php /** @var SelectItem $group */
                                foreach ($_getvar('groups') as $group): ?>
                                    <option
                                            value="<?php echo $group->getId(); ?>" <?php echo ($group->getId() === $user->getUserGroupId()) ? 'selected' : ''; ?>><?php echo $group->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="descField"><?php echo __('Notes') ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea class="mdl-textfield__input" rows="3"
                                  id="notes" name="notes"
                                  maxlength="1000" <?php echo $_getvar('readonly'); ?>><?php echo htmlspecialchars($user->getNotes(), ENT_QUOTES); ?></textarea>
                                <label class="mdl-textfield__label"
                                       for="notes"><?php echo __('Notes'); ?></label>
                            </div>
                        </td>
                    </tr>

                    <?php if ($_getvar('customFields')): ?>
                        <?php include $this->includeTemplate('aux-customfields', 'common'); ?>
                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <div class="mdl-tabs__panel" id="options-panel">
                <table class="popup-data">
                    <tbody>
                    <tr>
                        <td>
                            <ul class="user-list-action mdl-list">
                                <?php if ($_getvar('ctx_userIsAdminApp') || $_getvar('isDemo')): ?>
                                    <li class="mdl-list__item mdl-list__item--two-line">
                                        <div class="mdl-switch__box">
                                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                                   for="adminapp_enabled">
                                                <input type="checkbox"
                                                       id="adminapp_enabled"
                                                       class="mdl-switch__input"
                                                       name="adminapp_enabled" <?php echo $user->isAdminApp() ? 'checked' : ' '; ?> <?php echo $_getvar('disabled'); ?>/>
                                            </label>
                                        </div>

                                        <span class="mdl-list__item-primary-content">
                                            <span><?php echo __('Application Admin'); ?></span>
                                            <span class="mdl-list__item-sub-title">
                                                <?php echo __('Application administrator'); ?>
                                            </span>
                                        </span>
                                    </li>

                                    <li class="mdl-list__item mdl-list__item--two-line">
                                        <div class="mdl-switch__box">
                                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                                   for="adminacc_enabled">
                                                <input type="checkbox"
                                                       id="adminacc_enabled"
                                                       class="mdl-switch__input"
                                                       name="adminacc_enabled" <?php echo $user->isAdminAcc() ? 'checked' : ' '; ?> <?php echo $_getvar('disabled'); ?>/>
                                            </label>
                                        </div>

                                        <span class="mdl-list__item-primary-content">
                                            <span><?php echo __('Accounts Admin'); ?></span>
                                            <span class="mdl-list__item-sub-title">
                                                <?php echo __('Accounts administrator'); ?>
                                            </span>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <?php if ($_getvar('mailEnabled')): ?>
                                    <li class="mdl-list__item mdl-list__item--two-line">
                                        <div class="mdl-switch__box">
                                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                                   for="changepass_enabled">
                                                <input type="checkbox"
                                                       id="changepass_enabled"
                                                       class="mdl-switch__input"
                                                       name="changepass_enabled" <?php echo $user->isChangePass() ? 'checked' : ' '; ?> <?php echo $_getvar('disabled'); ?>/>
                                            </label>
                                        </div>

                                        <span class="mdl-list__item-primary-content">
                                            <span><?php echo __('Password Change'); ?></span>
                                            <span class="mdl-list__item-sub-title">
                                                <?php echo __('Force password change'); ?>
                                            </span>
                                        </span>
                                    </li>
                                <?php endif; ?>

                                <li class="mdl-list__item mdl-list__item--two-line">
                                    <div class="mdl-switch__box">
                                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                               for="disabled">
                                            <input type="checkbox" id="disabled"
                                                   class="mdl-switch__input"
                                                   name="disabled" <?php echo $user->isDisabled() ? 'checked' : ' '; ?> <?php echo $_getvar('disabled'); ?>/>
                                        </label>
                                    </div>

                                    <span class="mdl-list__item-primary-content">
                                        <span><?php echo __('Disabled'); ?></span>
                                        <span class="mdl-list__item-sub-title"></span>
                                    </span>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <?php if ($_getvar('isView')): ?>
                <div class="mdl-tabs__panel" id="details-panel">
                    <table class="popup-data">
                        <tbody>
                        <tr>
                            <td class="descField"><?php echo __('Entries'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Entries'); ?></div>

                                <?php echo $user->getLoginCount(); ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Last Access'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Last Access'); ?></div>

                                <?php echo $user->getLastLogin(); ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Last Modification'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Last Modification'); ?></div>

                                <?php echo $user->getLastUpdate(); ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Master Password Date'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Master Password Date'); ?></div>

                                <?php echo gmdate('Y-m-d H:i:s', $user->getLastUpdateMPass()); ?>
                            </td>
                        </tr>

                        <tr>
                            <td class="descField"><?php echo __('Used in'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Used in'); ?></div>
                                <div class="list-wrap">
                                    <ul class="mdl-list">
                                        <?php foreach ($_getvar('usage') as $item): ?>
                                            <li class="mdl-list__item mdl-list__item"
                                                title="<?php echo $item->ref; ?>">
                                                <span class="mdl-list__item-primary-content">
                                                <i class="material-icons mdl-list__item-icon"><?php echo $item->icon; ?></i>
                                                    <?php printf('%s: %s', $item->ref, $item->name ?: $item->id); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>

                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <input type="hidden" name="isLdap"
                   value="<?php echo $user->isLdap(); ?>"/>
            <input type="hidden" name="isAjax" value="1">
        </form>
    </div>

    <?php if (!$_getvar('isView')): ?>
        <div class="action-in-box">
            <button form="frmUsers"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
