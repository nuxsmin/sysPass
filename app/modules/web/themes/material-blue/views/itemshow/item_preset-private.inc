<?php
/**
 * @var AccountPrivate $private
 * @var ThemeIcons     $icons
 * @var ConfigData     $configData
 * @var callable       $_getvar
 * @var Template       $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\Mvc\View\Template;

$private = $_getvar('private');
?>

<table class="popup-data">
    <tbody>
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <ul class="private-account-list-action mdl-list">
                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="private_user_enabled">
                            <input type="checkbox" id="private_user_enabled"
                                   class="mdl-switch__input"
                                   name="private_user_enabled" <?php echo $private->isPrivateUser() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Private Account'); ?></span>
                        <span class="mdl-list__item-sub-title">
                            <?php echo __('Accounts will be only visible by the user.'); ?>
                            <br>
                            <?php echo __('Administrators won\'t be able to display the accounts.'); ?>
                        </span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                               for="private_group_enabled">
                            <input type="checkbox" id="private_group_enabled"
                                   class="mdl-switch__input"
                                   name="private_group_enabled" <?php echo $private->isPrivateGroup() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Private Account for Group'); ?></span>
                        <span class="mdl-list__item-sub-title">
                            <?php echo __('Accounts will be only visible by the user and users belonging to the main group.'); ?>
                            <br>
                            <?php echo __('Administrators won\'t be able to display the accounts.'); ?>
                        </span>
                    </span>
                </li>
            </ul>
        </td>
    </tr>
    </tbody>
</table>