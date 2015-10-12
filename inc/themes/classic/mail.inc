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
                    <label for="mailenabled"><?php echo ($chkMail) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="mail_enabled" id="mailenabled"
                           class="checkbox" <?php echo $chkMail, ' ', $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_server" size="20" value="<?php echo $mailServer; ?>" maxlength="128"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Puerto'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_port" size="20" value="<?php echo $mailPort; ?>" maxlength="5"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar Autentificación'); ?>
                </td>
                <td class="valField">
                    <label for="mailauthenabled"><?php echo ($chkMailAuth) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="mail_authenabled" id="mailauthenabled"
                           class="checkbox" <?php echo $chkMailAuth, ' ', $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Usuario'); ?>
                </td>
                <td class="valField">
                    <input type="text" name="mail_user" size="20" value="<?php echo $mailUser; ?>" maxlength="50"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave'); ?>
                </td>
                <td class="valField">
                    <input type="password" id="mail_passR" name="mail_pass" size="20" class="passwordfield__input-show" value="<?php echo $mailPass; ?>" maxlength="50"/>
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
                    <input type="text" name="mail_from" size="20" value="<?php echo $mailFrom; ?>" maxlength="128"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar peticiones por correo'); ?>
                </td>
                <td class="valField">
                    <label for="mailrequestsenabled"><?php echo ($chkMailRequests) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="mail_requestsenabled" id="mailrequestsenabled"
                           class="checkbox" <?php echo $chkMailRequests, ' ', $isDisabled; ?> />
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
        <button type="submit" form="frmMail" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Core\Init::$WEBURI; ?>imgs/check.png" alt="save" />
        </button>
    </div>
</div> <!-- End Tab - Mail -->