<?php /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - Encryption -->
<div id="tabs-<?php echo $encryption_tabIndex; ?>">

    <div id="title" class="midroundup titleNormal">
        <?php echo _('Clave Maestra'); ?>
    </div>

    <form method="post" name="frmCrypt" id="frmCrypt" class="form-action" data-onsubmit="config/save" data-type="crypt"
          data-hash="">
        <table class="data tblConfig round">
            <?php if ($lastUpdateMPass > 0): ?>
                <tr>
                    <td class="descField">
                        <?php echo _('Último cambio'); ?>
                    </td>
                    <td class="valField">
                        <?php echo date('r', $lastUpdateMPass); ?>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="descField">
                    <?php echo _('Clave Maestra actual'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="curMasterPwd" name="curMasterPwd" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                               maxlength="255" required/>
                        <label class="mdl-textfield__label"
                               for="curMasterPwd"><?php echo _('Clave Maestra actual'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nueva Clave Maestra'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="newMasterPwd" name="newMasterPwd" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               maxlength="255" required/>
                        <label class="mdl-textfield__label"
                               for="newMasterPwd"><?php echo _('Nueva Clave Maestra'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nueva Clave Maestra (repetir)'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="newMasterPwdR" name="newMasterPwdR" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" required/>
                        <label class="mdl-textfield__label"
                               for="newMasterPwdR"><?php echo _('Nueva Clave Maestra (repetir)'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('No modificar cuentas'); ?>

                    <div id="help-chkNoAccountChange"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-chkNoAccountChange">
                        <p>
                            <?php echo _('Establece una nueva clave maestra sin re-encriptar las cuentas'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="chkNoAccountChange">
                        <input type="checkbox" id="chkNoAccountChange"
                               class="mdl-switch__input mdl-color-text--indigo-400" name="chkNoAccountChange"/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Confirmar cambio'); ?>
                </td>
                <td class="valField">
                    <div
                        class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                    <?php echo _('Guarde la nueva clave en un lugar seguro.'); ?>
                    <br>
                    <div
                        class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                    <?php echo _('Se volverán a encriptar las claves de todas las cuentas.'); ?>
                    <br>
                    <div
                        class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                    <?php echo _('Los usuarios deberán de introducir la nueva clave maestra.'); ?>
                    <br>
                    <br>
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="confirmPassChange">
                        <input type="checkbox" id="confirmPassChange" class="mdl-switch__input"
                               name="confirmPassChange"/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
        </table>
        <input type="hidden" name="activeTab" value="<?php echo $encryption_tabIndex; ?>"/>
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_ENCRYPTION; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>
    <div class="action">
        <button id="help_mpass_button" type="button"
                class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                data-help="help_mpass">
            <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
        </button>

        <div id="help_mpass" class="help-box" title="<?php echo _('Ayuda'); ?>">
            <p class="help-text"><?php echo _('La clave maestra es utilizada para encriptar las claves de las cuentas de sysPass para mantenerlas seguras.'); ?></p>

            <p class="help-text"><?php echo _('Es recomendable cambiarla cada cierto tiempo y utilizar una clave compleja que incluya números, letras y símbolos.'); ?></p>
        </div>

        <button form="frmCrypt"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPlay()->getClassButton(); ?>"
                title="<?php echo $icons->getIconPlay()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
        </button>
    </div>

    <div id="title" class="midroundup titleNormal">
        <?php echo _('Clave Temporal'); ?>
    </div>

    <form method="post" name="frmTempMasterPass" id="frmTempMasterPass" class="form-action"
          data-onsubmit="config/save"
          data-type="temppass"
          data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS; ?>"
          data-activetab="<?php echo $encryption_tabIndex; ?>">
        <table class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Último cambio'); ?>
                </td>
                <td class="valField">
                    <?php echo ($tempMasterPassTime > 0) ? date('r', $tempMasterPassTime) : _('No generada'); ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Válido hasta'); ?>
                </td>
                <td class="valField">
                    <?php if (time() > $tempMasterMaxTime): ?>
                        <span style="color: red"><?php echo date('r', $tempMasterMaxTime); ?></span>
                    <?php elseif ($tempMasterMaxTime > 0): echo date('r', $tempMasterMaxTime); ?>
                    <?php else: echo _('No generada'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Validez (s)'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="tmpass_maxtime" name="tmpass_maxtime" type="number" step="600"
                               pattern="[0-9]{2,6}" class="mdl-textfield__input mdl-color-text--indigo-400" value="3600"
                               required/>
                        <label class="mdl-textfield__label" for="tmpass_maxtime"><?php echo _('Validez (s)'); ?></label>
                    </div>
                </td>
            </tr>
            <?php if (!empty($tempMasterPass)): ?>
                <tr>
                    <td class="descField">
                        <?php echo _('Clave'); ?>
                    </td>
                    <td class="valField">
                        <?php echo $tempMasterPass; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS; ?>"/>
        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1"/>
    </form>
    <div class="action">
        <button id="help_tmpass_button"
                class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                data-help="help_tmpass">
            <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
        </button>

        <div id="help_tmpass" class="help-box" title="<?php echo _('Ayuda'); ?>">
            <p class="help-text"><?php echo _('La clave temporal es utilizada como clave maestra para los usuarios que necesitan introducirla al iniciar la sesión, así no es necesario facilitar la clave maestra original.'); ?></p>
        </div>

        <button form="frmTempMasterPass"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPlay()->getClassButton(); ?>"
                title="<?php echo _('Generar'); ?>">
            <i class="material-icons"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
        </button>
    </div>
</div> <!-- End Tab - Encryption -->