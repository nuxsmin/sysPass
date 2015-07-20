</div> <!-- Close container -->
<footer>
    <div id="session">
        <i class="material-icons" title="<?php echo _('Salir'); ?>" OnClick="doLogout();">exit_to_app</i>
        <?php if ($showPassIcon): ?>
            <i class="material-icons" title="<?php echo _('Cambiar clave de usuario'); ?>"
               data-itemid="<?php echo $userId; ?>"
               Onclick="usrUpdPass(this,<?php echo \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>, '<?php echo $sk; ?>')">security</i>
        <?php endif; ?>
        <?php echo $userName, ' (', $userGroup, ') ', $adminApp; ?>
    </div>
    <div id="updates"></div>
    <div id="status"><?php echo (DEBUG) ? 'Debug ON' : ''; ?></div>
    <div id="project">
        <a href="<?php echo $appInfo['appwebsite']; ?>" target="_blank"
           title="<?php echo _('Ayuda :: FAQ :: Changelog'); ?>"><?php echo $appInfo['appname'], ' ', $appVersion; ?></a>
        &nbsp;::&nbsp;
        <a href="<?php echo $appInfo['appblog']; ?>" target="_blank"
           title="<?php echo _('Un proyecto de cygnux.org'); ?>">cygnux.org</a>
    </div>
    <!-- Close Project -->
</footer> <!-- Close footer -->
<script>$('input[type="text"], select, textarea').placeholder().mouseenter(function () {
        $(this).focus();
    });</script>
</div> <!-- Close wrap -->
</body>
</html>