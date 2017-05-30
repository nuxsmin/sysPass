<?php /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - Encryption -->
<div id="tabs-<?php echo $encryption_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">

        <div id="title" class="midroundup titleNormal">
            <?php echo __('Clave Maestra'); ?>
        </div>

        <form method="post" name="frmCrypt" id="frmCrypt" class="form-action" data-onsubmit="config/masterpass"
              data-type="masterpass"
              data-hash="">
            <table class="data tblConfig round">
                <?php if ($lastUpdateMPass > 0): ?>
                    <tr>
                        <td class="descField">
                            <?php echo __('Último cambio'); ?>
                        </td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Último cambio'); ?></div>

                            <?php echo date('r', $lastUpdateMPass); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td class="descField">
                        <?php echo __('Clave Maestra actual'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="curMasterPwd" name="curMasterPwd" type="password"
                                   class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                                   maxlength="255" required/>
                            <label class="mdl-textfield__label"
                                   for="curMasterPwd"><?php echo __('Clave Maestra actual'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Nueva Clave Maestra'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="newMasterPwd" name="newMasterPwd" type="password"
                                   class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                                   maxlength="255" required/>
                            <label class="mdl-textfield__label"
                                   for="newMasterPwd"><?php echo __('Nueva Clave Maestra'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Nueva Clave Maestra (repetir)'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="newMasterPwdR" name="newMasterPwdR" type="password"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="255" required/>
                            <label class="mdl-textfield__label"
                                   for="newMasterPwdR"><?php echo __('Nueva Clave Maestra (repetir)'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-chkNoAccountChange"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-chkNoAccountChange">
                            <p>
                                <?php echo __('Establece una nueva clave maestra sin re-encriptar las cuentas'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="chkNoAccountChange">
                            <input type="checkbox" id="chkNoAccountChange"
                                   class="mdl-switch__input mdl-color-text--indigo-400" name="chkNoAccountChange"/>
                            <span class="mdl-switch__label"><?php echo __('No modificar cuentas'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField"></td>
                    <td class="valField">
                        <div
                                class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                        <?php echo __('Guarde la nueva clave en un lugar seguro.'); ?>
                        <br>
                        <div
                                class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                        <?php echo __('Se volverán a encriptar las claves de todas las cuentas.'); ?>
                        <br>
                        <div
                                class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                        <?php echo __('Los usuarios deberán de introducir la nueva clave maestra.'); ?>
                        <br>
                        <div
                                class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                        <strong><?php printf(__('Se van a actualizar %s cuentas. Este proceso puede tardar algo de tiempo.'), $numAccounts); ?></strong>
                        <br>
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="confirmPassChange">
                            <input type="checkbox" id="confirmPassChange" class="mdl-switch__input"
                                   name="confirmPassChange"/>
                            <span class="mdl-switch__label"><?php echo __('Confirmar cambio'); ?></span>
                        </label>
                    </td>
                </tr>
            </table>

            <?php if ($numAccounts >= 500): ?>
                <input type="hidden" name="useTask" value="1">
                <input type="hidden" name="taskId" value="<?php echo $taskId; ?>">
                <input type="hidden" name="lock" value="masterpass">
            <?php endif; ?>

            <input type="hidden" name="activeTab" value="<?php echo $encryption_tabIndex; ?>"/>
            <input type="hidden" name="actionId"
                   value="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_ENCRYPTION; ?>"/>
            <input type="hidden" name="isAjax" value="1"/>
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        </form>
        <div class="tab-actions">
            <ul>
                <li>
                    <button id="help_mpass_button" type="button"
                            class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                            title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                            data-help="help_mpass">
                        <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
                    </button>

                    <div id="help_mpass" class="help-box" title="<?php echo __('Ayuda'); ?>">
                        <p class="help-text"><?php echo __('La clave maestra es utilizada para encriptar las claves de las cuentas de sysPass para mantenerlas seguras.'); ?></p>

                        <p class="help-text"><?php echo __('Es recomendable cambiarla cada cierto tiempo y utilizar una clave compleja que incluya números, letras y símbolos.'); ?></p>

                        <p class="help-text"><?php printf(__('Para más información consulte: %s'), '<a target="_blank" href="https://doc.syspass.org">https://doc.syspass.org</a>'); ?></p>
                    </div>
                </li>
                <?php if ($isAdminApp): ?>
                    <li>
                        <button type="button"
                                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconCheck()->getClassButton(); ?>"
                                data-onclick="config/refreshMpass"
                                data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_ENCRYPTION_REFRESH; ?>"
                                data-sk="<?php echo $sk; ?>"
                                title="<?php echo __('Actualizar hash de clave maestra'); ?>">
                            <i class="material-icons"><?php echo $icons->getIconRefresh()->getIcon(); ?></i>
                        </button>
                    </li>
                <?php endif; ?>

                <?php if ($numAccounts > 0): ?>
                    <li>
                        <button form="frmCrypt"
                                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPlay()->getClassButton(); ?>"
                                title="<?php echo $icons->getIconPlay()->getTitle(); ?>">
                            <i class="material-icons"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div id="title" class="midroundup titleNormal">
            <?php echo __('Clave Temporal'); ?>
        </div>

        <form method="post" name="frmTempMasterPass" id="frmTempMasterPass" class="form-action"
              data-onsubmit="config/save"
              data-type="temppass"
              data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS; ?>"
              data-activetab="<?php echo $encryption_tabIndex; ?>">
            <table class="data tblConfig round">
                <tr>
                    <td class="descField">
                        <?php echo __('Último cambio'); ?>
                    </td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Último cambio'); ?></div>

                        <?php echo ($tempMasterPassTime > 0) ? date('r', $tempMasterPassTime) : __('No generada'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Válido hasta'); ?>
                    </td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Válido hasta'); ?></div>

                        <?php if (time() > $tempMasterMaxTime): ?>
                            <span style="color: red"><?php echo date('r', $tempMasterMaxTime); ?></span>
                        <?php elseif ($tempMasterMaxTime > 0): echo date('r', $tempMasterMaxTime); ?>
                        <?php else: echo __('No generada'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Intentos'); ?>
                    </td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Intentos'); ?></div>

                        <?php echo $tempMasterAttempts; ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Validez (s)'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="tmpass_maxtime" name="tmpass_maxtime" type="number" step="600"
                                   pattern="[0-9]{2,6}" class="mdl-textfield__input mdl-color-text--indigo-400"
                                   value="3600"
                                   required/>
                            <label class="mdl-textfield__label"
                                   for="tmpass_maxtime"><?php echo __('Validez (s)'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">


                        <div id="help-chkSendEmail"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-chkSendEmail">
                            <p>
                                <?php echo __('Envía un correo a todos los usuarios o sólo a los del grupo seleccionado.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="tmpass_chkSendEmail">
                            <input type="checkbox" id="tmpass_chkSendEmail"
                                   class="mdl-switch__input mdl-color-text--indigo-400" name="tmpass_chkSendEmail"/>
                            <span class="mdl-switch__label"><?php echo __('Enviar correo'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Grupo'); ?>
                    </td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Grupo'); ?></div>

                        <select id="tmpass_group" name="tmpass_group"
                                class="select-box select-box-deselect">
                            <option value=""><?php echo __('Seleccionar Grupo'); ?></option>
                            <?php foreach ($groups as $group): ?>
                                <option
                                        value="<?php echo $group->id; ?>"><?php echo $group->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php if (!empty($tempMasterPass)): ?>
                    <tr>
                        <td class="descField">
                            <?php echo __('Clave'); ?>
                        </td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Clave'); ?></div>

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
        <div class="tab-actions">
            <ul>
                <li>
                    <button id="btnBack" type="button"
                            class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                            title="<?php echo __('Atrás'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                    </button>
                </li>
                <li>
                    <button id="help_tmpass_button"
                            class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                            title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                            data-help="help_tmpass">
                        <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
                    </button>


                    <div id="help_tmpass" class="help-box" title="<?php echo __('Ayuda'); ?>">
                        <p class="help-text"><?php echo __('La clave temporal es utilizada como clave maestra para los usuarios que necesitan introducirla al iniciar la sesión, así no es necesario facilitar la clave maestra original.'); ?></p>
                    </div>
                </li>
                <li>
                    <button form="frmTempMasterPass"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPlay()->getClassButton(); ?>"
                            title="<?php echo __('Generar'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div> <!-- End Tab - Encryption -->