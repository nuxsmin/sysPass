<div id="header">
    <div id="session" class="midround shadow">
        <?php if ($showPassIcon): ?>
            <?php echo $userName, ' (', $userGroup, ') ', $adminApp; ?>
            <img id="btnUserPass" src="<?php echo $icons->getIconEditPass()->getIcon(); ?>" class="iconMini"
                title="<?php echo _('Cambiar clave de usuario'); ?>"
                 data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>"
                 data-item-id="<?php echo $userId; ?>"
                 data-sk="<?php echo $sk; ?>"> />
            <img id="btnPrefs" src="imgs/preferences.png" class="iconMini" title="<?php echo _('Preferencias'); ?>"
                 data-item-id="<?php echo $userId; ?>"
                 data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES; ?>"
                 OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES; ?>)" />
        <?php else: ?>
            <?php echo $userName, ' (', $userGroupName, ') ', $adminApp; ?>
        <?php endif; ?>
        <img src="imgs/exit.png" title="<?php echo _('Salir'); ?>" OnClick="sysPassUtil.Common.doLogout();"/>
    </div>
</div>