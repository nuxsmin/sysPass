<div id="header">
    <div id="session" class="midround shadow">
		    <?php printf('%s (%s) %s', $userName, $userGroup, $adminApp); ?>
        <?php if ($showPassIcon): ?>
            <img src="imgs/key.png" class="iconMini"
                title="<?php echo _('Cambiar clave de usuario'); ?>"
                data-itemid="<?php echo $userId; ?>"
                Onclick="sysPassUtil.Common.usrUpdPass(this,<?php echo \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>, '<?php echo $sk; ?>')" />
        <?php endif; ?>
        <img src="imgs/preferences.png" class="iconMini" title="<?php echo _('Preferencias'); ?>"
             data-itemid="<?php echo $userId; ?>"
             OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_USR_PREFERENCES; ?>)" />
        <img src="imgs/exit.png" title="<?php echo _('Salir'); ?>" OnClick="sysPassUtil.Common.doLogout();"/>
    </div>
</div>