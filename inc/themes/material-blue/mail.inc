<?php /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - Mail -->
<div id="tabs-<?php echo $mail_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Correo'); ?>
    </div>

    <form method="post" name="frmMail" id="frmMail" onsubmit="sysPassUtil.Common.configMgmt('config', this); return false;">
        <table id="tblMail" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar notificaciones de correo'); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_enabled">
                        <input type="checkbox" id="mail_enabled" class="mdl-switch__input mdl-color-text--indigo-400"
                               name="mail_enabled"
                            <?php echo $chkMail, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_server" name="mail_server" type="text" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="128" value="<?php echo $mailServer; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="mail_server"><?php echo _('Servidor'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Puerto'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_port" name="mail_port" type="number" pattern="[0-9]{1,5}"
                               class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="5"
                               value="<?php echo $mailPort; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="mail_port"><?php echo _('Puerto'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar Autentificación'); ?>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_authenabled">
                        <input type="checkbox" id="mail_authenabled" class="mdl-switch__input mdl-color-text--indigo-400"
                               name="mail_authenabled" <?php echo $chkMailAuth, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Usuario'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_user" name="mail_user" type="email" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo $mailUser; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="mail_user"><?php echo _('Usuario'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_pass" name="mail_pass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               maxlength="128" value="<?php echo $mailPass; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="mail_pass"><?php echo _('Clave'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Seguridad'); ?>
                </td>
                <td class="valField">
                    <select name="mail_security" id="sel-mailsecurity" size="1" class="sel-chosen-ns">

                        <?php echo '<option>' . _('Deshabilitada') . '</option>'; ?>
                        <?php foreach ($mailSecurity as $security): ?>
                            <?php echo "<option ", ($currentMailSecurity == $security) ? "SELECTED" : "", ">$security</option>"; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Dirección de correo de envío'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mail_from" name="mail_from" type="email" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="128" value="<?php echo $mailFrom; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label"
                               for="mail_from"><?php echo _('Dirección de correo de envío'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar peticiones por correo'); ?>

                    <div id="help-mail_requestsenabled" class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-mail_requestsenabled">
                        <p>
                            <?php echo _('Habilita que los usuarios puedan solicitar modificaciones o acceso a las cuentas sin permisos.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_requestsenabled">
                        <input type="checkbox" id="mail_requestsenabled" class="mdl-switch__input mdl-color-text--indigo-400"
                               name="mail_requestsenabled" <?php echo $chkMailRequests, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
        </table>

        <?php if ($isDemoMode): ?>
            <input type="hidden" name="mail_enabled" value="0"/>
        <?php endif; ?>

        <input type="hidden" name="activeTab" value="<?php echo $mail_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $mail_actionId; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button form="frmMail"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div> <!-- End Tab - Mail -->