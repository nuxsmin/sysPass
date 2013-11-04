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
define('APP_ROOT', '..');
include_once (APP_ROOT . "/inc/init.php");

SP_Util::checkReferer('POST');


if (!SP_Init::isLoggedIn()) {
    SP_Common::printXML(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP_Common::parseParams('p', 'sk', FALSE);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printXML(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$frmSaveType = SP_Common::parseParams('p', 'type', 0);
$frmAction = SP_Common::parseParams('p', 'action', 0);
$frmItemId = SP_Common::parseParams('p', 'id', 0);

$objUser = new SP_Users;

if ($frmSaveType == 1 || $frmSaveType == 2) {
    // Variables POST del formulario
    $frmLdap = SP_Common::parseParams('p', 'ldap', 0);
    $frmUsrName = SP_Common::parseParams('p', 'name');
    $frmUsrLogin = SP_Common::parseParams('p', 'login');
    $frmUsrProfile = SP_Common::parseParams('p', 'profileid', 0);
    $frmUsrGroup = SP_Common::parseParams('p', 'groupid', 0);
    $frmUsrEmail = SP_Common::parseParams('p', 'email');
    $frmUsrNotes = SP_Common::parseParams('p', 'notes');
    $frmUsrPass = SP_Common::parseParams('p', 'pass');
    $frmUsrPassV = SP_Common::parseParams('p', 'passv');
    $frmAdminApp = SP_Common::parseParams('p', 'adminapp', 0, FALSE, 1);
    $frmAdminAcc = SP_Common::parseParams('p', 'adminacc', 0, FALSE, 1);
    $frmDisabled = SP_Common::parseParams('p', 'disabled', 0, FALSE, 1);

    // Nuevo usuario o editar
    if ($frmAction == 1 OR $frmAction == 2) {
        if (!$frmUsrName && !$frmLdap) {
            SP_Common::printXML(_('Es necesario un nombre de usuario'), 2);
        }

        if (!$frmUsrLogin && !$frmLdap) {
            SP_Common::printXML(_('Es necesario un login'), 2);
        }

        if ($frmUsrProfile == "") {
            SP_Common::printXML(_('Es necesario un perfil'), 2);
        }

        if (!$frmUsrGroup) {
            SP_Common::printXML(_('Es necesario un grupo'), 2);
        }

        if (!$frmUsrEmail && !$frmLdap) {
            SP_Common::printXML(_('Es necesario un email'), 2);
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
        $objUser->userPass = $frmUsrPass;

        switch ($objUser->checkUserExist()) {
            case 1:
                SP_Common::printXML(_('Login de usuario duplicado'), 2);
                break;
            case 2:
                SP_Common::printXML(_('Email de usuario duplicado'), 2);
                break;
        }

        if ($frmAction == 1) {
            if (!$frmUsrPass && !$frmUsrPassV) {
                SP_Common::printXML(_('La clave no puede estar en blanco'), 2);
            }

            if ($frmUsrPass != $frmUsrPassV) {
                SP_Common::printXML(_('Las claves no coinciden'), 2);
            }

            if ($objUser->manageUser("add")) {
                $message['action'] = _('Nuevo Usuario');
                $message['text'][] = _('Nombre') . ': ' . $frmUsrName . ' (' . $frmUsrLogin . ')';

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Usuario creado'), 0);
            } 
            
            SP_Common::printXML(_('Error al crear el usuario'));
        } elseif ($frmAction == 2) {
            if ($objUser->manageUser("update")) {
                $message['action'] = _('Modificar Usuario');
                $message['text'][] = _('Nombre') . ': ' . $frmUsrName . ' (' . $frmUsrLogin . ')';

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Usuario actualizado'), 0);
            }
            
            SP_Common::printXML(_('Error al actualizar el usuario'));
        }
    // Cambio de clave
    } elseif ($frmAction == 3) {
        $userLogin = $objUser->getUserLoginById($frmItemId);
        
        if ( SP_Config::getValue('demoenabled', 0) && $userLogin == 'demo'){
            SP_Common::printXML(_('Acción Inválida').'(DEMO)');
        }
        
        if (!$frmUsrPass || !$frmUsrPassV) {
            SP_Common::printXML(_('La clave no puede estar en blanco'), 2);
        }

        if ($frmUsrPass != $frmUsrPassV) {
            SP_Common::printXML(_('Las claves no coinciden'), 2);
        }

        $objUser->userId = $frmItemId;
        $objUser->userPass = $frmUsrPass;

        if ($objUser->manageUser("updatepass")) {
            $message['action'] = _('Modificar Clave Usuario');
            $message['text'][] = _('Login') . ': ' . $userLogin;

            SP_Common::wrLogInfo($message);
            SP_Common::sendEmail($message);

            SP_Common::printXML(_('Clave actualizada'), 0);
        }
        
        SP_Common::printXML(_('Error al modificar la clave'));
    // Eliminar usuario
    } elseif ($frmAction == 4) {

        $userLogin = $objUser->getUserLoginById($frmItemId);
        
        if ( SP_Config::getValue('demoenabled', 0) && $userLogin == 'demo' ){
            SP_Common::printXML(_('Acción Inválida').'(DEMO)');
        }
        
        $objUser->userId = $frmItemId;

        if ($frmItemId == $_SESSION["uid"]) {
            SP_Common::printXML(_('No es posible eliminar, usuario en uso'));
        }

        if ($objUser->manageUser("delete")) {
            $message['action'] = _('Eliminar Usuario');
            $message['text'][] = _('Login') . ': ' . $userLogin;

            SP_Common::wrLogInfo($message);
            SP_Common::sendEmail($message);

            SP_Common::printXML(_('Usuario eliminado'), 0);
        }
        
        SP_Common::printXML(_('Error al eliminar el usuario'));
    } 
    
    SP_Common::printXML(_('Acción Inválida'));
} elseif ($frmSaveType == 3 || $frmSaveType == 4) {
    // Variables POST del formulario
    $frmGrpName = SP_Common::parseParams('p', 'name');
    $frmGrpDesc = SP_Common::parseParams('p', 'description');

    // Nuevo grupo o editar
    if ($frmAction == 1 OR $frmAction == 2) {
        if (!$frmGrpName) {
            SP_Common::printXML(_('Es necesario un nombre de grupo'), 2);
        }

        $objUser->groupId = $frmItemId;
        $objUser->groupName = $frmGrpName;
        $objUser->groupDesc = $frmGrpDesc;

        if (!$objUser->checkGroupExist()) {
            SP_Common::printXML(_('Nombre de grupo duplicado'), 2);
        }

        if ($frmAction == 1) {
            if ($objUser->manageGroup("add")) {
                $message['action'] = _('Nuevo Grupo');
                $message['text'][] = _('Nombre') . ': ' . $frmGrpName;

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Grupo creado'), 0);
            } else {
                SP_Common::printXML(_('Error al crear el grupo'));
            }
        } else if ($frmAction == 2) {
            if ($objUser->manageGroup("update")) {
                $message['action'] = _('Modificar Grupo');
                $message['text'][] = _('Nombre') . ': ' . $frmGrpName;

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Grupo actualizado'), 0);
            }
            
            SP_Common::printXML(_('Error al actualizar el grupo'));
        }

    // Eliminar grupo
    } elseif ($frmAction == 4) {
        $objUser->groupId = $frmItemId;

        $resGroupUse = $objUser->checkGroupInUse();

        if (is_string($resGroupUse)) {
            SP_Common::printXML(_('No es posible eliminar:Grupo en uso por') . ' ' . $resGroupUse);
        } else {
            $groupName = $objUser->getGroupNameById($frmItemId);
            
            if ($objUser->manageGroup("delete")) {
                $message['action'] = _('Eliminar Grupo');
                $message['text'][] = _('Nombre') . ': ' . $groupName;

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Grupo eliminado'), 0);
            }
            
            SP_Common::printXML(_('Error al eliminar el grupo'));
        }
    }

    SP_Common::printXML(_('Acción Inválida'));
} elseif ($frmSaveType == 5 || $frmSaveType == 6) {
    $profileProp = array();

    // Variables POST del formulario
    $frmProfileName = SP_Common::parseParams('p', 'profile_name');
    $objUser->profileId = $frmItemId;

    // Profile properties Array
    $profileProp["pAccView"] = SP_Common::parseParams('p', 'profile_accview', 0, FALSE, 1);
    $profileProp["pAccViewPass"] = SP_Common::parseParams('p', 'profile_accviewpass', 0, FALSE, 1);
    $profileProp["pAccViewHistory"] = SP_Common::parseParams('p', 'profile_accviewhistory', 0, FALSE, 1);
    $profileProp["pAccEdit"] = SP_Common::parseParams('p', 'profile_accedit', 0, FALSE, 1);
    $profileProp["pAccEditPass"] = SP_Common::parseParams('p', 'profile_acceditpass', 0, FALSE, 1);
    $profileProp["pAccAdd"] = SP_Common::parseParams('p', 'profile_accadd', 0, FALSE, 1);
    $profileProp["pAccDel"] = SP_Common::parseParams('p', 'profile_accdel', 0, FALSE, 1);
    $profileProp["pAccFiles"] = SP_Common::parseParams('p', 'profile_accfiles', 0, FALSE, 1);
    $profileProp["pConfig"] = SP_Common::parseParams('p', 'profile_config', 0, FALSE, 1);
    $profileProp["pConfigCat"] = SP_Common::parseParams('p', 'profile_configcat', 0, FALSE, 1);
    $profileProp["pConfigMpw"] = SP_Common::parseParams('p', 'profile_configmpw', 0, FALSE, 1);
    $profileProp["pConfigBack"] = SP_Common::parseParams('p', 'profile_configback', 0, FALSE, 1);
    $profileProp["pUsers"] = SP_Common::parseParams('p', 'profile_users', 0, FALSE, 1);
    $profileProp["pGroups"] = SP_Common::parseParams('p', 'profile_groups', 0, FALSE, 1);
    $profileProp["pProfiles"] = SP_Common::parseParams('p', 'profile_profiles', 0, FALSE, 1);
    $profileProp["pEventlog"] = SP_Common::parseParams('p', 'profile_eventlog', 0, FALSE, 1);

    // Nuevo perfil o editar
    if ($frmAction == 1 OR $frmAction == 2) {
        if (!$frmProfileName) {
            SP_Common::printXML(_('Es necesario un nombre de perfil'), 2);
        }

        $objUser->profileName = $frmProfileName;

        if (!$objUser->checkProfileExist()) {
            SP_Common::printXML(_('Nombre de perfil duplicado'), 2);
        }

        if ($frmAction == 1) {
            if ($objUser->manageProfiles("add", $profileProp)) {
                $message['action'] = _('Nuevo Perfil');
                $message['text'][] = _('Nombre') . ': ' . $frmProfileName;

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Perfil creado'), 0);
            }
            
            SP_Common::printXML(_('Error al crear el perfil'));
        } else if ($frmAction == 2) {
            if ($objUser->manageProfiles("update", $profileProp)) {
                $message['action'] = _('Modificar Perfil');
                $message['text'][] = _('Nombre') . ': ' . $frmProfileName;

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Perfil actualizado'), 0);
            }
            
            SP_Common::printXML(_('Error al actualizar el perfil'));
        }

    // Eliminar perfil
    } elseif ($frmAction == 4) {
        $resProfileUse = $objUser->checkProfileInUse();

        if (is_string($resProfileUse)) {
            SP_Common::printXML(_('No es posible eliminar: Perfil en uso por') . ' ' . $resProfileUse);
        } else {
            $profileName = $objUser->getProfileNameById($frmItemId);
            
            if ($objUser->manageProfiles("delete")) {
                $message['action'] = _('Eliminar Perfil');
                $message['text'][] = _('Nombre') . ': ' . $profileName;

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Perfil eliminado'), 0);
            }
            
            SP_Common::printXML(_('Error al eliminar el perfil'));
        }
    }
    
    SP_Common::printXML(_('Acción Inválida'));
}