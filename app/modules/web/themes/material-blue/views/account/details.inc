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


use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;

use function SP\__;

/**
 * @var ThemeIconsInterface $icons
 * @var callable $_getvar
 * @var AccountView $accountView
 * @var AccountPermission $accountAcl
 * @var ConfigDataInterface $configData
 */

?>

<div id="data-details">
    <table class="data">
        <tr>
            <td class="descField"><?php
                echo __('Visits'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Visits'); ?></div>

                <?php
                echo $accountView->getCountView() . '(' . $accountView->getCountDecrypt() . ')'; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php
                echo __('Date Added'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Date Added'); ?></div>

                <?php
                echo $accountView->getDateAdd() ?>
            </td>
        </tr>

        <?php
        if ($_getvar('isModified') && $accountView->getUserEditName()): ?>
            <tr>
                <td class="descField"><?php
                    echo __('Last Modification'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Last Modification'); ?></div>

                    <?php
                    printf(
                        '%s (%s)',
                        $accountView->getDateEdit(),
                        htmlspecialchars($accountView->getUserEditName(), ENT_QUOTES)
                    ); ?>
                </td>
            </tr>
        <?php
        endif; ?>

        <tr>
            <td class="descField"><?php
                echo __('Owner'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Owner'); ?></div>

                <?php
                echo htmlspecialchars($accountView->getUserName(), ENT_QUOTES) ?: htmlspecialchars(
                    $accountView->getUserLogin(),
                    ENT_QUOTES
                ); ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php
                echo __('Main Group'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Main Group'); ?></div>

                <?php
                echo htmlspecialchars($accountView->getUserGroupName(), ENT_QUOTES); ?>
            </td>
        </tr>

        <?php
        if ($accountAcl->isModified()): ?>
            <tr>
                <td class="descField"><?php
                    echo __('Date Modified'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Date Modified'); ?></div>

                    <?php
                    echo $accountView->getDateEdit(); ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Editor'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Editor'); ?></div>

                    <?php
                    echo htmlspecialchars($accountView->getUserEditName(), ENT_QUOTES) ?: htmlspecialchars(
                        $accountView->getUserEditLogin(),
                        ENT_QUOTES
                    ); ?>
                </td>
            </tr>
        <?php
        endif; ?>

        <tr>
            <td class="descField"><?php
                echo __('Password Date'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('Password Date'); ?></div>

                <?php
                echo $_getvar('accountPassDate'); ?>
            </td>
        </tr>

        <?php
        if ($_getvar('accountPassDateChange', 0) > 0): ?>
            <tr>
                <td class="descField"><?php
                    echo __('Password Expiry Date'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Password Expiry Date'); ?></div>

                    <?php
                    echo $_getvar('accountPassDateChange'); ?>
                </td>
            </tr>
        <?php
        endif; ?>
        <tr>
            <td class="descField"><?php
                echo __('ID'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php
                    echo __('ID'); ?></div>

                <?php
                echo $accountView->getId(); ?>
            </td>
        </tr>
    </table>
</div>
