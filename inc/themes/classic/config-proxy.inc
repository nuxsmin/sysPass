<div id="title" class="midroundup titleNormal">
    <?php echo _('Proxy'); ?>
</div>

<table id="tblProxy" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Usar Proxy'); ?>
        </td>
        <td class="valField">
            <label for="proxy_enabled"><?php echo ($chkProxy) ? _('SI') : _('NO'); ?></label>
            <input type="checkbox" name="proxy_enabled" id="proxy_enabled"
                   class="checkbox" <?php echo $chkProxy, ' ', $isDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Servidor'); ?>
        </td>
        <td class="valField">
            <input type="text" name="proxy_server" value="<?php echo $proxyServer; ?>"
                   maxlength="128"/>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Puerto'); ?>
        </td>
        <td class="valField">
            <input type="number" name="proxy_port" size="20" pattern="[0-9]{1,5}" step="1" max="65535"
                   value="<?php echo $proxyPort; ?>" maxlength="5"/>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Usuario'); ?>
        </td>
        <td class="valField">
            <input type="text" name="proxy_user" value="<?php echo $proxyServer; ?>"
                   maxlength="128"/>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Clave'); ?>
        </td>
        <td class="valField">
            <input type="password" name="proxy_pass" class="passwordfield__input"
                   value="<?php echo $proxyServer; ?>" maxlength="128"/>
        </td>
    </tr>

</table>