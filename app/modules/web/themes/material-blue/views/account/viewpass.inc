<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 * @var callable                       $_getvar
 * @var ThemeIcons                     $icons
 * @var AccountExtData                 $accountData
 * @var AccountAcl                     $accountAcl
 * @var \SP\Domain\Config\Ports\ConfigDataInterface $configData
 */

use SP\Core\UI\ThemeIcons;
use SP\DataModel\AccountExtData;
use SP\Domain\Account\Services\AccountAcl;

?>
<div id="box-popup" class="box-password-view">
    <h2 class="center"><?php echo $_getvar('header'); ?>&nbsp;
        <?php if ($_getvar('isLinked')): ?>
            <i title="<?php echo __('Linked Account'); ?>"
               class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
        <?php endif; ?>
    </h2>
    <table>
        <tbody>
        <tr>
            <?php if (!$_getvar('isImage')): ?>
                <td class="dialog-text dialog-user-text"><?php echo wordwrap($_getvar('login'), 50, PHP_EOL, true); ?></td>
                <td class="dialog-button right">
                    <button class="dialog-clip-button mdl-button mdl-js-button mdl-js-ripple-effect"
                            title="<?php echo __('Copy Username'); ?>"
                            data-clipboard-target=".dialog-user-text">
                        <?php echo __('User'); ?>
                        <i class="material-icons"><?php echo $icons->getIconClipboard()->getIcon(); ?></i>
                    </button>
                </td>
            <?php else: ?>
                <td class="dialog-user-text">
                    <img src="data:image/png;base64,<?php echo $_getvar('login'); ?>"/>
                </td>
                <td>&nbsp;</td>
            <?php endif; ?>
        </tr>
        <tr>
            <?php if (!$_getvar('isImage')): ?>
                <td class="dialog-text dialog-pass-text"><?php echo wordwrap($_getvar('pass'), 50, PHP_EOL, true); ?></td>
                <td class="dialog-button right">
                    <button class="dialog-clip-button mdl-button mdl-js-button mdl-js-ripple-effect"
                            title="<?php echo __('Copy Password'); ?>"
                            data-clipboard-target=".dialog-pass-text">
                        <?php echo __('Password'); ?>
                        <i class="material-icons"><?php echo $icons->getIconClipboard()->getIcon(); ?></i>
                    </button>
                </td>
            <?php else: ?>
                <td class="dialog-pass-text">
                    <img src="data:image/png;base64,<?php echo $_getvar('pass'); ?>"/>
                </td>
                <td>&nbsp;</td>
            <?php endif; ?>
        </tr>
        </tbody>
    </table>
</div>
