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
 * @var TemplateInterface $this
 * @var ThemeIconsInterface $icons
 */

use SP\Core\UI\ThemeIconsInterface;
use SP\Mvc\View\TemplateInterface;

?>
<div id="actions" class="installer" align="center">
    <ul class="errors round">
        <li class="msg-warning">
            <i class="material-icons <?php
            echo $icons->warning()->getClass(); ?>"><?php
                echo $icons->warning()->getIcon(); ?></i>
            <strong><?php
                echo __('The application needs to be updated'); ?></strong>
            <p class="hint">
                <?php
                echo __('Please contact to the administrator'); ?>
            </p>
            <p class="hint">
                <?php
                echo __('Please enter the security code to start the upgrade'); ?>
            </p>
        </li>
    </ul>

    <form id="frmUpgrade" method="get" class="form-action"
          data-onsubmit="main/upgrade"
          data-action-route="upgrade/upgrade">
        <fieldset>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="key" name="key" type="text" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       autocomplete="off" autofocus>
                <label class="mdl-textfield__label"
                       for="key"><?php
                    echo __('Security Code'); ?></label>
            </div>

            <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                   for="chkConfirm">
                <input type="checkbox" id="chkConfirm"
                       class="mdl-checkbox__input" name="chkConfirm" required>
                <span class="mdl-checkbox__label"><?php
                    echo __('I\'ve done a full sysPass backup'); ?></span>
            </label>
        </fieldset>

        <div>
            <ul class="errors">
                <li class="msg-warning">
                    <i class="icon material-icons <?php
                    echo $icons->warning()->getClass(); ?>"><?php
                        echo $icons->warning()->getIcon(); ?></i>
                    <?php
                    echo __('Please, wait while the process is running'); ?>
                </li>
            </ul>
        </div>

        <div class="buttons">
            <button id="btnChange"
                    class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent"
                    type="submit">
                <?php
                echo __('Update'); ?>
                <i class="material-icons"
                   title="<?php
                   echo __('Start Update'); ?>"><?php
                    echo $icons->play()->getIcon(); ?></i>
            </button>
        </div>
    </form>
</div>
