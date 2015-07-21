<div id="actions" align="center">

    <div id="logo">
        <img src="<?php use SP\Html;
        use SP\Util;

        echo $logo; ?>" alt="sysPass logo"/>
    </div>

    <form id="frmPassReset" action="" method="post"
          onsubmit="sendAjax($(this).serialize(),'/ajax/ajax_passReset.php'); return false;">
        <fieldset id="resetdata">
            <legend><?php echo _('Solicitud de Cambio de Clave'); ?></legend>
            <?php if (!$passReset): ?>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="login" name="login" type="text" required class="mdl-textfield__input blue100"
                           value="<?php echo Util::init_var('login'); ?>" maxlength="80" autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="login"><?php echo _('Usuario'); ?></label>
                </div>
                <br>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="email" name="email" type="email" required class="mdl-textfield__input blue100"
                           value="<?php echo Util::init_var('email'); ?>" maxlength="80" autocomplete="off">
                    <label class="mdl-textfield__label"
                           for="email"><?php echo _('Email del Usuario'); ?></label>
                </div>
            <?php else: ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="pass" name="pass" type="password" required class="mdl-textfield__input blue100"
                           value="<?php echo Util::init_var('pass'); ?>" maxlength="255" autocomplete="off"
                           onKeyUp="checkPassLevel(this.value)">
                    <label class="mdl-textfield__label"
                           for="pass"><?php echo _('Clave'); ?></label>
                </div>
                <i class="showpass material-icons" title="<?php echo _('Mostrar Clave'); ?>">remove_red_eye</i>

                    <span class="passLevel fullround"
                          title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>

                <input type="hidden" name="time" value="<?php echo $time; ?>">
                <input type="hidden" name="hash" value="<?php echo $hash; ?>">
            <?php endif; ?>
            <input type="hidden" name="isAjax" value="1">
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        </fieldset>

        <div class="buttons">
            <button id="btnBack" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
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
    $(function(){
        $('#btnBack').click(function(){
            window.history.back();
        })
    })
</script>