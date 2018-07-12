<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData $configData
 */
?>

<div id="title" class="titleNormal">
    <i class="material-icons">view_headline</i>
    <?php echo __('Eventos'); ?>
</div>

<table id="tblEvents" class="data tblConfig">
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <ul class="config-events-list-action mdl-list">
                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="log_enabled">
                            <input type="checkbox" id="log_enabled"
                                   class="mdl-switch__input"
                                   name="log_enabled" <?php echo $configData->isLogEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Habilitar log de eventos'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php echo __('Guarda las acciones realizadas en la aplicación.'); ?></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="syslog_enabled">
                            <input type="checkbox" id="syslog_enabled"
                                   class="mdl-switch__input"
                                   name="syslog_enabled" <?php echo $configData->isSyslogEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Habilitar Syslog'); ?></span>
                        <span class="mdl-list__item-sub-title"></span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="remotesyslog_enabled">
                            <input type="checkbox" id="remotesyslog_enabled"
                                   class="mdl-switch__input"
                                   name="remotesyslog_enabled" <?php echo $configData->isSyslogRemoteEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Habilitar Syslog Remoto'); ?></span>
                        <span class="mdl-list__item-sub-title"></span>
                    </span>
                </li>

            </ul>
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
                       value="<?php echo $configData->getSyslogServer(); ?>"/>
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
                       value="<?php echo $configData->getSyslogPort(); ?>"/>
                <label class="mdl-textfield__label"
                       for="remotesyslog_port"><?php echo __('Puerto'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Eventos'); ?>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php echo __('Eventos'); ?></div>

            <select id="log_events" name="log_events[]" multiple="multiple" class="select-box" data-create="true">
                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $logEvents */
                foreach ($logEvents as $logEvent): ?>
                    <option
                            value="<?php echo $logEvent->getName(); ?>"
                        <?php echo $logEvent->isSelected() ? 'selected' : '' ?>><?php echo $logEvent->getName(); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
</table>