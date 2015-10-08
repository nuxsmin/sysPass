<div id="actions" align="center">

    <div id="logo"></div>

    <form id="frmPassReset" action="" method="post">
        <fieldset id="resetdata">
            <legend><?php echo _('Solicitud de Cambio de Clave'); ?></legend>
            <?php if (!$passReset): ?>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="login" name="login" type="text" required class="mdl-textfield__input fg-blue100"
                           value="<?php echo SP\Util::init_var('login'); ?>" maxlength="80" autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="login"><?php echo _('Usuario'); ?></label>
                </div>
                <br>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="email" name="email" type="email" required class="mdl-textfield__input fg-blue100"
                           value="<?php echo SP\Util::init_var('email'); ?>" maxlength="80" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="email"><?php echo _('Email del Usuario'); ?></label>
                </div>
            <?php else: ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="pass" name="pass" type="password" required class="mdl-textfield__input fg-blue100 passwordfield__input"
                           value="<?php echo SP\Util::init_var('pass'); ?>" maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="pass"><?php echo _('Clave'); ?></label>
                </div>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="passR" name="passR" type="password" required class="mdl-textfield__input fg-blue100"
                           value="<?php echo SP\Util::init_var('passR'); ?>" maxlength="255" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="pass"><?php echo _('Clave (repetir)'); ?></label>
                </div>
                <input type="hidden" name="time" value="<?php echo $time; ?>">
                <input type="hidden" name="hash" value="<?php echo $hash; ?>">
            <?php endif; ?>
            <input type="hidden" name="isAjax" value="1">
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        </fieldset>

        <div class="buttons">
            <button id="btnBack" type="button" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                <i class="material-icons" title="<?php echo _('Volver a iniciar sesión'); ?>">arrow_back</i>
                <?php echo _('Volver'); ?>
            </button>

            <?php if (!$passReset): ?>
                <button id="btnRequest" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <?php echo _('Solicitar'); ?>
                    <i class="material-icons" title="<?php echo _('Solicitar'); ?>">play_circle_filled</i>
                </button>
            <?php else: ?>
                <button id="btnChange" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <?php echo _('Cambiar'); ?>
                    <i class="material-icons" title="<?php echo _('Cambiar'); ?>">play_circle_filled</i>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    window.onload = function () {
        sysPassUtil.passwordDetect();

        $('#btnBack').click(function(){
            window.history.back();
        });

        $('#frmPassReset').submit(function(){
            sysPassUtil.Common.sendAjax($(this).serialize(),'/ajax/ajax_passReset.php');
            return false;
        });
    };
</script>