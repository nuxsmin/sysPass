<?php /** @var \SP\Account\AccountAcl $AccountAcl */ ?>

<div id="title" class="midroundup titleNormal">
    <?php echo $title['name']; ?>
    <?php if ($gotData && $accountIsHistory): ?>
        <i id="history-icon" class="material-icons"
           title="<?php echo _('Histórico'); ?>"><?php echo $title['icon']; ?></i>
    <?php else: ?>
        <i id="history-icon" class="material-icons"><?php echo $title['icon']; ?></i>
    <?php endif; ?>
</div>

<?php if (!$isView): ?>
<form method="post" name="frmaccount" id="frmAccount" class="form-action" data-onsubmit="account/save" data-hash="">
    <input type="hidden" name="actionId" value="<?php echo $actionId; ?>">
    <?php if ($gotData): ?>
        <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
    <?php endif; ?>
    <input type="hidden" name="sk" value="">
    <input type="hidden" name="isAjax" value="1">

    <?php endif; ?>
    <table class="data round">
        <tr>
            <td class="descField"><?php echo _('Nombre'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo $gotData ? $accountData->account_name : ''; ?>">
                        <label class="mdl-textfield__label" for="name"><?php echo _('Nombre de cuenta'); ?></label>
                    </div>
                <?php else: ?>
                    <?php echo $accountData->account_name; ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Cliente'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <select id="selCustomer" name="customerId" class="select-box sel-chosen-customer">
                        <option value=""><?php echo _('Seleccionar Cliente'); ?></option>
                        <?php foreach ($customers as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($gotData && $id == $accountData->account_customerId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="material-icons btn-action" title="<?php echo _('Nuevo Cliente'); ?>"
                       data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW; ?>"
                       data-item-id="0"
                       data-item-type="<?php echo \SP\Core\ItemsTypeInterface::ITEM_CUSTOMERS; ?>"
                       data-item-dst="selCustomer"
                       data-onclick="appMgmt/show"><?php echo $icons->getIconAdd()->getIcon(); ?></i>
                <?php else: ?>
                    <?php echo $accountData->customer_name; ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Categoría'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <select id="selCategory" name="categoryId" class="select-box sel-chosen-category" required>
                        <option value=""><?php echo _('Seleccionar Categoría'); ?></option>
                        <?php foreach ($categories as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($gotData && $id == $accountData->account_categoryId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (\SP\Core\Acl::checkUserAccess(\SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES)): ?>
                        <i class="material-icons btn-action" title="<?php echo _('Nueva Categoría'); ?>"
                           data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_NEW; ?>"
                           data-item-id="0"
                           data-item-type="<?php echo \SP\Core\ItemsTypeInterface::ITEM_CATEGORIES; ?>"
                           data-item-dst="selCategory"
                           data-onclick="appMgmt/show"><?php echo $icons->getIconAdd()->getIcon(); ?></i>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo $accountData->category_name; ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('URL / IP'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="url" name="url" type="text" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" value="<?php echo $gotData ? $accountData->account_url : ''; ?>">
                        <label class="mdl-textfield__label" for="name"><?php echo _('URL o IP de acceso'); ?></label>
                    </div>
                <?php else: ?>
                    <?php echo $accountData->account_url; ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Usuario'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="login" name="login" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo $gotData ? $accountData->account_login : ''; ?>">
                        <label class="mdl-textfield__label" for="name"><?php echo _('Usuario de acceso'); ?></label>
                    </div>
                <?php else: ?>
                    <?php echo $accountData->account_login; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($AccountAcl->isShowPass()): ?>
            <tr>
                <td class="descField"><?php echo _('Clave'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="accountpass" name="pass" type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               maxlength="255"
                               autocomplete="off">
                        <label class="mdl-textfield__label" for="accountpass"><?php echo _('Clave'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="accountpassR" name="passR" type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="accountpassR"><?php echo _('Clave (Repetir)'); ?></label>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="descField"><?php echo _('Notas'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea class="mdl-textfield__input mdl-color-text--indigo-400" rows="3" id="notes"
                                  name="notes"
                                  maxlength="1000"><?php echo $gotData ? $accountData->account_notes : ''; ?></textarea>
                        <label class="mdl-textfield__label"
                               for="notes"><?php echo _('Notas sobre la cuenta'); ?></label>
                    </div>
                <?php else: ?>
                    <?php echo $accountData->account_notes; ?>
                <?php endif; ?>

        </tr>
        <tr>
            <td class="descField"><?php echo _('Etiquetas'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <select id="selTags" name="tags[]" multiple="multiple">
                        <option value=""><?php echo _('Seleccionar Etiquetas'); ?></option>
                    </select>
                <?php else: ?>
                    <?php foreach ($accountTags as $tag): ?>
                        <span class="tag"><?php echo $tag; ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($AccountAcl->isShowHistory() && is_array($historyData) && count($historyData) > 0): ?>
            <tr>
                <td class="descField"><?php echo _('Historial'); ?></td>
                <td class="valField">
                    <select id="historyId" name="historyId" class="select-box"
                            data-action-id="<?php echo SP\Core\ActionsInterface::ACTION_ACC_VIEW_HISTORY; ?>"
                            data-onchange="account/showHistory">
                        <option value=""><?php echo _('Seleccionar fecha'); ?></option>
                        <?php foreach ($historyData as $historyId => $historyDate): ?>
                            <option
                                value="<?php echo $historyId; ?>" <?php echo ($gotData && $accountIsHistory && $historyId === $accountId) ? 'selected' : ''; ?>><?php echo $historyDate; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($actionId === \SP\Core\ActionsInterface::ACTION_ACC_EDIT && $accountData->user_editName): ?>
            <tr>
                <td class="descField"><?php echo _('Última Modificación'); ?></td>
                <?php if ($accountData->user_editName): ?>
                    <td class="valField"><?php echo $accountData->account_dateEdit, ' ', _('por'), ' ', $accountData->user_editName; ?></td>
                <?php endif; ?>
            </tr>
        <?php endif; ?>

    </table>

    <?php if (!$isView): ?>
        <?php include $this->includeTemplate('account-permissions'); ?>
    <?php endif; ?>

    <?php if ($customFields): ?>
        <table class="data round extra-info secure-info">
            <?php include $this->includeTemplate('aux-customfields', 'common'); ?>
        </table>
    <?php endif; ?>

    <?php if (!$isView): ?>
</form>
<?php endif; ?>

<!--Files box -->
<?php if ($gotData && $AccountAcl->isShowFiles()): ?>
    <?php include $this->includeTemplate('files'); ?>
<?php endif; ?>

<!--More info about account details -->
<?php if ($gotData && $AccountAcl->isShowDetails()): ?>
    <?php include $this->includeTemplate('details'); ?>
<?php endif; ?>

<?php if ($gotData && $accountIsHistory): ?>
    <form method="post" name="frmAccount" id="frmAccount"
          onsubmit="sysPassUtil.Common.saveAccount('frmAccount'); return false;">
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT_RESTORE; ?>">
        <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1">
    </form>
<?php endif; ?>

<?php include $this->includeTemplate('actions'); ?>

<script>
    $(function () {
        <?php if (!$isView): ?>
        $('input:text:visible:first').focus();

        var accountTags = <?php echo $gotData ? $accountTagsJson : '{}'; ?>;
        var availableTags = <?php echo $tagsJson; ?>;

        $('#selTags').selectize({
                persist: false,
                maxItems: null,
                valueField: 'tag_id',
                labelField: 'tag_name',
                searchField: ['tag_name'],
                plugins: ['remove_button'],
                options: availableTags,
                items: accountTags
            }
        );

        var otherUsers = <?php echo $otherUsersJson; ?>;

        $('#otherusers').selectize({
            persist: false,
            valueField: 'user_id',
            labelField: 'user_login',
            searchField: ['user_login'],
            plugins: ['remove_button'],
            options: otherUsers,
            onInitialize: function () {
                var userId = <?php echo $gotData ? $accountData->account_userId : 0; ?>;

                if (userId > 0) {
                    this.removeOption(userId);
                }
            }
        });

        var otherGroups = <?php echo $otherGroupsJson; ?>;

        $('#othergroups').selectize({
            persist: false,
            valueField: 'usergroup_id',
            labelField: 'usergroup_name',
            searchField: ['usergroup_name'],
            plugins: ['remove_button'],
            options: otherGroups,
            onInitialize: function () {
                var userGroupId = <?php echo $gotData ? $accountData->account_userGroupId : 0; ?>;

                if (userGroupId > 0) {
                    this.removeOption(userGroupId);
                }
            }
        });
        <?php endif; ?>


        sysPassApp.sk.set("<?php echo $sk; ?>");
        sysPassApp.triggers().views.account();
    });
</script>
