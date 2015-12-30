<div id="title" class="midroundup titleNormal">
    <?php echo _('Eventos'); ?>
</div>

<table id="tblEvents" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Habilitar log de eventos'); ?>
            <div id="help-eventlog" class="icon material-icons fg-blue80">help_outline</div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-eventlog">
                <p>
                    <?php echo _('Guarda las acciones realizadas en la aplicación.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="log_enabled">
                <input type="checkbox" id="log_enabled" class="mdl-switch__input fg-blue100" name="log_enabled"
                    <?php echo $chkLog, ' ', $isDisabled; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Habilitar Syslog'); ?>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="syslog_enabled">
                <input type="checkbox" id="syslog_enabled" class="mdl-switch__input fg-blue100" name="syslog_enabled"
                    <?php echo $chkSyslog, ' ', $isDisabled; ?>/>
                <span class="mdl-switch__label"></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Habilitar Syslog Remoto'); ?>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="remotesyslog_enabled">
                <input type="checkbox" id="remotesyslog_enabled" class="mdl-switch__input fg-blue100" name="remotesyslog_enabled"
                    <?php echo $chkRemoteSyslog, ' ', $isDisabled; ?>/>
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
                <input id="remotesyslog_server" name="remotesyslog_server" type="text"
                       class="mdl-textfield__input fg-blue100"
                       value="<?php echo $remoteSyslogServer; ?>"/>
                <label class="mdl-textfield__label"
                       for="remotesyslog_server"><?php echo _('Nombre o dirección IP'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Puerto'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="remotesyslog_port" name="remotesyslog_port" type="number"
                       pattern="[0-9]{1-5}" step="1" max="65535" class="mdl-textfield__input fg-blue100"
                       value="<?php echo $remoteSyslogPort; ?>"/>
                <label class="mdl-textfield__label"
                       for="remotesyslog_port"><?php echo _('Puerto'); ?></label>
            </div>
        </td>
    </tr>
</table>