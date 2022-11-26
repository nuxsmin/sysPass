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
 * @var ThemeIcons                     $icons
 * @var callable                       $_getvar
 * @var \SP\Domain\Config\Ports\ConfigDataInterface $configData
 */

use SP\Core\UI\ThemeIcons;

?>

<div id="data-linkedAccounts">
    <div class="data">
        <ul class="mdl-list">
            <?php foreach ($_getvar('linkedAccounts') as $account): ?>
                <li class="mdl-list__item">
                    <span class="btn-action mdl-list__item-primary-content">
                        <i class="material-icons mdl-list__item-icon">layers</i>
                        <?php printf('%s (%s)', htmlspecialchars($account->name, ENT_QUOTES), htmlspecialchars($account->clientName, ENT_QUOTES)); ?>
                    </span>
                    <span class="list-actions">
                        <i class="material-icons btn-action mdl-list__item-icon <?php echo $icons->getIconEdit()->getClass(); ?>"
                           title="<?php echo __('Edit Account'); ?>"
                           data-action-route="account/edit"
                           data-item-id="<?php echo $account->id; ?>"
                           data-onclick="account/edit">
                            <?php echo $icons->getIconEdit()->getIcon(); ?></i>

                        <i class="material-icons btn-action mdl-list__item-icon <?php echo $icons->getIconView()->getClass(); ?>"
                           title="<?php echo __('Account Details'); ?>"
                           data-action-route="account/view"
                           data-item-id="<?php echo $account->id; ?>"
                           data-onclick="account/view">
                        <?php echo $icons->getIconView()->getIcon(); ?></i>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
