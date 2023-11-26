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
?>

<div id="title" class="titleNormal">
    <i class="material-icons">info</i>
    <?php

    use SP\Domain\Config\Ports\ConfigDataInterface;
    use SP\Domain\Core\UI\ThemeIconsInterface;
    use SP\Domain\Install\Services\InstallerService;
    use SP\Mvc\View\TemplateInterface;
    use SP\Plugin\PluginInterface;
    use SP\Util\VersionUtil;

    use function SP\__;

    echo __('Application Information'); ?>
</div>

<table class="data">
    <tr>
        <td class="descField">
            <?php
            echo __('sysPass version'); ?>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('sysPass version'); ?></div>

            <?php
            printf(
                '%s (<b>%s</b>)',
                InstallerService::VERSION_TEXT,
                VersionUtil::getVersionStringNormalized()
            ); ?>
            <br>
            <?php
            printf('Config: %s', $configData->getConfigVersion()); ?>
            <br>
            <?php
            printf('App: %s', $configData->getAppVersion()); ?>
            <br>
            <?php
            printf('DB: %s', $configData->getDatabaseVersion()); ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Database'); ?>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('Database'); ?></div>

            <?php
            foreach ($_getvar('dbInfo') as $infoattr => $infoval): ?>
                <?php
                printf('<b>%s</b> : %s', $infoattr, $infoval); ?>
                <br>
            <?php
            endforeach; ?>
            <?php
            printf('<b>%s</b>: %s', __('Name'), (!$_getvar('isDemo')) ? $_getvar('dbName') : '***'); ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('PHP'); ?>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('PHP'); ?></div>

            <?php
            printf('<b>%s</b>: %s', __('Version'), PHP_VERSION); ?>
            <br>
            <?php
            printf('<b>%s</b>: %s', __('Extensions'), implode(', ', get_loaded_extensions())); ?>
            <br>
            <?php
            if (!empty($_getvar('missingExtensions'))) {
                printf(
                    '<b>%s</b>: %s',
                    __('Unavailable extensions'),
                    implode(', ', $_getvar('missingExtensions'))
                );
                echo '<br>';
            }
            ?>
            <?php
            printf('<b>%s</b>: %d KB', __('Used Memory'), (memory_get_usage(true) / 1024)); ?>
            <br>
            <?php
            printf('<b>%s</b>: %s', __('User'), (!$_getvar('isDemo')) ? get_current_user() : '***'); ?>
            <br>
            <?php
            printf('<b>%s</b>: %d MB/s', __('Download rate'), $_getvar('downloadRate')); ?>
            <?php
            if (function_exists('opcache_get_status') && PHP_VERSION_ID >= 50600): ?>
                <br>
                <?php
                printf('<b>%s</b>', __('OP Cache'));
                foreach (opcache_get_status(false)['opcache_statistics'] as $key => $value):
                    printf('<br/>%s : %s', $key, $value);
                endforeach;
                ?>
            <?php
            endif; ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Server'); ?>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('Server'); ?>
            </div>
            <?php
            printf('<b>%s</b>', !$_getvar('isDemo') ? $_SERVER['SERVER_SOFTWARE'] : '***');
            ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Configuration Backup'); ?>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('Configuration Backup'); ?></div>

            <?php
            echo $_getvar('configBackupDate'); ?>

            <?php
            if ($_getvar('downloadLog')): ?>
                <div>
                    <a href="<?php
                    echo $_getRoute('configGeneral/downloadConfigBackup/json'); ?>"
                       class="download" target="_blank">

                        <button type="button" class="mdl-button mdl-js-button">
                            <?php
                            printf(__('Download %s'), 'JSON'); ?>
                        </button>
                    </a>
                </div>
            <?php
            endif; ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Language'); ?>
            <div id="help-infolang"
                 class="icon material-icons <?php
                 echo $icons->help()->getClass(); ?>"><?php
                echo $icons->help()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-infolang">
                <p>
                    <?php
                    echo __('Tells whether the language is available or not.'); ?>
                </p>

                <p>
                    <?php
                    echo __(
                        'If it is not installed, you would need install the right operating system locales. More info at Wiki.'
                    ); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('Language'); ?></div>

            <?php
            echo $_getvar('locale'); ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Encrypted Session'); ?>
            <div id="help-infosession"
                 class="icon material-icons <?php
                 echo $icons->help()->getClass(); ?>"><?php
                echo $icons->help()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-infosession">
                <p>
                    <?php
                    echo __('Tells whether the session data are encrypted in the server side or not'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('Encrypted Session'); ?></div>

            <?php
            echo $_getvar('securedSession') ? __('Yes') : __('No'); ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php
            echo __('Loaded Plugins'); ?>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php
                echo __('Loaded Plugins'); ?></div>

            <ul class="mdl-list">
                <?php
                /** @var PluginInterface $plugin */
                foreach ($_getvar('plugins') as $name => $plugin): ?>
                    <li class="mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">extension</i>
                                    <?php
                                    echo $name; ?>
                                    <br>
                                    <?php
                                    printf('Version: %s', implode('.', $plugin->getVersion())); ?>
                                    <br>
                                    <?php
                                    printf('Autor: %s', $plugin->getAuthor()); ?>
                                </span>
                    </li>
                <?php
                endforeach; ?>
            </ul>
        </td>
    </tr>

    <?php
    if ($_getvar('downloadLog')): ?>
        <tr>
            <td class="descField">
                <?php
                echo __('Log file'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Log file'); ?></div>

                <a href="<?php
                echo $_getRoute('configGeneral/downloadLog'); ?>"
                   class="download" target="_blank">

                    <button type="button" class="mdl-button mdl-js-button">
                        <?php
                        printf(__('Download %s'), basename(LOG_FILE)); ?>
                    </button>
                </a>
            </td>
        </tr>
    <?php
    endif; ?>
</table>

<div class="tab-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->back()->getClassButton(); ?>"
                    title="<?php
                    echo __('Back'); ?>">
                <i class="material-icons"><?php
                    echo $icons->back()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
