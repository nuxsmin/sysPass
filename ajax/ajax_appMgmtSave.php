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

use SP\Request;
use SP\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}


// Variables POST del formulario
$actionId = SP\Request::analyze('actionId', 0);
$itemId = SP\Request::analyze('itemId', 0);
$onCloseAction = SP\Request::analyze('onCloseAction');
$activeTab = SP\Request::analyze('activeTab', 0);
$customFields = SP\Request::analyze('customfield');

// Acción al cerrar la vista
$doActionOnClose = "doAction('$onCloseAction','',$activeTab);";

$userLogin = UserUtil::getUserLoginById($itemId);

if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_DELETE
) {
    $isLdap = SP\Request::analyze('isLdap', 0);
    $userPassR = SP\Request::analyze('passR', '', false, false, false);

    $User = new SP\User();
    $User->setUserId($itemId);
    $User->setUserName(SP\Request::analyze('name'));
    $User->setUserLogin(SP\Request::analyze('login'));
    $User->setUserEmail(SP\Request::analyze('email'));
    $User->setUserNotes(SP\Request::analyze('notes'));
    $User->setUserGroupId(SP\Request::analyze('groupid', 0));
    $User->setUserProfileId(SP\Request::analyze('profileid', 0));
    $User->setUserIsAdminApp(SP\Request::analyze('adminapp', 0, false, 1));
    $User->setUserIsAdminAcc(SP\Request::analyze('adminacc', 0, false, 1));
    $User->setUserIsDisabled(SP\Request::analyze('disabled', 0, false, 1));
    $User->setUserChangePass(SP\Request::analyze('changepass', 0, false, 1));
    $User->setUserPass(SP\Request::analyze('pass', '', false, false, false));

    // Nuevo usuario o editar
    if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDIT
    ) {
        if (empty($User->getUserName()) && !$isLdap) {
            SP\Common::printJSON(_('Es necesario un nombre de usuario'), 2);
        } elseif (empty($User->getUserLogin()) && !$isLdap) {
            SP\Common::printJSON(_('Es necesario un login'), 2);
        } elseif (!$User->getUserProfileId()) {
            SP\Common::printJSON(_('Es necesario un perfil'), 2);
        } elseif (!$User->getUserGroupId()) {
            SP\Common::printJSON(_('Es necesario un grupo'), 2);
        } elseif (empty($User->getUserEmail()) && !$isLdap) {
            SP\Common::printJSON(_('Es necesario un email'), 2);
        }

        switch ($User->checkUserExist()) {
            case UserUtil::USER_LOGIN_EXIST:
                SP\Common::printJSON(_('Login de usuario duplicado'), 2);
                break;
            case UserUtil::USER_MAIL_EXIST:
                SP\Common::printJSON(_('Email de usuario duplicado'), 2);
                break;
        }

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_NEW) {
            if (empty($User->getUserPass()) && empty($userPassR)) {
                SP\Common::printJSON(_('La clave no puede estar en blanco'), 2);
            } elseif ($User->getUserPass() != $userPassR) {
                SP\Common::printJSON(_('Las claves no coinciden'), 2);
            }

            if ($User->addUser()) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\CustomFields($id, $User->getUserId(), $value);
                        $CustomFields->addCustomField();
                    }
                }

                SP\Common::printJSON(_('Usuario creado'), 0, $doActionOnClose);
            }

            SP\Common::printJSON(_('Error al crear el usuario'));
        } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDIT) {
            if ($User->updateUser()) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\CustomFields($id, $User->getUserId(), $value);
                        $CustomFields->updateCustomField();
                    }
                }

                SP\Common::printJSON(_('Usuario actualizado'), 0, $doActionOnClose);
            }

            SP\Common::printJSON(_('Error al actualizar el usuario'));
        }
    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_EDITPASS) {
        if (SP\Util::demoIsEnabled() && UserUtil::getUserLoginById($itemId) == 'demo') {
            SP\Common::printJSON(_('Ey, esto es una DEMO!!'));
        } elseif (empty($User->getUserPass()) || empty($userPassR)) {
            SP\Common::printJSON(_('La clave no puede estar en blanco'), 2);
        } elseif ($User->getUserPass() != $userPassR) {
            SP\Common::printJSON(_('Las claves no coinciden'), 2);
        }

        if ($User->updateUserPass()) {
            SP\Common::printJSON(_('Clave actualizada'), 0);
        }

        SP\Common::printJSON(_('Error al modificar la clave'));
        // Eliminar usuario
    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_USERS_DELETE) {
        if (SP\Util::demoIsEnabled() && UserUtil::getUserLoginById($itemId) == 'demo') {
            SP\Common::printJSON(_('Ey, esto es una DEMO!!'));
        } elseif ($User->getUserId() == SP\Session::getUserId()) {
            SP\Common::printJSON(_('No es posible eliminar, usuario en uso'));
        }

        if ($User->deleteUser() && SP\CustomFields::deleteCustomFieldForItem($User->getUserId(), \SP\Controller\ActionsInterface::ACTION_USR_USERS)) {
            SP\Common::printJSON(_('Usuario eliminado'), 0, $doActionOnClose);
        }

        SP\Common::printJSON(_('Error al eliminar el usuario'));
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_DELETE
) {
    // Variables POST del formulario
    $frmGrpName = SP\Request::analyze('name');
    $frmGrpDesc = SP\Request::analyze('description');
    $frmGrpUsers = SP\Request::analyze('users');

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_EDIT
    ) {
        if (empty($frmGrpName)) {
            SP\Common::printJSON(_('Es necesario un nombre de grupo'), 2);
        }

        SP\Groups::$groupId = $itemId;
        SP\Groups::$groupName = $frmGrpName;
        SP\Groups::$groupDescription = $frmGrpDesc;

        if (SP\Groups::checkGroupExist()) {
            SP\Common::printJSON(_('Nombre de grupo duplicado'), 2);
        }

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_NEW) {
            if (SP\Groups::addGroup($frmGrpUsers)) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\CustomFields($id, SP\Groups::$queryLastId, $value);
                        $CustomFields->addCustomField();
                    }
                }

                SP\Common::printJSON(_('Grupo creado'), 0, $doActionOnClose);
            } else {
                SP\Common::printJSON(_('Error al crear el grupo'));
            }
        } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_GROUPS_EDIT) {
            if (SP\Groups::updateGroup($frmGrpUsers)) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\CustomFields($id, $itemId, $value);
                        $CustomFields->updateCustomField();
                    }
                }

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

            if (SP\Groups::deleteGroup() && SP\CustomFields::deleteCustomFieldForItem($itemId, \SP\Controller\ActionsInterface::ACTION_USR_GROUPS)) {
                SP\Common::printJSON(_('Grupo eliminado'), 0, $doActionOnClose);
            }

            SP\Common::printJSON(_('Error al eliminar el grupo'));
        }
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_DELETE
) {
    $Profile = new \SP\Profile();

    // Variables POST del formulario
    $name = SP\Request::analyze('profile_name');

    $Profile->setName($name);
    $Profile->setId(SP\Request::analyze('itemId', 0));
    $Profile->setAccAdd(SP\Request::analyze('profile_accadd', 0, false, 1));
    $Profile->setAccView(SP\Request::analyze('profile_accview', 0, false, 1));
    $Profile->setAccViewPass(SP\Request::analyze('profile_accviewpass', 0, false, 1));
    $Profile->setAccViewHistory(SP\Request::analyze('profile_accviewhistory', 0, false, 1));
    $Profile->setAccEdit(SP\Request::analyze('profile_accedit', 0, false, 1));
    $Profile->setAccEditPass(SP\Request::analyze('profile_acceditpass', 0, false, 1));
    $Profile->setAccDelete(SP\Request::analyze('profile_accdel', 0, false, 1));
    $Profile->setAccFiles(SP\Request::analyze('profile_accfiles', 0, false, 1));
    $Profile->setConfigGeneral(SP\Request::analyze('profile_config', 0, false, 1));
    $Profile->setConfigEncryption(SP\Request::analyze('profile_configmpw', 0, false, 1));
    $Profile->setConfigBackup(SP\Request::analyze('profile_configback', 0, false, 1));
    $Profile->setConfigImport(SP\Request::analyze('profile_configimport', 0, false, 1));
    $Profile->setMgmCategories(SP\Request::analyze('profile_categories', 0, false, 1));
    $Profile->setMgmCustomers(SP\Request::analyze('profile_customers', 0, false, 1));
    $Profile->setMgmCustomFields(SP\Request::analyze('profile_customfields', 0, false, 1));
    $Profile->setMgmUsers(SP\Request::analyze('profile_users', 0, false, 1));
    $Profile->setMgmGroups(SP\Request::analyze('profile_groups', 0, false, 1));
    $Profile->setMgmProfiles(SP\Request::analyze('profile_profiles', 0, false, 1));
    $Profile->setMgmApiTokens(SP\Request::analyze('profile_apitokens', 0, false, 1));
    $Profile->setEvl(SP\Request::analyze('profile_eventlog', 0, false, 1));

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_EDIT
    ) {
        if (empty($Profile->getName())) {
            SP\Common::printJSON(_('Es necesario un nombre de perfil'), 2);
        } elseif (SP\Profile::checkProfileExist($Profile->getId(), $Profile->getName())) {
            SP\Common::printJSON(_('Nombre de perfil duplicado'), 2);
        }

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_NEW) {
            if ($Profile->profileAdd()) {
                SP\Common::printJSON(_('Perfil creado'), 0, $doActionOnClose);
            }

            SP\Common::printJSON(_('Error al crear el perfil'));
        } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_EDIT) {
            if ($Profile->profileUpdate()) {
                SP\Common::printJSON(_('Perfil actualizado'), 0, $doActionOnClose);
            }

            SP\Common::printJSON(_('Error al actualizar el perfil'));
        }

    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_USR_PROFILES_DELETE) {
        $resProfileUse = SP\Profile::checkProfileInUse($Profile->getId());

        if ($resProfileUse['users'] > 0) {
            $uses[] = _('Usuarios') . " (" . $resProfileUse['users'] . ")";

            SP\Common::printJSON(_('No es posible eliminar') . ';;' . _('Perfil en uso por:') . ';;' . implode(';;', $uses));
        } else {
            if ($Profile->profileDelete()) {
                SP\Common::printJSON(_('Perfil eliminado'), 0, $doActionOnClose);
            }

            SP\Common::printJSON(_('Error al eliminar el perfil'));
        }
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE
) {
    // Variables POST del formulario
    $frmCustomerName = SP\Request::analyze('name');
    $frmCustomerDesc = SP\Request::analyze('description');

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    ) {
        if (empty($frmCustomerName)) {
            SP\Common::printJSON(_('Es necesario un nombre de cliente'), 2);
        }

        SP\Customer::$customerName = $frmCustomerName;
        SP\Customer::$customerDescription = $frmCustomerDesc;

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW) {
            try {
                SP\Customer::addCustomer($itemId);

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\CustomFields($id, SP\Customer::$customerLastId, $value);
                        $CustomFields->addCustomField();
                    }
                }
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage(), 2);
            }

            SP\Common::printJSON(_('Cliente creado'), 0, $doActionOnClose);
        } else if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT) {
            try {
                SP\Customer::updateCustomer($itemId);

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\CustomFields($id, $itemId, $value);
                        $CustomFields->updateCustomField();
                    }
                }
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage(), 2);
            }

            SP\Common::printJSON(_('Cliente actualizado'), 0, $doActionOnClose);
        }
    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE) {
        try {
            SP\Customer::deleteCustomer($itemId);
            SP\CustomFields::deleteCustomFieldForItem($itemId, \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMERS);
        } catch (\SP\SPException $e) {
            SP\Common::printJSON($e->getMessage());
        }

        SP\Common::printJSON(_('Cliente eliminado'), 0, $doActionOnClose);
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_DELETE
) {
    // Variables POST del formulario
    $frmCategoryName = SP\Request::analyze('name');
    $frmCategoryDesc = SP\Request::analyze('description');

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    ) {
        if (empty($frmCategoryName)) {
            SP\Common::printJSON(_('Es necesario un nombre de categoría'), 2);
        }

        SP\Category::$categoryName = $frmCategoryName;
        SP\Category::$categoryDescription = $frmCategoryDesc;

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_NEW) {
            try {
                SP\Category::addCategory();

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\CustomFields($id, SP\Category::$categoryLastId, $value);
                        $CustomFields->addCustomField();
                    }
                }
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage(), 2);
            }

            SP\Common::printJSON(_('Categoría creada'), 0, $doActionOnClose);
        } else if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_EDIT) {
            try {
                SP\Category::updateCategory($itemId);

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\CustomFields($id, $itemId, $value);
                        $CustomFields->updateCustomField();
                    }
                }
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage(), 2);
            }

            SP\Common::printJSON(_('Categoría actualizada'), 0, $doActionOnClose);
        }

    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES_DELETE) {
        try {
            SP\Category::deleteCategory($itemId);
            SP\CustomFields::deleteCustomFieldForItem($itemId, \SP\Controller\ActionsInterface::ACTION_MGM_CATEGORIES);
        } catch (\SP\SPException $e) {
            SP\Common::printJSON($e->getMessage());
        }

        SP\Common::printJSON(_('Categoría eliminada'), 0, $doActionOnClose);
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_APITOKENS_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_APITOKENS_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_APITOKENS_DELETE
) {
    $ApiTokens = new \SP\ApiTokens();
    $ApiTokens->setTokenId($itemId);
    $ApiTokens->setUserId(SP\Request::analyze('users', 0));
    $ApiTokens->setActionId(SP\Request::analyze('actions', 0));
    $ApiTokens->setRefreshToken(SP\Request::analyze('refreshtoken', false, false, true));

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_APITOKENS_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_APITOKENS_EDIT)
    {
        if ($ApiTokens->getUserId() === 0 || $ApiTokens->getActionId() === 0) {
            SP\Common::printJSON(_('Usuario o acción no indicado'), 2);
        }

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_APITOKENS_NEW){
            try {
                $ApiTokens->addToken();
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage(), 2);
            }

            SP\Common::printJSON(_('Autorización creada'), 0, $doActionOnClose);
        } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_APITOKENS_EDIT){
            try {
                $ApiTokens->updateToken();
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage(), 2);
            }

            SP\Common::printJSON(_('Autorización actualizada'), 0, $doActionOnClose);
        }

    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_APITOKENS_DELETE){
        try {
            $ApiTokens->deleteToken();
        } catch (\SP\SPException $e) {
            SP\Common::printJSON($e->getMessage(), 2);
        }

        SP\Common::printJSON(_('Autorización eliminada'), 0, $doActionOnClose);
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT
    || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE
) {
    // Variables POST del formulario
    $frmFieldName = SP\Request::analyze('name');
    $frmFieldType = SP\Request::analyze('type', 0);
    $frmFieldModule = SP\Request::analyze('module', 0);
    $frmFieldHelp = SP\Request::analyze('help');
    $frmFieldRequired = SP\Request::analyze('required', false, false, true);

    if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW
        || $actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT)
    {
        if (!$frmFieldName) {
            SP\Common::printJSON(_('Nombre del campo no indicado'), 2);
        } elseif ($frmFieldType === 0) {
            SP\Common::printJSON(_('Tipo del campo no indicado'), 2);
        } elseif ($frmFieldModule === 0) {
            SP\Common::printJSON(_('Módulo del campo no indicado'), 2);
        }

        $CustomFieldDef = new \SP\CustomFieldDef($frmFieldName, $frmFieldType, $frmFieldModule);
        $CustomFieldDef->setHelp($frmFieldHelp);
        $CustomFieldDef->setRequired($frmFieldRequired);

        if ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW){
            try {
                $CustomFieldDef->addCustomField();
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage(), 2);
            }

            SP\Common::printJSON(_('Campo creado'), 0, $doActionOnClose);
        } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT){
            try {
                $CustomFieldDef->setId($itemId);
                $CustomFieldDef->updateCustomField();
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage(), 2);
            }

            SP\Common::printJSON(_('Campo actualizado'), 0, $doActionOnClose);
        }

    } elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE){
        try {
            \SP\CustomFieldDef::deleteCustomField($itemId);
        } catch (\SP\SPException $e) {
            SP\Common::printJSON($e->getMessage(), 2);
        }

        SP\Common::printJSON(_('Campo eliminado'), 0, $doActionOnClose);
    }
} else {
    SP\Common::printJSON(_('Acción Inválida'));
}