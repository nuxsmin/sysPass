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
 * @var callable $_getvar
 * @var ConfigDataInterface $configData
 * @var AccountSearchItem $account
 * @var DataGrid $data
 */

use SP\Core\UI\ThemeIconsInterface;
use SP\Domain\Account\Services\AccountSearchItem;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Html\DataGrid\DataGrid;

if (!isset($data)) {
    $data = $_getvar('data');
}
?>

<div id="res-content">
    <?php
    if ($data->getData()->getDataCount() === 0): ?>
        <div class="noRes round"><?php
            echo __('No records found'); ?></div>
    <?php
    else: ?>
        <!-- Data Header-->
        <div id="search-header">
            <?php
            include $data->getDataHeaderTemplate(); ?>
        </div>

        <?php
        if (AccountSearchItem::$topNavbar): ?>
            <!-- Data Pager -->
            <div id="search-pager-top">
                <?php
                include $data->getDataPagerTemplate(); ?>
            </div>
        <?php
        endif; ?>

        <!-- Data Rows-->
        <div id="search-rows">
            <?php
            include $data->getDataRowTemplate(); ?>
        </div>

        <!-- Data Pager -->
        <div id="search-pager-bottom">
            <?php
            include $data->getDataPagerTemplate(); ?>
        </div>
    <?php
    endif; ?>
</div>
