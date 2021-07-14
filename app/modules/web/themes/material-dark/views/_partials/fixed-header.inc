<?php
/**
 * @var callable $_getvar
 * @var Template $this
 */

use SP\Html\Assets\IconInterface;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Mvc\View\Template;

?>
<div class="mdl-layout mdl-js-layout mdl-layout--fixed-header mdl-layout--no-desktop-drawer-button">
    <header class="mdl-layout__header mdl-color--indigo-400">
        <div class="mdl-layout__header-row">
            <!-- Title -->
            <span class="mdl-layout-icon">
                <img class="logo btn-menu"
                     src="<?php echo $_getvar('logo_no_bg_color'); ?>"
                     alt="logo"
                     data-route="<?php echo $_getvar('homeRoute'); ?>"
                     data-view="search"
                     data-historyReset="1"/>
            </span>

            <?php if ($_getvar('useMenu') && $_getvar('loggedIn')): ?>
                <!-- Add spacer, to align navigation to the right -->
                <div class="mdl-layout-spacer"></div>
                <!-- Navigation -->
                <nav class="mdl-navigation mdl-layout--large-screen-only">
                    <?php /** @var DataGridAction $action */
                    foreach ($_getvar('actions') as $action): ?>
                        <a id="btn-<?php echo $action->getId(); ?>"
                           class="btn-menu mdl-navigation__link"
                            <?php foreach ($action->getData() as $dataName => $dataValue): ?>
                                <?php echo 'data-', $dataName, '="', $dataValue, '"'; ?>
                            <?php endforeach; ?>>
                            <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                        </a>
                        <span for="btn-<?php echo $action->getId(); ?>"
                              class="mdl-tooltip mdl-tooltip--bottom"><?php echo $action->getTitle(); ?></span>
                    <?php endforeach; ?>

                    <a id="notifications" class="btn-menu mdl-navigation__link"
                       data-route="notification/index"
                       data-historyreset="1"
                       data-view="notifications">
                        <i class="material-icons mdl-badge mdl-badge--overlap notifications-badge"
                           data-badge="0"
                           data-color-class="mdl-color-text--amber-200">notifications</i>
                    </a>

                    <span for="notifications"
                          class="mdl-tooltip mdl-tooltip--bottom notifications-tooltip">
                    </span>

                    <!-- User's menu -->
                    <!-- Right aligned menu below button -->
                    <button id="users-menu-lower-right"
                            class="mdl-button mdl-js-button mdl-button--icon">
                        <i class="material-icons">more_vert</i>
                    </button>

                    <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect"
                        for="users-menu-lower-right">
                        <?php if ($_getvar('showPassIcon')): ?>
                            <li id="btnUserPass"
                                class="btn-action mdl-menu__item"
                                data-onclick="appMgmt/show"
                                data-action-route="user/editPass"
                                data-item-id="<?php echo $_getvar('ctx_userId'); ?>"
                                data-sk="<?php echo $_getvar('sk'); ?>">
                                <i class="material-icons">lock</i>
                                <?php echo __('Change User\'s Password'); ?>
                            </li>
                        <?php endif; ?>
                        <li id="btnPrefs" class="btn-action mdl-menu__item"
                            data-onclick="user/showSettings"
                            data-action-route="userSettingsManager/index">
                            <i class="material-icons">account_circle</i>
                            <?php echo __('User preferences'); ?>
                        </li>
                        <li class="mdl-menu__item--full-bleed-divider"></li>
                        <li class="btn-action mdl-menu__item"
                            title="<?php echo __('Sign Out'); ?>"
                            data-onclick="main/logout">
                            <i class="material-icons">exit_to_app</i>
                            <?php echo __('Sign Out'); ?>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($_getvar('useMenu') && $_getvar('loggedIn')): ?>
        <div class="mdl-layout__drawer">
            <span class="mdl-layout-title"><?php echo $_getvar('app_name'); ?></span>
            <nav class="mdl-navigation">
                <?php /** @var DataGridAction $action */
                foreach ($_getvar('actions') as $action): ?>
                    <a id="btn-drawer-<?php echo $action->getId(); ?>"
                       class="btn-menu mdl-navigation__link"
                        <?php foreach ($action->getData() as $dataName => $dataValue): ?>
                            <?php echo 'data-', $dataName, '="', $dataValue, '"'; ?>
                        <?php endforeach; ?>>
                        <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                        <?php echo $action->getTitle(); ?>
                    </a>
                <?php endforeach; ?>

                <a id="notifications-drawer"
                   class="btn-menu mdl-navigation__link"
                   data-route="notification/index"
                   data-historyreset="1"
                   data-view="notifications">
                    <i class="material-icons mdl-badge mdl-badge--overlap notifications-badge"
                       data-badge="0"
                       data-color-class="mdl-color-text--amber-200">notifications</i>
                    <?php echo __('Notifications'); ?>
                </a>

                <span for="notifications-drawer"
                      class="mdl-tooltip mdl-tooltip--bottom notifications-tooltip">
                </span>

                <span class="mdl-menu__item--full-bleed-divider"></span>

                <?php if ($_getvar('showPassIcon')): ?>
                    <a class="btn-action mdl-navigation__link"
                       title="<?php echo __('Change User\'s Password'); ?>"
                       data-onclick="appMgmt/show"
                       data-action-route="user/editPass"
                       data-item-id="<?php echo $_getvar('ctx_userId'); ?>"
                       data-sk="<?php echo $_getvar('sk'); ?>">
                        <i class="btn-action material-icons">security</i>
                        <?php echo __('Change User\'s Password'); ?>
                    </a>
                <?php endif; ?>

                <a class="btn-action mdl-navigation__link"
                   title="<?php echo __('User preferences'); ?>"
                   data-onclick="user/showSettings"
                   data-action-route="userSettingsManager/index">
                    <i class="btn-action material-icons">account_circle</i>
                    <span id="user-name"><?php echo $_getvar('ctx_userName'); ?></span>
                    <span for="user-name"
                          class="mdl-tooltip mdl-tooltip--top"><?php printf('%s : %s', __('Group'), $_getvar('ctx_userGroup')); ?></span>
                    <?php
                    /** @var IconInterface $ctx_userType */
                    $ctx_userType = $_getvar('ctx_userType');

                    if ($ctx_userType): ?>
                        <i id="user-type"
                           class="material-icons"><?php echo $ctx_userType->getIcon(); ?></i>
                        <span for="user-type"
                              class="mdl-tooltip mdl-tooltip--top"><?php echo $ctx_userType->getTitle(); ?></span>
                    <?php endif; ?>
                </a>

                <span class="mdl-menu__item--full-bleed-divider"></span>

                <a class="btn-action mdl-navigation__link"
                   title="<?php echo __('Sign Out'); ?>"
                   data-onclick="main/logout">
                    <i class="material-icons">exit_to_app</i>
                    <?php echo __('Sign Out'); ?>
                </a>

                <span class="mdl-menu__item--full-bleed-divider"></span>

                <a class="mdl-navigation__link" href="#">
                    <?php if ($_getvar('httpsEnabled')): ?>
                        <i class="material-icons mdl-color-text--teal-500">lock</i>
                    <?php else: ?>
                        <i class="material-icons mdl-color-text--red-900">lock_open</i>
                    <?php endif; ?>

                    <?php if ($_getvar('isDemo')): ?>
                        <span class="status-info"><?php echo __('Demo'); ?></span>
                    <?php endif; ?>

                    <?php echo $_getvar('app_name'), ' ', $_getvar('app_version'); ?>
                </a>
            </nav>
        </div>
    <?php endif; ?>

    <main class="mdl-layout__content">
        <div id="content"
             data-page="<?php echo $_getvar('contentPage', ''); ?>">
            <?php if ($this->hashContentTemplates()):
                foreach ($this->getContentTemplates() as $template):
                    include $template;
                endforeach;
            endif; ?>
        </div>
    </main>
</div> <!-- Close mdl-layout -->
