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
 * @var CustomFieldItem[] $customFields
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Core\UI\ThemeIconsInterface;
use SP\Domain\CustomField\Services\CustomFieldItem;
use SP\Mvc\View\TemplateInterface;

$customFields = $_getvar('customFields');

foreach ($customFields as $index => $field):?>
    <tr>
        <td class="descField">
            <?php
            echo htmlspecialchars($field->definitionName, ENT_QUOTES); ?>
            <?php
            if ($field->isEncrypted && $field->isValueEncrypted === true): ?>
                <i class="icon material-icons mdl-color-text--teal-500"
                   title="<?php
                   echo __('Encrypted'); ?>">
                    lock_outline
                </i>
            <?php
            elseif ($field->isEncrypted && $field->isValueEncrypted === false): ?>
                <i class="icon material-icons mdl-color-text--amber-500"
                   title="<?php
                   echo __('Data will be encrypted after saving'); ?>">
                    lock_outline
                </i>
            <?php
            elseif ($field->isEncrypted === 0 && $field->isValueEncrypted === true): ?>
                <i class="icon material-icons mdl-color-text--red-500"
                   title="<?php
                   echo __('Data will be unencrypted after saving'); ?>">
                    lock_open
                </i>
            <?php
            endif; ?>

            <?php
            if ($field->help): ?>
                <div id="help-<?php
                echo $field->formId; ?>"
                     class="icon material-icons <?php
                     echo $icons->help()->getClass(); ?>">
                    <?php
                    echo $icons->help()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-<?php
                     echo $field->formId; ?>">
                    <p><?php
                        echo $field->help; ?></p>
                </div>
            <?php
            endif; ?>
        </td>
        <td class="valField">
            <?php
            if ($field->typeName === 'color' && $_getvar('isView')): ?>
                <span class="round custom-input-color"
                      style="background-color: <?php
                      echo $field->value; ?>;"></span>
            <?php
            elseif ($field->typeName === 'password'): ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="<?php
                    echo $field->formId; ?>"
                           name="customfield[<?php
                           echo $field->definitionId; ?>]"
                           type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__no-pki <?php
                           echo $_getvar('isView') ? 'passwordfield__input-show' : 'passwordfield__input'; ?>"
                           data-clipboard="1"
                           maxlength="500"
                           value="<?php
                           echo !$_getvar('showViewCustomPass') && !empty($field->value) ? '***' : htmlspecialchars(
                               $field->value,
                               ENT_QUOTES
                           ); ?>" <?php
                    echo $field->required ? 'required' : ''; ?> <?php
                    echo $_getvar('readonly'); ?>>
                    <label class="mdl-textfield__label"
                           for="<?php
                           echo $field->formId; ?>"><?php
                        echo htmlspecialchars($field->definitionName, ENT_QUOTES); ?></label>
                </div>
            <?php
            elseif ($field->typeName === 'textarea'): ?>
                <div class="mdl-textfield mdl-js-textfield">
                    <textarea
                            class="mdl-textfield__input mdl-color-text--indigo-400"
                            type="text" rows="3"
                            name="customfield[<?php
                            echo $field->definitionId; ?>]"
                            id="<?php
                            echo $field->formId; ?>" <?php
                    echo $_getvar('readonly'); ?>><?php
                        echo htmlspecialchars($field->value, ENT_QUOTES); ?></textarea>
                    <label class="mdl-textfield__label"
                           for="<?php
                           echo $field->formId; ?>"><?php
                        echo htmlspecialchars($field->definitionName, ENT_QUOTES); ?></label>
                </div>
            <?php
            else: ?>
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="<?php
                    echo $field->formId; ?>"
                           name="customfield[<?php
                           echo $field->definitionId; ?>]"
                           type="<?php
                           echo $field->typeName; ?>"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="500"
                           value="<?php
                           echo htmlspecialchars($field->value, ENT_QUOTES); ?>" <?php
                    echo $field->required ? 'required' : ''; ?> <?php
                    echo $_getvar('readonly'); ?>>
                    <label class="mdl-textfield__label"
                           for="<?php
                           echo $field->formId; ?>"><?php
                        echo htmlspecialchars($field->definitionName, ENT_QUOTES); ?></label>
                </div>
            <?php
            endif; ?>
        </td>
    </tr>
<?php
endforeach; ?>
