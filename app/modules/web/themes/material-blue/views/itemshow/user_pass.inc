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
 * @var User $user
 * @var ThemeIconsInterface $icons
 * @var ConfigDataInterface $configData
 * @var callable $_getvar
 * @var TemplateInterface $this
 */

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\User\Models\User;
use SP\Modules\Web\Mvc\View\TemplateInterface;

use function SP\__;

$user = $_getvar('user');
?>

<div id="box-popup"
     class="sp-dialog sp-dialog--compact sp-dialog--password"
     role="dialog"
     aria-modal="true"
     aria-labelledby="box-popup-title">
    <h2 class="center" id="box-popup-title"><?php
        echo $_getvar('header'); ?></h2>

    <form method="post" name="updUsrPass" id="frmUpdUsrPass" class="form-action"
          data-onsubmit="user/savePassword"
          data-action-route="<?php
          echo $_getvar('route', ''); ?>"
          data-hash="">
        <div class="sp-dialog-context" aria-label="<?php echo htmlspecialchars(__('User'), ENT_QUOTES); ?>">
            <div class="sp-dialog-context__item">
                <span class="sp-dialog-context__label"><?php echo __('Name'); ?></span>
                <span class="sp-dialog-context__value"><?php
                    echo htmlspecialchars($user->getName(), ENT_QUOTES); ?></span>
            </div>
            <div class="sp-dialog-context__item">
                <span class="sp-dialog-context__label"><?php echo __('Login'); ?></span>
                <span class="sp-dialog-context__value"><?php
                    echo htmlspecialchars($user->getLogin(), ENT_QUOTES); ?></span>
            </div>
        </div>

        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><label for="password"><?php
                    echo __('Password'); ?></label></td>
                <td class="valField">
                    <div class="form-control">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="password" name="password" type="password"
                                   required
                                   autocomplete="new-password"
                                   class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input">
                            <label class="mdl-textfield__label"
                                   for="password"><?php
                                echo __('Password'); ?></label>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><label for="password_repeat"><?php
                    echo __('Password (repeat)'); ?></label></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="password_repeat" name="password_repeat"
                               type="password" required
                               autocomplete="new-password"
                               data-mismatch-msg="<?php echo htmlspecialchars(__('Passwords do not match'), ENT_QUOTES); ?>"
                               class="mdl-textfield__input mdl-color-text--indigo-400">
                        <label class="mdl-textfield__label"
                               for="password_repeat"><?php
                            echo __('Password (repeat)'); ?></label>
                    </div>
                    <div class="sp-field-error sp-password-match-error"
                         role="alert"
                         aria-live="polite"
                         hidden></div>
                </td>
            </tr>
            </tbody>
        </table>
    </form>

    <div class="action-in-box sp-dialog-footer">
        <button type="button"
                class="btn-popup-close mdl-button mdl-js-button sp-btn-secondary">
            <?php echo __('Cancel'); ?>
        </button>
        <button type="submit"
                form="frmUpdUsrPass"
                class="mdl-button mdl-js-button sp-btn-primary <?php
                echo $icons->save()->getClassButton(); ?>"
                title="<?php
                echo $icons->save()->getTitle(); ?>">
            <?php echo __('Save'); ?>
        </button>
    </div>
</div>
