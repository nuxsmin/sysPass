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

use SP\Http\Request;
use SP\Core\SessionUtil;
use SP\Mgmt\User\UserUtil;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!\SP\Core\Init::isLoggedIn()) {
    \SP\Http\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = \SP\Http\Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    \SP\Http\Response::printJSON(_('CONSULTA INVÁLIDA'));
}


// Variables POST del formulario
$actionId = \SP\Http\Request::analyze('actionId', 0);
$itemId = \SP\Http\Request::analyze('itemId', 0);
$onCloseAction = \SP\Http\Request::analyze('onCloseAction');
$activeTab = \SP\Http\Request::analyze('activeTab', 0);
$customFields = \SP\Http\Request::analyze('customfield');

// Acción al cerrar la vista
$doActionOnClose = "sysPassUtil.Common.doAction('$onCloseAction','',$activeTab);";

$userLogin = UserUtil::getUserLoginById($itemId);

if ($actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_NEW
    || $actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_EDIT
    || $actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_EDITPASS
    || $actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_DELETE
) {
    $isLdap = \SP\Http\Request::analyze('isLdap', 0);
    $userPassR = \SP\Http\Request::analyzeEncrypted('passR');

    $User = new \SP\Mgmt\User\User();
    $User->setUserId($itemId);
    $User->setUserName(\SP\Http\Request::analyze('name'));
    $User->setUserLogin(\SP\Http\Request::analyze('login'));
    $User->setUserEmail(\SP\Http\Request::analyze('email'));
    $User->setUserNotes(\SP\Http\Request::analyze('notes'));
    $User->setUserGroupId(\SP\Http\Request::analyze('groupid', 0));
    $User->setUserProfileId(\SP\Http\Request::analyze('profileid', 0));
    $User->setUserIsAdminApp(\SP\Http\Request::analyze('adminapp', 0, false, 1));
    $User->setUserIsAdminAcc(\SP\Http\Request::analyze('adminacc', 0, false, 1));
    $User->setUserIsDisabled(\SP\Http\Request::analyze('disabled', 0, false, 1));
    $User->setUserChangePass(\SP\Http\Request::analyze('changepass', 0, false, 1));
    $User->setUserPass(\SP\Http\Request::analyzeEncrypted('pass'));

    // Nuevo usuario o editar
    if ($actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_NEW
        || $actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_EDIT
    ) {
        if (!$User->getUserName() && !$isLdap) {
            \SP\Http\Response::printJSON(_('Es necesario un nombre de usuario'), 2);
        } elseif (!$User->getUserLogin() && !$isLdap) {
            \SP\Http\Response::printJSON(_('Es necesario un login'), 2);
        } elseif (!$User->getUserProfileId()) {
            \SP\Http\Response::printJSON(_('Es necesario un perfil'), 2);
        } elseif (!$User->getUserGroupId()) {
            \SP\Http\Response::printJSON(_('Es necesario un grupo'), 2);
        } elseif (!$User->getUserEmail() && !$isLdap) {
            \SP\Http\Response::printJSON(_('Es necesario un email'), 2);
        } elseif (Checks::demoIsEnabled() && !\SP\Core\Session::getUserIsAdminApp() && $User->getUserLogin() == 'demo') {
            \SP\Http\Response::printJSON(_('Ey, esto es una DEMO!!'));
        }

        switch ($User->checkUserExist()) {
            case UserUtil::USER_LOGIN_EXIST:
                \SP\Http\Response::printJSON(_('Login de usuario duplicado'), 2);
                break;
            case UserUtil::USER_MAIL_EXIST:
                \SP\Http\Response::printJSON(_('Email de usuario duplicado'), 2);
                break;
        }

        if ($actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_NEW) {
            if (!$User->getUserPass() || !$userPassR) {
                \SP\Http\Response::printJSON(_('La clave no puede estar en blanco'), 2);
            } elseif ($User->getUserPass() != $userPassR) {
                \SP\Http\Response::printJSON(_('Las claves no coinciden'), 2);
            }

            if ($User->addUser()) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\Mgmt\CustomFields($id, $User->getUserId(), $value);
                        $CustomFields->addCustomField();
                    }
                }

                \SP\Http\Response::printJSON(_('Usuario creado'), 0, $doActionOnClose);
            }

            \SP\Http\Response::printJSON(_('Error al crear el usuario'));
        } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_EDIT) {
            if ($User->updateUser()) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\Mgmt\CustomFields($id, $User->getUserId(), $value);
                        $CustomFields->updateCustomField();
                    }
                }

                \SP\Http\Response::printJSON(_('Usuario actualizado'), 0, $doActionOnClose);
            }

            \SP\Http\Response::printJSON(_('Error al actualizar el usuario'));
        }
    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_EDITPASS) {


        if (Checks::demoIsEnabled() && UserUtil::getUserLoginById($itemId) == 'demo') {
            \SP\Http\Response::printJSON(_('Ey, esto es una DEMO!!'));
        } elseif (!$User->getUserPass() || !$userPassR) {
            \SP\Http\Response::printJSON(_('La clave no puede estar en blanco'), 2);
        } elseif ($User->getUserPass() != $userPassR) {
            \SP\Http\Response::printJSON(_('Las claves no coinciden'), 2);
        }

        if ($User->updateUserPass()) {
            \SP\Http\Response::printJSON(_('Clave actualizada'), 0);
        }

        \SP\Http\Response::printJSON(_('Error al modificar la clave'));
        // Eliminar usuario
    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_USR_USERS_DELETE) {
        if (Checks::demoIsEnabled() && UserUtil::getUserLoginById($itemId) == 'demo') {
            \SP\Http\Response::printJSON(_('Ey, esto es una DEMO!!'));
        } elseif ($User->getUserId() == \SP\Core\Session::getUserId()) {
            \SP\Http\Response::printJSON(_('No es posible eliminar, usuario en uso'));
        }

        if ($User->deleteUser() && \SP\Mgmt\CustomFields::deleteCustomFieldForItem($User->getUserId(), \SP\Core\ActionsInterface::ACTION_USR_USERS)) {
            \SP\Http\Response::printJSON(_('Usuario eliminado'), 0, $doActionOnClose);
        }

        \SP\Http\Response::printJSON(_('Error al eliminar el usuario'));
    }
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_USR_GROUPS_NEW
    || $actionId === \SP\Core\ActionsInterface::ACTION_USR_GROUPS_EDIT
    || $actionId === \SP\Core\ActionsInterface::ACTION_USR_GROUPS_DELETE
) {
    // Variables POST del formulario
    $frmGrpName = \SP\Http\Request::analyze('name');
    $frmGrpDesc = \SP\Http\Request::analyze('description');
    $frmGrpUsers = \SP\Http\Request::analyze('users');

    if ($actionId === \SP\Core\ActionsInterface::ACTION_USR_GROUPS_NEW
        || $actionId === \SP\Core\ActionsInterface::ACTION_USR_GROUPS_EDIT
    ) {
        if (!$frmGrpName) {
            \SP\Http\Response::printJSON(_('Es necesario un nombre de grupo'), 2);
        }

        \SP\Mgmt\User\Groups::$groupId = $itemId;
        \SP\Mgmt\User\Groups::$groupName = $frmGrpName;
        \SP\Mgmt\User\Groups::$groupDescription = $frmGrpDesc;

        if (\SP\Mgmt\User\Groups::checkGroupExist()) {
            \SP\Http\Response::printJSON(_('Nombre de grupo duplicado'), 2);
        }

        if ($actionId === \SP\Core\ActionsInterface::ACTION_USR_GROUPS_NEW) {
            if (\SP\Mgmt\User\Groups::addGroup($frmGrpUsers)) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\Mgmt\CustomFields($id, \SP\Mgmt\User\Groups::$queryLastId, $value);
                        $CustomFields->addCustomField();
                    }
                }

                \SP\Http\Response::printJSON(_('Grupo creado'), 0, $doActionOnClose);
            } else {
                \SP\Http\Response::printJSON(_('Error al crear el grupo'));
            }
        } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_USR_GROUPS_EDIT) {
            if (\SP\Mgmt\User\Groups::updateGroup($frmGrpUsers)) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\Mgmt\CustomFields($id, $itemId, $value);
                        $CustomFields->updateCustomField();
                    }
                }

                \SP\Http\Response::printJSON(_('Grupo actualizado'), 0, $doActionOnClose);
            }

            \SP\Http\Response::printJSON(_('Error al actualizar el grupo'));
        }
    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_USR_GROUPS_DELETE) {
        \SP\Mgmt\User\Groups::$groupId = $itemId;

        $resGroupUse = \SP\Mgmt\User\Groups::checkGroupInUse();

        if ($resGroupUse['users'] > 0 || $resGroupUse['accounts'] > 0) {
            if ($resGroupUse['users'] > 0) {
                $uses[] = _('Usuarios') . " (" . $resGroupUse['users'] . ")";
            }

            if ($resGroupUse['accounts'] > 0) {
                $uses[] = _('Cuentas') . " (" . $resGroupUse['accounts'] . ")";
            }

            \SP\Http\Response::printJSON(_('No es posible eliminar') . ';;' . _('Grupo en uso por:') . ';;' . implode(';;', $uses));
        } else {
            $groupName = \SP\Mgmt\User\Groups::getGroupNameById($itemId);

            if (\SP\Mgmt\User\Groups::deleteGroup() && \SP\Mgmt\CustomFields::deleteCustomFieldForItem($itemId, \SP\Core\ActionsInterface::ACTION_USR_GROUPS)) {
                \SP\Http\Response::printJSON(_('Grupo eliminado'), 0, $doActionOnClose);
            }

            \SP\Http\Response::printJSON(_('Error al eliminar el grupo'));
        }
    }
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_USR_PROFILES_NEW
    || $actionId === \SP\Core\ActionsInterface::ACTION_USR_PROFILES_EDIT
    || $actionId === \SP\Core\ActionsInterface::ACTION_USR_PROFILES_DELETE
) {
    $Profile = new \SP\Mgmt\User\Profile();

    // Variables POST del formulario
    $name = \SP\Http\Request::analyze('profile_name');

    $Profile->setName($name);
    $Profile->setId(\SP\Http\Request::analyze('itemId', 0));
    $Profile->setAccAdd(\SP\Http\Request::analyze('profile_accadd', 0, false, 1));
    $Profile->setAccView(\SP\Http\Request::analyze('profile_accview', 0, false, 1));
    $Profile->setAccViewPass(\SP\Http\Request::analyze('profile_accviewpass', 0, false, 1));
    $Profile->setAccViewHistory(\SP\Http\Request::analyze('profile_accviewhistory', 0, false, 1));
    $Profile->setAccEdit(\SP\Http\Request::analyze('profile_accedit', 0, false, 1));
    $Profile->setAccEditPass(\SP\Http\Request::analyze('profile_acceditpass', 0, false, 1));
    $Profile->setAccDelete(\SP\Http\Request::analyze('profile_accdel', 0, false, 1));
    $Profile->setAccFiles(\SP\Http\Request::analyze('profile_accfiles', 0, false, 1));
    $Profile->setAccPublicLinks(\SP\Http\Request::analyze('profile_accpublinks', 0, false, 1));
    $Profile->setConfigGeneral(\SP\Http\Request::analyze('profile_config', 0, false, 1));
    $Profile->setConfigEncryption(\SP\Http\Request::analyze('profile_configmpw', 0, false, 1));
    $Profile->setConfigBackup(\SP\Http\Request::analyze('profile_configback', 0, false, 1));
    $Profile->setConfigImport(\SP\Http\Request::analyze('profile_configimport', 0, false, 1));
    $Profile->setMgmCategories(\SP\Http\Request::analyze('profile_categories', 0, false, 1));
    $Profile->setMgmCustomers(\SP\Http\Request::analyze('profile_customers', 0, false, 1));
    $Profile->setMgmCustomFields(\SP\Http\Request::analyze('profile_customfields', 0, false, 1));
    $Profile->setMgmUsers(\SP\Http\Request::analyze('profile_users', 0, false, 1));
    $Profile->setMgmGroups(\SP\Http\Request::analyze('profile_groups', 0, false, 1));
    $Profile->setMgmProfiles(\SP\Http\Request::analyze('profile_profiles', 0, false, 1));
    $Profile->setMgmApiTokens(\SP\Http\Request::analyze('profile_apitokens', 0, false, 1));
    $Profile->setMgmPublicLinks(\SP\Http\Request::analyze('profile_publinks', 0, false, 1));
    $Profile->setEvl(\SP\Http\Request::analyze('profile_eventlog', 0, false, 1));

    if ($actionId === \SP\Core\ActionsInterface::ACTION_USR_PROFILES_NEW
        || $actionId === \SP\Core\ActionsInterface::ACTION_USR_PROFILES_EDIT
    ) {
        if (!$Profile->getName()) {
            \SP\Http\Response::printJSON(_('Es necesario un nombre de perfil'), 2);
        } elseif (\SP\Mgmt\User\Profile::checkProfileExist($Profile->getId(), $Profile->getName())) {
            \SP\Http\Response::printJSON(_('Nombre de perfil duplicado'), 2);
        }

        if ($actionId === \SP\Core\ActionsInterface::ACTION_USR_PROFILES_NEW) {
            if ($Profile->profileAdd()) {
                \SP\Http\Response::printJSON(_('Perfil creado'), 0, $doActionOnClose);
            }

            \SP\Http\Response::printJSON(_('Error al crear el perfil'));
        } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_USR_PROFILES_EDIT) {
            if ($Profile->profileUpdate()) {
                \SP\Http\Response::printJSON(_('Perfil actualizado'), 0, $doActionOnClose);
            }

            \SP\Http\Response::printJSON(_('Error al actualizar el perfil'));
        }

    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_USR_PROFILES_DELETE) {
        $resProfileUse = \SP\Mgmt\User\Profile::checkProfileInUse($Profile->getId());

        if ($resProfileUse['users'] > 0) {
            $uses[] = _('Usuarios') . " (" . $resProfileUse['users'] . ")";

            \SP\Http\Response::printJSON(_('No es posible eliminar') . ';;' . _('Perfil en uso por:') . ';;' . implode(';;', $uses));
        } else {
            if ($Profile->profileDelete()) {
                \SP\Http\Response::printJSON(_('Perfil eliminado'), 0, $doActionOnClose);
            }

            \SP\Http\Response::printJSON(_('Error al eliminar el perfil'));
        }
    }
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE
) {
    // Variables POST del formulario
    $frmCustomerName = \SP\Http\Request::analyze('name');
    $frmCustomerDesc = \SP\Http\Request::analyze('description');

    if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
        || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    ) {
        if (!$frmCustomerName) {
            \SP\Http\Response::printJSON(_('Es necesario un nombre de cliente'), 2);
        }

        \SP\Mgmt\Customer::$customerName = $frmCustomerName;
        \SP\Mgmt\Customer::$customerDescription = $frmCustomerDesc;

        if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_NEW) {
            try {
                \SP\Mgmt\Customer::addCustomer($itemId);

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\Mgmt\CustomFields($id, \SP\Mgmt\Customer::$customerLastId, $value);
                        $CustomFields->addCustomField();
                    }
                }
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage(), 2);
            }

            \SP\Http\Response::printJSON(_('Cliente creado'), 0, $doActionOnClose);
        } else if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT) {
            try {
                \SP\Mgmt\Customer::updateCustomer($itemId);

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\Mgmt\CustomFields($id, $itemId, $value);
                        $CustomFields->updateCustomField();
                    }
                }
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage(), 2);
            }

            \SP\Http\Response::printJSON(_('Cliente actualizado'), 0, $doActionOnClose);
        }
    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE) {
        try {
            \SP\Mgmt\Customer::deleteCustomer($itemId);
            \SP\Mgmt\CustomFields::deleteCustomFieldForItem($itemId, \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMERS);
        } catch (\SP\Core\SPException $e) {
            \SP\Http\Response::printJSON($e->getMessage());
        }

        \SP\Http\Response::printJSON(_('Cliente eliminado'), 0, $doActionOnClose);
    }
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_NEW
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_DELETE
) {
    // Variables POST del formulario
    $frmCategoryName = \SP\Http\Request::analyze('name');
    $frmCategoryDesc = \SP\Http\Request::analyze('description');

    if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_NEW
        || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    ) {
        if (!$frmCategoryName) {
            \SP\Http\Response::printJSON(_('Es necesario un nombre de categoría'), 2);
        }

        \SP\Mgmt\Category::$categoryName = $frmCategoryName;
        \SP\Mgmt\Category::$categoryDescription = $frmCategoryDesc;

        if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_NEW) {
            try {
                \SP\Mgmt\Category::addCategory();

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\Mgmt\CustomFields($id, \SP\Mgmt\Category::$categoryLastId, $value);
                        $CustomFields->addCustomField();
                    }
                }
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage(), 2);
            }

            \SP\Http\Response::printJSON(_('Categoría creada'), 0, $doActionOnClose);
        } else if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_EDIT) {
            try {
                \SP\Mgmt\Category::updateCategory($itemId);

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new \SP\Mgmt\CustomFields($id, $itemId, $value);
                        $CustomFields->updateCustomField();
                    }
                }
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage(), 2);
            }

            \SP\Http\Response::printJSON(_('Categoría actualizada'), 0, $doActionOnClose);
        }

    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES_DELETE) {
        try {
            \SP\Mgmt\Category::deleteCategory($itemId);
            \SP\Mgmt\CustomFields::deleteCustomFieldForItem($itemId, \SP\Core\ActionsInterface::ACTION_MGM_CATEGORIES);
        } catch (\SP\Core\SPException $e) {
            \SP\Http\Response::printJSON($e->getMessage());
        }

        \SP\Http\Response::printJSON(_('Categoría eliminada'), 0, $doActionOnClose);
    }
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_APITOKENS_NEW
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_APITOKENS_EDIT
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_APITOKENS_DELETE
) {
    $ApiTokens = new \SP\Api\ApiTokens();
    $ApiTokens->setTokenId($itemId);
    $ApiTokens->setUserId(\SP\Http\Request::analyze('users', 0));
    $ApiTokens->setActionId(\SP\Http\Request::analyze('actions', 0));
    $ApiTokens->setRefreshToken(\SP\Http\Request::analyze('refreshtoken', false, false, true));

    if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_APITOKENS_NEW
        || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_APITOKENS_EDIT
    ) {
        if ($ApiTokens->getUserId() === 0 || $ApiTokens->getActionId() === 0) {
            \SP\Http\Response::printJSON(_('Usuario o acción no indicado'), 2);
        }

        if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_APITOKENS_NEW) {
            try {
                $ApiTokens->addToken();
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage(), 2);
            }

            \SP\Http\Response::printJSON(_('Autorización creada'), 0, $doActionOnClose);
        } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_APITOKENS_EDIT) {
            try {
                $ApiTokens->updateToken();
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage(), 2);
            }

            \SP\Http\Response::printJSON(_('Autorización actualizada'), 0, $doActionOnClose);
        }

    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_APITOKENS_DELETE) {
        try {
            $ApiTokens->deleteToken();
        } catch (\SP\Core\SPException $e) {
            \SP\Http\Response::printJSON($e->getMessage(), 2);
        }

        \SP\Http\Response::printJSON(_('Autorización eliminada'), 0, $doActionOnClose);
    }
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE
) {
    // Variables POST del formulario
    $frmFieldName = \SP\Http\Request::analyze('name');
    $frmFieldType = \SP\Http\Request::analyze('type', 0);
    $frmFieldModule = \SP\Http\Request::analyze('module', 0);
    $frmFieldHelp = \SP\Http\Request::analyze('help');
    $frmFieldRequired = \SP\Http\Request::analyze('required', false, false, true);

    if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW
        || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT
    ) {
        if (!$frmFieldName) {
            \SP\Http\Response::printJSON(_('Nombre del campo no indicado'), 2);
        } elseif ($frmFieldType === 0) {
            \SP\Http\Response::printJSON(_('Tipo del campo no indicado'), 2);
        } elseif ($frmFieldModule === 0) {
            \SP\Http\Response::printJSON(_('Módulo del campo no indicado'), 2);
        }

        $CustomFieldDef = new \SP\Mgmt\CustomFieldDef($frmFieldName, $frmFieldType, $frmFieldModule);
        $CustomFieldDef->setHelp($frmFieldHelp);
        $CustomFieldDef->setRequired($frmFieldRequired);

        if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW) {
            try {
                $CustomFieldDef->addCustomField();
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage(), 2);
            }

            \SP\Http\Response::printJSON(_('Campo creado'), 0, $doActionOnClose);
        } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT) {
            try {
                $CustomFieldDef->setId($itemId);
                $CustomFieldDef->updateCustomField();
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage(), 2);
            }

            \SP\Http\Response::printJSON(_('Campo actualizado'), 0, $doActionOnClose);
        }

    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE) {
        try {
            \SP\Mgmt\CustomFieldDef::deleteCustomField($itemId);
        } catch (\SP\Core\SPException $e) {
            \SP\Http\Response::printJSON($e->getMessage(), 2);
        }

        \SP\Http\Response::printJSON(_('Campo eliminado'), 0, $doActionOnClose);
    }
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_DELETE
    || $actionId === \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH
) {
    if ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW) {
        $frmFieldNotify = \SP\Http\Request::analyze('notify', false, false, true);
        $doActionOnClose = "sysPassUtil.Common.doAction(" . \SP\Core\ActionsInterface::ACTION_ACC_VIEW . ",'',$itemId);";

        try {
            $PublicLink = new \SP\Mgmt\PublicLink($itemId, \SP\Mgmt\PublicLink::TYPE_ACCOUNT);
            $PublicLink->setNotify($frmFieldNotify);
            $PublicLink->newLink();
        } catch (\SP\Core\SPException $e) {
            \SP\Http\Response::printJSON($e->getMessage());
        }

        \SP\Http\Response::printJSON(_('Enlace creado'), 0, $doActionOnClose);
    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_DELETE) {
        try {
            $PublicLink = new \SP\Mgmt\PublicLink($itemId, \SP\Mgmt\PublicLink::TYPE_ACCOUNT);
            $PublicLink->deleteLink();
        } catch (\SP\Core\SPException $e) {
            \SP\Http\Response::printJSON($e->getMessage());
        }

        \SP\Http\Response::printJSON(_('Enlace eliminado'), 0, $doActionOnClose);
    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH) {
        try {
            $PublicLink = \SP\Mgmt\PublicLink::getLinkById($itemId);
            $PublicLink->refreshLink();
        } catch (\SP\Core\SPException $e) {
            \SP\Http\Response::printJSON($e->getMessage());
        }

        \SP\Http\Response::printJSON(_('Enlace actualizado'), 0, $doActionOnClose);
    }
} else {
    \SP\Http\Response::printJSON(_('Acción Inválida'));
}