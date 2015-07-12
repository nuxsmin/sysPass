<div id="header">
    <div id="session" class="midround shadow">
        <?php if ($showPassIcon): ?>
            <?php echo $userName, ' (', $userGroup, ') ', $adminApp; ?><img src="imgs/key.png" class="iconMini"
                                                                            title="<?php echo _('Cambiar clave de usuario'); ?>"
                                                                            data-itemid="<?php echo $userId; ?>"
                                                                            Onclick="usrUpdPass(this,<?php echo \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS; ?>, '<?php echo $sk; ?>')" />
        <?php else: ?>
            <?php echo $userName, ' (', $userGroup, ') ', $adminApp; ?>
        <?php endif; ?>
        <img src="imgs/exit.png" title="<?php echo _('Salir'); ?>" OnClick="doLogout();"/>
    </div>
</div>