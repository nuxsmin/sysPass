<div id="title" class="midroundup titleNormal">
    <?php echo _('Eventos'); ?>
</div>

<table id="tblEvents" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Habilitar log de eventos'); ?>

            <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
            <div class="tooltip" for="help-eventlog" style="display: none;">
                <p>
                    <?php echo _('Guarda las acciones realizadas en la aplicación.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label for="log_enabled"><?php echo ($chkLog) ? _('SI') : _('NO'); ?></label>
            <input type="checkbox" name="log_enabled" id="log_enabled"
                   class="checkbox" <?php echo $chkLog, ' ', $isDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Habilitar Syslog'); ?>
        </td>
        <td class="valField">
            <label for="syslog_enabled"><?php echo ($chkSyslog) ? _('SI') : _('NO'); ?></label>
            <input type="checkbox" name="syslog_enabled" id="syslog_enabled"
                   class="checkbox" <?php echo $chkSyslog, ' ', $isDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Habilitar Syslog Remoto'); ?>
        </td>
        <td class="valField">
            <label for="remotesyslog_enabled"><?php echo ($chkRemoteSyslog) ? _('SI') : _('NO'); ?></label>
            <input type="checkbox" name="remotesyslog_enabled" id="remotesyslog_enabled"
                   class="checkbox" <?php echo $chkRemoteSyslog, ' ', $isDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Servidor'); ?>
        </td>
        <td class="valField">
            <input type="text" name="remotesyslog_server" value="<?php echo $remoteSyslogServer; ?>"
                   maxlength="128"/>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Puerto'); ?>
        </td>
        <td class="valField">
            <input type="number" name="remotesyslog_port" size="20" pattern="[0-9]{1,5}" step="1" max="65535"
                   value="<?php echo $remoteSyslogPort; ?>" maxlength="5"/>
        </td>
    </tr>
</table>