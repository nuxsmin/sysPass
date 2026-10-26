<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 * @var SessionTimeout $sessionTimeout
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\ItemPreset\Models\SessionTimeout;
use SP\Mvc\View\TemplateInterface;

use function SP\__;

$sessionTimeout = $_getvar('sessionTimeout');
?>

<table class="popup-data">
    <tbody>
    <tr>
        <td class="descField"><?php
            echo __('IP Address'); ?></td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="ip_address" name="ip_address" type="text" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="33"
                       value="<?php
                       printf('%s/%s', $sessionTimeout->getAddress(), $sessionTimeout->getMask()); ?>">
                <label class="mdl-textfield__label"
                       for="ip_address"><?php
                    echo __('IP Address'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField"><?php
            echo __('Timeout'); ?></td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="timeout" name="timeout" type="number" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       min="0" max="604800"
                       value="<?php
                       echo $sessionTimeout->getTimeout(); ?>">
                <label class="mdl-textfield__label"
                       for="ip_address"><?php
                    echo __('Timeout'); ?></label>
            </div>
        </td>
    </tr>
    </tbody>
</table>
