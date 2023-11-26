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
 * @var CustomFieldDefinitionData $field
 * @var \SP\Domain\Core\UI\ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\DataModel\CustomFieldDefinitionData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\TemplateInterface;

$field = $_getvar('field');
?>

<div id="box-popup">
    <h2 class="center"><?php
        echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmCustomFields" id="frmCustomFields"
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
                               echo htmlspecialchars($field->getName(), ENT_QUOTES); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php
                            echo __('Field name'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Type'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Type'); ?></div>

                    <select name="type" id="sel-type" size="1"
                            class="select-box">
                        <?php
                        /** @var SelectItem $type */
                        foreach ($_getvar('types') as $type): ?>
                            <option
                                    value='<?php
                                    echo $type->getId(); ?>' <?php
                            echo ($type->getId() === $field->getTypeId()) ? 'selected' : ''; ?>><?php
                                echo __($type->getItemProperty('text')); ?></option>
                        <?php
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Module'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php
                        echo __('Module'); ?></div>

                    <select name="module" id="sel-module" size="1"
                            class="select-box">
                        <?php
                        /** @var SelectItem $module */
                        foreach ($_getvar('modules') as $module): ?>
                            <option
                                    value='<?php
                                    echo $module->getId(); ?>' <?php
                            echo $module->isSelected() ? 'selected' : ''; ?>><?php
                                echo $module->getName(); ?></option>
                        <?php
                        endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php
                    echo __('Help Text'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="help" name="help" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php
                               echo $field->getHelp(); ?>">
                        <label class="mdl-textfield__label"
                               for="help"><?php
                            echo __('Field\'s help'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                           for="required">
                        <input type="checkbox" id="required"
                               class="mdl-switch__input mdl-color-text--indigo-400"
                               name="required"
                            <?php
                            echo $field->getRequired() ? 'checked' : ''; ?>/>
                        <span class="mdl-switch__label"><?php
                            echo __('Required'); ?></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField"></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                           for="encrypted">
                        <input type="checkbox" id="encrypted"
                               class="mdl-switch__input mdl-color-text--indigo-400"
                               name="encrypted" <?php
                        echo $field->getisEncrypted() ? 'checked' : ''; ?>/>
                        <span class="mdl-switch__label"><?php
                            echo __('Encrypted'); ?></span>
                    </label>
                </td>
            </tr>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php
                echo $icons->save()->getClassButton(); ?>"
                form="frmCustomFields"
                title="<?php
                echo $icons->save()->getTitle(); ?>">
            <i class="material-icons"><?php
                echo $icons->save()->getIcon(); ?></i>
        </button>
    </div>
</div>
