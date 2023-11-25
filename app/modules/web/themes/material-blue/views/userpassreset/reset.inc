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

use SP\Core\UI\ThemeIconsInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Mvc\View\TemplateInterface;

?>

<div id="box-pub-noheader">
    <div class="box-spacer"></div>
    <div class="box-header">
        <?php
        echo __('Request Password Change'); ?>
    </div>
    <div id="box-passreset" class="box-form round">
        <form id="frmUserPassReset" action="" method="post" class="form-action"
              data-onsubmit="user/passreset"
              data-action-route="userPassReset/saveReset">
            <fieldset id="box-data">
                <div class="form-control">
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="password" name="password" type="password"
                               required
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               value="" maxlength="255"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="password"><?php
                            echo __('Password'); ?></label>
                    </div>
                </div>
                <div class="form-control">
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="password_repeat" name="password_repeat"
                               type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="" maxlength="255"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="password_repeat"><?php
                            echo __('Password (repeat)'); ?></label>
                    </div>
                </div>
                <input type="hidden" name="hash"
                       value="<?php
                       echo $_getvar('hash'); ?>">
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

                <button id="btnChange"
                        class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <?php
                    echo __('Change'); ?>
                    <i class="material-icons"
                       title="<?php
                       echo __('Change'); ?>"><?php
                        echo $icons->play()->getIcon(); ?></i>
                </button>
            </div>
        </form>
    </div>
</div>
