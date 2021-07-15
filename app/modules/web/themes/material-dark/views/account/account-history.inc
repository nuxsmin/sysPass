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
use SP\Mvc\View\Components\SelectItem;
use SP\Services\Account\AccountAcl;

/** @var AccountExtData $accountData */
$accountData = $_getvar('accountData');

/** @var AccountAcl $accountAcl */
$accountAcl = $_getvar('accountAcl');
?>

<div class="data-container">
    <div id="title" class="titleNormal">
        <i class="material-icons"
           title="<?php echo __('History'); ?>"><?php echo $_getvar('title')['icon']; ?></i>
        <?php echo $_getvar('title')['name']; ?>
    </div>

    <div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
        <div class="mdl-tabs__tab-bar">
            <a href="#account-panel"
               class="mdl-tabs__tab is-active"><?php echo __('Account'); ?></a>

            <?php if ($accountAcl->isShowDetails()): ?>
                <a href="#detail-panel"
                   class="mdl-tabs__tab"><?php echo __('Details'); ?></a>
            <?php endif; ?>
        </div>

        <div class="mdl-tabs__panel is-active" id="account-panel">
            <form method="post" name="frmaccount" id="frmAccount"
                  class="form-action"
                  data-onsubmit="account/save"
                  data-action-route="<?php echo $_getvar('formRoute', ''); ?>"
                  data-item-id="<?php echo $_getvar('accountId', ''); ?>"
                  data-hash="">
                <input type="hidden" name="sk" value="<?php echo $sk; ?>">
                <input type="hidden" name="isAjax" value="1">

                <table class="data">
                    <tr>
                        <td class="descField"><?php echo __('Name'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="name" name="name" type="text"
                                       required
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       maxlength="50"
                                       value="<?php echo $accountData->getName() ?>"
                                       tabindex="1" readonly>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('Account name'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Client'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Client'); ?></div>

                            <select id="client_id" name="client_id"
                                    class="select-box sel-chosen-client"
                                    tabindex="2" required disabled>
                                <?php /** @var SelectItem $client */
                                foreach ($_getvar('clients') as $client): ?>
                                    <option
                                            value="<?php echo $client->getId(); ?>" <?php echo $client->isSelected() ? 'selected' : ''; ?>><?php echo $client->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Category'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Category'); ?></div>

                            <select id="category_id" name="category_id"
                                    class="select-box"
                                    tabindex="3" required disabled>
                                <option value=""><?php echo __('Select Category'); ?></option>
                                <?php /** @var SelectItem $category */
                                foreach ($_getvar('categories') as $category): ?>
                                    <option
                                            value="<?php echo $category->getId(); ?>" <?php echo $category->isSelected() ? 'selected' : ''; ?>><?php echo $category->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('URL / IP'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="url" name="url" type="text"
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       maxlength="255" tabindex="4"
                                       value="<?php echo $accountData->getUrl(); ?>"
                                       readonly>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('Access URL or IP'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('User'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="login" name="login" type="text"
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       maxlength="50" tabindex="5"
                                       value="<?php echo $accountData->getLogin(); ?>"
                                       readonly>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('Access user'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Password Expiry Date'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="password_date_expire"
                                       name="password_date_expire" type="date"
                                       class="mdl-textfield__input mdl-color-text--indigo-400 password-datefield__input"
                                       tabindex="8"
                                       value="<?php echo $_getvar('accountPassDateChange'); ?>"
                                       readonly>
                                <label class="mdl-textfield__label"
                                       for="password_date_expire"><?php echo __('Date'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Notes'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea
                                class="mdl-textfield__input mdl-color-text--indigo-400"
                                rows="3" id="notes"
                                name="notes" tabindex="9"
                                maxlength="5000"
                                readonly><?php echo $accountData->getNotes(); ?></textarea>
                                <label class="mdl-textfield__label"
                                       for="notes"><?php echo __('Notes about the account'); ?></label>
                            </div>
                    </tr>
                    <?php if ($accountAcl->isShowHistory()
                        && count($_getvar('historyData', [])) > 0): ?>
                        <tr>
                            <td class="descField"><?php echo __('History'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('History'); ?></div>

                                <select id="history_id" name="history_id"
                                        class="select-box" tabindex="12"
                                        data-action-route="account/viewHistory"
                                        data-onchange="account/viewHistory">
                                    <option value=""><?php echo __('Select date'); ?></option>
                                    <?php /** @var SelectItem $history */
                                    foreach ($_getvar('historyData') as $history): ?>
                                        <option
                                                value="<?php echo $history->getId(); ?>"
                                            <?php echo $history->isSelected() ? 'selected' : ''; ?>><?php echo $history->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="descField"><?php echo __('Last Modification'); ?></td>
                        <td class="valField"><?php printf('%s (%s)', $accountData->getDateEdit(), $accountData->getUserEditName() ?: $accountData->getUserEditLogin()); ?></td>
                    </tr>
                </table>

            </form>
        </div>

        <div class="mdl-tabs__panel" id="detail-panel">
            <?php if ($accountAcl->isShowDetails()): include $this->includeTemplate('details'); endif; ?>
        </div>

    </div>

    <?php include $this->includeTemplate('actions'); ?>
</div>
