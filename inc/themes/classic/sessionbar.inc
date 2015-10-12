<div id="header">
    <div id="session" class="midround shadow">
        <?php if ($showPassIcon): ?>
            <?php echo $userName, ' (', $userGroup, ') ', $adminApp; ?>
            <img src="imgs/key.png" class="iconMini"
                title="<?php echo _('Cambiar clave de usuario'); ?>"
                data-itemid="<?php echo $userId; ?>"
                Onclick="sysPassUtil.Common.usrUpdPass(this,<?php echo \SP\Core\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>, '<?php echo $sk; ?>')" />
            <img src="imgs/preferences.png" class="iconMini" title="<?php echo _('Preferencias'); ?>"
                 data-itemid="<?php echo $userId; ?>"
                 OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES; ?>)" />
        <?php else: ?>
            <?php echo $userName, ' (', $userGroupName, ') ', $adminApp; ?>
        <?php endif; ?>
        <img src="imgs/exit.png" title="<?php echo _('Salir'); ?>" OnClick="sysPassUtil.Common.doLogout();"/>
    </div>
</div>