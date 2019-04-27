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
?>

<div class="data-container">
    <div id="title"
         class="titleNormal"><?php echo __('Request Account Modification'); ?></div>

    <form method="post" name="requestmodify" id="frmRequestModify"
          class="form-action"
          data-onsubmit="account/request"
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
                <td class="descField"><?php echo __('Request'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <textarea
                            class="mdl-textfield__input mdl-color-text--indigo-400"
                            rows="3" id="description"
                            name="description" maxlength="1000"></textarea>
                        <label class="mdl-textfield__label"
                               for="description"><?php echo __('Request description'); ?></label>
                    </div>
                </td>
            </tr>
        </table>

        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>

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
                <button id="btnSave" form="frmRequestModify"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                        title="<?php echo __('Send'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
                </button>
            </li>
        </ul>
    </div>
</div>