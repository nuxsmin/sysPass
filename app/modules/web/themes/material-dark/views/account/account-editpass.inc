<?php
/**
 * @var callable       $_getvar
 * @var ThemeIcons     $icons
 * @var AccountExtData $accountData
 * @var AccountAcl     $accountAcl
 * @var ConfigData     $configData
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\AccountExtData;
use SP\Services\Account\AccountAcl;

/** @var AccountExtData $accountData */
$accountData = $_getvar('accountData');

/** @var AccountAcl $accountAcl */
$accountAcl = $_getvar('accountAcl');
?>

<div class="data-container">
    <div id="title" class="titleNormal">
        <i class="material-icons"><?php echo $_getvar('title')['icon']; ?></i>
        <?php echo $_getvar('title')['name']; ?>
    </div>

    <form method="post" name="editpass" id="frmEditPass" class="form-action"
          data-onsubmit="account/save"
          data-action-route="<?php echo $_getvar('formRoute', ''); ?>"
          data-item-id="<?php echo $_getvar('accountId', ''); ?>"
          data-hash="">
        <table class="data">
            <tr>
                <td class="descField"><?php echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getName(); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="name"><?php echo __('Account name'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Client'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="client" name="client" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getClientName(); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="client"><?php echo __('Client'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('URL / IP'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="url" name="url" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getUrl(); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="url"><?php echo __('URL / IP'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('User'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="login" name="login" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getLogin(); ?>"
                               readonly>
                        <label class="mdl-textfield__label"
                               for="login"><?php echo __('User'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Password'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="password" name="password" type="password"
                               required
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="password"><?php echo __('Password'); ?></label>
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
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="password_repeat"><?php echo __('Password'); ?></label>
                    </div>
                </td>
            </tr>
            <?php if ($configData->isAccountExpireEnabled()): ?>
                <tr>
                    <td class="descField"><?php echo __('Password Expiry Date'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="password_date_expire"
                                   name="password_date_expire" type="date"
                                   class="mdl-textfield__input mdl-color-text--indigo-400 password-datefield__input"
                                   value="<?php echo $_getvar('accountPassDateChange'); ?>"
                                   data-dst-unix="password_date_expire_unix">
                            <input type='hidden'
                                   name='password_date_expire_unix' value=""/>
                            <label class="mdl-textfield__label"
                                   for="password_date_expire"><?php echo __('Date'); ?></label>
                        </div>
                        <i class="material-icons btn-action"
                           title="<?php echo __('Select date'); ?>">date_range</i>
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">

        <div class="item-actions">
            <ul>
                <li>
                    <button id="btnBack" type="button"
                            class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                            title="<?php echo __('Back'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                    </button>
                </li>
                <li>
                    <button id="btnSave" name="btnSave" type="submit"
                            form="frmEditPass"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                            title="<?php echo __('Save'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>
    </form>
</div>

<script>
    $('input:password:visible:first').focus();
</script>
