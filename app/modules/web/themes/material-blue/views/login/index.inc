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
use SP\Modules\Web\Mvc\View\TemplateInterface;

use function SP\__;

?>

<div id="box-pub-noheader">
    <div class="sp-auth-brand">
        <img class="sp-auth-logo"
             src="<?php echo $_getvar('logo_stacked_primary'); ?>"
             alt="<?php echo htmlspecialchars($_getvar('app_name'), ENT_QUOTES); ?>"/>
    </div>
    <div id="box-login" class="box-form sp-panel round">
        <h1 class="sp-auth-title"><?php echo __('Sign in'); ?></h1>
        <form method="post" name="frmLogin" id="frmLogin" class="form-action"
              data-onsubmit="main/login"
              data-route="login/login">
            <fieldset id="box-data">
                <div class="form-control">
                    <div class="mdl-textfield mdl-js-textfield">
                        <label class="mdl-textfield__label" for="user"><?php echo __('User'); ?></label>
                        <input id="user" name="user" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="80" autocomplete="username" autofocus>
                    </div>
                </div>

                <div class="form-control">
                    <div class="mdl-textfield mdl-js-textfield">
                        <label class="mdl-textfield__label" for="pass"><?php echo __('Password'); ?></label>
                        <input id="pass" name="pass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="current-password">
                    </div>
                </div>

                <div id="soldpass" class="extra-hidden form-control">
                    <div class="mdl-textfield mdl-js-textfield">
                        <label class="mdl-textfield__label" for="oldpass"><?php echo __('Previous Password'); ?></label>
                        <input id="oldpass" name="oldpass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                    </div>
                </div>

                <div id="smpass" class="extra-hidden form-control">
                    <div class="mdl-textfield mdl-js-textfield">
                        <label class="mdl-textfield__label" for="mpass"><?php echo __('Master Password'); ?></label>
                        <input id="mpass" name="mpass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                    </div>
                </div>

                <?php
                if ($_getvar('from') && $_getvar('from_hash')): ?>
                    <input type="hidden" name="from"
                           value="<?php
                           echo $_getvar('from'); ?>"/>
                    <input type="hidden" name="h"
                           value="<?php
                           echo $_getvar('from_hash'); ?>"/>
                <?php
                endif; ?>

                <input type="hidden" name="login" value="1"/>
                <input type="hidden" name="isAjax" value="1"/>
            </fieldset>
            <div id="box-buttons">
                <button id="btnLogin" type="submit" form="frmLogin"
                        class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--colored sp-btn-primary"
                        title="<?php
                        echo __('Sign in'); ?>">
                    <?php echo __('Sign in'); ?>
                </button>
            </div>
        </form>

        <!-- Close boxData -->
        <?php
        if ($_getvar('mailEnabled')): ?>
            <div id="box-actions">
                <a href="index.php?r=userPassReset"><?php
                    echo __('Forgot password?'); ?></a>
            </div>
        <?php
        endif; ?>
    </div><!-- Close boxLogin -->

    <?php
    if ($_getvar('updated')): ?>
        <div id="box-updated"
             class="round5"><?php
            echo __('Application successfully updated'); ?></div>
    <?php
    endif; ?>

    <?php
    if ($_getvar('isDemo')): ?>
        <div id="demo-info">
            <ul>
                <li title="<?php
                echo __('User'); ?>">
                    <i class="material-icons">perm_identity</i>
                    <span>demo</span>
                </li>
                <li title="<?php
                echo __('Password'); ?>">
                    <i class="material-icons">vpn_key</i>
                    <span>syspass</span>
                </li>
                <li title="<?php
                echo __('Master Password'); ?>">
                    <i class="material-icons">vpn_key</i>
                    <span>12345678900</span>
                </li>
            </ul>
        </div>
    <?php
    endif; ?>
</div>
