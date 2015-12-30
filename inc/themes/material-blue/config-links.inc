<div id="title" class="midroundup titleNormal">
    <?php echo _('Enlaces Públicos'); ?>
</div>

<table id="tblFiles" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Habilitar Enlaces Públicos'); ?>
            <div id="help-publinks" class="icon material-icons fg-blue80">help_outline</div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-publinks">
                <p>
                    <?php echo _('Habilita la posibilidad de generar enlaces públicos para ver los detalles de una cuenta.'); ?>
                </p>

                <p>
                    <?php echo _('Las cuentas enlazadas serán visibles por cualquiera que disponga del enlace.'); ?>
                </p>

                <p>
                    <?php echo _('Para crear enlaces, los usuarios tienen que tener activada la opción en su perfl.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="publinks_enabled">
                <input type="checkbox" id="publinks_enabled" class="mdl-switch__input fg-blue100"
                       name="publinks_enabled"
                    <?php echo $chkPubLinks, ' ', $isDisabled; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Tiempo de caducidad'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="publinks_maxtime" name="publinks_maxtime" type="number" step="5"
                       pattern="[0-9]{1,5}" class="mdl-textfield__input fg-blue100" maxlength="5"
                       value="<?php echo $pubLinksMaxTime; ?>"/>
                <label class="mdl-textfield__label"
                       for="publinks_maxtime"><?php echo _('Tiempo en minutos'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Número máximo  de visitas'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="publinks_maxviews" name="publinks_maxviews" type="number" step="1"
                       pattern="[0-9]{1,5}" class="mdl-textfield__input fg-blue100" maxlength="5"
                       value="<?php echo $pubLinksMaxViews; ?>"/>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Usar imagen para clave'); ?>
            <div id="help-publinksimage" class="icon material-icons fg-blue80">help_outline</div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-publinksimage">
                <p>
                    <?php echo _('La clave de la cuenta es visualizada como una imagen.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="publinks_image_enabled">
                <input type="checkbox" id="publinks_image_enabled" class="mdl-switch__input fg-blue100"
                       name="publinks_image_enabled"
                    <?php echo $chkPubLinksImage, ' ', $isDisabled; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
</table>