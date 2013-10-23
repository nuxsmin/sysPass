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
$account->accountParentId = ( isset($_SESSION["accParentId"]) ) ? $_SESSION["accParentId"] : 0;

$userGroupId = $_SESSION["ugroup"];
$userIsAdminApp = $_SESSION["uisadminapp"];
$userIsAdminAcc = $_SESSION["uisadminacc"];

switch ($action) {
    case 'accnew':
        $savetype = 1;
        $title = array('class' => 'titleGreen', 'name' => _('Nueva Cuenta'));
        $showform = true;
        $nextaction = 'accedit';
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

if ( $data['id'] > 0) {
    $account->checkAccountAccess($action) || SP_Html::showCommonError('nopermission');
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
$showHistory = ($action == 'accview' && SP_Users::checkUserAccess("accviewhistory") && $isModified);
$showDetails = ($action == 'accview' || $action == 'accviewhistory' || $action == 'accdelete');
$showPass = ($action == "accnew" || $action == 'acccopy');
$showFiles = (($action == "accedit" || $action == "accview" || $action == "accviewhistory")
            && (SP_Config::getValue('filesenabled') == 1 && SP_Users::checkUserAccess("accfiles")));
$showViewPass = (($action == "accview"  || $action == "accviewhistory")
                &&  ($account->checkAccountAccess("accviewpass") && SP_Users::checkUserAccess("accviewpass")));
$showSave = ($action == "accedit" || $action == "accnew"  || $action == "acccopy");
$showEdit = ($action == "accview"
            && $account->checkAccountAccess("accedit")
            && SP_Users::checkUserAccess("accedit")
            && ! $account->accountIsHistory);
$showEditPass = ($action == "accedit" 
                && $account->checkAccountAccess("acceditpass")
                && SP_Users::checkUserAccess("acceditpass")
                && ! $account->accountIsHistory);
$showDelete = ($action == "accdelete" && $account->checkAccountAccess("accdelete")  && SP_Users::checkUserAccess("accdelete"));
$filesDelete = ( $action == 'accedit' ) ? 1 : 0;
$skey = SP_Common::getSessionKey(TRUE);
?>

<div id="title" class="midroundup <? echo $title['class']; ?>"><? echo $title['name']; ?></div>
<? if ( $showform ): ?>
<form METHOD="post" name="frmaccount" id="frmAccount">
<? endif; ?>
    <? if ( $account->accountIsHistory ): ?>
    <table class="data round tblIcon">
    <? else: ?>
    <table class="data round">
    <? endif; ?>
        <tr>
            <td class="descField"><? echo _('Nombre'); ?></td>
            <td class="valField">
                <? if ( $showform ): ?>
                    <input name="name" type="text" placeholder="<? echo _('Nombre de cuenta'); ?>" required maxlength="50" value="<? echo $account->accountName; ?>">
                <? 
                else:
                    echo $account->accountName;
                endif;
                ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Cliente'); ?></td>
            <td class="valField">
                <? if ( $showform ): ?>
                    <? SP_Html::printSelect(SP_Customer::getCustomers(), $customersSelProp); ?>
                    <br><br>
                    <input type="text" name="customer_new" maxlength="50" placeholder="<? echo _('Buscar en desplegable o introducir'); ?>" />
                <? 
                else:
                    echo $account->accountCustomerName;
                endif;
                ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Categoría'); ?></td>
            <td class="valField">
                <? if ( $showform ):
                    SP_Html::printSelect(SP_Category::getCategories(), $categoriesSelProp);
                else:
                    echo $account->accountCategoryName;
                endif;
                ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><? echo _('URL / IP'); ?></td>
            <td class="valField">
                <? if ( $showform ): ?>
                    <input name="url" type="text" placeholder="<? echo _('URL o IP de acceso'); ?>" maxlength="255" value="<? echo $account->accountUrl; ?>">
                <? 
                else:
                    echo $account->accountUrl;
                endif;
                ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Usuario'); ?></td>
            <td class="valField">
                <? if ( $showform ): ?>
                    <input name="login" type="text" placeholder="<? echo _('Usuario de acceso'); ?>" maxlength="50" value="<? echo $account->accountLogin; ?>">
                <? 
                else:
                    echo $account->accountLogin;
                endif;
                ?>
            </td>
        </tr>
        <? if ( $showPass ): ?>
            <tr>
                <td class="descField"><? echo _('Clave'); ?></td>
                <td class="valField">
                    <input name="password" type="password" id="txtPass" maxlength="255" OnKeyUp="checkPassLevel(this.value)">
                    <img src="imgs/user-pass.png" title="<? echo _('La clave generada se mostrará aquí'); ?>" class="inputImg" id="viewPass" />
                    &nbsp;&nbsp;
                    <img src="imgs/genpass.png" title="<? echo _('Generar clave aleatoria'); ?>" class="inputImg" OnClick="password(11, true, true);" />
                </td>
            </tr>
            <tr>
                <td class="descField"><? echo _('Clave (repetir)'); ?></td>
                <td class="valField">
                    <input name="password2" type="password" maxlength="255">
                    <span id="passLevel" title="<? echo _('Nivel de fortaleza de la clave'); ?>" ></span>
                </td>
            </tr>
        <? endif; ?>
        <? if ( $showform ): ?>
        <tr>
            <td class="descField"><? echo _('Grupos Secundarios'); ?></td>
            <td class="valField">
                <select id="selGroups" name="ugroups[]" multiple="multiple" size="5" >
<?
                    foreach (SP_Account::getSecGroups() as $groupName => $groupId) {
                        $uGroupSelected = '';
                        
                        if ($groupId != $account->accountUserGroupId) {
                            if ( isset($accountGroups) && is_array($accountGroups)){
                                $uGroupSelected = ( in_array($groupId, $accountGroups)) ? "selected" : "";
                            }
                            echo "<option value='" . $groupId . "' $uGroupSelected>" . $groupName . "</option>";
                        }
                    }
 ?>
                </select>
            </td>
        </tr>
        <? endif; ?>
        <tr>
            <td class="descField"><? echo _('Notas'); ?></td>
            <td class="valField">
                <textarea name="notice" type="text" cols="30" rows="5" placeholder="<? echo _('Notas sobre la cuenta'); ?>" maxlength="1000"><? echo $account->accountNotes; ?></textarea>
            </td>
        </tr>
        
    <? if ( $showform ): ?>
    <input type="hidden" name="hash" value="<? echo $changesHash; ?>">
    <input type="hidden" name="next" value="<? echo $nextaction; ?>">
    <input type="hidden" name="savetyp" value="<? echo $savetype; ?>">
    <input type="hidden" name="accountid" value="<? echo $account->accountId; ?>" />
    <input type="hidden" name="sk" value="<? echo $skey; ?>">
    <input type="hidden" name="is_ajax" value="1">
</form>
    <? endif; ?>

        <? if ( $showFiles ): ?>
            <tr>
                <td class="descField"><? echo _('Archivos'); ?></td>
                <td class="valField">
                    <div id="downFiles"></div>
                    <? if ( $account->accountIsHistory ): ?>
                        <script>getFiles(<? echo $account->accountParentId; ?>, <? echo $filesDelete; ?>, '<? echo $skey; ?>');</script>
                    <? else: ?>
                        <script>getFiles(<? echo $account->accountId; ?>, <? echo $filesDelete; ?>, '<? echo $skey; ?>');	</script>
                        <? if ( $action == "accedit" ): ?>
                            <div id="fileUpload">
                                <form method="post" enctypr="multipart/form-data" action="ajax/ajax_files.php" name="upload_form" id="upload_form">
                                    <input type="hidden" name="accountId" id="account" value="<? echo $account->accountId; ?>" />
                                    <input type="hidden" name="action" id="action" value="upload" />
                                    <input type="hidden" name="sk" value="<? echo $skey; ?>">
                                    <input type="text" id="inFilename" placeholder="<? echo _('Seleccionar archivo'); ?>" />
                                    <input type="file" id="inFile" name="inFile" OnChange="$('#inFilename').val(this.value);" />
                                    <img id="btnUpload" src="imgs/upload.png" title="<? echo _('Subir archivo (max. 1 MB)'); ?>" class="inputImg" OnClick="upldFile(<? echo $account->accountId; ?>)" />
                                    <input type="hidden" name="is_ajax" value="1">
                                </form>
                            </div>
                        <? endif; ?>
                    <? endif; ?>
                </td>
            </tr>
        <? endif; ?>

<!--More info about account details-->
        <? if ( $showDetails ): ?>
        <tr>
            <td class="descField"><? echo _('Visitas'); ?></td>
            <td class="valField"><? echo $account->accountNumView."(".$account->accountNumViewDecrypt.")"; ?></td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Fecha Alta'); ?></td>
            <td class="valField"><? echo $account->accountDateAdd ?></td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Creador'); ?></td>
            <td class="valField"><? echo $account->accountUserName; ?></td>
        </tr>
        <tr>
            <td class="descField"><? echo _('Grupo Principal'); ?></td>
            <td class="valField"><? echo $account->accountUserGroupName; ?></td>
        </tr>
        <? if ( count($accountGroups) > 0 ): ?>
        <tr>
            <td class="descField"><? echo _('Grupos Secundarios'); ?></td>
            <td class="valField">
<? 
                foreach ( SP_Account::getSecGroups() as $groupName => $groupId ){
                    if ( $groupId != $account->accountUserGroupId ){
                        if ( in_array($groupId, $accountGroups)){
                            $accUGroups[] = $groupName;
                        }
                    }
                }
                echo implode(" | ",$accUGroups);
?>
            </td>
        </tr>
        <? endif; ?>
            <? if ( $isModified ): ?>
            <tr>
                <td class="descField"><? echo _('Fecha Edición'); ?></td>
                <td class="valField"><? echo $account->accountDateEdit; ?></td></tr>
            <tr>
                <td class="descField"><? echo _('Editor'); ?></td>
                <td class="valField"><? echo $account->accountUserEditName; ?></td>
            </tr>
            <? endif; ?>
        <? endif; ?>
        
        <? if ( $showHistory ): ?>
        <tr>
            <td class="descField"><? echo _('Historial'); ?></td>
            <td class="valField">
<? 
            $arrSelectProp = array ( "name" => "historyId",
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
            <script>$("#sel-history").chosen({disable_search : true, placeholder_text_single: "<? echo _('Seleccionar fecha'); ?>"});</script>
            </td>
        </tr>
        <? endif; ?>
            
        <? if ( $action == "accedit"): ?>
            <tr>
                <td class="descField"><? echo _('Última Modificación'); ?></td>
                <? if ($account->accountUserEditName): ?>
                    <td class="valField"><? echo $account->accountDateEdit; ?> <? echo _('por'); ?> <? echo $account->accountUserEditName; ?></td>
                <? endif; ?>
            </tr>
        <? endif; ?>
    </table>

    <div class="action">
        <ul>
            <li>
                <? if ( $account->accountIsHistory ): ?>
                    <img SRC="imgs/back.png" title="<? echo _('Ver Actual'); ?>" class="inputImg" id="btnBack" OnClick="doAction('accview','accsearch',<? echo $account->accountParentId; ?>)" />
                <? else: ?>
                    <img src="imgs/back.png" title="<? echo _('Atrás'); ?>" class="inputImg" id="btnBack" OnClick="doAction('<? echo $account->lastAction; ?>', '<? echo $action; ?>',<? echo $account->accountId; ?>)" />
                <? endif; ?>
            </li>

            <? if ( $showViewPass ): ?>
            <li>
                <img src="imgs/user-pass.png" title="<? echo _('Ver clave'); ?>" onClick="viewPass(<? echo $account->accountId; ?>,1,<? echo $account->accountIsHistory; ?>)" class="inputImg" />
            </li>
            <? endif; ?>
        
            <? if ( $showSave ): ?>
            <li>
                <img src="imgs/check.png" title="<? echo _('Guardar'); ?>" class="inputImg" id="btnSave" OnClick="saveAccount('frmAccount');" />
            </li>
            <? endif; ?>
            
            <? if ( $showEditPass ): ?>
            <li>
                <img src="imgs/key.png" title="<? echo _('Modificar Clave de Cuenta'); ?>" class="inputImg" OnClick="doAction('acceditpass', '<? echo $action; ?>',<? echo $account->accountId; ?>)"/>
            </li>
            <? endif; ?>

             <? if ( $showEdit ): ?>
                <li>
                    <img src="imgs/edit.png" title="<? echo _('Modificar Cuenta'); ?>" class="inputImg" OnClick="doAction('accedit','accview',<? echo $account->accountId; ?>)" />
                </li>
            <? endif; ?>
            
            <? if ( $showDelete ): ?>
            <li>
                <img src="imgs/delete.png" title="<? echo _('Eliminar Cuenta'); ?>" class="inputImg" OnClick="delAccount(<? echo $account->accountId; ?>,3,'<? echo $skey; ?>');" />
            </li>
            <? endif; ?>
        </ul>
    </div>

<? if ( $showform ): ?>
    <script>
        $("#selCustomer").chosen({
            placeholder_text_single: "<? echo _('Seleccionar Cliente'); ?>", 
            disable_search_threshold: 10,
            no_results_text: "<? echo _('Sin resultados'); ?>"
        });
        $("#selCategory").chosen({
            placeholder_text_single: "<? echo _('Seleccionar Categoría'); ?>",
            disable_search_threshold: 10,
            no_results_text: "<? echo _('Sin resultados'); ?>"
        });
        $("#selGroups").chosen({
            placeholder_text_multiple: "<? echo _('Seleccionar grupos secundarios'); ?>",
        });
    </script>
<? endif; ?>