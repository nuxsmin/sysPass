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
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Modules\Web\Mvc\View\Components\SelectItem;
use SP\Modules\Web\Mvc\View\TemplateInterface;

use function SP\__;

?>
<div id="actions" class="installer sp-flow-page sp-panel">
    <div id="page-title">
        <h1><?php
            printf(__('Installation %s'), $_getvar('app_version')); ?></h1>
    </div>

    <?php
    include $this->includePartial('error-list'); ?>

    <form id="frmInstall" class="form-action" data-onsubmit="main/install"
          data-route="install/install">
        <input type="hidden" name="install" value="true"/>
        <input type="hidden" name="isAjax" value="1"/>

        <fieldset id="adminaccount">
            <legend><?php
                echo __('sysPass Admin'); ?></legend>

            <div class="sp-flow-field">
                <div class="mdl-textfield mdl-js-textfield">
                    <label class="mdl-textfield__label"
                           for="adminlogin"><?php
                        echo __('sysPass admin user'); ?></label>
                    <input id="adminlogin" name="adminlogin" type="text" required
                           class="mdl-textfield__input"
                           value="admin" maxlength="80" autocomplete="username"
                           aria-describedby="adminlogin-hint"
                           autofocus>
                </div>
                <p id="adminlogin-hint" class="sp-field-hint"><?php
                    echo __('sysPass administrator\'s login'); ?></p>
            </div>

            <div class="sp-flow-field">
                <div class="form-control">
                    <div class="mdl-textfield mdl-js-textfield">
                        <label class="mdl-textfield__label"
                               for="adminpass"><?php
                            echo __('Password'); ?></label>
                        <input id="adminpass" name="adminpass" type="password" required
                               class="mdl-textfield__input passwordfield__input"
                               value="" maxlength="255" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </fieldset>

        <fieldset id="masterpwd">
            <legend><?php
                echo __('Master Password'); ?></legend>

            <div class="sp-flow-field">
                <div class="form-control">
                    <div class="mdl-textfield mdl-js-textfield">
                        <label class="mdl-textfield__label"
                               for="masterpassword"><?php
                            echo __('Master Password'); ?></label>
                        <input id="masterpassword" name="masterpassword" type="password"
                               required
                               class="mdl-textfield__input passwordfield__input"
                               value="" maxlength="255" autocomplete="new-password"
                               aria-describedby="masterpassword-hint">
                    </div>
                </div>
                <p id="masterpassword-hint" class="sp-field-hint"><?php
                    echo __('The Master Password length need to be at least 11 characters'); ?></p>
            </div>

            <div class="sp-flow-field">
                <div class="mdl-textfield mdl-js-textfield">
                    <label class="mdl-textfield__label"
                           for="masterpasswordR"><?php
                        echo __('Password (repeat)'); ?></label>
                    <input id="masterpasswordR" name="masterpasswordr"
                           type="password" required
                           class="mdl-textfield__input"
                           value="" maxlength="255" autocomplete="new-password"
                           aria-describedby="masterpasswordR-error"
                           data-mismatch-msg="<?php
                           echo htmlspecialchars(__('Passwords do not match'), ENT_QUOTES); ?>">
                </div>
                <div id="masterpasswordR-error"
                     class="sp-field-error sp-password-match-error"
                     aria-live="polite"
                     hidden></div>
            </div>
        </fieldset>

        <fieldset id="databaseField">
            <legend><?php
                echo __('DB Configuration'), ' (MySQL)'; ?></legend>

            <input type="hidden" id="hasMySQL" value="true"/>
            <input type="hidden" id="dbtype" name="dbtype" value="mysql"/>

            <div class="sp-flow-field">
                <div class="mdl-textfield mdl-js-textfield">
                    <label class="mdl-textfield__label"
                           for="dbuser"><?php
                        echo __('DB access user'); ?></label>
                    <input id="dbuser" name="dbuser" type="text" required
                           class="mdl-textfield__input"
                           value="root" autocomplete="off"
                           aria-describedby="dbuser-hint">
                </div>
                <p id="dbuser-hint" class="sp-field-hint"><?php
                    echo __('An user with MySQL admin rights'); ?></p>
            </div>

            <div class="sp-flow-field">
                <div class="form-control">
                    <div class="mdl-textfield mdl-js-textfield">
                        <label class="mdl-textfield__label"
                               for="dbpass"><?php
                            echo __('DB access password'); ?></label>
                        <input id="dbpass" name="dbpass" type="password" required
                               class="mdl-textfield__input passwordfield__input-show"
                               value="" autocomplete="off">
                    </div>
                </div>
            </div>

            <div class="sp-flow-field">
                <div class="mdl-textfield mdl-js-textfield">
                    <label class="mdl-textfield__label"
                           for="dbname"><?php
                        echo __('sysPass database name'); ?></label>
                    <input id="dbname" name="dbname" type="text" required
                           class="mdl-textfield__input"
                           value="syspass" autocomplete="off"
                           pattern="[0-9a-zA-Z_$\-]+">
                </div>
            </div>

            <div class="sp-flow-field">
                <div class="mdl-textfield mdl-js-textfield">
                    <label class="mdl-textfield__label"
                           for="dbhost"><?php
                        echo __('sysPass database server'); ?></label>
                    <input id="dbhost" name="dbhost" type="text" required
                           class="mdl-textfield__input"
                           value="localhost" autocomplete="off"
                           aria-describedby="dbhost-hint"
                           pattern="([0-9a-zA-Z._\-]+|([0-9]{1,3}\.)+)(:[0-9]{1,5})?|unix:.*">
                </div>
                <p id="dbhost-hint" class="sp-field-hint"><?php
                    echo __('Server name to install sysPass database'); ?></p>
            </div>
        </fieldset>

        <fieldset id="site">
            <legend><?php
                echo __('General'); ?></legend>

            <div class="sp-flow-field">
                <label class="sp-flow-label" for="sel-sitelang"><?php
                    echo __('Language'); ?></label>
                <select name="sitelang" id="sel-sitelang" size="1"
                        class="select-box"
                        aria-describedby="sitelang-hint"
                        title="<?php
                        echo htmlspecialchars(__('Language'), ENT_QUOTES); ?>">
                    <?php
                    /** @var SelectItem $lang */
                    foreach ($_getvar('langs') as $lang): ?>
                        <option value="<?php
                        echo $lang->getId(); ?>" <?php
                        echo $lang->isSelected() ? 'selected' : ''; ?>><?php
                            echo $lang->getName(); ?></option>
                    <?php
                    endforeach; ?>
                </select>
                <p id="sitelang-hint" class="sp-field-hint"><?php
                    echo __('Sets the default language for sysPass and the initial administrator account after installation'); ?></p>
            </div>

            <div class="sp-flow-field">
                <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect sp-flow-checkbox"
                       for="hostingmode">
                    <input type="checkbox" id="hostingmode"
                           class="mdl-checkbox__input" name="hostingmode"
                           aria-describedby="hostingmode-hint">
                    <span class="mdl-checkbox__label"><?php
                        echo __('Hosting Mode'); ?></span>
                </label>
                <p id="hostingmode-hint" class="sp-field-hint"><?php
                    echo __('It does not create or verify the user\'s permissions on the DB'); ?></p>
            </div>
        </fieldset>

        <div id="install-feedback"
             class="sp-alert sp-alert--error sp-flow-status"
             role="alert"
             aria-live="polite"
             hidden>
            <i class="material-icons sp-alert__icon" aria-hidden="true">error_outline</i>
            <div class="sp-alert__body">
                <strong class="sp-alert__title"></strong>
                <p class="sp-alert__text" hidden></p>
            </div>
        </div>

        <div id="install-running-status"
             class="sp-alert sp-alert--info sp-flow-status"
             role="status"
             aria-live="polite"
             hidden>
            <i class="material-icons sp-alert__icon" aria-hidden="true">hourglass_empty</i>
            <div class="sp-alert__body">
                <span class="sp-alert__text"><?php
                    echo __('Performing task. Please, do not close browser\'s window/tab.'); ?></span>
            </div>
        </div>

        <div class="buttons sp-flow-actions">
            <button id="btnInstall"
                    class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored sp-btn-primary"
                    type="submit">
                <span><?php
                    echo __('Install'); ?></span>
                <i class="material-icons" aria-hidden="true"><?php
                    echo $icons->play()->getIcon(); ?></i>
            </button>
        </div>
    </form>
</div>
