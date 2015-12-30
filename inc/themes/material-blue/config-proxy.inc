<div id="title" class="midroundup titleNormal">
    <?php echo _('Proxy'); ?>
</div>

<table id="tblProxy" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Usar Proxy'); ?>
        </td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="proxy_enabled">
                <input type="checkbox" id="proxy_enabled" class="mdl-switch__input fg-blue100"
                       name="proxy_enabled"
                    <?php echo $chkProxy, ' ', $isDisabled; ?>/>
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
                <input id="proxy_server" name="proxy_server" type="text"
                       class="mdl-textfield__input fg-blue100"
                       maxlength="128" value="<?php echo $proxyServer; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_server"><?php echo _('Servidor proxy'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Puerto'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_port" name="proxy_port" type="number"
                       class="mdl-textfield__input fg-blue100" pattern="[0-9]{1,5}"
                       maxlength="128" value="<?php echo $proxyPort; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_port"><?php echo _('Puerto del servidor proxy'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Usuario'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_user" name="proxy_user" type="text"
                       class="mdl-textfield__input fg-blue100"
                       maxlength="128" value="<?php echo $proxyUser; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_user"><?php echo _('Usuario del servidor proxy'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Clave'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_pass" name="proxy_pass" type="password"
                       class="mdl-textfield__input passwordfield__input fg-blue100"
                       maxlength="128" value="<?php echo $proxyPass; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_pass"><?php echo _('Clave del servidor proxy'); ?></label>
            </div>
        </td>
    </tr>

</table>