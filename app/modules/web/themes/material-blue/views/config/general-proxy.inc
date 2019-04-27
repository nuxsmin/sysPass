<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

?>

<div id="title" class="titleNormal">
    <i class="material-icons">device_hub</i>
    <?php echo __('Proxy'); ?>
</div>

<table id="tblProxy" class="data tblConfig">
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <ul class="config-proxy-list-action mdl-list">
                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="proxy_enabled">
                            <input type="checkbox" id="proxy_enabled"
                                   class="mdl-switch__input"
                                   name="proxy_enabled" <?php echo $configData->isProxyEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Use Proxy'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php echo __('Use a proxy server for checking out for updates and notifications.'); ?></span>
                    </span>
                </li>
            </ul>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Server'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_server" name="proxy_server" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="128"
                       value="<?php echo $configData->getProxyServer(); ?>">
                <label class="mdl-textfield__label"
                       for="proxy_server"><?php echo __('Proxy server'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Port'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_port" name="proxy_port" type="number"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       pattern="[0-9]{1,5}"
                       maxlength="128"
                       value="<?php echo $configData->getProxyPort(); ?>">
                <label class="mdl-textfield__label"
                       for="proxy_port"><?php echo __('Proxy server port'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('User'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_user" name="proxy_user" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="128"
                       value="<?php echo $configData->getProxyUser(); ?>">
                <label class="mdl-textfield__label"
                       for="proxy_user"><?php echo __('Proxy server user'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Password'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_pass" name="proxy_pass" type="password"
                       class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                       maxlength="128"
                       value="<?php echo $configData->getProxyPass() ? '***' : ''; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_pass"><?php echo __('Proxy server password'); ?></label>
            </div>
        </td>
    </tr>

</table>