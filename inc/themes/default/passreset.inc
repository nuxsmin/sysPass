<div id="actions" align="center">

    <div id="logo">
        <img src="<?php use SP\Html;
        use SP\Util;

        echo $logo; ?>" alt="sysPass logo"/>
    </div>

    <form id="passreset" action="" method="post"
          onsubmit="sendAjax($(this).serialize(),'/ajax/ajax_passReset.php'); return false;">
        <fieldset id="resetdata">
            <legend><?php echo _('Solicitud de Cambio de Clave'); ?></legend>
            <?php if (!$passReset): ?>
                <p>
                    <input type="text" name="login" id="login" title="<?php echo _('Login del Usuario'); ?>"
                           placeholder="<?php echo _('Usuario'); ?> "
                           value="<?php echo Util::init_var('login'); ?>" autocomplete="off" autofocus required/>
                </p>
                <p>
                    <input type="text" name="email" id="email" title="<?php echo _('Email del Usuario'); ?>"
                           placeholder="<?php echo _('Email'); ?>  "
                           value="<?php echo Util::init_var('email'); ?>" autocomplete="off" autofocus required/>
                </p>
            <?php else: ?>
                <p>
                    <input type="password" name="pass" id="pass" title="<?php echo _('Nueva Clave'); ?>"
                           placeholder="<?php echo _('Clave'); ?>" value="<?php echo Util::init_var('pass'); ?>"
                           onKeyUp="checkPassLevel(this.value)" required/>
                    <span class="passLevel fullround"
                          title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
                </p>
                <p>
                    <input type="password" name="passv" id="passv"
                           title="<?php echo _('Nueva Clave (Verificar)'); ?>"
                           placeholder="<?php echo _('Clave (Verificar)'); ?>"
                           value="<?php echo Util::init_var('passv'); ?>" required/>
                    <span class="passLevel fullround"
                          title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
                </p>
                <input type="hidden" name="time" value="<?php echo $time; ?>">
                <input type="hidden" name="hash" value="<?php echo $hash; ?>">
            <?php endif; ?>
            <input type="hidden" name="isAjax" value="1">
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        </fieldset>

        <div class="buttons">
            <?php echo Html::anchorText(_('Volver'), 'index.php', _('Volver a iniciar sesión'), 'class="button round5"'); ?>
            <?php if (!$passReset): ?>
                <input type="submit" class="button round5" value="<?php echo _('Solicitar'); ?>"
                       title="<?php echo _('Solicitar cambio de clave'); ?>"/>
            <?php else: ?>
                <input type="submit" class="button round5" value="<?php echo _('Cambiar'); ?>"
                       title="<?php echo _('Cambiar Clave'); ?>"/>
            <?php endif; ?>
        </div>
    </form>
</div>