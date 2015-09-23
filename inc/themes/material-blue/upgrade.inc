<div id="actions" class="upgrade" align="center">
    <div id="logo">
        <img src="<?php echo $logo; ?>" alt="sysPass logo"/>
    </div>

    <form id="upgrade" action="index.php" method="get">
        <fieldset>
            <legend><?php echo _('Actualización de BBDD'); ?></legend>

            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="h" name="h" type="text" required class="mdl-textfield__input fg-blue100"
                       autocomplete="off" autofocus>
                <label class="mdl-textfield__label"
                       for="h"><?php echo _('Código de Seguridad'); ?></label>
            </div>

            <input type="hidden" name="a" value="<?php echo $action; ?>">
            <input type="hidden" name="t" value="<?php echo $time; ?>">
            <input type="hidden" name="upgrade" value="1">
        </fieldset>

        <div class="buttons">
            <button id="btnChange" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                <?php echo _('Actualizar'); ?>
                <i class="material-icons" title="<?php echo _('Iniciar Actualización'); ?>">play_circle_filled</i>
            </button>
        </div>
    </form>
</div>
