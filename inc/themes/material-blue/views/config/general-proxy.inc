<div id="title" class="midroundup titleNormal">
    <?php echo __('Proxy'); ?>
</div>

<table id="tblProxy" class="data tblConfig round">
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="proxy_enabled">
                <input type="checkbox" id="proxy_enabled" class="mdl-switch__input mdl-color-text--indigo-400"
                       name="proxy_enabled"
                    <?php echo $chkProxy; ?>/>
                <span class="mdl-switch__label"><?php echo __('Usar Proxy'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Servidor'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_server" name="proxy_server" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="128" value="<?php echo $proxyServer; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_server"><?php echo __('Servidor proxy'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Puerto'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_port" name="proxy_port" type="number"
                       class="mdl-textfield__input mdl-color-text--indigo-400" pattern="[0-9]{1,5}"
                       maxlength="128" value="<?php echo $proxyPort; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_port"><?php echo __('Puerto del servidor proxy'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Usuario'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_user" name="proxy_user" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="128" value="<?php echo $proxyUser; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_user"><?php echo __('Usuario del servidor proxy'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Clave'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_pass" name="proxy_pass" type="password"
                       class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                       maxlength="128" value="<?php echo $proxyPass; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_pass"><?php echo __('Clave del servidor proxy'); ?></label>
            </div>
        </td>
    </tr>

</table>