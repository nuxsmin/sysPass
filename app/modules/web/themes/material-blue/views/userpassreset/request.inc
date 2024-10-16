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

/** @var $icons ThemeIconsInterface */

use SP\Domain\Core\UI\ThemeIconsInterface;

use function SP\__;

?>

<div id="box-pub-noheader">
    <div class="box-spacer"></div>
    <div class="box-header">
        <?php
        echo __('Request Password Change'); ?>
    </div>
    <div id="box-passreset" class="box-form round">
        <form id="frmPassReset" action="" method="post" class="form-action"
              data-onsubmit="user/passreset"
              data-action-route="userPassReset/saveRequest">
            <fieldset id="box-data">
                <div class="form-control">
                    <i class="material-icons">perm_identity</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="login" name="login" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off" autofocus>
                        <label class="mdl-textfield__label"
                               for="login"><?php
                            echo __('User'); ?></label>
                    </div>
                </div>
                <div class="form-control">
                    <i class="material-icons">email</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="email" name="email" type="email" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="email"><?php
                            echo __('User\'s Email'); ?></label>
                    </div>
                </div>
                <input type="hidden" name="isAjax" value="1">
            </fieldset>

            <div id="box-buttons">
                <button id="btnBack" type="button"
                        class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <i class="material-icons"
                       title="<?php
                       echo __('Go back to login'); ?>"><?php
                        echo $icons->back()->getIcon(); ?></i>
                    <?php
                    echo __('Back'); ?>
                </button>

                <button id="btnRequest"
                        class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <?php
                    echo __('Request'); ?>
                    <i class="material-icons"
                       title="<?php
                       echo __('Request'); ?>"><?php
                        echo $icons->play()->getIcon(); ?></i>
                </button>
            </div>
        </form>
    </div>
</div>
