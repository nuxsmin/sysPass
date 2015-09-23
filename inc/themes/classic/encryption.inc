<!-- Start Tab - Encryption -->
<div id="tabs-<?php use SP\Acl;
use SP\Common;

echo $encryption_tabIndex; ?>">

    <div id="title" class="midroundup titleNormal">
        <?php echo _('Clave Maestra'); ?>
    </div>

    <form method="post" name="frmCrypt" id="frmCrypt" onsubmit="sysPassUtil.Common.configMgmt('config', this); return false;">
        <table class="data tblConfig round">
            <?php if ($lastUpdateMPass > 0): ?>
                <tr>
                    <td class="descField">
                        <?php echo _('Último cambio'); ?>
                    </td>
                    <td class="valField">
                        <?php echo date("r", $lastUpdateMPass); ?>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="descField">
                    <?php echo _('Clave Maestra actual'); ?>
                </td>
                <td class="valField">
                    <input type="password" id="curMasterPwd" name="curMasterPwd" class="passwordfield__input-show"
                           maxlength="255">
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nueva Clave Maestra'); ?>
                </td>
                <td class="valField">
                    <input type="password" id="newMasterPwd" name="newMasterPwd" class="passwordfield__input"
                           maxlength="255">
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nueva Clave Maestra (repetir)'); ?>
                </td>
                <td class="valField">
                    <input type="password" id="newMasterPwdR" name="newMasterPwdR" maxlength="255">
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('No modificar cuentas'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip"/>

                    <div class="tooltip" for="help-chkNoAccountChange" style="display:none;">
                        <p>
                            <?php echo _('Establece una nueva clave maestra sin re-encriptar las cuentas'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="chkNoAccountChange"><?php echo _('NO'); ?></label>
                    <input type="checkbox" class="checkbox" name="chkNoAccountChange" id="chkNoAccountChange"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Confirmar cambio'); ?>
                </td>
                <td class="valField">
                    <img src="imgs/warning.png" ALT="<?php echo _('Atención'); ?>" class="iconMini"/>
                    <?php echo _('Guarde la nueva clave en un lugar seguro.'); ?>
                    <br>
                    <img src="imgs/warning.png" ALT="<?php echo _('Atención'); ?>" class="iconMini"/>
                    <?php echo _('Se volverán a encriptar las claves de todas las cuentas.'); ?>
                    <br>
                    <img src="imgs/warning.png" ALT="<?php echo _('Atención'); ?>" class="iconMini"/>
                    <?php echo _('Los usuarios deberán de introducir la nueva clave maestra.'); ?>
                    <br>
                    <br>
                    <label for="confirmPassChange"><?php echo _('NO'); ?></label>
                    <input type="checkbox" class="checkbox" name="confirmPassChange" id="confirmPassChange"/>
                </td>
            </tr>
        </table>
        <input type="hidden" name="activeTab" value="<?php echo $encryption_tabIndex; ?>"/>
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button id="help_mpass_button" type="button" title="<?php echo _('Ayuda'); ?>"
                class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/help.png" alt="save"/>
        </button>
        <div id="help_mpass" class="help-box" title="<?php echo _('Ayuda'); ?>">
            <p class="help-text"><?php echo _('La clave maestra es utilizada para encriptar las claves de las cuentas de sysPass para mantenerlas seguras.'); ?></p>

            <p class="help-text"><?php echo _('Es recomendable cambiarla cada cierto tiempo y utilizar una clave compleja que incluya números, letras y símbolos.'); ?></p>
        </div>
        <button type="submit" form="frmCrypt" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>

    <div id="title" class="midroundup titleNormal">
        <?php echo _('Clave Temporal'); ?>
    </div>

    <form method="post" name="frmTempMasterPass" id="frmTempMasterPass"
          onsubmit="sysPassUtil.Common.configMgmt('config', this); return false;">
        <table class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Último cambio'); ?>
                </td>
                <td class="valField">
                    <?php echo ($tempMasterPassTime > 0) ? date("r", $tempMasterPassTime) : _('No generada'); ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Válido hasta'); ?>
                </td>
                <td class="valField">
                    <?php if (time() > $tempMasterMaxTime): ?>
                        <span style="color: red"><?php echo date("r", $tempMasterMaxTime); ?></span>
                    <?php elseif ($tempMasterMaxTime > 0): echo date("r", $tempMasterMaxTime); ?>
                    <?php else: echo _('No generada'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Validez (s)'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="tmpass_maxtime" id="tmpass_maxtime" title="<?php echo _('Validez'); ?>"
                           value="3600"/>
                </td>
            </tr>
        </table>
        <input type="hidden" name="activeTab" value="<?php echo $encryption_tabIndex; ?>"/>
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button id="help_tmpass_button" type="button" title="<?php echo _('Ayuda'); ?>"
                class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/help.png" alt="save"/>
        </button>
        <div id="help_tmpass" class="help-box" title="<?php echo _('Ayuda'); ?>">
            <p class="help-text"><?php echo _('La clave temporal es utilizada como clave maestra para los usuarios que necesitan introducirla al iniciar la sesión, así no es necesario facilitar la clave maestra original.'); ?></p>
        </div>
        <button type="submit" form="frmTempMasterPass" title="<?php echo _('Generar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/genpass.png" alt="save"/>
        </button>
    </div>
</div> <!-- End Tab - Encryption -->

<script>
    $(function () {
        $("#tmpass_maxtime").spinner({step: 60, min: 60, numberFormat: "n"});
        $("#help_tmpass_button").click(function () {
            $("#help_tmpass").dialog("open");
        });
        $("#help_mpass_button").click(function () {
            $("#help_mpass").dialog("open");
        });
    });
</script>