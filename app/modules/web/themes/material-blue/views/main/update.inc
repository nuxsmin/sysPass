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

use SP\Core\UI\ThemeIconsInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Mvc\View\TemplateInterface;

?>

<?php
if ($_getvar('numNotices', 0) > 0): ?>
    <a href="https://github.com/nuxsmin/sysPass/labels/Notices" target="_blank">
        <div id="notices-info"
             class="material-icons mdl-badge mdl-badge--overlap mdl-color-text--amber-200"
             data-badge="<?php
             echo $_getvar('numNotices'); ?>">feedback
        </div>
    </a>
    <span for="notices-info"
          class="mdl-tooltip mdl-tooltip--top"><?php
        echo $_getvar('noticesTitle'); ?></span>
<?php
endif; ?>

<?php
if ($_getvar('hasUpdates')): ?>
    <a id="link-updates" href="<?php
    echo $_getvar('url'); ?>" target="_blank">
        <?php
        echo $_getvar('title'); ?>
        &nbsp;&nbsp;
        <div id="help-hasupdates"
             class="icon material-icons mdl-color-text--indigo-200">
            cloud_download
        </div>
    </a>
    <span for="link-updates"
          class="mdl-tooltip mdl-tooltip--top"><?php
        echo $_getvar('description'); ?></span>
<?php
elseif ($_getvar('updateStatus') === true): ?>
    <div id="updates-info" class="icon material-icons mdl-color-text--teal-200">
        check_circle
    </div>
    <span for="updates-info"
          class="mdl-tooltip mdl-tooltip--top"><?php
        echo __('Updated'); ?></span>
<?php
elseif ($_getvar('updateStatus') === false): ?>
    <div id="updates-info"
         class="icon material-icons mdl-color-text--amber-200">
        warning
    </div>
    <span for="updates-info"
          class="mdl-tooltip mdl-tooltip--top"><?php
        echo __('Error while checking for updates'); ?></span>
<?php
endif; ?>
