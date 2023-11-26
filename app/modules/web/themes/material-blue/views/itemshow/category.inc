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
 * @var  CategoryData $category
 * @var \SP\Domain\Core\UI\ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\DataModel\CategoryData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\TemplateInterface;

$category = $_getvar('category');
?>
<div id="box-popup">
    <h2 class="center"><?php
        echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmCategories" id="frmCategories"
          class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php
          echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php
                    echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php
                               echo htmlspecialchars($category->getName(), ENT_QUOTES); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php
                            echo __('Category name'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Description'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="description" name="description" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php
                               echo htmlspecialchars($category->getDescription(), ENT_QUOTES); ?>">
                        <label class="mdl-textfield__label"
                               for="description"><?php
                            echo __('Category description'); ?></label>
                    </div>
                </td>
            </tr>
            <?php
            if ($_getvar('customFields')): ?>
                <?php
                include $this->includeTemplate('aux-customfields', 'common'); ?>
            <?php
            endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                echo $icons->save()->getClassButton(); ?>"
                form="frmCategories"
                title="<?php
                echo $icons->save()->getTitle(); ?>">
            <i class="material-icons"><?php
                echo $icons->save()->getIcon(); ?></i>
        </button>
    </div>
</div>
