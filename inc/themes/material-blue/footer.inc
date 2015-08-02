            </div> <!-- Close container -->
            <footer>
                <?php if ($loggedIn): ?>
                    <div id="session">
                        <i class="material-icons" title="<?php echo _('Salir'); ?>" OnClick="doLogout();">exit_to_app</i>
                        <?php if ($showPassIcon): ?>
                            <i class="material-icons" title="<?php echo _('Cambiar clave de usuario'); ?>"
                               data-itemid="<?php echo $userId; ?>"
                               Onclick="usrUpdPass(this,<?php echo \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>, '<?php echo $sk; ?>')">security</i>
                        <?php endif; ?>
                        <i class="material-icons" title="<?php echo _('Preferencias de usuario'); ?>"
                           OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_USR_PREFERENCES; ?>)">account_circle</i>
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
                </div> <!-- Close Project -->
                <div id="status">
                    <?php echo (DEBUG) ? 'Debug ON' : ''; ?>
                    <div id="updates"></div>
                </div>
            </footer> <!-- Close footer -->
        </div> <!-- Close wrap -->
    </body> <!-- Close BODY...almost done..go..go..go -->
</html> <!-- Close HTML...ufff too much work!! :)) -->
<!-- Insert coin . . . -->