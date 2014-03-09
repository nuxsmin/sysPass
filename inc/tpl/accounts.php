<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

$action = $data['action'];

$account = new SP_Account;
$account->accountId = $data['id'];
$account->lastAction = $data['lastaction'];
$account->accountParentId = SP_Common::parseParams('s', 'accParentId', 0);

$userId = SP_Common::parseParams('s', 'uid', 0);
$userGroupId = SP_Common::parseParams('s', 'ugroup', 0);
$userIsAdminApp = SP_Common::parseParams('s', 'uisadminapp', 0);
$userIsAdminAcc = SP_Common::parseParams('s', 'uisadminacc', 0);

$changesHash = '';
$chkUserEdit = '';
$chkGroupEdit = '';

switch ($action) {
    case 'accnew':
        $savetype = 1;
        $title = array('class' => 'titleGreen', 'name' => _('Nueva Cuenta'));
        $showform = true;
        $nextaction = 'accsearch';
        break;
    case "acccopy":
        $savetype = 1;
        $title = array('class' => 'titleGreen', 'name' => _('Copiar Cuenta'));
        $showform = true;
        $nextaction = 'acccopy';

        $accountUsers = $account->getUsersAccount();
        $accountGroups = $account->getGroupsAccount();
        $accountData = $account->getAccount();
        break;
    case "accedit":
        $savetype = 2;
        $title = array('class' => 'titleOrange', 'name' => _('Editar Cuenta'));
        $showform = true;
        $nextaction = 'accedit';

        $accountUsers = $account->getUsersAccount();
        $accountGroups = $account->getGroupsAccount();
        $accountData = $account->getAccount();
        break;
    case "accdelete":
        $savetype = 0;
        $title = array('class' => 'titleRed', 'name' => _('Eliminar Cuenta'));
        $showform = false;

        $accountData = $account->getAccount();
        break;
    case "accview":
        $savetype = 0;
        $title = array('class' => 'titleNormal', 'name' => _('Detalles de Cuenta'));
        $showform = false;

        $_SESSION["accParentId"] = $data['id'];
        $account->incrementViewCounter();
        $accountUsers = $account->getUsersAccount();
        $accountGroups = $account->getGroupsAccount();
        $accountData = $account->getAccount();
        break;
    case "accviewhistory":
        $savetype = 5;
        $title = array('class' => 'titleNormal', 'name' => _('Detalles de Cuenta'));
        $showform = false;

        $account->accountIsHistory = true;
        $accountUsers = $account->getUsersAccount();
        $accountGroups = $account->getGroupsAccount();
        $accountData = $account->getAccountHistory();
        break;
    default :
        return;
}

if ($data['id'] > 0) {
    // Comprobar permisos de acceso
    SP_ACL::checkAccountAccess($action, $account->getAccountDataForACL()) || SP_Html::showCommonError('noaccpermission');

    $changesHash = $account->calcChangesHash();
    $chkUserEdit = ($accountData->account_otherUserEdit) ? 'checked' : '';
    $chkGroupEdit = ($accountData->account_otherGroupEdit) ? 'checked' : '';
}

$customersSelProp = array("name" => "customerId",
    "id" => "selCustomer",
    "class" => "",
    "size" => 1,
    "label" => "",
    "selected" => $accountData->account_customerId,
    "default" => "",
    "js" => "",
    "attribs" => "");

$categoriesSelProp = array("name" => "categoryId",
    "id" => "selCategory",
    "class" => "",
    "size" => 1,
    "label" => "",
    "selected" => $accountData->account_categoryId,
    "default" => "",
    "js" => "",
    "attribs" => "");

$isModified = ($accountData->account_dateEdit && $accountData->account_dateEdit <> '0000-00-00 00:00:00');
$showHistory = (($action == 'accview' || $action == 'accviewhistory') && SP_ACL::checkUserAccess("accviewhistory") && ($isModified || $action == 'accviewhistory'));
$showDetails = ($action == 'accview' || $action == 'accviewhistory' || $action == 'accdelete');
$showPass = ($action == "accnew" || $action == 'acccopy');
$showFiles = (($action == "accedit" || $action == "accview" || $action == "accviewhistory")
    && (SP_Util::fileIsEnabled() && SP_ACL::checkUserAccess("accfiles")));
$showViewPass = (($action == "accview" || $action == "accviewhistory")
    && (SP_ACL::checkAccountAccess("accviewpass", $account->getAccountDataForACL()) && SP_ACL::checkUserAccess("accviewpass")));
$showSave = ($action == "accedit" || $action == "accnew" || $action == "acccopy");
$showEdit = ($action == "accview"
    && SP_ACL::checkAccountAccess("accedit", $account->getAccountDataForACL())
    && SP_ACL::checkUserAccess("accedit")
    && !$account->accountIsHistory);
$showEditPass = ($action == "accedit"
    && SP_ACL::checkAccountAccess("acceditpass", $account->getAccountDataForACL())
    && SP_ACL::checkUserAccess("acceditpass")
    && !$account->accountIsHistory);
$showDelete = ($action == "accdelete"
    && SP_ACL::checkAccountAccess("accdelete", $account->getAccountDataForACL())
    && SP_ACL::checkUserAccess("accdelete"));
$showRestore = ($action == "accviewhistory"
    && SP_ACL::checkAccountAccess("accedit", $account->getAccountDataForACL($account->accountParentId))
    && SP_ACL::checkUserAccess("accedit"));
$filesDelete = ($action == 'accedit') ? 1 : 0;
$skey = SP_Common::getSessionKey(true);
$maxFileSize = round(SP_Config::getValue('files_allowed_size') / 1024, 1);
?>

    <div id="title" class="midroundup <?php echo $title['class']; ?>"><?php echo $title['name']; ?></div>
<?php if ($showform): ?>
    <form METHOD="post" name="frmaccount" id="frmAccount">
<?php endif; ?>
<?php if ($account->accountIsHistory): ?>
    <table class="data round tblIcon">
    <?php else: ?>
    <table class="data round">
<?php endif; ?>
    <tr>
        <td class="descField"><?php echo _('Nombre'); ?></td>
        <td class="valField">
            <?php
            if ($showform) {
                ?>
                <input name="name" type="text" placeholder="<?php echo _('Nombre de cuenta'); ?>" required
                       maxlength="50"
                       value="<?php echo ($action != 'accnew') ? $accountData->account_name : ''; ?>">
            <?php
            } else {
                echo $accountData->account_name;
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Cliente'); ?></td>
        <td class="valField">
            <?php
            if ($showform) {
                SP_Html::printSelect(DB::getValuesForSelect('customers', 'customer_id', 'customer_name'), $customersSelProp);
                ?>
                <br><br>
                <input type="text" name="customer_new" maxlength="50"
                       placeholder="<?php echo _('Buscar en desplegable o introducir'); ?>"/>
            <?php
            } else {
                echo $accountData->customer_name;
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Categoría'); ?></td>
        <td class="valField">
            <?php
            if ($showform) {
                SP_Html::printSelect(DB::getValuesForSelect('categories', 'category_id', 'category_name'), $categoriesSelProp);
            } else {
                echo $accountData->category_name;
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('URL / IP'); ?></td>
        <td class="valField">
            <?php
            if ($showform) {
                ?>
                <input name="url" type="text" placeholder="<?php echo _('URL o IP de acceso'); ?>"
                       maxlength="255"
                       value="<?php echo ($action != 'accnew') ? $accountData->account_url : ''; ?>">
            <?php
            } else {
                echo $accountData->account_url;
            }
            ?>
        </td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Usuario'); ?></td>
        <td class="valField">
            <?php
            if ($showform) {
                ?>
                <input name="login" type="text" placeholder="<?php echo _('Usuario de acceso'); ?>"
                       maxlength="50"
                       value="<?php echo ($action != 'accnew') ? $accountData->account_login : ''; ?>">
            <?php
            } else {
                echo $accountData->account_login;
            }
            ?>
        </td>
    </tr>
<?php if ($showPass): ?>
    <tr>
        <td class="descField"><?php echo _('Clave'); ?></td>
        <td class="valField">
            <input name="password" type="password" id="txtPass" maxlength="255"
                   OnKeyUp="checkPassLevel(this.value)">
            <img src="imgs/user-pass.png" title="<?php echo _('La clave generada se mostrará aquí'); ?>"
                 class="inputImg" id="viewPass"/>
            &nbsp;&nbsp;
            <img src="imgs/genpass.png" title="<?php echo _('Generar clave aleatoria'); ?>" class="inputImg"
                 OnClick="password(11, true, true);"/>
        </td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
        <td class="valField">
            <input name="password2" type="password" maxlength="255">
            <span id="passLevel" title="<?php echo _('Nivel de fortaleza de la clave'); ?>"></span>
        </td>
    </tr>
<?php endif; ?>
    <tr>
        <td class="descField"><?php echo _('Notas'); ?></td>
        <td class="valField">
            <textarea name="notice" cols="30" rows="5" placeholder="<?php echo _('Notas sobre la cuenta'); ?>"
                      maxlength="1000" <?php echo (!$showform) ? 'READONLY' : ''; ?> ><?php echo ($action != 'accnew') ? $accountData->account_notes : ''; ?></textarea>
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
                        $users = array_flip(DB::getValuesForSelect('usrData', 'user_id', 'user_name'));

                        foreach ($users as $otherUserName => $otherUserId) {
                            $userSelected = '';

                            if ($otherUserId != $accountData->account_userId) {
                                if (isset($accountUsers) && is_array($accountUsers)) {
                                    $userSelected = (in_array($otherUserId, $accountUsers)) ? "selected" : "";
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
                        $groups = array_flip(DB::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'));

                        foreach ($groups as $otherGroupName => $otherGroupId) {
                            $uGroupSelected = '';

                            if ($otherGroupId != $accountData->account_userGroupId) {
                                if (isset($accountGroups) && is_array($accountGroups)) {
                                    $uGroupSelected = (in_array($otherGroupId, $accountGroups)) ? "selected" : "";
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
    <input type="hidden" name="savetyp" value="<?php echo $savetype; ?>">
    <input type="hidden" name="accountid" value="<?php echo $account->accountId; ?>"/>
    <input type="hidden" name="sk" value="<?php echo $skey; ?>">
    <input type="hidden" name="isAjax" value="1">
    </form>
<?php endif; ?>

    <!--Files boxes-->
<?php if ($showFiles): ?>
    <tr>
        <td class="descField"><?php echo _('Archivos'); ?></td>
        <td class="valField">
            <div id="downFiles"></div>
            <?php if ($account->accountIsHistory): ?>
                <script>getFiles(<?php echo $account->accountParentId; ?>, <?php echo $filesDelete; ?>, '<?php echo $skey; ?>');</script>
            <?php else: ?>
                <script>getFiles(<?php echo $account->accountId; ?>, <?php echo $filesDelete; ?>, '<?php echo $skey; ?>');    </script>
            <?php if ($action == "accedit"): ?>
                <form method="post" enctypr="multipart/form-data" name="upload_form" id="fileUpload">
                    <input type="file" id="inFile" name="inFile"/>
                </form>
                <div id="dropzone" class="round"
                     data-files-ext="<?php echo SP_Config::getValue('files_allowed_exts'); ?>"
                     title="<?php echo _('Soltar archivos aquí (max. 5) o click para seleccionar') . '<br><br>' . _('Tamaño máximo de archivo') . ' ' . $maxFileSize . ' MB'; ?>">
                    <img src="imgs/upload.png" alt="upload" class="opacity50"/>
                </div>
                <script> dropFile(<?php echo $account->accountId; ?>, '<?php echo $skey; ?>', <?php echo $maxFileSize; ?>); </script>
            <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endif; ?>

<?php if ($showHistory): ?>
    <tr>
        <td class="descField"><?php echo _('Historial'); ?></td>
        <td class="valField">
            <?php
            $arrSelectProp = array("name" => "historyId",
                "id" => "sel-history",
                "class" => "",
                "size" => 1,
                "label" => "",
                "selected" => ($account->accountIsHistory) ? $account->accountId : "",
                "default" => "",
                "js" => "OnChange=\"if ( $('#sel-history').val() > 0 ) doAction('accviewhistory','accview', $('#sel-history').val());\"",
                "attribs" => '');

            SP_Html::printSelect($account->getAccountHistoryList(), $arrSelectProp);
            ?>
            <script>$("#sel-history").chosen({disable_search: true, placeholder_text_single: "<?php echo _('Seleccionar fecha'); ?>"});</script>
        </td>
    </tr>
<?php endif; ?>

<?php if ($action == "accedit"): ?>
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
        <?php if (count($accountUsers) > 0): ?>
            <tr>
                <td class="descField"><?php echo _('Usuarios Secundarios'); ?></td>
                <td class="valField">
                    <?php
                    $users = SP_Users::getUsersNameForAccount($accountData->account_id);

                    foreach ($users as $userId => $userName) {
                        if ($userId != $accountData->account_userId) {
                            if (in_array($userId, $accountUsers)) {
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
        <?php if (count($accountGroups) > 0): ?>
            <tr>
                <td class="descField"><?php echo _('Grupos Secundarios'); ?></td>
                <td class="valField">
                    <?php
                    $groups = SP_Groups::getGroupsNameForAccount($accountData->account_id);

                    foreach ($groups as $groupId => $groupName) {
                        if ($groupId != $accountData->account_userGroupId) {
                            if (in_array($groupId, $accountGroups)) {
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
                <td class="valField"><?php echo ($accountData->user_editName) ? $accountData->user_editName : $accountData->user_editLogin ; ?></td>
            </tr>
        <?php endif; ?>
    </table>
<?php endif; ?>

<?php if ($account->accountIsHistory): ?>
    <form METHOD="post" name="frmaccount" id="frmAccount">
        <input type="hidden" name="hash" value="<?php echo $changesHash; ?>">
        <input type="hidden" name="next" value="<?php echo $nextaction; ?>">
        <input type="hidden" name="savetyp" value="<?php echo $savetype; ?>">
        <input type="hidden" name="accountid" value="<?php echo $account->accountId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $skey; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
<?php endif; ?>

    <div class="action">
        <ul>
            <?php if ($account->accountIsHistory): ?>
                <li>
                    <img SRC="imgs/back.png" title="<?php echo _('Ver Actual'); ?>" class="inputImg" id="btnBack"
                         OnClick="doAction('accview','accsearch',<?php echo $account->accountParentId; ?>)"/>
                </li>
            <?php else: ?>
                <li>
                    <img src="imgs/back.png" title="<?php echo _('Atrás'); ?>" class="inputImg" id="btnBack"
                         OnClick="doAction('<?php echo $account->lastAction; ?>', '<?php echo $action; ?>',<?php echo $account->accountId; ?>)"/>
                </li>
            <?php endif; ?>

            <?php if ($showRestore): ?>
                <li>
                    <img SRC="imgs/restore.png" title="<?php echo _('Restaurar cuenta desde este punto'); ?>" class="inputImg" id="btnRestore"
                         OnClick="saveAccount('frmAccount');"/>
                </li>
            <?php endif; ?>

            <?php if ($showViewPass): ?>
                <li>
                    <img src="imgs/user-pass.png" title="<?php echo _('Ver Clave'); ?>"
                         onClick="viewPass(<?php echo $account->accountId; ?>,1,<?php echo $account->accountIsHistory; ?>)"
                         class="inputImg"/>
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
                         OnClick="doAction('acceditpass', '<?php echo $action; ?>',<?php echo $account->accountId; ?>)"/>
                </li>
            <?php endif; ?>

            <?php if ($showEdit): ?>
                <li>
                    <img src="imgs/edit.png" title="<?php echo _('Modificar Cuenta'); ?>" class="inputImg"
                         OnClick="doAction('accedit','accview',<?php echo $account->accountId; ?>)"/>
                </li>
            <?php elseif (!$showEdit && $action == 'accview' && SP_Util::mailrequestIsEnabled()): ?>
                <li>
                    <img src="imgs/request.png" title="<?php echo _('Solicitar Modificación'); ?>" class="inputImg"
                         OnClick="doAction('accrequest','accview',<?php echo $account->accountId; ?>)"/>
                </li>
            <?php endif; ?>

            <?php if ($showDelete): ?>
                <li>
                    <img src="imgs/delete.png" title="<?php echo _('Eliminar Cuenta'); ?>" class="inputImg"
                         OnClick="delAccount(<?php echo $account->accountId; ?>,3,'<?php echo $skey; ?>');"/>
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
    </script>
<?php endif; ?>