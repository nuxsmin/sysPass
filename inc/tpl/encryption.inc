<!-- Start Tab - Encryption -->
<div id="tabs-<?php echo $encryption_tabIndex;?>">

    <div id="title" class="midroundup titleNormal">
        <?php echo _('Clave Maestra'); ?>
    </div>

    <form method="post" name="frmCrypt" id="frmCrypt">
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
                    <input type="password" name="curMasterPwd" maxlength="255">
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nueva Clave Maestra'); ?>
                </td>
                <td class="valField">
                    <input type="password" name="newMasterPwd" maxlength="255" OnKeyUp="checkPassLevel(this.value)">
                    <span class="passLevel fullround" title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nueva Clave Maestra (repetir)'); ?>
                </td>
                <td class="valField">
                    <input type="password" name="newMasterPwdR" maxlength="255">
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('No modificar cuentas'); ?>
                    <?php echo SP_Common::printHelpButton("config", 16); ?>
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
        <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
        <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
        <input type="hidden" name="action" value="<?php echo SP_Acl::ACTION_CFG_ENCRYPTION; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>
    <div class="action">
        <ul>
            <li>
                <img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg"
                     OnClick="configMgmt('savempwd');"/>
            </li>
            <li>
                <img id="help_mpass_button" src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImg" />
                <div id="help_mpass" class="help-box" title="<?php echo _('Ayuda'); ?>">
                    <p class="help-text"><?php echo _('La clave maestra es utilizada para encriptar las claves de las cuentas de sysPass para mantenerlas seguras.'); ?></p>
                    <p class="help-text"><?php echo _('Es recomendable cambiarla cada cierto tiempo y utilizar una clave compleja que incluya números, letras y símbolos.'); ?></p>
                </div>
            </li>
        </ul>
    </div>

    <div id="title" class="midroundup titleNormal">
        <?php echo _('Clave Temporal'); ?>
    </div>

    <form method="post" name="frmTempMasterPass" id="frmTempMasterPass">
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
        <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
        <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
        <input type="hidden" name="action" value="<?php echo SP_Acl::ACTION_CFG_ENCRYPTION_TEMPPASS; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>
    <div class="action">
        <ul>
            <li>
                <img src="imgs/genpass.png" title="<?php echo _('Generar'); ?>" class="inputImg"
                     OnClick="configMgmt('gentmpass');"/>
            </li>
            <li>
                <img id="help_tmpass_button" src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImg" />
                <div id="help_tmpass" class="help-box" title="<?php echo _('Ayuda'); ?>">
                    <p class="help-text"><?php echo _('La clave temporal es utilizada como clave maestra para los usuarios que necesitan introducirla al iniciar la sesión, así no es necesario facilitar la clave maestra original.'); ?></p>
                </div>
            </li>
        </ul>
    </div>
</div> <!-- End Tab - Encryption -->

<script>
    $('#frmCrypt .checkbox').button();
    $('#frmCrypt .ui-button').click(function () {
        // El cambio de clase se produce durante el evento de click
        // Si tiene la clase significa que el estado anterior era ON y ahora es OFF
        if ($(this).hasClass('ui-state-active')) {
            $(this).children().html('<?php echo _('NO'); ?>');
        } else {
            $(this).children().html('<?php echo _('SI'); ?>');
        }
    });
    $("#tmpass_maxtime").spinner({
        step: 60, min: 60, numberFormat: "n", stop: function (event, ui) {
            accSearch(0);
        }
    });
    $(".help-box").dialog({autoOpen: false, title: '<?php echo _('Ayuda'); ?>'});
    $("#help_tmpass_button").click(function() {
        $("#help_tmpass").dialog("open");
    });
    $("#help_mpass_button").click(function() {
        $("#help_mpass").dialog("open");
    });
</script>