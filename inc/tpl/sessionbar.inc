<div id="header">
    <div id="session" class="midround shadow">
        <?php if ($showPassIcon): ?>
            <?php echo $userName, ' (', $userGroup, ') ', $adminApp; ?><img src="imgs/key.png" class="iconMini"
                                                                            title="<? echo _('Cambiar clave de usuario'); ?>"
                                                                            Onclick="usrUpdPass(<?php echo $userId, ',\'', $userLogin, '\''; ?>)" />
        <?php else: ?>
            <?php echo $userName, ' (', $userGroup, ') ', $adminApp; ?>
        <?php endif; ?>
        <img src="imgs/exit.png" title="<?php echo _('Salir'); ?>" OnClick="doLogout();"/>
    </div>
</div>