<header class="mdl-layout__header mdl-color--indigo-400">
    <div class="mdl-layout__header-row">
        <!-- Title -->
        <span class="mdl-layout-icon"><img class="logo" src="imgs/logo_full_new.png" alt="logo"/></span>
<!--        <span class="mdl-layout-title">--><?php //echo '#', $appInfo['appname']; ?><!--</span>-->
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
        </nav>
    </div>
</header>
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
        <?php if ($showPassIcon): ?>
            <a class="btn-action mdl-navigation__link"
               title="<?php echo _('Cambiar clave de usuario'); ?>"
               data-onclick="appMgmt/show"
               data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>"
               data-item-id="<?php echo $userId; ?>"
               data-sk="<?php echo $sk; ?>">
                <i class="btn-action material-icons">security</i>
                <?php echo _('Cambiar clave de usuario'); ?>
            </a>
        <?php endif; ?>
        <a class="btn-menu mdl-navigation__link"
           title="<?php echo _('Preferencias de usuario'); ?>"
           data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES; ?>">
            <i class="btn-action material-icons">account_circle</i>
            <?php printf('%s (%s) %s', $userName, $userGroup, $adminApp); ?>
        </a>
        <a class="btn-action mdl-navigation__link" title="<?php echo _('Salir'); ?>"
           data-onclick="main/logout">
            <i class="material-icons">exit_to_app</i>
            <?php echo _('Salir'); ?>
        </a>
        <a class="mdl-navigation__link" href="#">
            <?php if ($httpsEnabled): ?>
                <i class="material-icons mdl-color-text--teal-500">lock</i>
            <?php else: ?>
                <i class="material-icons mdl-color-text--red-900">lock_open</i>
            <?php endif; ?>

            <?php if ($isDemoMode): ?>
                <span class="status-info"><?php echo _('Demo'); ?></span>
            <?php endif; ?>

            <?php echo $appInfo['appname'], ' ', $appVersion; ?>
        </a>
    </nav>
</div>
<main class="mdl-layout__content">
    <div id="content"></div>

