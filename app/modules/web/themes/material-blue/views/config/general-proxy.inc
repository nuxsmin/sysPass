<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\TemplateInterface;

?>

<div id="title" class="titleNormal">
    <i class="material-icons">device_hub</i>
    <?php
    echo __('Proxy'); ?>
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
                                   name="proxy_enabled" <?php
                            echo $configData->isProxyEnabled() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php
                            echo __('Use Proxy'); ?></span>
                        <span class="mdl-list__item-sub-title"><?php
                            echo __('Use a proxy server for checking out for updates and notifications.'); ?></span>
                    </span>
                </li>
            </ul>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Server'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_server" name="proxy_server" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="128"
                       value="<?php
                       echo htmlspecialchars($configData->getProxyServer(), ENT_QUOTES); ?>">
                <label class="mdl-textfield__label"
                       for="proxy_server"><?php
                    echo __('Proxy server'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Port'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_port" name="proxy_port" type="number"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       pattern="[0-9]{1,5}"
                       maxlength="128"
                       value="<?php
                       echo $configData->getProxyPort(); ?>">
                <label class="mdl-textfield__label"
                       for="proxy_port"><?php
                    echo __('Proxy server port'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('User'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_user" name="proxy_user" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="128"
                       value="<?php
                       echo htmlspecialchars($configData->getProxyUser(), ENT_QUOTES); ?>">
                <label class="mdl-textfield__label"
                       for="proxy_user"><?php
                    echo __('Proxy server user'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Password'); ?>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="proxy_pass" name="proxy_pass" type="password"
                       class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                       maxlength="128"
                       value="<?php
                       echo $configData->getProxyPass() ? '***' : ''; ?>">
                <label class="mdl-textfield__label"
                       for="proxy_pass"><?php
                    echo __('Proxy server password'); ?></label>
            </div>
        </td>
    </tr>

</table>
