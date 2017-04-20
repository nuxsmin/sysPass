<?php /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - Mail -->
<div id="tabs-<?php echo $mail_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <div id="title" class="midroundup titleNormal">
            <?php echo __('Correo'); ?>
        </div>

        <form method="post" name="frmMail" id="frmMail" class="form-action" data-onsubmit="config/save" data-type="mail"
              data-hash="">
            <table id="tblMail" class="data tblConfig round">
                <tr>
                    <td class="descField"></td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_enabled">
                            <input type="checkbox" id="mail_enabled"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="mail_enabled"
                                <?php echo $chkMail; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Habilitar notificaciones de correo'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Servidor'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="mail_server" name="mail_server" type="text"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="128" value="<?php echo $mailServer; ?>"/>
                            <label class="mdl-textfield__label" for="mail_server"><?php echo __('Servidor'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Puerto'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="mail_port" name="mail_port" type="number" pattern="[0-9]{1,5}"
                                   class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="5"
                                   value="<?php echo $mailPort; ?>"/>
                            <label class="mdl-textfield__label" for="mail_port"><?php echo __('Puerto'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"></td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_authenabled">
                            <input type="checkbox" id="mail_authenabled"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="mail_authenabled" <?php echo $chkMailAuth; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Habilitar Autentificación'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Usuario'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="mail_user" name="mail_user" type="email"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="50" value="<?php echo $mailUser; ?>"/>
                            <label class="mdl-textfield__label" for="mail_user"><?php echo __('Usuario'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Clave'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="mail_pass" name="mail_pass" type="password"
                                   class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                                   maxlength="128" value="<?php echo $mailPass; ?>"/>
                            <label class="mdl-textfield__label" for="mail_pass"><?php echo __('Clave'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Seguridad'); ?>
                    </td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Seguridad'); ?></div>

                        <select name="mail_security" id="sel-mailsecurity" size="1" class="select-box sel-chosen-ns">
                            <option value=""><?php echo __('Deshabilitada'); ?></option>
                            <?php foreach ($mailSecurity as $security): ?>
                                <option
                                    value="<?php echo $security; ?>" <?php echo ($currentMailSecurity == $security) ? 'selected' : ''; ?>><?php echo $security; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <?php echo __('Dirección de correo de envío'); ?>
                    </td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="mail_from" name="mail_from" type="email"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   maxlength="128" value="<?php echo $mailFrom; ?>"/>
                            <label class="mdl-textfield__label"
                                   for="mail_from"><?php echo __('Dirección de correo de envío'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-mail_requestsenabled"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-mail_requestsenabled">
                            <p>
                                <?php echo __('Habilita que los usuarios puedan solicitar modificaciones o acceso a las cuentas sin permisos.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_requestsenabled">
                            <input type="checkbox" id="mail_requestsenabled"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="mail_requestsenabled" <?php echo $chkMailRequests; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Habilitar peticiones por correo'); ?></span>
                        </label>
                    </td>
                </tr>
            </table>

            <input type="hidden" name="activeTab" value="<?php echo $mail_tabIndex; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $mail_actionId; ?>"/>
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
                    <button form="frmMail"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                            title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                        <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div> <!-- End Tab - Mail -->