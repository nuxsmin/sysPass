<div id="title" class="midroundup titleNormal">
    <?php echo _('Cuentas'); ?>
</div>

<table id="tblAccounts" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Nombre de cuenta como enlace'); ?>
            <div id="help-account_link" class="icon material-icons fg-blue80">help_outline</div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-account_link">
                <p>
                    <?php echo _('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_link">
                <input type="checkbox" id="account_link" class="mdl-switch__input fg-blue100"
                       name="account_link" <?php echo $chkAccountLink; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Búsquedas globales'); ?>
            <div id="help-globalsearch" class="icon material-icons fg-blue80">help_outline</div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-globalsearch">
                <p>
                    <?php echo _('Permite que todos los usuarios puedan realizar búsquedas en todas las cuentas, pero no pueden ver el contenido de las que no tienen permisos.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="globalsearch">
                <input type="checkbox" id="globalsearch" class="mdl-switch__input fg-blue100"
                       name="globalsearch" <?php echo $chkGlobalSearch; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Resultados por página'); ?>
            <div id="help-account_count" class="icon material-icons fg-blue80">help_outline</div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-account_count">
                <p>
                    <?php echo _('Número de resultados por página a mostrar, al realizar una búsqueda.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="account_count" name="account_count" type="number" step="6"
                       pattern="[0-9]{1,5}" class="mdl-textfield__input fg-blue100" maxlength="5"
                       value="<?php echo $accountCount; ?>" <?php echo $isDisabled; ?> required/>
                <label class="mdl-textfield__label"
                       for="account_count"><?php echo _('Número de resultados por página'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Imagen para mostrar clave'); ?>
            <div id="help-account_passtoimage" class="icon material-icons fg-blue80">help_outline</div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-account_passtoimage">
                <p>
                    <?php echo _('Generar una imagen con el texto de la clave de la cuenta.'); ?>
                </p>

                <p>
                    <?php echo _('Util para entornos donde copiar la clave supone un riesgo de seguridad.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_passtoimage">
                <input type="checkbox" id="account_passtoimage" class="mdl-switch__input fg-blue100"
                       name="account_passtoimage"
                    <?php echo $chkAccountPassToImage; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Resultados en Tarjetas'); ?>
            <div id="help-resultsascards" class="icon material-icons fg-blue80">help_outline</div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-resultsascards">
                <p>
                    <?php echo _('Muestra los resultados de búsqueda de cuentas en formato tarjeta.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="resultsascards">
                <input type="checkbox" id="resultsascards" class="mdl-switch__input fg-blue100"
                       name="resultsascards"
                    <?php echo $chkResultsAsCards; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
</table>
