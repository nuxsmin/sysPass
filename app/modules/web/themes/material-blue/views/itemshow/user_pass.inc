<?php
/**
 * @var UserData   $user
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\UserData;
use SP\Mvc\View\Template;

$user = $_getvar('user');
?>

<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="updUsrPass" id="frmUpdUsrPass" class="form-action"
          data-onsubmit="user/savePassword"
          data-action-route="<?php echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tr>
                <td class="descField"><?php echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $user->getName(); ?>" readonly
                               disabled/>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Login'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield">
                        <input id="login" name="login" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $user->getLogin(); ?>"
                               readonly disabled/>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Password'); ?></td>
                <td class="valField">
                    <div class="form-control">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="password" name="password" type="password"
                                   required
                                   class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                                   maxlength="50">
                            <label class="mdl-textfield__label"
                                   for="password"><?php echo __('Password'); ?></label>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Password (repeat)'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="password_repeat" name="password_repeat"
                               type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50">
                        <label class="mdl-textfield__label"
                               for="password_repeat"><?php echo __('Password (repeat)'); ?></label>
                    </div>
                </td>
            </tr>
        </table>
    </form>

    <div class="action-in-box">
        <button form="frmUpdUsrPass"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>