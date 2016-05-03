<div id="title" class="midroundup <?php echo $title['class']; ?>"><?php echo $title['name']; ?></div>
<?php if ($showform): ?>
<form method="post" name="frmaccount" id="frmAccount" onsubmit="sysPassUtil.Common.saveAccount('frmAccount'); return false;">
    <?php endif; ?>
    <?php if ($gotData && $accountIsHistory): ?>
    <table class="data round tblIcon">
        <?php else: ?>
        <table class="data round">
            <?php endif; ?>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <?php if ($showform): ?>
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
                    <?php if ($showform): ?>
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
                    <?php if ($showform): ?>
                        <select id="selCategory" name="categoryId" class="select-box sel-chosen-category" required>
                            <option value=""></option>
                            <?php foreach ($categories as $id => $name): ?>
                                <option
                                    value="<?php echo $id; ?>" <?php echo ($gotData && $id == $accountData->account_categoryId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(\SP\Acl::checkUserAccess(\SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES)): ?>
                            <i class="material-icons" title="<?php echo _('Nueva Categoría'); ?>" onclick="sysPassUtil.Common.doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_MGM; ?>,<?php echo $actionId; ?>, 0)">add</i>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php echo $accountData->category_name; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('URL / IP'); ?></td>
                <td class="valField">
                    <?php if ($showform): ?>
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
                    <?php if ($showform): ?>
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
                        <input name="pass" type="password" id="pass" class="password passwordfield__input" maxlength="255"
                               OnKeyUp="checkPassLevel(this.value)" autocomplete="off">
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                    <td class="valField">
                        <input name="passR" type="password" id="passR" class="password" maxlength="255" autocomplete="off">
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="descField"><?php echo _('Notas'); ?></td>
                <td class="valField">
            <textarea name="notes" cols="30" rows="5" placeholder="<?php echo _('Notas sobre la cuenta'); ?>"
                      maxlength="1000" <?php echo (!$showform) ? 'READONLY' : ''; ?> ><?php echo ($gotData) ? $accountData->account_notes : ''; ?></textarea>
                </td>
            </tr>
            <?php if ($showform): ?>
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
            <?php endif; ?>

            <?php if ($showform): ?>
            <input type="hidden" name="hash" value="<?php echo $changesHash; ?>">
            <input type="hidden" name="next" value="<?php echo $nextaction; ?>">
            <input type="hidden" name="actionId" value="<?php echo $actionId; ?>">
            <?php if ($gotData): ?>
                <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
            <?php endif; ?>
            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
            <input type="hidden" name="isAjax" value="1">
</form>
<?php endif; ?>

<!--Files boxes-->
<?php if ($showFiles): ?>
    <tr>
        <td class="descField"><?php echo _('Archivos'); ?></td>
        <td class="valField">
            <div id="downFiles"></div>
            <?php if ($accountIsHistory): ?>
                <script>sysPassUtil.Common.getFiles(<?php echo $accountParentId; ?>, <?php echo intval($showDelete); ?>, '<?php echo $sk; ?>');</script>
            <?php else: ?>
                <script>sysPassUtil.Common.getFiles(<?php echo $accountId; ?>, <?php echo intval($showDelete); ?>, '<?php echo $sk; ?>');</script>
                <?php if ($actionId == \SP\Controller\ActionsInterface::ACTION_ACC_EDIT): ?>
                    <div id="dropzone" class="round active-tooltip"
                         data-files-ext="<?php echo $filesAllowedExts; ?>"
                         title="<?php echo _('Soltar archivos aquí (max. 5) o click para seleccionar') . '<br><br>' . _('Tamaño máximo de archivo') . ' ' . $maxFileSize . ' MB'; ?>">
                        <img src="imgs/upload.png" alt="upload" class="opacity50"/>
                    </div>
                    <form method="post" enctype="multipart/form-data" name="upload_form" id="fileUploadForm">
                        <input type="file" id="inFile" multiple name="inFile"/>
                    </form>
                    <script>
                        var upload = sysPassUtil.Common.fileUpload({targetId: 'dropzone', url: '/ajax/ajax_files.php'});

                        upload.setRequestData({accountId: <?php echo $accountId; ?>, sk: '<?php echo $sk; ?>', action: 'upload'});
                        upload.setRequestDoneAction(function(){
                            sysPassUtil.Common.getFiles(<?php echo $accountId; ?>, <?php echo intval($showDelete); ?>, '<?php echo $sk; ?>');
                        });
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
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

                    if (historyId > 0)
                        sysPassUtil.Common.doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW_HISTORY; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>, historyId);
                });

                $('#selHistory').chosen({
                    disable_search: true,
                    placeholder_text_single: "<?php echo _('Seleccionar fecha'); ?>"
                });
            </script>
        </td>
    </tr>
<?php endif; ?>

<?php if ($actionId == \SP\Controller\ActionsInterface::ACTION_ACC_EDIT): ?>
    <tr>
        <td class="descField"><?php echo _('Última Modificación'); ?></td>
        <?php if ($accountData->user_editName): ?>
            <td class="valField"><?php echo $accountData->account_dateEdit; ?> <?php echo _('por'); ?> <?php echo $accountData->user_editName; ?></td>
        <?php endif; ?>
    </tr>
<?php endif; ?>
</table>

<?php if($customFields): ?>
<table class="data round extra-info">
    <?php foreach($customFields as $field):?>
            <tr>
                <td class="descField">
                    <?php echo $field->text; ?>
                    <?php if($field->help): ?>
                        <img src="imgs/help.png" title="" class="inputImgMini help-tooltip" />
                        <div class="tooltip" for="help-<?php echo $field->name; ?>" style="display: none;">
                            <p><?php echo $field->help; ?></p>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="valField">
                    <?php if ($showform): ?>
                            <input id="<?php echo $field->name; ?>" name="customfield[<?php echo $field->id; ?>]"
                            type="<?php echo $field->typeName; ?>"
                            class="<?php echo ($field->type === \SP\CustomFieldsBase::TYPE_PASSWORD) ? 'passwordfield__input-show passwordfield__no-pki' : ''; ?>"
                            maxlength="500" value="<?php echo ($gotData) ? \SP\Html::sanitize($field->value) : ''; ?>"
                            <?php echo ($field->required) ? 'required' : ''; ?>>
                    <?php else: ?>
                        <?php if($field->type === \SP\CustomFields::TYPE_PASSWORD && !$showViewPass):?>
                            ****
                        <?php else:?>
                            <?php echo $field->value; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<!--More info about account details-->
<?php if ($showDetails): ?>
    <table class="data round extra-info">
        <tr>
            <td class="descField"><?php echo _('Visitas'); ?></td>
            <td class="valField"><?php echo $accountData->account_countView . "(" . $accountData->account_countDecrypt . ")"; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Fecha Alta'); ?></td>
            <td class="valField"><?php echo $accountData->account_dateAdd ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Creador'); ?></td>
            <td class="valField"><?php echo ($accountData->user_name) ? $accountData->user_name : $accountData->user_login; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Grupo Principal'); ?></td>
            <td class="valField"><?php echo $accountData->usergroup_name; ?></td>
        </tr>
        <?php if (is_array($accountOtherUsers) && count($accountOtherUsers) > 0): ?>
            <tr>
                <td class="descField"><?php echo _('Usuarios Secundarios'); ?></td>
                <td class="valField">
                    <?php
                    foreach ($accountOtherUsersName as $userId => $userName) {
                        if ($userId != $accountData->account_userId) {
                            if (in_array($userId, $accountOtherUsers)) {
                                $accUsers[] = $userName;
                            }
                        }
                    }

                    $usersEdit = ($accountData->account_otherUserEdit) ? '(+)' : '';
                    echo $usersEdit . ' ' . implode(" | ", $accUsers);
                    ?>
                </td>
            </tr>
        <?php endif; ?>
        <?php if (is_array($accountOtherGroups) && count($accountOtherGroups) > 0): ?>
            <tr>
                <td class="descField"><?php echo _('Grupos Secundarios'); ?></td>
                <td class="valField">
                    <?php
                    foreach ($accountOtherGroupsName as $groupId => $groupName) {
                        if ($groupId != $accountData->account_userGroupId) {
                            if (in_array($groupId, $accountOtherGroups)) {
                                $accGroups[] = $groupName;
                            }
                        }
                    }

                    $groupsEdit = ($accountData->account_otherGroupEdit) ? '(+)' : '';

                    echo $groupsEdit . ' ' . implode(" | ", $accGroups);
                    ?>
                </td>
            </tr>
        <?php endif; ?>
        <?php if ($isModified): ?>
            <tr>
                <td class="descField"><?php echo _('Fecha Edición'); ?></td>
                <td class="valField"><?php echo $accountData->account_dateEdit; ?></td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Editor'); ?></td>
                <td class="valField"><?php echo ($accountData->user_editName) ? $accountData->user_editName : $accountData->user_editLogin; ?></td>
            </tr>
        <?php endif; ?>
    </table>
<?php endif; ?>

<?php if ($gotData && $accountIsHistory): ?>
    <form method="post" name="frmAccount" id="frmAccount" onsubmit="sysPassUtil.Common.saveAccount('frmAccount'); return false;">
        <input type="hidden" name="hash" value="<?php echo $changesHash; ?>">
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_RESTORE; ?>">
        <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
<?php endif; ?>

<div class="action">
    <?php if ($showDelete): ?>
        <button type="button" title="<?php echo _('Eliminar Cuenta'); ?>" class="button-action"
            OnClick="sysPassUtil.Common.delAccount(<?php echo $accountId; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_DELETE; ?>,'<?php echo $sk; ?>');">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/delete.png" alt="save" />
        </button>
    <?php endif; ?>

    <?php if ($showRestore): ?>
        <button type="submit" form="frmAccount" title="<?php echo _('Restaurar cuenta desde este punto'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/restore.png" alt="save" />
        </button>
    <?php endif; ?>

    <?php if ($showViewPass): ?>
        <button type="button" title="<?php echo _('Ver Clave'); ?>" class="button-action"
            onClick="sysPassUtil.Common.viewPass(<?php echo $accountId; ?>,1,<?php echo $accountIsHistory; ?>)">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/user-pass.png" alt="save" />
        </button>

        <?php if(!\SP\Util::accountPassToImageIsEnabled()): ?>
        <button type="button" title="<?php echo _('Copiar Clave en Portapapeles'); ?>" class="button-action clip-pass-button"
            data-account-id="<?php echo $accountId; ?>">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/clipboard.png" alt="save" />
        </button>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($showEditPass): ?>
        <button type="button" title="<?php echo _('Modificar Clave de Cuenta'); ?>" class="button-action"
            OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_PASS; ?>, <?php echo $actionId; ?>,<?php echo $accountId; ?>)">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/key.png" alt="save" />
        </button>
    <?php endif; ?>

    <?php if ($gotData && $accountIsHistory): ?>
        <button type="button" title="<?php echo _('Ver Actual'); ?>" class="button-action"
            OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $accountParentId; ?>)">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/back.png" alt="save" />
        </button>
    <?php else: ?>
        <button type="button" title="<?php echo _('Volver'); ?>" class="button-action"
            OnClick="sysPassUtil.Common.doAction(<?php echo $lastAction; ?>, <?php echo $actionId; ?>,<?php echo $accountParentId; ?>)">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/back.png" alt="save" />
        </button>
    <?php endif; ?>

    <?php if ($showEdit): ?>
        <button type="button" title="<?php echo _('Modificar Cuenta'); ?>" class="button-action"
            OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_EDIT; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo $accountId; ?>)">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/edit.png" alt="save" />
        </button>
    <?php elseif (!$showEdit && $actionId == \SP\Controller\ActionsInterface::ACTION_ACC_VIEW && \SP\Util::mailrequestIsEnabled()): ?>
        <button type="button" title="<?php echo _('Solicitar Modificación'); ?>" class="button-action"
            OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_REQUEST; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo $accountId; ?>)">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/request.png" alt="save" />
        </button>
    <?php endif; ?>

    <?php if ($showSave): ?>
        <button type="submit" form="frmAccount" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save" />
        </button>
    <?php endif; ?>
</div>

<?php if ($showform): ?>
    <script>
        $(function(){
            $("#selGroups").chosen({
                placeholder_text_multiple: "<?php echo _('Seleccionar grupos secundarios'); ?>"
            });

            $("#selUsers").chosen({
                placeholder_text_multiple: "<?php echo _('Seleccionar usuarios'); ?>"
            });

            $('input:text:visible:first').focus();

            $('.checkbox').button().click(
                function(){
                    if ($(this).attr('checked') == undefined){
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