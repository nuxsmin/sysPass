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

use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Modules\Web\Mvc\View\TemplateInterface;

use function SP\__;

?>
<div id="actions" class="installer sp-flow-page sp-panel">
    <div id="page-title">
        <h1><?php
            echo __('Update Application'); ?></h1>
    </div>

    <div class="sp-alert sp-alert--warning" role="note">
        <i class="material-icons sp-alert__icon" aria-hidden="true"><?php
            echo $icons->warning()->getIcon(); ?></i>
        <div class="sp-alert__body">
            <strong class="sp-alert__title"><?php
                echo __('The application needs to be updated'); ?></strong>
            <p class="sp-alert__text"><?php
                echo __('Please contact to the administrator'); ?></p>
        </div>
    </div>

    <form id="frmUpgrade" method="post" class="form-action"
          data-onsubmit="main/upgrade"
          data-action-route="upgrade/upgrade">
        <fieldset>
            <div class="sp-flow-field">
                <div class="mdl-textfield mdl-js-textfield">
                    <label class="mdl-textfield__label"
                           for="key"><?php
                        echo __('Security Code'); ?></label>
                    <input id="key" name="key" type="text" required
                           class="mdl-textfield__input"
                           autocomplete="off" spellcheck="false"
                           aria-describedby="key-hint" autofocus>
                </div>
                <p id="key-hint" class="sp-field-hint"><?php
                    echo __('Please enter the security code to start the upgrade'); ?></p>
            </div>

            <div class="sp-flow-field sp-upgrade-confirm">
                <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect sp-flow-checkbox"
                       for="chkConfirm">
                    <input type="checkbox" id="chkConfirm"
                           class="mdl-checkbox__input" name="chkConfirm" required>
                    <span class="mdl-checkbox__label"><?php
                        echo __('I\'ve done a full sysPass backup'); ?></span>
                </label>
            </div>
        </fieldset>

        <div id="upgrade-feedback"
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

        <div id="upgrade-running-status"
             class="sp-alert sp-alert--info sp-flow-status"
             role="status"
             aria-live="polite"
             hidden>
            <i class="material-icons sp-alert__icon" aria-hidden="true">hourglass_empty</i>
            <div class="sp-alert__body">
                <span class="sp-alert__text"><?php
                    echo __('Please, wait while the process is running'); ?></span>
            </div>
        </div>

        <div class="buttons sp-flow-actions">
            <button id="btnChange"
                    class="mdl-button mdl-js-button mdl-button--raised mdl-button--colored sp-btn-primary"
                    type="submit">
                <span><?php
                    echo __('Update'); ?></span>
                <i class="material-icons" aria-hidden="true"
                   title="<?php
                   echo htmlspecialchars(__('Start Update'), ENT_QUOTES); ?>"><?php
                    echo $icons->play()->getIcon(); ?></i>
            </button>
        </div>
    </form>
</div>
