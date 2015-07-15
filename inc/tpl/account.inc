<div id="title" class="midroundup <?php echo $title['class']; ?>"><?php echo $title['name']; ?></div>
<?php if ($showform): ?>
<form method="post" name="frmaccount" id="frmAccount">
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
                        <select id="selCustomer" name="customerId" class="select-box">
                            <option value="0"></option>
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
                        <select id="selCategory" name="categoryId" class="select-box">
                            <option value="0"></option>
                            <?php foreach ($categories as $id => $name): ?>
                                <option
                                    value="<?php echo $id; ?>" <?php echo ($gotData && $id == $accountData->account_categoryId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <input name="password" type="password" id="txtPass" class="password" maxlength="255"
                               OnKeyUp="checkPassLevel(this.value)" autocomplete="off">
                        <img class="showpass inputImg" src="imgs/show.png" title="<?php echo _('Mostrar Clave'); ?>"
                         alt="<?php echo _('Mostrar Clave'); ?>"/>
                        <img id="passGen" src="imgs/genpass.png" title="<?php echo _('Generar clave aleatoria'); ?>"
                             class="inputImg"/>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                    <td class="valField">
                        <input name="password2" type="password" class="password" maxlength="255" autocomplete="off">
                        <span class="passLevel fullround"
                              title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="descField"><?php echo _('Notas'); ?></td>
                <td class="valField">
            <textarea name="notice" cols="30" rows="5" placeholder="<?php echo _('Notas sobre la cuenta'); ?>"
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
                <script>getFiles(<?php echo $accountParentId; ?>, <?php echo intval($showDelete); ?>, '<?php echo $sk; ?>');</script>
            <?php else: ?>
                <script>getFiles(<?php echo $accountId; ?>, <?php echo intval($showDelete); ?>, '<?php echo $sk; ?>');    </script>
                <?php if ($actionId == \SP\Controller\ActionsInterface::ACTION_ACC_EDIT): ?>
                    <form method="post" enctypr="multipart/form-data" name="upload_form" id="fileUpload">
                        <input type="file" id="inFile" name="inFile"/>
                    </form>
                    <div id="dropzone" class="round"
                         data-files-ext="<?php echo $filesAllowedExts; ?>"
                         title="<?php echo _('Soltar archivos aquí (max. 5) o click para seleccionar') . '<br><br>' . _('Tamaño máximo de archivo') . ' ' . $maxFileSize . ' MB'; ?>">
                        <img src="imgs/upload.png" alt="upload" class="opacity50"/>
                    </div>
                    <script> dropFile(<?php echo $accountId; ?>, '<?php echo $sk; ?>', <?php echo $maxFileSize; ?>); </script>
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
                        value="<?php echo $id; ?>" <?php echo ($gotData && $id === $accountId) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <script>
                $('#selHistory').on('change', function (e) {
                    var historyId = $('#selHistory').val();

                    if (historyId > 0)
                        doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW_HISTORY; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>, historyId);
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
    <form method="post" name="frmaccount" id="frmAccount">
        <input type="hidden" name="hash" value="<?php echo $changesHash; ?>">
        <input type="hidden" name="actionId"
               value="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_RESTORE; ?>">
        <input type="hidden" name="accountid" value="<?php echo $accountId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
<?php endif; ?>

<div class="action">
    <ul>
        <?php if ($gotData && $accountIsHistory): ?>
            <li>
                <img src="imgs/back.png" title="<?php echo _('Ver Actual'); ?>" class="inputImg" id="btnBack"
                     OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $accountParentId; ?>)"/>
            </li>
        <?php else: ?>
            <li>
                <img src="imgs/back.png" title="<?php echo _('Atrás'); ?>" class="inputImg" id="btnBack"
                     OnClick="doAction(<?php echo $lastAction; ?>, <?php echo $actionId; ?>,<?php echo $accountParentId; ?>)"/>
            </li>
        <?php endif; ?>

        <?php if ($showRestore): ?>
            <li>
                <img src="imgs/restore.png" title="<?php echo _('Restaurar cuenta desde este punto'); ?>"
                     class="inputImg" id="btnRestore"
                     OnClick="saveAccount('frmAccount');"/>
            </li>
        <?php endif; ?>

        <?php if ($showViewPass): ?>
            <li>
                <img src="imgs/user-pass.png" title="<?php echo _('Ver Clave'); ?>"
                     onClick="viewPass(<?php echo $accountId; ?>,1,<?php echo $accountIsHistory; ?>)"
                     class="inputImg"/>
            </li>
            <li>
                <img src="imgs/clipboard.png" title="<?php echo _('Copiar Clave en Portapapeles'); ?>"
                     onmousedown="viewPass(<?php echo $accountId; ?>, false, <?php echo $accountIsHistory; ?>)"
                     data-clipboard-target="clip-pass-text"
                     class="inputImg clip-pass-button"/>
            </li>
        <?php endif; ?>

        <?php if ($showSave): ?>
            <li>
                <img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" id="btnSave"
                     OnClick="saveAccount('frmAccount');"/>
            </li>
        <?php endif; ?>

        <?php if ($showEditPass): ?>
            <li>
                <img src="imgs/key.png" title="<?php echo _('Modificar Clave de Cuenta'); ?>" class="inputImg"
                     OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_PASS; ?>, <?php echo $actionId; ?>,<?php echo $accountId; ?>)"/>
            </li>
        <?php endif; ?>

        <?php if ($showEdit): ?>
            <li>
                <img src="imgs/edit.png" title="<?php echo _('Modificar Cuenta'); ?>" class="inputImg"
                     OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_EDIT; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo $accountId; ?>)"/>
            </li>
        <?php elseif (!$showEdit && $actionId == \SP\Controller\ActionsInterface::ACTION_ACC_VIEW && \SP\Util::mailrequestIsEnabled()): ?>
            <li>
                <img src="imgs/request.png" title="<?php echo _('Solicitar Modificación'); ?>" class="inputImg"
                     OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_REQUEST; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo $accountId; ?>)"/>
            </li>
        <?php endif; ?>

        <?php if ($showDelete): ?>
            <li>
                <img src="imgs/delete.png" title="<?php echo _('Eliminar Cuenta'); ?>" class="inputImg"
                     OnClick="delAccount(<?php echo $accountId; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_DELETE; ?>,'<?php echo $sk; ?>');"/>
            </li>
        <?php endif; ?>
    </ul>
</div>

<?php if ($showform): ?>
    <script>
        $("#selCustomer").chosen({
            placeholder_text_single: "<?php echo _('Seleccionar Cliente'); ?>",
            disable_search_threshold: 10,
            no_results_text: "<?php echo _('Sin resultados'); ?>"
        });
        $("#selCategory").chosen({
            placeholder_text_single: "<?php echo _('Seleccionar Categoría'); ?>",
            disable_search_threshold: 10,
            no_results_text: "<?php echo _('Sin resultados'); ?>"
        });
        $("#selGroups").chosen({
            placeholder_text_multiple: "<?php echo _('Seleccionar grupos secundarios'); ?>"
        });
        $("#selUsers").chosen({
            placeholder_text_multiple: "<?php echo _('Seleccionar usuarios'); ?>"
        });
        $('input:text:visible:first').focus();
        $('.checkbox').button();
        $('.ui-button').click(function () {
            // El cambio de clase se produce durante el evento de click
            // Si tiene la clase significa que el estado anterior era ON y ahora es OFF
            if ($(this).hasClass('ui-state-active')) {
                $(this).children().html('<?php echo _('NO'); ?>');
            } else {
                $(this).children().html('<?php echo _('SI'); ?>');
            }
        });

        $('#passGen').click(function () {
            password(11, true, true);
        });

        $('.showpass').click(function () {
            var passInput = $(this).prev();

            if (passInput.attr('type') == 'password') {
                passInput.get(0).type = 'text';
            } else {
                passInput.get(0).type = 'password';
            }
        })
    </script>
<?php endif; ?>

<?php if ($showViewPass): ?>
    <div id="clip-pass-text" style="visibility: hidden"></div>

    <script>
        passToClip = 0;

        function clipboard() {
            var client = new ZeroClipboard($('.clip-pass-button'), {
                swfPath: "js/ZeroClipboard.swf",
                debug: false,
                title: '<?php echo _('Copiar Clave en Portapapeles'); ?>'
            });

            client.on("aftercopy", function (e) {
                resMsg("ok", "<?php echo _('Clave Copiada al Portapapeles'); ?>");
            });

            client.on("error", function (e) {
                ZeroClipboard.destroy();
            });
        }

        clipboard();
    </script>
<?php endif; ?>