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
 * @var PublicLinkList $publicLink
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Account\Models\PublicLinkList;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\TemplateInterface;

use function SP\__;

$publicLink = $_getvar('publicLink');
?>
<div id="box-popup">
    <h2 class="center"><?php
        echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>
    <form method="post" name="frmPublickLink" id="frmPublickLink"
          class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php
          echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php
                    echo __('Account'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Account'); ?></div>

                    <select id="accountId" name="accountId"
                            class="select-box" <?php
                    echo $_getvar('disabled'); ?>>
                        <option value=""><?php
                            echo __('Select Account'); ?></option>
                        <?php
                        /** @var SelectItem $account */
                        foreach ($_getvar('accounts') as $account): ?>
                            <option
                                    value="<?php
                                    echo $account->getId(); ?>"
                                <?php
                                echo $account->isSelected() ? 'selected' : ''; ?>>
                                <?php
                                printf(
                                    '%s (%s)',
                                    htmlspecialchars($account->getName(), ENT_QUOTES),
                                    htmlspecialchars($account->getItemProperty('clientName'), ENT_QUOTES)
                                ); ?>
                            </option>
                        <?php
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Notify'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                           for="notify"
                           title="<?php
                           echo __('Notify'); ?>">
                        <input type="checkbox" id="notify"
                               class="mdl-switch__input mdl-color-text--indigo-400"
                               name="notify" <?php
                        echo $_getvar('isView') && $publicLink->isNotify() ? 'CHECKED' : ''; ?> <?php
                        echo $_getvar('disabled'); ?>/>
                        <span class="mdl-switch__label"><?php
                            echo __('Notify'); ?></span>
                    </label>
                </td>
            </tr>
            <?php
            if ($_getvar('isView')): ?>
                <tr>
                    <td class="descField"><?php
                        echo __('Creation Date'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php
                            echo __('Creation Date'); ?></div>

                        <?php
                        echo $publicLink->getDateAddFormat(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php
                        echo __('Expiry Date '); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php
                            echo __('Expiry Date '); ?></div>

                        <?php
                        echo $publicLink->getDateExpireFormat(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php
                        echo __('User'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php
                            echo __('User'); ?></div>

                        <?php
                        echo $publicLink->getUserLogin(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php
                        echo __('Visits'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php
                            echo __('Visits'); ?></div>

                        <?php
                        echo $publicLink->getCountViewsString(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php
                        echo __('Hash'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php
                            echo __('Hash'); ?></div>

                        <?php
                        echo $publicLink->getHash(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php
                        echo __('URL'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php
                            echo __('URL'); ?></div>

                        <a href="<?php
                        echo $_getvar('publicLinkURL'); ?>"
                           target="_blank"><?php
                            echo $_getvar('publicLinkURL'); ?></a>
                    </td>
                </tr>
                <?php
                if (is_array($_getvar('usageInfo'))): ?>
                    <tr>
                        <td class="descField"><?php
                            echo __('Use'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php
                                echo __('Use'); ?></div>

                            <div class="list-wrap">
                                <ul class="mdl-list">
                                    <?php
                                    foreach ($_getvar('usageInfo') as $useInfo): ?>
                                        <li class="mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">access_time</i>
                                    <?php
                                    printf(
                                        '%s - %s',
                                        $_getvar('isDemo') ? '*.*.*.*' : $useInfo['who'],
                                        date('Y-m-d H:i', $useInfo['time'])
                                    ); ?>
                                </span>
                                        </li>
                                    <?php
                                    endforeach; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php
                endif; ?>
            <?php
            endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>

    <?php
    if (!$_getvar('isView')): ?>
        <div class="action-in-box">
            <button form="frmPublickLink"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                    echo $icons->save()->getClassButton(); ?>"
                    title="<?php
                    echo $icons->save()->getTitle(); ?>">
                <i class="material-icons"><?php
                    echo $icons->save()->getIcon(); ?></i>
            </button>
        </div>
    <?php
    endif; ?>
</div>
