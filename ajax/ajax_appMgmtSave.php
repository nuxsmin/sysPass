<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Email;
use SP\Request;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
     SP\Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk =  SP\Request::analyze('sk', false);

if (!$sk || ! SP\Common::checkSessionKey($sk)) {
     SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}


// Variables POST del formulario
$actionId =  SP\Request::analyze('actionId', 0);
$itemId =  SP\Request::analyze('itemId', 0);
$onCloseAction =  SP\Request::analyze('onCloseAction');
$activeTab =  SP\Request::analyze('activeTab', 0);

// Acción al cerrar la vista
$doActionOnClose = "doAction('$onCloseAction','',$activeTab);";

$userLogin = SP\Users::getUserLoginById($itemId);

if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_DELETE
) {
    $user = new SP\Users;

    // Variables POST del formulario
    $isLdap =  SP\Request::analyze('isLdap', 0);
    $userName =  SP\Request::analyze('name');
    $userLogin =  SP\Request::analyze('login');
    $userProfile =  SP\Request::analyze('profileid', 0);
    $userGroup =  SP\Request::analyze('groupid', 0);
    $userEmail =  SP\Request::analyze('email');
    $userNotes =  SP\Request::analyze('notes');
    $userPass =  SP\Request::analyze('pass', '', false, false, false);
    $userPassV =  SP\Request::analyze('passv', '', false, false, false);
    $userIsAdminApp =  SP\Request::analyze('adminapp', 0, false, 1);
    $userIsAdminAcc =  SP\Request::analyze('adminacc', 0, false, 1);
    $userIsDisabled =  SP\Request::analyze('disabled', 0, false, 1);
    $userIsChangePass =  SP\Request::analyze('changepass', 0, false, 1);

    // Nuevo usuario o editar
    if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDIT
    ) {
        if (!$userName && !$isLdap) {
             SP\Common::printJSON(_('Es necesario un nombre de usuario'), 2);
        }

        if (!$userLogin && !$isLdap) {
             SP\Common::printJSON(_('Es necesario un login'), 2);
        }

        if ($userProfile == "") {
             SP\Common::printJSON(_('Es necesario un perfil'), 2);
        }

        if (!$userGroup) {
             SP\Common::printJSON(_('Es necesario un grupo'), 2);
        }

        if (!$userEmail && !$isLdap) {
             SP\Common::printJSON(_('Es necesario un email'), 2);
        }

        $user->userId = $itemId;
        $user->userName = $userName;
        $user->userLogin = $userLogin;
        $user->userEmail = $userEmail;
        $user->userNotes = $userNotes;
        $user->userGroupId = $userGroup;
        $user->userProfileId = $userProfile;
        $user->userIsAdminApp = $userIsAdminApp;
        $user->userIsAdminAcc = $userIsAdminAcc;
        $user->userIsDisabled = $userIsDisabled;
        $user->userChangePass = $userIsChangePass;
        $user->userPass = $userPass;

        switch ($user->checkUserExist()) {
        case 1:
             SP\Common::printJSON(_('Login de usuario duplicado'), 2);
            break;
        case 2:
             SP\Common::printJSON(_('Email de usuario duplicado'), 2);
            break;
        }

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_NEW) {
            if (!$userPass && !$userPassV) {
                 SP\Common::printJSON(_('La clave no puede estar en blanco'), 2);
            }

            if ($userPass != $userPassV) {
                 SP\Common::printJSON(_('Las claves no coinciden'), 2);
            }

            if ($user->addUser()) {
                 SP\Common::printJSON(_('Usuario creado'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al crear el usuario'));
        } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDIT) {
            if ($user->updateUser()) {
                 SP\Common::printJSON(_('Usuario actualizado'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al actualizar el usuario'));
        }
    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS) {
        if ( SP\Util::demoIsEnabled() && $userLogin == 'demo') {
             SP\Common::printJSON(_('Ey, esto es una DEMO!!'));
        }

        if (!$userPass || !$userPassV) {
             SP\Common::printJSON(_('La clave no puede estar en blanco'), 2);
        }

        if ($userPass != $userPassV) {
             SP\Common::printJSON(_('Las claves no coinciden'), 2);
        }

        $user->userId = $itemId;
        $user->userPass = $userPass;

        if ($user->updateUserPass()) {
             SP\Common::printJSON(_('Clave actualizada'), 0);
        }

         SP\Common::printJSON(_('Error al modificar la clave'));
        // Eliminar usuario
    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_DELETE) {
        if ( SP\Util::demoIsEnabled() && $userLogin == 'demo') {
             SP\Common::printJSON(_('Ey, esto es una DEMO!!'));
        }

        $user->userId = $itemId;

        if ($itemId == SP\Session::getUserId()) {
             SP\Common::printJSON(_('No es posible eliminar, usuario en uso'));
        }

        if ($user->deleteUser()) {
             SP\Common::printJSON(_('Usuario eliminado'), 0, $doActionOnClose);
        }

         SP\Common::printJSON(_('Error al eliminar el usuario'));
    }

     SP\Common::printJSON(_('Acción Inválida'));
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_DELETE
) {
    // Variables POST del formulario
    $frmGrpName =  SP\Request::analyze('name');
    $frmGrpDesc =  SP\Request::analyze('description');

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_EDIT
    ) {
        if (!$frmGrpName) {
             SP\Common::printJSON(_('Es necesario un nombre de grupo'), 2);
        }

        SP\Groups::$groupId = $itemId;
        SP\Groups::$groupName = $frmGrpName;
        SP\Groups::$groupDescription = $frmGrpDesc;

        if (SP\Groups::checkGroupExist()) {
             SP\Common::printJSON(_('Nombre de grupo duplicado'), 2);
        }

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_NEW) {
            if (SP\Groups::addGroup()) {
                 SP\Common::printJSON(_('Grupo creado'), 0, $doActionOnClose);
            } else {
                 SP\Common::printJSON(_('Error al crear el grupo'));
            }
        } else if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_EDIT) {
            if (SP\Groups::updateGroup()) {
                 SP\Common::printJSON(_('Grupo actualizado'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al actualizar el grupo'));
        }
    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_DELETE) {
        SP\Groups::$groupId = $itemId;

        $resGroupUse = SP\Groups::checkGroupInUse();

        if ($resGroupUse['users'] > 0 || $resGroupUse['accounts'] > 0) {
            if ($resGroupUse['users'] > 0) {
                $uses[] = _('Usuarios') . " (" . $resGroupUse['users'] . ")";
            }

            if ($resGroupUse['accounts'] > 0) {
                $uses[] = _('Cuentas') . " (" . $resGroupUse['accounts'] . ")";
            }

             SP\Common::printJSON(_('No es posible eliminar') . ';;' . _('Grupo en uso por:') . ';;' . implode(';;', $uses));
        } else {
            $groupName = SP\Groups::getGroupNameById($itemId);

            if (SP\Groups::deleteGroup()) {
                 SP\Common::printJSON(_('Grupo eliminado'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al eliminar el grupo'));
        }
    }

     SP\Common::printJSON(_('Acción Inválida'));
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_DELETE
) {
    $profile = new \SP\Profile();

    // Variables POST del formulario
    $name = SP\Request::analyze('profile_name');

    $profile->setName($name);
    $profile->setId(SP\Request::analyze('itemId', 0));
    $profile->setAccAdd(SP\Request::analyze('profile_accadd', 0, false, 1));
    $profile->setAccView(SP\Request::analyze('profile_accview', 0, false, 1));
    $profile->setAccViewPass(SP\Request::analyze('profile_accviewpass', 0, false, 1));
    $profile->setAccViewHistory(SP\Request::analyze('profile_accviewhistory', 0, false, 1));
    $profile->setAccEdit(SP\Request::analyze('profile_accedit', 0, false, 1));
    $profile->setAccEditPass(SP\Request::analyze('profile_acceditpass', 0, false, 1));
    $profile->setAccDelete(SP\Request::analyze('profile_accdel', 0, false, 1));
    $profile->setConfigGeneral(SP\Request::analyze('profile_config', 0, false, 1));
    $profile->setConfigEncryption(SP\Request::analyze('profile_configmpw', 0, false, 1));
    $profile->setConfigBackup(SP\Request::analyze('profile_configback', 0, false, 1));
    $profile->setMgmCategories(SP\Request::analyze('profile_categories', 0, false, 1));
    $profile->setMgmCustomers(SP\Request::analyze('profile_customers', 0, false, 1));
    $profile->setMgmUsers(SP\Request::analyze('profile_users', 0, false, 1));
    $profile->setMgmGroups(SP\Request::analyze('profile_groups', 0, false, 1));
    $profile->setMgmProfiles(SP\Request::analyze('profile_profiles', 0, false, 1));
    $profile->setEvl(SP\Request::analyze('profile_eventlog', 0, false, 1));

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_EDIT
    ) {
        if (!$name) {
             SP\Common::printJSON(_('Es necesario un nombre de perfil'), 2);
        } elseif (SP\Profile::checkProfileExist($profile->getId(), $profile->getName())) {
             SP\Common::printJSON(_('Nombre de perfil duplicado'), 2);
        }

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_NEW) {
            if ($profile->profileAdd()) {
                 SP\Common::printJSON(_('Perfil creado'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al crear el perfil'));
        } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_EDIT) {
            if ($profile->profileUpdate()) {
                 SP\Common::printJSON(_('Perfil actualizado'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al actualizar el perfil'));
        }

    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_DELETE) {
        $resProfileUse = SP\Profile::checkProfileInUse($profile->getId());

        if ($resProfileUse['users'] > 0) {
            $uses[] = _('Usuarios') . " (" . $resProfileUse['users'] . ")";

             SP\Common::printJSON(_('No es posible eliminar') . ';;' . _('Perfil en uso por:') . ';;' . implode(';;', $uses));
        } else {
            if ($profile->profileDelete()) {
                SP\Common::printJSON(_('Perfil eliminado'), 0, $doActionOnClose);
            }

            SP\Common::printJSON(_('Error al eliminar el perfil'));
        }
    }

     SP\Common::printJSON(_('Acción Inválida'));
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE
) {
    // Variables POST del formulario
    $frmCustomerName =  SP\Request::analyze('name');
    $frmCustomerDesc =  SP\Request::analyze('description');

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    ) {
        if (!$frmCustomerName) {
             SP\Common::printJSON(_('Es necesario un nombre de cliente'), 2);
        }

        SP\Customer::$customerName = $frmCustomerName;
        SP\Customer::$customerDescription = $frmCustomerDesc;

        if (SP\Customer::checkDupCustomer($itemId)) {
             SP\Common::printJSON(_('Nombre de cliente duplicado'), 2);
        }

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW) {
            if (SP\Customer::addCustomer()) {
                 SP\Common::printJSON(_('Cliente creado'), 0, $doActionOnClose);
            } else {
                 SP\Common::printJSON(_('Error al crear el cliente'));
            }
        } else if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT) {
            if (SP\Customer::updateCustomer($itemId)) {
                 SP\Common::printJSON(_('Cliente actualizado'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al actualizar el cliente'));
        }

    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE) {
        $resCustomerUse = SP\Customer::checkCustomerInUse($itemId);

        if ($resCustomerUse['accounts'] > 0) {
            $uses[] = _('Cuentas') . " (" . $resCustomerUse['accounts'] . ")";

             SP\Common::printJSON(_('No es posible eliminar') . ';;' . _('Cliente en uso por:') . ';;' . implode(';;', $uses));
        } else {

            if (SP\Customer::delCustomer($itemId)) {
                 SP\Common::printJSON(_('Cliente eliminado'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al eliminar el cliente'));
        }
    }

     SP\Common::printJSON(_('Acción Inválida'));
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_DELETE
) {
    // Variables POST del formulario
    $frmCategoryName =  SP\Request::analyze('name');
    $frmCategoryDesc =  SP\Request::analyze('description');

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    ) {
        if (!$frmCategoryName) {
             SP\Common::printJSON(_('Es necesario un nombre de categoría'), 2);
        }

        SP\Category::$categoryName = $frmCategoryName;
        SP\Category::$categoryDescription = $frmCategoryDesc;

        if (SP\Category::checkDupCategory($itemId)) {
             SP\Common::printJSON(_('Nombre de categoría duplicado'), 2);
        }

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_NEW) {
            if (SP\Category::addCategory()) {
                 SP\Common::printJSON(_('Categoría creada'), 0, $doActionOnClose);
            } else {
                 SP\Common::printJSON(_('Error al crear la categoría'));
            }
        } else if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_EDIT) {
            if (SP\Category::updateCategory($itemId)) {
                 SP\Common::printJSON(_('Categoría actualizada'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al actualizar la categoría'));
        }

    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_DELETE) {
        $resCategoryUse = SP\Category::checkCategoryInUse($itemId);

        if ($resCategoryUse !== true) {
             SP\Common::printJSON(_('No es posible eliminar') . ';;' . _('Categoría en uso por:') . ';;' . $resCategoryUse);
        } else {

            if (SP\Category::delCategory($itemId)) {
                 SP\Common::printJSON(_('Categoría eliminada'), 0, $doActionOnClose);
            }

             SP\Common::printJSON(_('Error al eliminar la categoría'));
        }
    }

     SP\Common::printJSON(_('Acción Inválida'));
}