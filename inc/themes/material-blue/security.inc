<!-- Start Tab - Security -->
<div id="tabs-<?php echo $security_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Autentificación'); ?>
    </div>

    <form method="post" name="frmSecurity" id="frmSecurity" data-url="/ajax/ajax_userPrefsSave.php">
        <table id="tblSite" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Autentificación en 2 pasos'); ?>
                    <div id="help-security_2fa" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-security_2fa">
                        <p>
                            <?php echo _('Habilita la autentificación en 2 pasos que requiere de la introducción de un token generado por una aplicación como Google Authenticator.'); ?>
                        </p>

                        <p>
                            <?php echo _('Escanee el código QR proporcionado y a continuación introduzca la clave de 6 dígitos.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="security_2faenabled">
                        <input type="checkbox" id="security_2faenabled" class="mdl-switch__input fg-blue100"
                               name="security_2faenabled" <?php echo ($chk2FAEnabled) ? 'checked' : ''; ?>/>
                        <span class="mdl-switch__label"><?php echo _('Activar'); ?></span>
                    </label>
                    <?php if (!$chk2FAEnabled && $qrCode): ?>
                        <br><br>
                        <img id="security_qrcode" src="data:image/gif;base64,<?php echo $qrCode; ?>" alt="QR Code"/>
                    <?php elseif ($qrCode === false): ?>
                        <p class="ui-state-error"><?php echo _('Error al obtener el código QR. Inténtelo de nuevo'); ?></p>
                    <?php endif; ?>
                    <br><br>

                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="security_pin" name="security_pin" type="text"
                               pattern="[0-9]{6}" class="mdl-textfield__input fg-blue100" maxlength="6" required/>
                        <label class="mdl-textfield__label"
                               for="security_pin"><?php echo _('Código'); ?></label>
                    </div>
                    <br>

                    <div class="icon material-icons fg-orange80">warning</div>
                    <?php echo _('Una vez activada, sólo es posible acceder si se dispone del dispositivo generador de códigos asociado.'); ?>
                </td>
            </tr>
        </table>

        <input type="hidden" name="itemId" value="<?php echo $userId; ?>"/>
        <input type="hidden" name="activeTab" value="<?php echo $security_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $security_actionId; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">

    </form>
    <div class="action">
        <button form="frmSecurity" type="submit"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div> <!-- End Tab - Security -->
<script type="text/javascript">
    $(function () {
        $('#frmSecurity').on('submit', function () {
            var url = $(this).data('url');
            var data = $(this).serialize();

            sysPassUtil.Common.sendAjax(data, url);

            return false;
        });
    })
</script>