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
 * @var AccountExtData                 $accountData
 * @var AccountAcl                     $accountAcl
 * @var \SP\Domain\Config\Ports\ConfigDataInterface $configData
 */

use SP\Core\UI\ThemeIcons;
use SP\DataModel\AccountExtData;
use SP\Domain\Account\Services\AccountAcl;
use SP\Html\DataGrid\Action\DataGridActionInterface;

?>

<div class="item-actions">
    <ul>
        <?php /** @var DataGridActionInterface $action */
        foreach ($_getvar('accountActions') as $action): ?>
            <li>
                <button id="<?php echo $action->getId(); ?>"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $action->getClassesAsString(), ' ', $action->getIcon()->getClassButton(); ?>"
                    <?php foreach ($action->getData() as $key => $value): echo 'data-', $key, '="', $value, '"'; endforeach; ?>
                    <?php foreach ($action->getAttributes() as $key => $value): echo $key, '="', $value, '"'; endforeach; ?>
                        title="<?php echo $action->getTitle(); ?>">
                    <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
    $accountActionsMenu = $_getvar('accountActionsMenu');
    if ($accountActionsMenu && count($accountActionsMenu) > 0): ?>
        <div>
            <button id="actions-menu-account"
                    class="mdl-button mdl-js-button mdl-button--icon">
                <i class="material-icons">more_vert</i>
            </button>
            <ul class="mdl-menu mdl-menu--top-right mdl-js-menu mdl-js-ripple-effect"
                for="actions-menu-account">
                <?php foreach ($accountActionsMenu as $action): ?>
                    <li class="mdl-menu__item btn-action <?php echo $action->getClassesAsString(); ?>"
                        id="<?php echo $action->getId(); ?>"
                        <?php foreach ($action->getData() as $key => $value): echo 'data-', $key, '="', $value, '"'; endforeach; ?>
                        <?php foreach ($action->getAttributes() as $key => $value): echo $key, '="', $value, '"'; endforeach; ?>
                        title="<?php echo $action->getTitle(); ?>">
                        <i class="material-icons <?php echo $action->getIcon()->getClass(); ?>"><?php echo $action->getIcon()->getIcon(); ?></i>
                        <?php echo $action->getName(); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
