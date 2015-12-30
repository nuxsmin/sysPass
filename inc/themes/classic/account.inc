<div id="title" class="midroundup <?php echo $title['class']; ?>"><?php echo $title['name']; ?></div>
<?php if (!$isView): ?>
<form method="post" name="frmaccount" id="frmAccount"
      onsubmit="sysPassUtil.Common.saveAccount('frmAccount'); return false;">
    <input type="hidden" name="hash" value="<?php echo $changesHash; ?>">
    <input type="hidden" name="next" value="<?php echo $nextaction; ?>">
    <input type="hidden" name="actionId" value="<?php echo $actionId; ?>">
    <?php if ($gotData): ?>
        <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
    <?php endif; ?>
    <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    <input type="hidden" name="isAjax" value="1">
    <?php endif; ?>

    <table class="data round <?php echo ($gotData && $accountIsHistory) ? 'tblIcon' : ''; ?>">
        <tr>
            <td class="descField"><?php echo _('Nombre'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <input name="name" type="text" placeholder="<?php echo _('Nombre de cuenta'); ?>" required
                           maxlength="50"
                           value="<?php echo ($gotData) ? $accountData->account_name : ''; ?>">
                <?php else: ?>
                    <?php echo $accountData->account_name; ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Cliente'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <select id="selCustomer" name="customerId" class="select-box sel-chosen-customer" required>
                        <option value=""></option>
                        <?php foreach ($customers as $id => $name): ?>
                            <option
                                value="<?php echo $id; ?>" <?php echo ($gotData && $id == $accountData->account_customerId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <br><br>
                    <input type="text" name="customer_new" maxlength="50"
                           placeholder="<?php echo _('Buscar en desplegable o introducir'); ?>"/>
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
                           onclick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_MGM; ?>,<?php echo $actionId; ?>, 0)">add</i>
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
                    <input name="url" type="text" placeholder="<?php echo _('URL o IP de acceso'); ?>"
                           maxlength="255"
                           value="<?php echo ($gotData) ? $accountData->account_url : ''; ?>">
                <?php else: ?>
                    <?php echo $accountData->account_url; ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Usuario'); ?></td>
            <td class="valField">
                <?php if (!$isView): ?>
                    <input name="login" type="text" placeholder="<?php echo _('Usuario de acceso'); ?>"
                           maxlength="50"
                           value="<?php echo ($gotData) ? $accountData->account_login : ''; ?>">
                <?php else: ?>
                    <?php echo $accountData->account_login; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($showPass): ?>
            <tr>
                <td class="descField"><?php echo _('Clave'); ?></td>
                <td class="valField">
                    <input name="pass" type="password" id="pass" class="password passwordfield__input"
                           maxlength="255"
                           OnKeyUp="checkPassLevel(this.value)" autocomplete="off">
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                <td class="valField">
                    <input name="passR" type="password" id="passR" class="password" maxlength="255"
                           autocomplete="off">
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="descField"><?php echo _('Notas'); ?></td>
            <td class="valField">
            <textarea name="notice" cols="30" rows="5" placeholder="<?php echo _('Notas sobre la cuenta'); ?>"
                      maxlength="1000" <?php echo ($isView) ? 'READONLY' : ''; ?> ><?php echo ($gotData) ? $accountData->account_notes : ''; ?></textarea>
            </td>
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
                            <span><?php echo _('Hablitar edición'); ?></span>
                            <label for="ueditenabled"><?php echo ($chkUserEdit) ? _('SI') : _('NO'); ?></label>
                            <input type="checkbox" name="ueditenabled" id="ueditenabled"
                                   class="checkbox" <?php echo $chkUserEdit; ?> />
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
                            <span><?php echo _('Hablitar edición'); ?></span>
                            <label for="geditenabled"><?php echo ($chkGroupEdit) ? _('SI') : _('NO'); ?></label>
                            <input type="checkbox" name="geditenabled" id="geditenabled"
                                   class="checkbox" <?php echo $chkGroupEdit; ?> />
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
                    <td class="valField"><?php echo $accountData->account_dateEdit, ' ',  _('por'), ' ', $accountData->user_editName; ?></td>
                <?php endif; ?>
            </tr>
        <?php endif; ?>

    </table>

    <?php if ($customFields): ?>
        <table class="data round extra-info">
            <?php include 'aux-customfields.inc'; ?>
        </table>
    <?php endif; ?>

<?php if (!$isView): ?>
</form>
<?php endif; ?>

<!--Files boxes-->
<?php if ($showFiles): include 'account-files.inc'; endif; ?>

<!--More info about account details-->
<?php if ($showDetails): include 'account-details.inc'; endif; ?>

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

            $('.checkbox').button().click(
                function () {
                    if ($(this).attr('checked') == undefined) {
                        $(this).button('option', 'label', '<?php echo _('NO'); ?>');
                    } else {
                        $(this).button('option', 'label', '<?php echo _('SI'); ?>');
                    }
                }
            );
        });
    </script>
<?php endif; ?>

<?php if ($showViewPass): ?>
    <div id="clip-pass-text" style="visibility: hidden"></div>
<?php endif; ?>