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
 * @var \SP\Domain\Core\UI\ThemeIconsInterface $icons
 * @var callable $_getvar
 * @var ConfigDataInterface $configData
 * @var AccountSearchItem $account
 * @var DataGridTab $data
 */

use SP\Domain\Account\Services\AccountSearchItem;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Html\DataGrid\DataGridTab;

if (!isset($data)) {
    $data = $_getvar('data');
}
?>

<div id="data-search-header" class="data-header data-header-minimal">
    <ul>
        <?php
        foreach ($data->getHeader()->getSortFields() as $field): ?>
            <li>
                <a id="btn-order-<?php
                echo $field->getSortKey(); ?>"
                   class="round <?php
                   echo ($data->getPager()->getSortKey() == $field->getSortKey()) ? 'filterOn' : ''; ?>">
                    <?php
                    echo $field->getName(); ?>
                </a>
                <span for="btn-order-<?php
                echo $field->getSortKey(); ?>"
                      class="mdl-tooltip mdl-tooltip--bottom"><?php
                    echo $field->getTitle(); ?></span>
                <?php
                $classDown = ($data->getPager()->getSortKey() == $field->getSortKey()
                              && $data->getPager()->getSortOrder() ===
                                 0) ? 'mdl-color--amber-A200' : 'md-dark md-inactive';
                $classUp = ($data->getPager()->getSortKey() == $field->getSortKey()
                            &&
                            $data->getPager()->getSortOrder() === 1) ? 'mdl-color--amber-A200' : 'md-dark md-inactive';
                ?>
                <div
                        class="icon material-icons sort-down cursor-pointer <?php
                        echo $classDown; ?>"
                        data-start="<?php
                        echo $data->getPager()->getLimitStart(); ?>"
                        data-key="<?php
                        echo $field->getSortKey(); ?>"
                        data-dir="0">
                    <?php
                    echo $field->getIconDown()->getIcon(); ?>
                </div>
                <div
                        class="icon material-icons sort-up cursor-pointer <?php
                        echo $classUp; ?>"
                        data-start="<?php
                        echo $data->getPager()->getLimitStart(); ?>"
                        data-key="<?php
                        echo $field->getSortKey(); ?>"
                        data-dir="1">
                    <?php
                    echo $field->getIconUp()->getIcon(); ?>
                </div>
            </li>
        <?php
        endforeach; ?>
        <li class="search-filter-spacer"></li>
    </ul>
</div>
