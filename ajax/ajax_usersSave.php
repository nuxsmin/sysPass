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

if (!isset($_POST["sk"]) || !SP_Common::checkSessionKey($_POST["sk"])) {
    SP_Common::printXML(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$frmSaveType = ( isset($_POST["type"]) ) ? (int) $_POST["type"] : 0;
$frmAction = ( isset($_POST["action"]) ) ? (int) $_POST["action"] : 0;
$frmItemId = ( isset($_POST["id"]) ) ? (int) $_POST["id"] : 0;

if ($frmAction == 3) {
    SP_Users::checkUserAccess("acceditpass", $frmItemId) || die('<DIV CLASS="error"' . _('No tiene permisos para acceder') . '</DIV');
} else {
    SP_Users::checkUserAccess("users") || die('<DIV CLASS="error"' . _('No tiene permisos para acceder') . '</DIV');
}

$objUser = new SP_Users;

if ($frmSaveType == 1 || $frmSaveType == 2) {
    // Variables POST del formulario
    $frmLdap = ( isset($_POST["ldap"]) ) ? $_POST["ldap"] : 0;
    $frmUsrName = ( isset($_POST["name"]) ) ? SP_Html::sanitize($_POST["name"]) : "";
    $frmUsrLogin = ( isset($_POST["login"]) ) ? SP_Html::sanitize($_POST["login"]) : "";
    $frmUsrProfile = ( isset($_POST["profileid"]) ) ? (int) $_POST["profileid"] : "";
    $frmUsrGroup = ( isset($_POST["groupid"]) ) ? (int) $_POST["groupid"] : "";
    $frmUsrEmail = ( isset($_POST["email"]) ) ? SP_Html::sanitize($_POST["email"]) : "";
    $frmUsrNotes = ( isset($_POST["notes"]) ) ? SP_Html::sanitize($_POST["notes"]) : "";
    $frmUsrPass = ( isset($_POST["pass"]) ) ? $_POST["pass"] : "";
    $frmUsrPassV = ( isset($_POST["passv"]) ) ? $_POST["passv"] : "";
    $frmAdminApp = ( isset($_POST["adminapp"]) && $_POST["adminapp"] == "on" ) ? 1 : 0;
    $frmAdminAcc = ( isset($_POST["adminacc"]) && $_POST["adminacc"] == "on" ) ? 1 : 0;
    $frmDisabled = ( isset($_POST["disabled"]) && $_POST["disabled"] == "on" ) ? 1 : 0;

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
            } else {
                SP_Common::printXML(_('Error al crear el usuario'));
            }
        } elseif ($frmAction == 2) {
            if ($objUser->manageUser("update")) {
                $message['action'] = _('Modificar Usuario');
                $message['text'][] = _('Nombre') . ': ' . $frmUsrName . ' (' . $frmUsrLogin . ')';

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Usuario actualizado'), 0);
            } else {
                SP_Common::printXML(_('Error al actualizar el usuario'));
            }
        }
        // Cambio de clave
    } elseif ($frmAction == 3 && !SP_Config::getValue('demoenabled', 0)) {
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
            $message['text'][] = _('Nombre') . ': ' . $frmUsrName . ' (' . $frmUsrLogin . ')';

            SP_Common::wrLogInfo($message);
            SP_Common::sendEmail($message);

            SP_Common::printXML(_('Clave actualizada'), 0);
        } else {
            SP_Common::printXML(_('Error al modificar la clave'));
        }
        // Eliminar usuario
    } elseif ($frmAction == 4 && !SP_Config::getValue('demoenabled', 0)) {

        $objUser->userId = $frmItemId;

        if ($frmItemId == $_SESSION["uid"]) {
            SP_Common::printXML(_('No es posible eliminar, usuario en uso'));
        }

        if ($objUser->manageUser("delete")) {
            $message['action'] = _('Eliminar Usuario');
            $message['text'][] = _('Login') . ': ' . $frmUsrName . ' (' . $frmUsrLogin . ')';

            SP_Common::wrLogInfo($message);
            SP_Common::sendEmail($message);

            SP_Common::printXML(_('Usuario eliminado'), 0);
        } else {
            SP_Common::printXML(_('Error al eliminar el usuario'));
        }
    } else {
        SP_Common::printXML(_('No es una acción válida'));
    }
} elseif ($frmSaveType == 3 || $frmSaveType == 4) {
    // Variables POST del formulario
    $frmGrpName = ( isset($_POST["name"]) ) ? SP_Html::sanitize($_POST["name"]) : "";
    $frmGrpDesc = ( isset($_POST["description"]) ) ? SP_Html::sanitize($_POST["description"]) : "";

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
            } else {
                SP_Common::printXML(_('Error al actualizar el grupo'));
            }
        }

        // Eliminar grupo
    } elseif ($frmAction == 4) {
        $objUser->groupId = $frmItemId;

        $resGroupUse = $objUser->checkGroupInUse();

        if (is_string($resGroupUse)) {
            SP_Common::printXML(_('No es posible eliminar:Grupo en uso por') . ' ' . $resGroupUse);
        } else {
            if ($objUser->manageGroup("delete")) {
                $message['action'] = _('Eliminar Grupo');
                $message['text'][] = _('Nombre') . ': ' . $frmGrpName;

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Grupo eliminado'), 0);
            } else {
                SP_Common::printXML(_('Error al eliminar el grupo'));
            }
        }
    } else {
        SP_Common::printXML(_('No es una acción válida'));
    }
} elseif ($frmSaveType == 5 || $frmSaveType == 6) {
    $profileProp = array();

    // Variables POST del formulario
    $frmProfileName = ( isset($_POST["profile_name"]) ) ? SP_Html::sanitize($_POST["profile_name"]) : "";
    $objUser->profileId = $frmItemId;

    // Profile properties Array
    $profileProp["pAccView"] = ( isset($_POST["profile_accview"]) ) ? 1 : 0;
    $profileProp["pAccViewPass"] = ( isset($_POST["profile_accviewpass"]) ) ? 1 : 0;
    $profileProp["pAccViewHistory"] = ( isset($_POST["profile_accviewhistory"]) ) ? 1 : 0;
    $profileProp["pAccEdit"] = ( isset($_POST["profile_accedit"]) ) ? 1 : 0;
    $profileProp["pAccEditPass"] = ( isset($_POST["profile_acceditpass"]) ) ? 1 : 0;
    $profileProp["pAccAdd"] = ( isset($_POST["profile_accadd"]) ) ? 1 : 0;
    $profileProp["pAccDel"] = ( isset($_POST["profile_accdel"]) ) ? 1 : 0;
    $profileProp["pAccFiles"] = ( isset($_POST["profile_accfiles"]) ) ? 1 : 0;
    $profileProp["pConfig"] = ( isset($_POST["profile_config"]) ) ? 1 : 0;
    $profileProp["pConfigCat"] = ( isset($_POST["profile_configcat"]) ) ? 1 : 0;
    $profileProp["pConfigMpw"] = ( isset($_POST["profile_configmpw"]) ) ? 1 : 0;
    $profileProp["pConfigBack"] = ( isset($_POST["profile_configback"]) ) ? 1 : 0;
    $profileProp["pUsers"] = ( isset($_POST["profile_users"]) ) ? 1 : 0;
    $profileProp["pGroups"] = ( isset($_POST["profile_groups"]) ) ? 1 : 0;
    $profileProp["pProfiles"] = ( isset($_POST["profile_profiles"]) ) ? 1 : 0;
    $profileProp["pEventlog"] = ( isset($_POST["profile_eventlog"]) ) ? 1 : 0;

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
            } else {
                SP_Common::printXML(_('Error al crear el perfil'));
            }
        } else if ($frmAction == 2) {
            if ($objUser->manageProfiles("update", $profileProp)) {
                $message['action'] = _('Modificar Perfil');
                $message['text'][] = _('Nombre') . ': ' . $frmProfileName;

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Perfil actualizado'), 0);
            } else {
                SP_Common::printXML(_('Error al actualizar el perfil'));
            }
        }

        // Eliminar perfil
    } elseif ($frmAction == 4) {
        $resProfileUse = $objUser->checkProfileInUse();

        if (is_string($resProfileUse)) {
            SP_Common::printXML(_('No es posible eliminar: Perfil en uso por') . ' ' . $resProfileUse);
        } else {
            if ($objUser->manageProfiles("delete")) {
                $message['action'] = _('Eliminar Perfil');
                $message['text'][] = _('Nombre') . ': ' . $frmProfileName;

                SP_Common::wrLogInfo($message);
                SP_Common::sendEmail($message);

                SP_Common::printXML(_('Perfil eliminado'), 0);
            } else {
                SP_Common::printXML(_('Error al eliminar el perfil'));
            }
        }
    } else {
        SP_Common::printXML(_('No es una acción válida'));
    }
}