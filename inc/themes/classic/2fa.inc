<div id="actions" align="center">

    <div id="logo">
        <img src="<?php echo $logo; ?>" alt="sysPass logo"/>
    </div>

    <form id="frm2FA" method="post">
        <fieldset id="resetdata">
            <legend><?php echo _('Autentificación en 2 pasos'); ?></legend>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="security_pin" name="security_pin" type="text"
                       pattern="[0-9]{6}" class="mdl-textfield__input fg-blue100" maxlength="6" required/>
                <label class="mdl-textfield__label"
                       for="login"><?php echo _('Introducir código'); ?></label>
            </div>
            <br>
            <input type="hidden" name="itemId" value="<?php echo $userId; ?>">
            <input type="hidden" name="time" value="<?php echo $time; ?>">
            <input type="hidden" name="hash" value="<?php echo $hash; ?>">
            <input type="hidden" name="isAjax" value="1">
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        </fieldset>
    </form>

    <div class="action">
        <button type="submit" form="frm2FA" title="<?php echo _('Verificar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>
</div>

<script>
    $(function () {
        $('#frm2FA').on('submit', function () {
            sendAjax($(this).serialize(),'/ajax/ajax_2fa.php');
            return false;
        })
    })
</script>