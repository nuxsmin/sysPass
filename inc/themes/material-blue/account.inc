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
<form method="post" name="frmaccount" id="frmAccount"
      onsubmit="sysPassUtil.Common.saveAccount('frmAccount'); return false;">
    <input type="hidden" name="hashcf" value="<?php echo $customFields['hash']; ?>"/>
    <input type="hidden" name="hash" value="<?php echo $changesHash; ?>">
    <input type="hidden" name="next" value="<?php echo $nextaction; ?>">
    <input type="hidden" name="actionId" value="<?php echo $actionId; ?>">
    <?php if ($gotData): ?>
        <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
    <?php endif; ?>
    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    <input type="hidden" name="isAjax" value="1">

    <?php endif; ?>
    <table class="data round">
        <tr>
            <td class="descField"><?php echo _('Nombre'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo ($gotData) ? $accountData->account_name : ''; ?>">
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
                        <option value=""></option>
                        <?php foreach ($customers as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($gotData && $id == $accountData->account_customerId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br><br>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="customer_new" name="customer_new" type="text" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo _('Seleccionar o escribir para crear uno nuevo'); ?></label>
                    </div>
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
                        <option value=""></option>
                        <?php foreach ($categories as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($gotData && $id == $accountData->account_categoryId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (\SP\Core\Acl::checkUserAccess(\SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES)): ?>
                        <i class="material-icons" title="<?php echo _('Nueva Categoría'); ?>"
                           onclick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_MGM; ?>,<?php echo $actionId; ?>, 0)"><?php echo $icons->getIconAdd()->getIcon(); ?></i>
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
                               maxlength="255" value="<?php echo ($gotData) ? $accountData->account_url : ''; ?>">
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
                        <input id="login" name="login" type="text" required class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo ($gotData) ? $accountData->account_login : ''; ?>">
                        <label class="mdl-textfield__label" for="name"><?php echo _('Usuario de acceso'); ?></label>
                    </div>
                <?php else: ?>
                    <?php echo $accountData->account_login; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($showPass): ?>
            <tr>
                <td class="descField"><?php echo _('Clave'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="accountpass" name="pass" type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input" maxlength="255"
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
                        <textarea class="mdl-textfield__input mdl-color-text--indigo-400" rows="3" id="notes" name="notes"
                                  maxlength="1000"><?php echo ($gotData) ? $accountData->account_notes : ''; ?></textarea>
                        <label class="mdl-textfield__label"
                               for="notes"><?php echo _('Notas sobre la cuenta'); ?></label>
                    </div>
                <?php else: ?>
                    <?php echo $accountData->account_notes; ?>
                <?php endif; ?>

        </tr>
        <?php if (!$isView): ?>
            <tr>
                <td class="descField"><?php echo _('Permisos'); ?></td>
                <td class="valField">
                    <div class="account-permissions">
                        <fieldset class="round5">
                            <legend><?php echo _('Usuarios'); ?></legend>
                            <select id="selUsers" name="otherusers[]" multiple="multiple">
                                <?php
                                $users = array_flip($otherUsers);

                                foreach ($users as $otherUserName => $otherUserId) {
                                    $userSelected = '';

                                    if ($gotData && $otherUserId != $accountData->account_userId) {
                                        if (is_array($accountOtherUsers)) {
                                            $userSelected = (in_array($otherUserId, $accountOtherUsers)) ? "selected" : "";
                                        }
                                        echo "<option value='" . $otherUserId . "' $userSelected>" . $otherUserName . "</option>";
                                    } else {
                                        if ($userId === $otherUserId) {
                                            continue;
                                        }
                                        echo "<option value='" . $otherUserId . "' $userSelected>" . $otherUserName . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <br><br>
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ueditenabled">
                                <input name="ueditenabled" type="checkbox" id="ueditenabled"
                                       class="mdl-switch__input" <?php echo $chkUserEdit; ?>/>
                                <span class="mdl-switch__label"><?php echo _('Hablitar edición'); ?></span>
                            </label>

                        </fieldset>
                    </div>
                    <div class="account-permissions">
                        <fieldset class="round5">
                            <legend><?php echo _('Grupos'); ?></legend>
                            <select id="selGroups" name="othergroups[]" multiple="multiple">
                                <?php
                                $groups = array_flip($otherGroups);

                                foreach ($groups as $otherGroupName => $otherGroupId) {
                                    $uGroupSelected = '';

                                    if ($gotData && $otherGroupId != $accountData->account_userGroupId) {
                                        if (is_array($accountOtherGroups)) {
                                            $uGroupSelected = (in_array($otherGroupId, $accountOtherGroups)) ? "selected" : "";
                                        }
                                        echo "<option value='" . $otherGroupId . "' $uGroupSelected>" . $otherGroupName . "</option>";
                                    } else {
                                        if ($userGroupId === $otherGroupId) {
                                            continue;
                                        }
                                        echo "<option value='" . $otherGroupId . "' $uGroupSelected>" . $otherGroupName . "</option>";
                                    }
                                }
                                ?>
                            </select>
                            <br><br>
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="geditenabled">
                                <input name="geditenabled" type="checkbox" id="geditenabled"
                                       class="mdl-switch__input" <?php echo $chkGroupEdit; ?>/>
                                <span class="mdl-switch__label"><?php echo _('Hablitar edición'); ?></span>
                            </label

                        </fieldset>
                    </div>
                </td>
            </tr>
            <?php if ($userIsAdminApp || $userIsAdminAcc): ?>
                <tr>
                    <td class="descField"><?php echo _('Grupo Principal'); ?></td>
                    <td class="valField">
                        <select id="selMainGroupId" name="mainGroupId" class="select-box sel-chosen-usergroup" required>
                            <option value="0"></option>
                            <?php foreach ($otherGroups as $id => $name): ?>
                                <option
                                    value="<?php echo $id; ?>" <?php echo ($gotData && $id == $accountData->account_userGroupId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>

        <?php endif; ?>

        <?php if ($showHistory && is_array($historyData) && count($historyData) > 0): ?>
            <tr>
                <td class="descField"><?php echo _('Historial'); ?></td>
                <td class="valField">
                    <select id="selHistory" name="historyId" class="select-box">
                        <option value="0"></option>
                        <?php foreach ($historyData as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($gotData && $accountIsHistory && $id === $accountId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <script>
                        $('#selHistory').on('change', function (e) {
                            var historyId = $('#selHistory').val();

                            if (historyId > 0) {
                                sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW_HISTORY; ?>, <?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>, historyId);
                            }
                        });

                        $('#selHistory').chosen({
                            disable_search: true,
                            placeholder_text_single: "<?php echo _('Seleccionar fecha'); ?>"
                        });
                    </script>
                </td>
            </tr>
        <?php endif; ?>

        <?php if ($actionId == \SP\Core\ActionsInterface::ACTION_ACC_EDIT && $accountData->user_editName): ?>
            <tr>
                <td class="descField"><?php echo _('Última Modificación'); ?></td>
                <?php if ($accountData->user_editName): ?>
                    <td class="valField"><?php echo $accountData->account_dateEdit, ' ', _('por'), ' ', $accountData->user_editName; ?></td>
                <?php endif; ?>
            </tr>
        <?php endif; ?>

    </table>

    <?php if ($customFields): ?>
        <table class="data round extra-info secure-info">
            <?php include 'aux-customfields.inc'; ?>
        </table>
    <?php endif; ?>

<?php if (!$isView): ?>
</form>
<?php endif; ?>

<!--Files box-->
<?php if ($showFiles): ?>
    <?php include 'account-files.inc'; ?>
<?php endif; ?>

<!--More info about account details-->
<?php if ($showDetails): ?>
    <?php include 'account-details.inc'; ?>
<?php endif; ?>

<?php if ($gotData && $accountIsHistory): ?>
    <form method="post" name="frmAccount" id="frmAccount"
          onsubmit="sysPassUtil.Common.saveAccount('frmAccount'); return false;">
        <input type="hidden" name="hash" value="<?php echo $changesHash; ?>">
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT_RESTORE; ?>">
        <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
<?php endif; ?>

<?php include 'account-actions.inc'; ?>

<?php if (!$isView): ?>
    <script>
        $(function () {
            $("#selGroups").chosen({
                placeholder_text_multiple: "<?php echo _('Seleccionar grupos secundarios'); ?>"
            });
            $("#selUsers").chosen({
                placeholder_text_multiple: "<?php echo _('Seleccionar usuarios'); ?>"
            });
            $('input:text:visible:first').focus();
        });
    </script>
<?php endif; ?>

<?php if ($showViewPass): ?>
    <div id="clip-pass-text" style="visibility: hidden"></div>
<?php endif; ?>