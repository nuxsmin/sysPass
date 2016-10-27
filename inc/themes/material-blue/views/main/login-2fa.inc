<div id="actions" align="center">

    <div id="logo"></div>

    <form id="frmPass2fa" action="" method="post" class="form-action" data-onsubmit="main/twofa">
        <fieldset id="resetdata">
            <legend><?php echo _('Autentificación en 2 pasos'); ?></legend>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="security_pin" name="security_pin" type="text"
                       pattern="[0-9]{6}" class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="6" required/>
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

        <div class="buttons">
            <button id="btnLogin" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                <?php echo _('Acceder'); ?>
                <i class="material-icons" title="<?php echo _('Solicitar'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
            </button>
        </div>
    </form>
</div>