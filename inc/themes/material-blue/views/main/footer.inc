</div> <!-- Close container -->
<footer>
    <?php if ($loggedIn): ?>
        <div id="session">
            <i id="btnLogout" class="material-icons" title="<?php echo _('Salir'); ?>">exit_to_app</i>
            <?php if ($showPassIcon): ?>
                <i id="btnUserPass" class="material-icons" title="<?php echo _('Cambiar clave de usuario'); ?>"
                   data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>"
                   data-item-id="<?php echo $userId; ?>"
                   data-sk="<?php echo $sk; ?>">security</i>
            <?php endif; ?>
            <i id="btnPrefs" class="material-icons" title="<?php echo _('Preferencias de usuario'); ?>"
               data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES; ?>">account_circle</i>
            <?php printf('%s (%s) %s', $userName, $userGroup, $adminApp); ?>
        </div>
    <?php endif; ?>
    <div id="project">
        <a href="<?php echo $appInfo['appwebsite']; ?>" target="_blank" class="active-tooltip"
           title="<?php echo _('Ayuda :: FAQ :: Changelog'); ?>">
            <?php echo $appInfo['appname'], ' ', $appVersion; ?>
        </a>
        &nbsp;::&nbsp;
        <a href="<?php echo $appInfo['appblog']; ?>" target="_blank" class="active-tooltip"
           title="<?php echo _('Un proyecto de cygnux.org'); ?>">cygnux.org</a>
    </div>
    <!-- Close Project -->
    <div id="status">
        <span class="status-info mdl-color-text--red-900"><?php echo DEBUG ? 'Debug ON' : ''; ?></span>
        <span class="status-info active-tooltip"
              title="<?php echo _('Indica si la conexión utiliza HTTPS.') . '<br><br>' . _('Las claves de formularios enviados se encriptan mediante PKI, el resto de datos no.'); ?>">
            <?php if ($httpsEnabled): ?>
                <i class="material-icons mdl-color-text--teal-500">lock</i>
            <?php else: ?>
                <i class="material-icons mdl-color-text--red-900">lock_open</i>
            <?php endif; ?>
        </span>
        <span class="status-info"><?php echo $isDemoMode ? 'Demo' : ''; ?></span>

        <div id="updates"></div>
    </div>
</footer> <!-- Close footer -->
</div> <!-- Close wrap -->