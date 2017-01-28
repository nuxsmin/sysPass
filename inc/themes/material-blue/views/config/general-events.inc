<?php /** @var $icons \Theme\Icons */ ?>

<div id="title" class="midroundup titleNormal">
    <?php echo __('Eventos'); ?>
</div>

<table id="tblEvents" class="data tblConfig round">
    <tr>
        <td class="descField">
            <div id="help-eventlog" class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-eventlog">
                <p>
                    <?php echo __('Guarda las acciones realizadas en la aplicación.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="log_enabled">
                <input type="checkbox" id="log_enabled" class="mdl-switch__input mdl-color-text--indigo-400" name="log_enabled"
                    <?php echo $chkLog; ?>/>
                <span class="mdl-switch__label"><?php echo __('Habilitar log de eventos'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="syslog_enabled">
                <input type="checkbox" id="syslog_enabled" class="mdl-switch__input mdl-color-text--indigo-400" name="syslog_enabled"
                    <?php echo $chkSyslog; ?>/>
                <span class="mdl-switch__label"><?php echo __('Habilitar Syslog'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="remotesyslog_enabled">
                <input type="checkbox" id="remotesyslog_enabled" class="mdl-switch__input mdl-color-text--indigo-400" name="remotesyslog_enabled"
                    <?php echo $chkRemoteSyslog; ?>/>
                <span class="mdl-switch__label"><?php echo __('Habilitar Syslog Remoto'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Servidor'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="remotesyslog_server" name="remotesyslog_server" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       value="<?php echo $remoteSyslogServer; ?>"/>
                <label class="mdl-textfield__label"
                       for="remotesyslog_server"><?php echo __('Nombre o dirección IP'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Puerto'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="remotesyslog_port" name="remotesyslog_port" type="number"
                       pattern="[0-9]{1-5}" step="1" max="65535" class="mdl-textfield__input mdl-color-text--indigo-400"
                       value="<?php echo $remoteSyslogPort; ?>"/>
                <label class="mdl-textfield__label"
                       for="remotesyslog_port"><?php echo __('Puerto'); ?></label>
            </div>
        </td>
    </tr>
</table>