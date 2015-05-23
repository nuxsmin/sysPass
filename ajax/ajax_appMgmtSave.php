<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

define('APP_ROOT', '..');
require_once APP_ROOT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'Init.php';

SP_Util::checkReferer('POST');


if (!SP_Init::isLoggedIn()) {
    SP_Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP_Common::parseParams('p', 'sk', false);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printJSON(_('CONSULTA INVÁLIDA'));
}


// Variables POST del formulario
$frmSaveType = SP_Common::parseParams('p', 'type', 0);
$frmAction = SP_Common::parseParams('p', 'action', 0);
$frmItemId = SP_Common::parseParams('p', 'id', 0);
$frmOnCloseAction = SP_Common::parseParams('p', 'onCloseAction');
$frmActiveTab = SP_Common::parseParams('p', 'activeTab', 0);

$doActionOnClose = "doAction('$frmOnCloseAction','',$frmActiveTab);";

$userLogin = SP_Users::getUserLoginById($frmItemId);

if ($frmSaveType == 1 || $frmSaveType == 2) {
    $objUser = new SP_Users;

    // Variables POST del formulario
    $frmLdap = SP_Common::parseParams('p', 'ldap', 0);
    $frmUsrName = SP_Common::parseParams('p', 'name');
    $frmUsrLogin = SP_Common::parseParams('p', 'login');
    $frmUsrProfile = SP_Common::parseParams('p', 'profileid', 0);
    $frmUsrGroup = SP_Common::parseParams('p', 'groupid', 0);
    $frmUsrEmail = SP_Common::parseParams('p', 'email');
    $frmUsrNotes = SP_Common::parseParams('p', 'notes');
    $frmUsrPass = SP_Common::parseParams('p', 'pass', '', false, false, false);
    $frmUsrPassV = SP_Common::parseParams('p', 'passv', '', false, false, false);
    $frmAdminApp = SP_Common::parseParams('p', 'adminapp', 0, false, 1);
    $frmAdminAcc = SP_Common::parseParams('p', 'adminacc', 0, false, 1);
    $frmDisabled = SP_Common::parseParams('p', 'disabled', 0, false, 1);
    $frmChangePass = SP_Common::parseParams('p', 'changepass', 0, false, 1);

    // Nuevo usuario o editar
    if ($frmAction == 1 OR $frmAction == 2) {
        if (!$frmUsrName && !$frmLdap) {
            SP_Common::printJSON(_('Es necesario un nombre de usuario'), 2);
        }

        if (!$frmUsrLogin && !$frmLdap) {
            SP_Common::printJSON(_('Es necesario un login'), 2);
        }

        if ($frmUsrProfile == "") {
            SP_Common::printJSON(_('Es necesario un perfil'), 2);
        }

        if (!$frmUsrGroup) {
            SP_Common::printJSON(_('Es necesario un grupo'), 2);
        }

        if (!$frmUsrEmail && !$frmLdap) {
            SP_Common::printJSON(_('Es necesario un email'), 2);
        }

        $objUser->userId = $frmItemId;
        $objUser->userName = $frmUsrName;
        $objUser->userLogin = $frmUsrLogin;
        $objUser->userEmail = $frmUsrEmail;
        $objUser->userNotes = $frmUsrNotes;
        $objUser->userGroupId = $frmUsrGroup;
        $objUser->userProfileId = $frmUsrProfile;
        $objUser->userIsAdminApp = $frmAdminApp;
        $objUser->userIsAdminAcc = $frmAdminAcc;
        $objUser->userIsDisabled = $frmDisabled;
        $objUser->userChangePass = $frmChangePass;
        $objUser->userPass = $frmUsrPass;

        switch ($objUser->checkUserExist()) {
            case 1:
                SP_Common::printJSON(_('Login de usuario duplicado'), 2);
                break;
            case 2:
                SP_Common::printJSON(_('Email de usuario duplicado'), 2);
                break;
        }

        if ($frmAction == 1) {
            if (!$frmUsrPass && !$frmUsrPassV) {
                SP_Common::printJSON(_('La clave no puede estar en blanco'), 2);
            }

            if ($frmUsrPass != $frmUsrPassV) {
                SP_Common::printJSON(_('Las claves no coinciden'), 2);
            }

            if ($objUser->addUser()) {
                SP_Common::printJSON(_('Usuario creado'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al crear el usuario'));
        } elseif ($frmAction == 2) {
            if ($objUser->updateUser()) {
                SP_Common::printJSON(_('Usuario actualizado'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al actualizar el usuario'));
        }
    // Cambio de clave
    } elseif ($frmAction == 3) {
        if (SP_Util::demoIsEnabled() && $userLogin == 'demo') {
            SP_Common::printJSON(_('Ey, esto es una DEMO!!'));
        }

        if (!$frmUsrPass || !$frmUsrPassV) {
            SP_Common::printJSON(_('La clave no puede estar en blanco'), 2);
        }

        if ($frmUsrPass != $frmUsrPassV) {
            SP_Common::printJSON(_('Las claves no coinciden'), 2);
        }

        $objUser->userId = $frmItemId;
        $objUser->userPass = $frmUsrPass;

        if ($objUser->updateUserPass()) {
            SP_Common::printJSON(_('Clave actualizada'), 0);
        }

        SP_Common::printJSON(_('Error al modificar la clave'));
    // Eliminar usuario
    } elseif ($frmAction == 4) {
        if (SP_Util::demoIsEnabled() && $userLogin == 'demo') {
            SP_Common::printJSON(_('Ey, esto es una DEMO!!'));
        }

        $objUser->userId = $frmItemId;

        if ($frmItemId == $_SESSION["uid"]) {
            SP_Common::printJSON(_('No es posible eliminar, usuario en uso'));
        }

        if ($objUser->deleteUser()) {
            SP_Common::printJSON(_('Usuario eliminado'), 0, $doActionOnClose);
        }

        SP_Common::printJSON(_('Error al eliminar el usuario'));
    }

    SP_Common::printJSON(_('Acción Inválida'));
} elseif ($frmSaveType == 3 || $frmSaveType == 4) {
    // Variables POST del formulario
    $frmGrpName = SP_Common::parseParams('p', 'name');
    $frmGrpDesc = SP_Common::parseParams('p', 'description');

    // Nuevo grupo o editar
    if ($frmAction == 1 OR $frmAction == 2) {
        if (!$frmGrpName) {
            SP_Common::printJSON(_('Es necesario un nombre de grupo'), 2);
        }

        SP_Groups::$groupId = $frmItemId;
        SP_Groups::$groupName = $frmGrpName;
        SP_Groups::$groupDescription = $frmGrpDesc;

        if (SP_Groups::checkGroupExist()) {
            SP_Common::printJSON(_('Nombre de grupo duplicado'), 2);
        }

        if ($frmAction == 1) {
            if (SP_Groups::addGroup()) {
                SP_Common::printJSON(_('Grupo creado'), 0, $doActionOnClose);
            } else {
                SP_Common::printJSON(_('Error al crear el grupo'));
            }
        } else if ($frmAction == 2) {
            if (SP_Groups::updateGroup()) {
                SP_Common::printJSON(_('Grupo actualizado'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al actualizar el grupo'));
        }

    // Eliminar grupo
    } elseif ($frmAction == 4) {
        SP_Groups::$groupId = $frmItemId;

        $resGroupUse = SP_Groups::checkGroupInUse();

        if ($resGroupUse['users'] > 0 || $resGroupUse['accounts'] > 0) {
            if ($resGroupUse['users'] > 0) {
                $uses[] = _('Usuarios') . " (" . $resGroupUse['users'] . ")";
            }

            if ($resGroupUse['accounts'] > 0) {
                $uses[] = _('Cuentas') . " (" . $resGroupUse['accounts'] . ")";
            }

            SP_Common::printJSON(_('No es posible eliminar') . ';;' . _('Grupo en uso por:') . ';;' . implode(';;', $uses));
        } else {
            $groupName = SP_Groups::getGroupNameById($frmItemId);

            if (SP_Groups::deleteGroup()) {
                SP_Common::printJSON(_('Grupo eliminado'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al eliminar el grupo'));
        }
    }

    SP_Common::printJSON(_('Acción Inválida'));
} elseif ($frmSaveType == 5 || $frmSaveType == 6) {
    $profileProp = array();

    // Variables POST del formulario
    $frmProfileName = SP_Common::parseParams('p', 'profile_name');
    SP_Profiles::$profileId = $frmItemId;

    // Profile properties Array
    $profileProp["pAccView"] = SP_Common::parseParams('p', 'profile_accview', 0, false, 1);
    $profileProp["pAccViewPass"] = SP_Common::parseParams('p', 'profile_accviewpass', 0, false, 1);
    $profileProp["pAccViewHistory"] = SP_Common::parseParams('p', 'profile_accviewhistory', 0, false, 1);
    $profileProp["pAccEdit"] = SP_Common::parseParams('p', 'profile_accedit', 0, false, 1);
    $profileProp["pAccEditPass"] = SP_Common::parseParams('p', 'profile_acceditpass', 0, false, 1);
    $profileProp["pAccAdd"] = SP_Common::parseParams('p', 'profile_accadd', 0, false, 1);
    $profileProp["pAccDel"] = SP_Common::parseParams('p', 'profile_accdel', 0, false, 1);
    $profileProp["pAccFiles"] = SP_Common::parseParams('p', 'profile_accfiles', 0, false, 1);
    $profileProp["pConfig"] = SP_Common::parseParams('p', 'profile_config', 0, false, 1);
    $profileProp["pAppMgmtCat"] = SP_Common::parseParams('p', 'profile_categories', 0, false, 1);
    $profileProp["pAppMgmtCust"] = SP_Common::parseParams('p', 'profile_customers', 0, false, 1);
    $profileProp["pConfigMpw"] = SP_Common::parseParams('p', 'profile_configmpw', 0, false, 1);
    $profileProp["pConfigBack"] = SP_Common::parseParams('p', 'profile_configback', 0, false, 1);
    $profileProp["pUsers"] = SP_Common::parseParams('p', 'profile_users', 0, false, 1);
    $profileProp["pGroups"] = SP_Common::parseParams('p', 'profile_groups', 0, false, 1);
    $profileProp["pProfiles"] = SP_Common::parseParams('p', 'profile_profiles', 0, false, 1);
    $profileProp["pEventlog"] = SP_Common::parseParams('p', 'profile_eventlog', 0, false, 1);

    // Nuevo perfil o editar
    if ($frmAction == 1 OR $frmAction == 2) {
        if (!$frmProfileName) {
            SP_Common::printJSON(_('Es necesario un nombre de perfil'), 2);
        }

        SP_Profiles::$profileName = $frmProfileName;

        if (SP_Profiles::checkProfileExist()) {
            SP_Common::printJSON(_('Nombre de perfil duplicado'), 2);
        }

        if ($frmAction == 1) {
            if (SP_Profiles::addProfile($profileProp)) {
                SP_Common::printJSON(_('Perfil creado'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al crear el perfil'));
        } else if ($frmAction == 2) {
            if (SP_Profiles::updateProfile($profileProp)) {
                SP_Common::printJSON(_('Perfil actualizado'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al actualizar el perfil'));
        }

    // Eliminar perfil
    } elseif ($frmAction == 4) {
        $resProfileUse = SP_Profiles::checkProfileInUse();

        if ($resProfileUse['users'] > 0) {
            $uses[] = _('Usuarios') . " (" . $resProfileUse['users'] . ")";

            SP_Common::printJSON(_('No es posible eliminar') . ';;' . _('Perfil en uso por:') . ';;' . implode(';;', $uses));
        } else {
            $profileName = SP_Profiles::getProfileNameById($frmItemId);

            if (SP_Profiles::deleteProfile()) {
                $message['action'] = _('Eliminar Perfil');
                $message['text'][] = SP_Html::strongText(_('Perfil') . ': ') . $profileName;

                SP_Log::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printJSON(_('Perfil eliminado'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al eliminar el perfil'));
        }
    }

    SP_Common::printJSON(_('Acción Inválida'));
} elseif ($frmSaveType == 7 || $frmSaveType == 8) {
    // Variables POST del formulario
    $frmCustomerName = SP_Common::parseParams('p', 'name');
    $frmCustomerDesc = SP_Common::parseParams('p', 'description');

    // Nuevo cliente o editar
    if ($frmAction == 1 OR $frmAction == 2) {
        if (!$frmCustomerName) {
            SP_Common::printJSON(_('Es necesario un nombre de cliente'), 2);
        }

        SP_Customer::$customerName = $frmCustomerName;
        SP_Customer::$customerDescription = $frmCustomerDesc;

        if (SP_Customer::checkDupCustomer($frmItemId)) {
            SP_Common::printJSON(_('Nombre de cliente duplicado'), 2);
        }

        if ($frmAction == 1) {
            if (SP_Customer::addCustomer()) {
                SP_Common::printJSON(_('Cliente creado'), 0, $doActionOnClose);
            } else {
                SP_Common::printJSON(_('Error al crear el cliente'));
            }
        } else if ($frmAction == 2) {
            if (SP_Customer::updateCustomer($frmItemId)) {
                SP_Common::printJSON(_('Cliente actualizado'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al actualizar el cliente'));
        }

    // Eliminar cliente
    } elseif ($frmAction == 4) {
        $resCustomerUse = SP_Customer::checkCustomerInUse($frmItemId);

        if ($resCustomerUse['accounts'] > 0) {
            $uses[] = _('Cuentas') . " (" . $resCustomerUse['accounts'] . ")";

            SP_Common::printJSON(_('No es posible eliminar') . ';;' . _('Cliente en uso por:') . ';;' . implode(';;', $uses));
        } else {

            if (SP_Customer::delCustomer($frmItemId)) {
                SP_Common::printJSON(_('Cliente eliminado'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al eliminar el cliente'));
        }
    }

    SP_Common::printJSON(_('Acción Inválida'));
} elseif ($frmSaveType == 9 || $frmSaveType == 10) {
    // Variables POST del formulario
    $frmCategoryName = SP_Common::parseParams('p', 'name');
    $frmCategoryDesc = SP_Common::parseParams('p', 'description');

    // Nueva categoría o editar
    if ($frmAction == 1 OR $frmAction == 2) {
        if (!$frmCategoryName) {
            SP_Common::printJSON(_('Es necesario un nombre de categoría'), 2);
        }

        SP_Category::$categoryName = $frmCategoryName;
        SP_Category::$categoryDescription = $frmCategoryDesc;

        if (SP_Category::checkDupCategory($frmItemId)) {
            SP_Common::printJSON(_('Nombre de categoría duplicado'), 2);
        }

        if ($frmAction == 1) {
            if (SP_Category::addCategory()) {
                SP_Common::printJSON(_('Categoría creada'), 0, $doActionOnClose);
            } else {
                SP_Common::printJSON(_('Error al crear la categoría'));
            }
        } else if ($frmAction == 2) {
            if (SP_Category::updateCategory($frmItemId)) {
                SP_Common::printJSON(_('Categoría actualizada'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al actualizar la categoría'));
        }

    // Eliminar categoría
    } elseif ($frmAction == 4) {
        $resCategoryUse = SP_Category::checkCategoryInUse($frmItemId);

        if ($resCategoryUse !== true) {
            SP_Common::printJSON(_('No es posible eliminar') . ';;' . _('Categoría en uso por:') . ';;' . $resCategoryUse);
        } else {

            if (SP_Category::delCategory($frmItemId)) {
                SP_Common::printJSON(_('Categoría eliminada'), 0, $doActionOnClose);
            }

            SP_Common::printJSON(_('Error al eliminar la categoría'));
        }
    }

    SP_Common::printJSON(_('Acción Inválida'));
}