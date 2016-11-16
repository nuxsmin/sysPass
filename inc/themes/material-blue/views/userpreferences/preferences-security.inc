<?php /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - Security -->
<div id="tabs-<?php echo $security_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Autentificación'); ?>
    </div>

    <form method="post" name="frmSecurity" id="frmSecurity" class="form-action"
          data-onsubmit="user/saveSecurity"
          data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES_SECURITY; ?>"
          data-activetab="<?php echo $security_tabIndex; ?>">
        <table id="tblSite" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Autentificación en 2 pasos'); ?>
                    <div id="help-security_2fa"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
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
                        <input type="checkbox" id="security_2faenabled"
                               class="mdl-switch__input mdl-color-text--indigo-400"
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
                               pattern="[0-9]{6}" class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="6"
                               required/>
                        <label class="mdl-textfield__label"
                               for="security_pin"><?php echo _('Código'); ?></label>
                    </div>
                    <br>

                    <div
                        class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                    <?php echo _('Una vez activada, sólo es posible acceder si se dispone del dispositivo generador de códigos asociado.'); ?>
                </td>
            </tr>
        </table>

        <input type="hidden" name="itemId" value="<?php echo $userId; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $security_actionId; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="">

    </form>
    <div class="action">
        <button form="frmSecurity" type="submit"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div> <!-- End Tab - Security -->