<!-- Start Tab - Security -->
<div id="tabs-<?php echo $security_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Autenticación'); ?>
    </div>

    <form method="post" name="frmSecurity" id="frmSecurity" data-url="/ajax/ajax_userPrefsSave.php">

        <table id="tblSite" class="data tblConfig round">

            <tr>
                <td class="descField">
                    <?php echo _('Autenticación en 2 pasos'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                    <div class="tooltip" for="help-security_2fa" style="display: none">
                        <p>
                            <?php echo _('Habilita la autentificación en 2 pasos que requiere de la introducción de un token generado por una aplicación como Google Authenticator.'); ?>
                        </p>

                        <p>
                            <?php echo _('Escanee el código QR proporcionado y a continuación introduzca la clave de 6 dígitos.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="security_2faenabled"><?php echo ($chk2FAEnabled) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="security_2faenabled" id="security_2faenabled"
                           class="checkbox" <?php echo $chk2FAEnabled, ' ', $isDisabled; ?> />

                    <?php if (!$chk2FAEnabled && $qrCode): ?>
                        <br><br>
                        <img id="security_qrcode" src="data:image/gif;base64,<?php echo $qrCode; ?>" alt="QR Code"/>
                    <?php elseif ($qrCode === false): ?>
                        <p class="ui-state-error"><?php echo _('Error al obtener el código QR. Inténtelo de nuevo'); ?></p>
                    <?php endif; ?>
                    <br><br>

                    <label class="mdl-textfield__label"
                           for="security_pin"><?php echo _('Código'); ?></label>
                    <input id="security_pin" name="security_pin" type="text"
                           pattern="[0-9]{6}" class="mdl-textfield__input fg-blue100" maxlength="6" required/>
                    <br><br>
                    <img src="imgs/warning.png" ALT="<?php echo _('Atención'); ?>" class="iconMini"/>
                    <?php echo _('Una vez activada, sólo es posible acceder si se dispone del dispositivo generador de códigos asociado.'); ?>
                </td>
            </tr>
        </table>

        <input type="hidden" name="itemId" value="<?php echo $userId; ?>"/>
        <input type="hidden" name="activeTab" value="<?php echo $security_tabIndex; ?>"/>
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Controller\ActionsInterface::ACTION_USR_PREFERENCES_SECURITY; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">

    </form>
    <div class="action">
        <button type="submit" form="frmSecurity" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>
</div> <!-- End Tab - Security -->
<script type="text/javascript">
    $(function () {
        $('#frmSecurity').on('submit', function () {
            var url = $(this).data('url');
            var data = $(this).serialize();

            sendAjax(data, url);

            return false;
        });
    })
</script>