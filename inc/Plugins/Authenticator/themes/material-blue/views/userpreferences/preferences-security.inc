<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */ /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - Security -->
<div id="tabs-<?php echo $security_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <div id="title" class="midroundup titleNormal">
            <?php echo _t('authenticator', 'Autentificación'); ?>
        </div>

        <form method="post" name="frmSecurity" id="frmSecurity" class="form-action"
              data-onsubmit="twofa/save"
              data-plugin="Authenticator"
              data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES_SECURITY; ?>"
              data-activetab="<?php echo $security_tabIndex; ?>">
            <table id="tblSite" class="data tblConfig round">
                <tr>
                    <td class="descField">
                        <?php echo _t('authenticator', 'Autentificación en 2 pasos'); ?>
                        <div id="help-security_2fa"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-security_2fa">
                            <p>
                                <?php echo _t('authenticator', 'Habilita la autentificación en 2 pasos que requiere de la introducción de un token generado por una aplicación como Google Authenticator.'); ?>
                            </p>

                            <p>
                                <?php echo _t('authenticator', 'Escanee el código QR proporcionado y a continuación introduzca la clave de 6 dígitos.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="security_2faenabled">
                            <input type="checkbox" id="security_2faenabled"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="security_2faenabled" <?php echo $chk2FAEnabled ? 'checked' : ''; ?>/>
                            <span class="mdl-switch__label"><?php echo _t('authenticator', 'Activar'); ?></span>
                        </label>
                        <?php if (!$chk2FAEnabled && $qrCode): ?>
                            <br><br>
                            <img id="security_qrcode" src="data:image/gif;base64,<?php echo $qrCode; ?>" alt="QR Code"/>
                        <?php elseif ($qrCode === false): ?>
                            <p class="ui-state-error"><?php echo _t('authenticator', 'Error al obtener el código QR. Inténtelo de nuevo'); ?></p>
                        <?php endif; ?>
                        <br><br>

                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="security_pin" name="security_pin" type="text"
                                   pattern="[0-9]{6}" class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="6"
                                   required/>
                            <label class="mdl-textfield__label"
                                   for="security_pin"><?php echo _t('authenticator', 'Código'); ?></label>
                        </div>
                        <br>

                        <div
                                class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></div>
                        <?php echo _t('authenticator', 'Una vez activada, sólo es posible acceder si se dispone del dispositivo generador de códigos asociado.'); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _t('authenticator', 'Días Caducidad'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="expiredays" name="expiredays" type="number" step="1"
                                   min="0" max="720" class="mdl-textfield__input mdl-color-text--indigo-400"
                                   value="<?php echo $expireDays ?: 90; ?>" required/>
                            <label class="mdl-textfield__label"
                                   for="expiredays"><?php echo _t('authenticator', 'Días'); ?></label>
                        </div>
                    </td>
                </tr>
            </table>

            <input type="hidden" name="itemId" value="<?php echo $userId; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $security_actionId; ?>"/>
            <input type="hidden" name="isAjax" value="1"/>
            <input type="hidden" name="sk" value="">

        </form>
        <div class="tab-actions">
            <ul>
                <li>
                    <button form="frmSecurity" type="submit"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                            title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                        <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div> <!-- End Tab - Security -->