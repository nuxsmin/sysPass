<?php
/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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

$userGroupId = SP_Common::parseParams('s', 'ugroup', 0);
$userIsAdminApp = SP_Common::parseParams('s', 'uisadminapp', 0);
$userIsAdminAcc = SP_Common::parseParams('s', 'uisadminacc', 0);

$changesHash = '';

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

        $accountGroups = $account->getGroupsAccount();
        $account->getAccount();
        break;
    case "accedit":
        $savetype = 2;
        $title = array('class' => 'titleOrange', 'name' => _('Editar Cuenta'));
        $showform = true;
        $nextaction = 'accedit';

        $accountGroups = $account->getGroupsAccount();
        $account->getAccount();
        break;
    case "accdelete":
        $savetype = 0;
        $title = array('class' => 'titleRed', 'name' => _('Eliminar Cuenta'));
        $showform = false;

        $account->getAccount();
        break;
    case "accview":
        $savetype = 0;
        $title = array('class' => 'titleNormal', 'name' => _('Detalles de Cuenta'));
        $showform = false;

        $_SESSION["accParentId"] = $data['id'];
        $account->incrementViewCounter();
        $accountGroups = $account->getGroupsAccount();
        $account->getAccount();
        break;
    case "accviewhistory":
        $savetype = 0;
        $title = array('class' => 'titleNormal', 'name' => _('Detalles de Cuenta'));
        $showform = false;

        $account->accountIsHistory = TRUE;
        $accountGroups = $account->getGroupsAccount();
        $account->getAccountHistory();
        break;
    default :
        return;
}

if ($data['id'] > 0) {
    $account->checkAccountAccess($action) || SP_Html::showCommonError('noaccpermission');
    $changesHash = $account->calcChangesHash();
}

$customersSelProp = array("name" => "customerId",
    "id" => "selCustomer",
    "class" => "",
    "size" => 1,
    "label" => "",
    "selected" => $account->accountCustomerId,
    "default" => "",
    "js" => "",
    "attribs" => "");

$categoriesSelProp = array("name" => "categoryId",
    "id" => "selCategory",
    "class" => "",
    "size" => 1,
    "label" => "",
    "selected" => $account->accountCategoryId,
    "default" => "",
    "js" => "",
    "attribs" => "");

$isModified = ( $account->accountDateEdit && $account->accountDateEdit <> '0000-00-00 00:00:00');
$showHistory = (($action == 'accview' || $action == 'accviewhistory' ) && SP_Users::checkUserAccess("accviewhistory") && $isModified);
$showDetails = ($action == 'accview' || $action == 'accviewhistory' || $action == 'accdelete');
$showPass = ($action == "accnew" || $action == 'acccopy');
$showFiles = (($action == "accedit" || $action == "accview" || $action == "accviewhistory") && (SP_Config::getValue('filesenabled') == 1 && SP_Users::checkUserAccess("accfiles")));
$showViewPass = (($action == "accview" || $action == "accviewhistory") && ($account->checkAccountAccess("accviewpass") && SP_Users::checkUserAccess("accviewpass")));
$showSave = ($action == "accedit" || $action == "accnew" || $action == "acccopy");
$showEdit = ($action == "accview" && $account->checkAccountAccess("accedit") && SP_Users::checkUserAccess("accedit") && !$account->accountIsHistory);
$showEditPass = ($action == "accedit" && $account->checkAccountAccess("acceditpass") && SP_Users::checkUserAccess("acceditpass") && !$account->accountIsHistory);
$showDelete = ($action == "accdelete" && $account->checkAccountAccess("accdelete") && SP_Users::checkUserAccess("accdelete"));
$filesDelete = ( $action == 'accedit' ) ? 1 : 0;
$skey = SP_Common::getSessionKey(TRUE);
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
                    <?php if ($showform): ?>
                        <input name="name" type="text" placeholder="<?php echo _('Nombre de cuenta'); ?>" required maxlength="50" value="<?php echo $account->accountName; ?>">
                    <?php else: ?>
                    <?php echo $account->accountName; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Cliente'); ?></td>
                <td class="valField">
                    <?php if ($showform): ?>
                        <?php SP_Html::printSelect(SP_Customer::getCustomers(), $customersSelProp); ?>
                        <br><br>
                        <input type="text" name="customer_new" maxlength="50" placeholder="<?php echo _('Buscar en desplegable o introducir'); ?>" />
                        <?php
                    else:
                        echo $account->accountCustomerName;
                    endif;
                    ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Categoría'); ?></td>
                <td class="valField">
                    <?php if ($showform): ?>
                    <?php SP_Html::printSelect(SP_Category::getCategories(), $categoriesSelProp); ?>
                    <?php else: ?>
                    <?php echo $account->accountCategoryName; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('URL / IP'); ?></td>
                <td class="valField">
                    <?php if ($showform): ?>
                        <input name="url" type="text" placeholder="<?php echo _('URL o IP de acceso'); ?>" maxlength="255" value="<?php echo $account->accountUrl; ?>">
                    <?php else: ?>
                    <?php echo $account->accountUrl; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Usuario'); ?></td>
                <td class="valField">
                    <?php if ($showform): ?>
                        <input name="login" type="text" placeholder="<?php echo _('Usuario de acceso'); ?>" maxlength="50" value="<?php echo $account->accountLogin; ?>">
                    <?php else: ?>
                    <?php echo $account->accountLogin; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($showPass): ?>
                <tr>
                    <td class="descField"><?php echo _('Clave'); ?></td>
                    <td class="valField">
                        <input name="password" type="password" id="txtPass" maxlength="255" OnKeyUp="checkPassLevel(this.value)">
                        <img src="imgs/user-pass.png" title="<?php echo _('La clave generada se mostrará aquí'); ?>" class="inputImg" id="viewPass" />
                        &nbsp;&nbsp;
                        <img src="imgs/genpass.png" title="<?php echo _('Generar clave aleatoria'); ?>" class="inputImg" OnClick="password(11, true, true);" />
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Clave (repetir)'); ?></td>
                    <td class="valField">
                        <input name="password2" type="password" maxlength="255">
                        <span id="passLevel" title="<?php echo _('Nivel de fortaleza de la clave'); ?>" ></span>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if ($showform): ?>
                <tr>
                    <td class="descField"><?php echo _('Grupos Secundarios'); ?></td>
                    <td class="valField">
                        <select id="selGroups" name="ugroups[]" multiple="multiple" size="5" >
                            <?php
                            foreach (SP_Account::getSecGroups() as $groupName => $groupId) {
                                $uGroupSelected = '';

                                if ($groupId != $account->accountUserGroupId && $groupId != $userGroupId) {
                                    if (isset($accountGroups) && is_array($accountGroups)) {
                                        $uGroupSelected = ( in_array($groupId, $accountGroups)) ? "selected" : "";
                                    }
                                    echo "<option value='" . $groupId . "' $uGroupSelected>" . $groupName . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="descField"><?php echo _('Notas'); ?></td>
                <td class="valField">
                    <textarea name="notice" type="text" cols="30" rows="5" placeholder="<?php echo _('Notas sobre la cuenta'); ?>" maxlength="1000"><?php echo $account->accountNotes; ?></textarea>
                </td>
            </tr>

            <?php if ($showform): ?>
                <input type="hidden" name="hash" value="<?php echo $changesHash; ?>">
                <input type="hidden" name="next" value="<?php echo $nextaction; ?>">
                <input type="hidden" name="savetyp" value="<?php echo $savetype; ?>">
                <input type="hidden" name="accountid" value="<?php echo $account->accountId; ?>" />
                <input type="hidden" name="sk" value="<?php echo $skey; ?>">
                <input type="hidden" name="is_ajax" value="1">
                </form>
            <?php endif; ?>

            <?php if ($showFiles): ?>
                <tr>
                    <td class="descField"><?php echo _('Archivos'); ?></td>
                    <td class="valField">
                        <div id="downFiles"></div>
                        <?php if ($account->accountIsHistory): ?>
                            <script>getFiles(<?php echo $account->accountParentId; ?>, <?php echo $filesDelete; ?>, '<?php echo $skey; ?>');</script>
                        <?php else: ?>
                            <script>getFiles(<?php echo $account->accountId; ?>, <?php echo $filesDelete; ?>, '<?php echo $skey; ?>');</script>
                            <?php if ($action == "accedit"): ?>
                                <div id="fileUpload">
                                    <form method="post" enctypr="multipart/form-data" action="ajax/ajax_files.php" name="upload_form" id="upload_form">
                                        <input type="hidden" name="accountId" id="account" value="<?php echo $account->accountId; ?>" />
                                        <input type="hidden" name="action" id="action" value="upload" />
                                        <input type="hidden" name="sk" value="<?php echo $skey; ?>">
                                        <input type="text" id="inFilename" placeholder="<?php echo _('Seleccionar archivo'); ?>" />
                                        <input type="file" id="inFile" name="inFile" OnChange="$('#inFilename').val(this.value);" />
                                        <img id="btnUpload" src="imgs/upload.png" title="<?php echo _('Subir archivo') . ' (max. ' . round(SP_Config::getValue('allowed_size') / 1024, 1) . ' MB)'; ?>" class="inputImg" OnClick="upldFile(<?php echo $account->accountId; ?>)" />
                                        <input type="hidden" name="is_ajax" value="1">
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>

            <!--More info about account details-->
            <?php if ($showDetails): ?>
                <tr>
                    <td class="descField"><?php echo _('Visitas'); ?></td>
                    <td class="valField"><?php echo $account->accountNumView . "(" . $account->accountNumViewDecrypt . ")"; ?></td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Fecha Alta'); ?></td>
                    <td class="valField"><?php echo $account->accountDateAdd ?></td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Creador'); ?></td>
                    <td class="valField"><?php echo $account->accountUserName; ?></td>
                </tr>
                <tr>
                    <td class="descField"><?php echo _('Grupo Principal'); ?></td>
                    <td class="valField"><?php echo $account->accountUserGroupName; ?></td>
                </tr>
                <?php if (count($accountGroups) > 0): ?>
                    <tr>
                        <td class="descField"><?php echo _('Grupos Secundarios'); ?></td>
                        <td class="valField">
                            <?php
                            foreach (SP_Account::getSecGroups() as $groupName => $groupId) {
                                if ($groupId != $account->accountUserGroupId) {
                                    if (in_array($groupId, $accountGroups)) {
                                        $accUGroups[] = $groupName;
                                    }
                                }
                            }
                            echo implode(" | ", $accUGroups);
                            ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php if ($isModified): ?>
                    <tr>
                        <td class="descField"><?php echo _('Fecha Edición'); ?></td>
                        <td class="valField"><?php echo $account->accountDateEdit; ?></td></tr>
                    <tr>
                        <td class="descField"><?php echo _('Editor'); ?></td>
                        <td class="valField"><?php echo $account->accountUserEditName; ?></td>
                    </tr>
                <?php endif; ?>
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
                            "selected" => ( $account->accountIsHistory ) ? $account->accountId : "",
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
                    <?php if ($account->accountUserEditName): ?>
                        <td class="valField"><?php echo $account->accountDateEdit; ?> <?php echo _('por'); ?> <?php echo $account->accountUserEditName; ?></td>
                    <?php endif; ?>
                </tr>
            <?php endif; ?>
        </table>

        <div class="action">
            <ul>
                <li>
                    <?php if ($account->accountIsHistory): ?>
                        <img SRC="imgs/back.png" title="<?php echo _('Ver Actual'); ?>" class="inputImg" id="btnBack" OnClick="doAction('accview', 'accsearch',<?php echo $account->accountParentId; ?>)" />
                    <?php else: ?>
                        <img src="imgs/back.png" title="<?php echo _('Atrás'); ?>" class="inputImg" id="btnBack" OnClick="doAction('<?php echo $account->lastAction; ?>', '<?php echo $action; ?>',<?php echo $account->accountId; ?>)" />
                    <?php endif; ?>
                </li>

                <?php if ($showViewPass): ?>
                    <li>
                        <img src="imgs/user-pass.png" title="<?php echo _('Ver clave'); ?>" onClick="viewPass(<?php echo $account->accountId; ?>, 1,<?php echo $account->accountIsHistory; ?>)" class="inputImg" />
                    </li>
                <?php endif; ?>

                <?php if ($showSave): ?>
                    <li>
                        <img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" id="btnSave" OnClick="saveAccount('frmAccount');" />
                    </li>
                <?php endif; ?>

                <?php if ($showEditPass): ?>
                    <li>
                        <img src="imgs/key.png" title="<?php echo _('Modificar Clave de Cuenta'); ?>" class="inputImg" OnClick="doAction('acceditpass', '<?php echo $action; ?>',<?php echo $account->accountId; ?>)"/>
                    </li>
                <?php endif; ?>

                <?php if ($showEdit): ?>
                    <li>
                        <img src="imgs/edit.png" title="<?php echo _('Modificar Cuenta'); ?>" class="inputImg" OnClick="doAction('accedit', 'accview',<?php echo $account->accountId; ?>)" />
                    </li>
                <?php endif; ?>

                <?php if ($showDelete): ?>
                    <li>
                        <img src="imgs/delete.png" title="<?php echo _('Eliminar Cuenta'); ?>" class="inputImg" OnClick="delAccount(<?php echo $account->accountId; ?>, 3, '<?php echo $skey; ?>');" />
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
                $('input:text:visible:first').focus();
            </script>
        <?php endif; ?>