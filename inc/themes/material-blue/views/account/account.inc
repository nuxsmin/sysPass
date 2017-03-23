<?php
/** @var \SP\Account\AccountAcl $AccountAcl */
/** @var \SP\DataModel\AccountExtData $accountData */
?>

<div class="data-container">
    <div id="title" class="midroundup titleNormal">
        <?php echo $title['name']; ?>
        <?php if ($gotData && $accountIsHistory): ?>
            <i id="history-icon" class="material-icons"
               title="<?php echo __('Histórico'); ?>"><?php echo $title['icon']; ?></i>
        <?php else: ?>
            <i id="history-icon" class="material-icons"><?php echo $title['icon']; ?></i>
        <?php endif; ?>
    </div>


    <?php if ($gotData && $accountIsHistory): ?>
        <form method="post" name="frmAccountRestore" id="frmAccountRestore" class="form-action"
              data-onsubmit="account/restore" data-hash="">
            <input type="hidden" name="actionId"
                   value="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT_RESTORE; ?>">
            <input type="hidden" name="itemId" value="<?php echo $accountId; ?>"/>
            <input type="hidden" name="accountHistoryId" value="<?php echo $accountHistoryId; ?>"/>
            <input type="hidden" name="sk" value="">
            <input type="hidden" name="isAjax" value="1">
        </form>
    <?php endif; ?>

    <form method="post" name="frmaccount" id="frmAccount" class="form-action"
          data-onsubmit="account/save" data-hash="">
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>">
        <?php if ($gotData): ?>
            <input type="hidden" name="itemId" value="<?php echo $accountId; ?>"/>
        <?php endif; ?>
        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1">

        <table class="data round">
            <tr>
                <td class="descField"><?php echo __('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php echo $gotData ? $accountData->getAccountName() : ''; ?>" <?php echo $readonly; ?>
                               tabindex="1">
                        <label class="mdl-textfield__label" for="name"><?php echo __('Nombre de cuenta'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Cliente'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Cliente'); ?></div>

                    <select id="selCustomer" name="customerId" class="select-box sel-chosen-customer"
                            tabindex="2" required <?php echo $disabled; ?>>
                        <option value=""><?php echo __('Seleccionar Cliente'); ?></option>
                        <?php foreach ($customers as $customer): ?>
                            <option
                                    value="<?php echo $customer->id; ?>" <?php echo ($gotData && $customer->id == $accountData->getAccountCustomerId()) ? 'selected' : ''; ?>><?php echo $customer->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$isView && \SP\Core\Acl::checkUserAccess(\SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS)): ?>
                        <i class="material-icons btn-action" title="<?php echo __('Nuevo Cliente'); ?>"
                           data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW; ?>"
                           data-item-id="0"
                           data-item-type="<?php echo \SP\Core\ItemsTypeInterface::ITEM_CUSTOMERS_USER; ?>"
                           data-item-dst="selCustomer"
                           data-onclick="appMgmt/show"><?php echo $icons->getIconAdd()->getIcon(); ?></i>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Categoría'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Categoría'); ?></div>

                    <select id="selCategory" name="categoryId" class="select-box sel-chosen-category"
                            tabindex="3" required <?php echo $disabled; ?>>
                        <option value=""><?php echo __('Seleccionar Categoría'); ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option
                                    value="<?php echo $category->id; ?>" <?php echo ($gotData && $category->id == $accountData->getAccountCategoryId()) ? 'selected' : ''; ?>><?php echo $category->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$isView && \SP\Core\Acl::checkUserAccess(\SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES)): ?>
                        <i class="material-icons btn-action" title="<?php echo __('Nueva Categoría'); ?>"
                           data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_NEW; ?>"
                           data-item-id="0"
                           data-item-type="<?php echo \SP\Core\ItemsTypeInterface::ITEM_CATEGORIES; ?>"
                           data-item-dst="selCategory"
                           data-onclick="appMgmt/show"><?php echo $icons->getIconAdd()->getIcon(); ?></i>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('URL / IP'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="url" name="url" type="text" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" tabindex="4"
                               value="<?php echo $gotData ? $accountData->getAccountUrl() : ''; ?>" <?php echo $readonly; ?>>
                        <label class="mdl-textfield__label" for="name"><?php echo __('URL o IP de acceso'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Usuario'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="login" name="login" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" tabindex="5"
                               value="<?php echo $gotData ? $accountData->getAccountLogin() : ''; ?>" <?php echo $readonly; ?>>
                        <label class="mdl-textfield__label" for="name"><?php echo __('Usuario de acceso'); ?></label>
                    </div>
                </td>
            </tr>
            <?php if ($AccountAcl->isShowPass()): ?>
                <tr>
                    <td class="descField"><?php echo __('Clave'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="accountpass" name="pass" type="password" required
                                   class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                                   maxlength="255" tabindex="6"
                                   autocomplete="off">
                            <label class="mdl-textfield__label" for="accountpass"><?php echo __('Clave'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Clave (repetir)'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="accountpassR" name="passR" type="password" required
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   tabindex="7" maxlength="255" autocomplete="off">
                            <label class="mdl-textfield__label"
                                   for="accountpassR"><?php echo __('Clave (repetir)'); ?></label>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if (!$isView): ?>
                <tr>
                    <td class="descField"><?php echo __('Fecha Caducidad Clave'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="accountpassdatechange" name="accountpassdatechange" type="date"
                                   class="mdl-textfield__input mdl-color-text--indigo-400 password-datefield__input"
                                   tabindex="8"
                                   value="<?php echo $accountPassDateChange; ?>" <?php echo $readonly; ?>>
                            <label class="mdl-textfield__label"
                                   for="accountpassdatechange"><?php echo __('Fecha'); ?></label>
                        </div>

                        <i class="material-icons btn-action"
                           title="<?php echo __('Seleccionar Fecha'); ?>">date_range</i>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="descField"><?php echo __('Notas'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea class="mdl-textfield__input mdl-color-text--indigo-400" rows="3" id="notes"
                                  name="notes" tabindex="9"
                                  maxlength="5000" <?php echo $readonly; ?>><?php echo $gotData ? $accountData->getAccountNotes() : ''; ?></textarea>
                        <label class="mdl-textfield__label"
                               for="notes"><?php echo __('Notas sobre la cuenta'); ?></label>
                    </div>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Etiquetas'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Etiquetas'); ?></div>

                    <?php if (!$isView): ?>
                        <select id="selTags" name="tags[]" multiple="multiple" tabindex="10">
                            <option value=""><?php echo __('Seleccionar Etiquetas'); ?></option>
                        </select>
                    <?php else: ?>
                        <?php foreach ($accountData->getTags() as $tag): ?>
                            <span class="tag"><?php echo $tag; ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (count($linkedAccounts) === 0): ?>
                <tr>
                    <td class="descField"><?php echo __('Cuenta Vinculada'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Cuenta Vinculada'); ?></div>

                        <select id="selParentAccount" name="parentAccountId" tabindex="11"
                                class="select-box select-box-deselect" <?php echo $disabled; ?>
                                data-sk="<?php echo $sk; ?>"
                                data-item-type="<?php echo \SP\Core\ItemsTypeInterface::ITEM_ACCOUNTS_USER; ?>"
                                data-item-id="<?php echo $gotData ? $accountId : ''; ?>"
                                data-selected-id="<?php echo $gotData ? $accountData->getAccountParentId() : ''; ?>">
                            <option value=""><?php echo __('Seleccionar Cuenta'); ?></option>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if ($AccountAcl->isShowHistory() && count($historyData) > 0): ?>
                <tr>
                    <td class="descField"><?php echo __('Historial'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Historial'); ?></div>

                        <select id="historyId" name="historyId" class="select-box" tabindex="12"
                                data-action-id="<?php echo SP\Core\ActionsInterface::ACTION_ACC_VIEW_HISTORY; ?>"
                                data-onchange="account/showHistory">
                            <option value=""><?php echo __('Seleccionar fecha'); ?></option>
                            <?php foreach ($historyData as $historyId => $historyData): ?>
                                <option
                                        value="<?php echo $historyId; ?>" <?php echo ($gotData && $accountIsHistory && $historyId === $accountHistoryId) ? 'selected' : ''; ?>><?php echo $historyData; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ($actionId === \SP\Core\ActionsInterface::ACTION_ACC_EDIT && $isModified && $accountData->getUserEditName()): ?>
                <tr>
                    <td class="descField"><?php echo __('Última Modificación'); ?></td>
                    <td class="valField"><?php printf('%s (%s)', $accountData->getAccountDateEdit(), $accountData->getUserEditName()); ?></td>
                </tr>
            <?php endif; ?>

            <?php if ($gotData && $AccountAcl->isShowLink() && $accountData->getPublicLinkHash()): ?>
                <tr>
                    <td class="descField"><?php echo __('Enlace Público'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Enlace Público'); ?></div>

                        <a href="<?php echo $publicLinkUrl; ?>" target="_blank"
                           title="<?php echo __('Enlace Público'); ?>">
                            <i class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
                        </a>
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <?php if (!$isView && $AccountAcl->isShowPermission()): ?>
            <?php include $this->includeTemplate('account-permissions'); ?>
        <?php endif; ?>

        <?php if (count($customFields) > 0): ?>
            <fieldset class="data round">
                <legend>
                    <?php echo __('Campos Personalizados'); ?>
                    <i id="btn-filters" class="material-icons show-extra-info" data-state="0"
                       data-target="#data-customfields"
                       data-icon-down="<?php echo $icons->getIconDown()->getIcon(); ?>"
                       data-icon-up="<?php echo $icons->getIconUp()->getIcon(); ?>"><?php echo $icons->getIconDown()->getIcon(); ?></i>
                </legend>

                <div id="data-customfields">
                    <table>
                        <?php include $this->includeTemplate('aux-customfields', 'common'); ?>
                    </table>
                </div>
            </fieldset>
        <?php endif; ?>

    </form>

    <!--Files box -->
    <?php if ($gotData && $AccountAcl->isShowFiles()): ?>
        <?php include $this->includeTemplate('files'); ?>
    <?php endif; ?>

    <?php if (count($linkedAccounts) > 0): ?>
        <?php include $this->includeTemplate('linkedAccounts'); ?>
    <?php endif; ?>

    <!--More info about account details -->
    <?php if ($gotData && $AccountAcl->isShowDetails()): ?>
        <?php include $this->includeTemplate('details'); ?>
    <?php endif; ?>

    <?php include $this->includeTemplate('actions'); ?>
</div>
<script>
    (function () {
        <?php if (!$isView): ?>
        $('input:text:visible:first').focus();

        $('#selTags').selectize({
                persist: false,
                maxItems: null,
                valueField: 'id',
                labelField: 'name',
                searchField: ['name'],
                plugins: ['remove_button'],
                options: <?php echo $tagsJson; ?>,
                items: <?php echo $gotData ? $accountTagsJson : '{}'; ?>
            }
        );
        <?php endif; ?>

        sysPassApp.sk.set("<?php echo $sk; ?>");
    })();
</script>
