<div id="actions" class="upgrade" align="center">
    <div id="logo">
        <img src="<?php echo $logo;; ?>" alt="sysPass logo"/>
    </div>

    <form id="upgrade" action="index.php" method="get">
        <fieldset>
            <legend><?php echo _('Actualización de BBDD'); ?></legend>
            <p>
                <input type="text" name="h" id="hash" title="<?php echo _('Introducir Código de Seguridad'); ?>"
                       placeholder="<?php echo _('Código de Seguridad'); ?> "
                       value="" autocomplete="off" autofocus required/>
            </p>
            <input type="hidden" name="a" value="<?php echo $action; ?>">
            <input type="hidden" name="t" value="<?php echo $time; ?>">
            <input type="hidden" name="upgrade" value="1">
        </fieldset>

        <div class="buttons">
            <input type="submit" class="button round5" value="<?php echo _('Actualizar'); ?>"
                   title="<?php echo _('Iniciar Actualización'); ?>"/>
        </div>
    </form>
</div>
