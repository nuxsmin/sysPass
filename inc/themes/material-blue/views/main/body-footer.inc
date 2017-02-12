<?php if ($useLayout === true): ?>
    </div> <!-- Close mdl-layout -->
<?php endif; ?>
</div> <!-- Close container -->
<footer>
    <div id="footer-left" class="footer-parts">
        <?php if ($loadApp === true): ?>
            <div id="session">
                <i id="btnLogout" class="material-icons">exit_to_app</i>
                <span for="btnLogout"
                      class="mdl-tooltip mdl-tooltip--top"><?php echo __('Salir'); ?></span>

                <?php if ($showPassIcon): ?>
                    <i id="btnUserPass" class="btn-action material-icons"
                       data-onclick="appMgmt/show"
                       data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>"
                       data-item-id="<?php echo $userId; ?>"
                       data-sk="<?php echo $sk; ?>">security</i>
                    <span for="btnUserPass"
                          class="mdl-tooltip mdl-tooltip--top"><?php echo __('Cambiar Clave de Usuario'); ?></span>
                <?php endif; ?>
                <i id="btnPrefs" class="material-icons"
                   data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES; ?>">account_circle</i>
                <span for="btnPrefs"
                      class="mdl-tooltip mdl-tooltip--top"><?php echo __('Preferencias de usuario'); ?></span>

                <?php if ($userNotices > 0): ?>
                    <div id="notices-user"
                         class="btn-menu material-icons mdl-badge mdl-badge--overlap mdl-color-text--amber-200"
                         data-badge="<?php echo $userNotices; ?>"
                         data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_NOT; ?>">notifications
                    </div>
                    <span for="notices-user"
                          class="mdl-tooltip mdl-tooltip--top"><?php printf(__('Hay %d notificaciones pendientes'), $userNotices); ?></span>
                <?php else: ?>
                    <div id="notices-user" class="btn-menu material-icons mdl-badge mdl-badge--overlap"
                         data-badge="0" data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_NOT; ?>">
                        notifications
                    </div>
                    <span for="notices-user"
                          class="mdl-tooltip mdl-tooltip--top"><?php echo __('No hay no hay notificaciones pendientes'); ?></span>
                <?php endif; ?>

                <span id="user-info">
                    <span id="user-name-footer"><?php echo $userName; ?></span>
                    <span for="user-name-footer"
                          class="mdl-tooltip mdl-tooltip--top"><?php printf('%s : %s', __('Grupo'), $userGroup); ?></span>

                    <?php /** @var \SP\Html\Assets\IconInterface $userType */
                    if ($userType !== null): ?>
                        <i id="user-type-footer" class="material-icons"><?php echo $userType->getIcon(); ?></i>
                        <span for="user-type-footer"
                              class="mdl-tooltip mdl-tooltip--top"><?php echo $userType->getTitle(); ?></span>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
    <div id="footer-right" class="footer-parts">
        <div id="status">
            <span class="status-info mdl-color-text--red-900"><?php echo DEBUG ? 'Debug ON' : ''; ?></span>

            <?php if ($httpsEnabled): ?>
                <span id="secure-info" class="status-info">
                    <i class="material-icons mdl-color-text--teal-500">lock</i>
                </span>
                <span for="secure-info"
                      class="mdl-tooltip mdl-tooltip--top"><?php echo 'HTTPS'; ?></span>
            <?php else: ?>
                <span id="secure-info" class="status-info">
                    <i class="material-icons mdl-color-text--red-900">lock_open</i>
                </span>
                <span for="secure-info"
                      class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large"><?php echo __('Indica si la conexión utiliza HTTPS.') . '<br><br>' . __('Las claves de formularios enviados se encriptan mediante PKI, el resto de datos no.'); ?></span>

            <?php endif; ?>

            <?php if ($isDemoMode): ?>
                <span class="status-info"><?php echo __('Demo'); ?></span>
            <?php endif; ?>

            <div id="updates"></div>
        </div>

        <div id="project">
            <a id="app-info" href="<?php echo $appInfo['appwebsite']; ?>" target="_blank">
                <?php echo $appInfo['appname'], ' ', $appVersion; ?>
            </a>
            <span for="app-info"
                  class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large"><?php echo __('Ayuda :: FAQ :: Changelog'); ?></span>
            &nbsp;::&nbsp;
            <a id="app-project" href="<?php echo $appInfo['appblog']; ?>" target="_blank">cygnux.org</a>
            <span for="app-project"
                  class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large"><?php echo __('Un proyecto de cygnux.org'); ?></span>
        </div>
        <!-- Close Project -->
    </div>
</footer> <!-- Close footer -->
</div> <!-- Close wrap -->