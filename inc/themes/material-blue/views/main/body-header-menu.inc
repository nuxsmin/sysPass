<header class="mdl-layout__header mdl-color--indigo-400">
    <div class="mdl-layout__header-row">
        <!-- Title -->
        <span class="mdl-layout-icon">
            <img class="logo btn-menu" src="<?php echo $logonobg; ?>"
                 alt="logo"
                 data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>"
                 data-view="search"
                 data-historyReset="1"/>
        </span>
        <!--        <span class="mdl-layout-title">--><?php //echo '#', $appInfo['appname']; ?><!--</span>-->
        <?php if ($loggedIn): ?>
            <!-- Add spacer, to align navigation to the right -->
            <div class="mdl-layout-spacer"></div>
            <!-- Navigation -->
            <nav class="mdl-navigation mdl-layout--large-screen-only">
                <?php /** @var \SP\Html\DataGrid\DataGridAction $action */
                foreach ($actions as $action): ?>
                    <a id="btn-<?php echo $action->getId(); ?>" class="btn-menu mdl-navigation__link"
                       data-action-id="<?php echo $action->getId(); ?>"
                        <?php foreach ($action->getData() as $dataName => $dataValue): ?>
                            <?php echo 'data-', $dataName, '="', $dataValue, '"'; ?>
                        <?php endforeach; ?>>
                        <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                    </a>
                    <span for="btn-<?php echo $action->getId(); ?>"
                          class="mdl-tooltip mdl-tooltip--bottom"><?php echo $action->getTitle(); ?></span>
                <?php endforeach; ?>

                <?php if ($userNotices > 0): ?>
                    <a id="notices-user" class="btn-menu mdl-navigation__link"
                         data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_NOT; ?>"
                         data-historyreset="1"
                         data-view="notices">
                        <i class="material-icons mdl-badge mdl-badge--overlap" data-badge="<?php echo $userNotices; ?>">notifications</i>
                    </a>

                    <span for="notices-user"
                          class="mdl-tooltip mdl-tooltip--bottom"><?php printf(__('Hay %d notificaciones pendientes'), $userNotices); ?></span>
                <?php else: ?>
                    <a id="notices-user" class="btn-menu mdl-navigation__link"
                         data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_NOT; ?>"
                         data-historyreset="1"
                         data-view="notices">
                        <i class="material-icons mdl-badge mdl-badge--overlap" data-badge="0">notifications</i>
                    </a>
                    <span for="notices-user"
                          class="mdl-tooltip mdl-tooltip--bottom"><?php echo __('No hay no hay notificaciones pendientes'); ?></span>
                <?php endif; ?>

                <!-- User's menu -->
                <!-- Right aligned menu below button -->
                <button id="users-menu-lower-right"
                        class="mdl-button mdl-js-button mdl-button--icon">
                    <i class="material-icons">more_vert</i>
                </button>

                <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect"
                    for="users-menu-lower-right">
                    <?php if ($showPassIcon): ?>
                        <li id="btnUserPass" class="btn-action mdl-menu__item"
                            data-onclick="appMgmt/show"
                            data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>"
                            data-item-id="<?php echo $userId; ?>"
                            data-sk="<?php echo $sk; ?>">
                            <i class="material-icons">security</i>
                            <?php echo __('Cambiar Clave de Usuario'); ?>
                        </li>
                    <?php endif; ?>
                    <li id="btnPrefs" class="mdl-menu__item"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES; ?>">
                        <i class="material-icons">account_circle</i>
                        <?php echo __('Preferencias de usuario'); ?>
                    </li>
                    <li id="btnLogout" class="mdl-menu__item">
                        <i class="material-icons">exit_to_app</i>
                        <?php echo __('Salir'); ?>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</header>

<?php if ($loggedIn === true): ?>
    <div class="mdl-layout__drawer">
        <span class="mdl-layout-title"><?php echo $appInfo['appname']; ?></span>
        <nav class="mdl-navigation">
            <?php /** @var \SP\Html\DataGrid\DataGridAction $action */
            foreach ($actions as $action): ?>
                <a class="btn-menu mdl-navigation__link"
                   title="<?php echo $action->getTitle(); ?>"
                   data-action-id="<?php echo $action->getId(); ?>"
                    <?php foreach ($action->getData() as $dataName => $dataValue): ?>
                        <?php echo 'data-', $dataName, '="', $dataValue, '"'; ?>
                    <?php endforeach; ?>>
                    <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                    <?php echo $action->getTitle(); ?>
                </a>
            <?php endforeach; ?>

            <?php if ($userNotices > 0): ?>
                <a id="notices-user" class="btn-menu mdl-navigation__link"
                     data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_NOT; ?>"
                     data-historyreset="1"
                     data-view="notices">
                    <i class="material-icons mdl-badge mdl-badge--overlap" data-badge="<?php echo $userNotices; ?>">notifications</i>
                    <?php echo __('Notificaciones'); ?>
                </a>
            <?php else: ?>
                <a id="notices-user" class="btn-menu mdl-navigation__link"
                     data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_NOT; ?>"
                     data-historyreset="1"
                     data-view="notices">
                    <i class="material-icons mdl-badge mdl-badge--overlap" data-badge="0">notifications</i>
                    <?php echo __('Notificaciones'); ?>
                </a>
            <?php endif; ?>

            <?php if ($showPassIcon): ?>
                <a class="btn-action mdl-navigation__link"
                   title="<?php echo __('Cambiar Clave de Usuario'); ?>"
                   data-onclick="appMgmt/show"
                   data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>"
                   data-item-id="<?php echo $userId; ?>"
                   data-sk="<?php echo $sk; ?>">
                    <i class="btn-action material-icons">security</i>
                    <?php echo __('Cambiar Clave de Usuario'); ?>
                </a>
            <?php endif; ?>
            <a class="btn-menu mdl-navigation__link"
               title="<?php echo __('Preferencias de usuario'); ?>"
               data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES; ?>">
                <i class="btn-action material-icons">account_circle</i>
                <span id="user-name"><?php echo $userName; ?></span>
                <span for="user-name"
                      class="mdl-tooltip mdl-tooltip--top"><?php printf('%s : %s', __('Grupo'), $userGroup); ?></span>
                <?php /** @var \SP\Html\Assets\IconInterface $userType */
                if ($userType !== null): ?>
                    <i id="user-type" class="material-icons"><?php echo $userType->getIcon(); ?></i>
                    <span for="user-type"
                          class="mdl-tooltip mdl-tooltip--top"><?php echo $userType->getTitle(); ?></span>
                <?php endif; ?>
            </a>
            <a class="btn-action mdl-navigation__link" title="<?php echo __('Salir'); ?>"
               data-onclick="main/logout">
                <i class="material-icons">exit_to_app</i>
                <?php echo __('Salir'); ?>
            </a>
            <a class="mdl-navigation__link" href="#">
                <?php if ($httpsEnabled): ?>
                    <i class="material-icons mdl-color-text--teal-500">lock</i>
                <?php else: ?>
                    <i class="material-icons mdl-color-text--red-900">lock_open</i>
                <?php endif; ?>

                <?php if ($isDemoMode): ?>
                    <span class="status-info"><?php echo __('Demo'); ?></span>
                <?php endif; ?>

                <?php echo $appInfo['appname'], ' ', $appVersion; ?>
            </a>
        </nav>
    </div>
<?php endif; ?>