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

use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\Core\SPException;
use SP\Http\Request;
use SP\Core\SessionUtil;
use SP\Http\Response;
use SP\Mgmt\Category;
use SP\Mgmt\Customer;
use SP\Mgmt\CustomFieldDef;
use SP\Mgmt\CustomFields;
use SP\Mgmt\Files;
use SP\Mgmt\PublicLink;
use SP\Mgmt\User\Groups;
use SP\Mgmt\User\Profile;
use SP\Mgmt\User\UserUtil;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!\SP\Core\Init::isLoggedIn()) {
    Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJSON(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$actionId = Request::analyze('actionId', 0);
$itemId = Request::analyze('itemId', 0);
$onCloseAction = Request::analyze('onCloseAction');
$activeTab = Request::analyze('activeTab', 0);
$customFields = Request::analyze('customfield');

// Acción al cerrar la vista
$doActionOnClose = "sysPassUtil.Common.doAction('$onCloseAction','',$activeTab);";

$userLogin = UserUtil::getUserLoginById($itemId);

if ($actionId === ActionsInterface::ACTION_USR_USERS_NEW
    || $actionId === ActionsInterface::ACTION_USR_USERS_EDIT
    || $actionId === ActionsInterface::ACTION_USR_USERS_EDITPASS
    || $actionId === ActionsInterface::ACTION_USR_USERS_DELETE
) {
    $isLdap = Request::analyze('isLdap', 0);
    $userPassR = Request::analyzeEncrypted('passR');

    $User = new \SP\Mgmt\User\User();
    $User->setUserId($itemId);
    $User->setUserName(Request::analyze('name'));
    $User->setUserLogin(Request::analyze('login'));
    $User->setUserEmail(Request::analyze('email'));
    $User->setUserNotes(Request::analyze('notes'));
    $User->setUserGroupId(Request::analyze('groupid', 0));
    $User->setUserProfileId(Request::analyze('profileid', 0));
    $User->setUserIsAdminApp(Request::analyze('adminapp', 0, false, 1));
    $User->setUserIsAdminAcc(Request::analyze('adminacc', 0, false, 1));
    $User->setUserIsDisabled(Request::analyze('disabled', 0, false, 1));
    $User->setUserChangePass(Request::analyze('changepass', 0, false, 1));
    $User->setUserPass(Request::analyzeEncrypted('pass'));

    // Nuevo usuario o editar
    if ($actionId === ActionsInterface::ACTION_USR_USERS_NEW
        || $actionId === ActionsInterface::ACTION_USR_USERS_EDIT
    ) {
        if (!$User->getUserName() && !$isLdap) {
            Response::printJSON(_('Es necesario un nombre de usuario'), 2);
        } elseif (!$User->getUserLogin() && !$isLdap) {
            Response::printJSON(_('Es necesario un login'), 2);
        } elseif (!$User->getUserProfileId()) {
            Response::printJSON(_('Es necesario un perfil'), 2);
        } elseif (!$User->getUserGroupId()) {
            Response::printJSON(_('Es necesario un grupo'), 2);
        } elseif (!$User->getUserEmail() && !$isLdap) {
            Response::printJSON(_('Es necesario un email'), 2);
        } elseif (Checks::demoIsEnabled() && !Session::getUserIsAdminApp() && $User->getUserLogin() == 'demo') {
            Response::printJSON(_('Ey, esto es una DEMO!!'));
        }

        switch ($User->checkUserExist()) {
            case UserUtil::USER_LOGIN_EXIST:
                Response::printJSON(_('Login de usuario duplicado'), 2);
                break;
            case UserUtil::USER_MAIL_EXIST:
                Response::printJSON(_('Email de usuario duplicado'), 2);
                break;
        }

        if ($actionId === ActionsInterface::ACTION_USR_USERS_NEW) {
            if (!$User->getUserPass() || !$userPassR) {
                Response::printJSON(_('La clave no puede estar en blanco'), 2);
            } elseif ($User->getUserPass() != $userPassR) {
                Response::printJSON(_('Las claves no coinciden'), 2);
            }

            if ($User->addUser()) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new CustomFields($id, $User->getUserId(), $value);
                        $CustomFields->addCustomField();
                    }
                }

                Response::printJSON(_('Usuario creado'), 0, $doActionOnClose);
            }

            Response::printJSON(_('Error al crear el usuario'));
        } elseif ($actionId === ActionsInterface::ACTION_USR_USERS_EDIT) {
            if ($User->updateUser()) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new CustomFields($id, $User->getUserId(), $value);
                        $CustomFields->updateCustomField();
                    }
                }

                Response::printJSON(_('Usuario actualizado'), 0, $doActionOnClose);
            }

            Response::printJSON(_('Error al actualizar el usuario'));
        }
    } elseif ($actionId === ActionsInterface::ACTION_USR_USERS_EDITPASS) {


        if (Checks::demoIsEnabled() && UserUtil::getUserLoginById($itemId) == 'demo') {
            Response::printJSON(_('Ey, esto es una DEMO!!'));
        } elseif (!$User->getUserPass() || !$userPassR) {
            Response::printJSON(_('La clave no puede estar en blanco'), 2);
        } elseif ($User->getUserPass() != $userPassR) {
            Response::printJSON(_('Las claves no coinciden'), 2);
        }

        if ($User->updateUserPass()) {
            Response::printJSON(_('Clave actualizada'), 0);
        }

        Response::printJSON(_('Error al modificar la clave'));
        // Eliminar usuario
    } elseif ($actionId === ActionsInterface::ACTION_USR_USERS_DELETE) {
        if (Checks::demoIsEnabled() && UserUtil::getUserLoginById($itemId) == 'demo') {
            Response::printJSON(_('Ey, esto es una DEMO!!'));
        } elseif ($User->getUserId() == Session::getUserId()) {
            Response::printJSON(_('No es posible eliminar, usuario en uso'));
        }

        if ($User->deleteUser() && CustomFields::deleteCustomFieldForItem($User->getUserId(), ActionsInterface::ACTION_USR_USERS)) {
            Response::printJSON(_('Usuario eliminado'), 0, $doActionOnClose);
        }

        Response::printJSON(_('Error al eliminar el usuario'));
    }
} elseif ($actionId === ActionsInterface::ACTION_USR_GROUPS_NEW
    || $actionId === ActionsInterface::ACTION_USR_GROUPS_EDIT
    || $actionId === ActionsInterface::ACTION_USR_GROUPS_DELETE
) {
    // Variables POST del formulario
    $frmGrpName = Request::analyze('name');
    $frmGrpDesc = Request::analyze('description');
    $frmGrpUsers = Request::analyze('users');

    if ($actionId === ActionsInterface::ACTION_USR_GROUPS_NEW
        || $actionId === ActionsInterface::ACTION_USR_GROUPS_EDIT
    ) {
        if (!$frmGrpName) {
            Response::printJSON(_('Es necesario un nombre de grupo'), 2);
        }

        Groups::$groupId = $itemId;
        Groups::$groupName = $frmGrpName;
        Groups::$groupDescription = $frmGrpDesc;

        if (Groups::checkGroupExist()) {
            Response::printJSON(_('Nombre de grupo duplicado'), 2);
        }

        if ($actionId === ActionsInterface::ACTION_USR_GROUPS_NEW) {
            if (Groups::addGroup($frmGrpUsers)) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new CustomFields($id, Groups::$queryLastId, $value);
                        $CustomFields->addCustomField();
                    }
                }

                Response::printJSON(_('Grupo creado'), 0, $doActionOnClose);
            } else {
                Response::printJSON(_('Error al crear el grupo'));
            }
        } elseif ($actionId === ActionsInterface::ACTION_USR_GROUPS_EDIT) {
            if (Groups::updateGroup($frmGrpUsers)) {
                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new CustomFields($id, $itemId, $value);
                        $CustomFields->updateCustomField();
                    }
                }

                Response::printJSON(_('Grupo actualizado'), 0, $doActionOnClose);
            }

            Response::printJSON(_('Error al actualizar el grupo'));
        }
    } elseif ($actionId === ActionsInterface::ACTION_USR_GROUPS_DELETE) {
        Groups::$groupId = $itemId;

        $resGroupUse = Groups::checkGroupInUse();

        if ($resGroupUse['users'] > 0 || $resGroupUse['accounts'] > 0) {
            if ($resGroupUse['users'] > 0) {
                $uses[] = _('Usuarios') . " (" . $resGroupUse['users'] . ")";
            }

            if ($resGroupUse['accounts'] > 0) {
                $uses[] = _('Cuentas') . " (" . $resGroupUse['accounts'] . ")";
            }

            Response::printJSON(_('No es posible eliminar') . ';;' . _('Grupo en uso por:') . ';;' . implode(';;', $uses));
        } else {
            $groupName = Groups::getGroupNameById($itemId);

            if (Groups::deleteGroup() && CustomFields::deleteCustomFieldForItem($itemId, ActionsInterface::ACTION_USR_GROUPS)) {
                Response::printJSON(_('Grupo eliminado'), 0, $doActionOnClose);
            }

            Response::printJSON(_('Error al eliminar el grupo'));
        }
    }
} elseif ($actionId === ActionsInterface::ACTION_USR_PROFILES_NEW
    || $actionId === ActionsInterface::ACTION_USR_PROFILES_EDIT
    || $actionId === ActionsInterface::ACTION_USR_PROFILES_DELETE
) {
    $Profile = new Profile();

    // Variables POST del formulario
    $name = Request::analyze('profile_name');

    $Profile->setName($name);
    $Profile->setId(Request::analyze('itemId', 0));
    $Profile->setAccAdd(Request::analyze('profile_accadd', 0, false, 1));
    $Profile->setAccView(Request::analyze('profile_accview', 0, false, 1));
    $Profile->setAccViewPass(Request::analyze('profile_accviewpass', 0, false, 1));
    $Profile->setAccViewHistory(Request::analyze('profile_accviewhistory', 0, false, 1));
    $Profile->setAccEdit(Request::analyze('profile_accedit', 0, false, 1));
    $Profile->setAccEditPass(Request::analyze('profile_acceditpass', 0, false, 1));
    $Profile->setAccDelete(Request::analyze('profile_accdel', 0, false, 1));
    $Profile->setAccFiles(Request::analyze('profile_accfiles', 0, false, 1));
    $Profile->setAccPublicLinks(Request::analyze('profile_accpublinks', 0, false, 1));
    $Profile->setConfigGeneral(Request::analyze('profile_config', 0, false, 1));
    $Profile->setConfigEncryption(Request::analyze('profile_configmpw', 0, false, 1));
    $Profile->setConfigBackup(Request::analyze('profile_configback', 0, false, 1));
    $Profile->setConfigImport(Request::analyze('profile_configimport', 0, false, 1));
    $Profile->setMgmCategories(Request::analyze('profile_categories', 0, false, 1));
    $Profile->setMgmCustomers(Request::analyze('profile_customers', 0, false, 1));
    $Profile->setMgmCustomFields(Request::analyze('profile_customfields', 0, false, 1));
    $Profile->setMgmUsers(Request::analyze('profile_users', 0, false, 1));
    $Profile->setMgmGroups(Request::analyze('profile_groups', 0, false, 1));
    $Profile->setMgmProfiles(Request::analyze('profile_profiles', 0, false, 1));
    $Profile->setMgmApiTokens(Request::analyze('profile_apitokens', 0, false, 1));
    $Profile->setMgmPublicLinks(Request::analyze('profile_publinks', 0, false, 1));
    $Profile->setEvl(Request::analyze('profile_eventlog', 0, false, 1));

    if ($actionId === ActionsInterface::ACTION_USR_PROFILES_NEW
        || $actionId === ActionsInterface::ACTION_USR_PROFILES_EDIT
    ) {
        if (!$Profile->getName()) {
            Response::printJSON(_('Es necesario un nombre de perfil'), 2);
        } elseif (Profile::checkProfileExist($Profile->getId(), $Profile->getName())) {
            Response::printJSON(_('Nombre de perfil duplicado'), 2);
        }

        if ($actionId === ActionsInterface::ACTION_USR_PROFILES_NEW) {
            if ($Profile->profileAdd()) {
                Response::printJSON(_('Perfil creado'), 0, $doActionOnClose);
            }

            Response::printJSON(_('Error al crear el perfil'));
        } elseif ($actionId === ActionsInterface::ACTION_USR_PROFILES_EDIT) {
            if ($Profile->profileUpdate()) {
                Response::printJSON(_('Perfil actualizado'), 0, $doActionOnClose);
            }

            Response::printJSON(_('Error al actualizar el perfil'));
        }

    } elseif ($actionId === ActionsInterface::ACTION_USR_PROFILES_DELETE) {
        $resProfileUse = Profile::checkProfileInUse($Profile->getId());

        if ($resProfileUse['users'] > 0) {
            $uses[] = _('Usuarios') . " (" . $resProfileUse['users'] . ")";

            Response::printJSON(_('No es posible eliminar') . ';;' . _('Perfil en uso por:') . ';;' . implode(';;', $uses));
        } else {
            if ($Profile->profileDelete()) {
                Response::printJSON(_('Perfil eliminado'), 0, $doActionOnClose);
            }

            Response::printJSON(_('Error al eliminar el perfil'));
        }
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
    || $actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    || $actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE
) {
    // Variables POST del formulario
    $frmCustomerName = Request::analyze('name');
    $frmCustomerDesc = Request::analyze('description');

    if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
        || $actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    ) {
        if (!$frmCustomerName) {
            Response::printJSON(_('Es necesario un nombre de cliente'), 2);
        }

        Customer::$customerName = $frmCustomerName;
        Customer::$customerDescription = $frmCustomerDesc;

        if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_NEW) {
            try {
                Customer::addCustomer($itemId);

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new CustomFields($id, Customer::$customerLastId, $value);
                        $CustomFields->addCustomField();
                    }
                }
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Cliente creado'), 0, $doActionOnClose);
        } else if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT) {
            try {
                Customer::updateCustomer($itemId);

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new CustomFields($id, $itemId, $value);
                        $CustomFields->updateCustomField();
                    }
                }
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Cliente actualizado'), 0, $doActionOnClose);
        }
    } elseif ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE) {
        try {
            Customer::deleteCustomer($itemId);
            CustomFields::deleteCustomFieldForItem($itemId, ActionsInterface::ACTION_MGM_CUSTOMERS);
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Cliente eliminado'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_NEW
    || $actionId === ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    || $actionId === ActionsInterface::ACTION_MGM_CATEGORIES_DELETE
) {
    // Variables POST del formulario
    $frmCategoryName = Request::analyze('name');
    $frmCategoryDesc = Request::analyze('description');

    if ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_NEW
        || $actionId === ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    ) {
        if (!$frmCategoryName) {
            Response::printJSON(_('Es necesario un nombre de categoría'), 2);
        }

        Category::$categoryName = $frmCategoryName;
        Category::$categoryDescription = $frmCategoryDesc;

        if ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_NEW) {
            try {
                Category::addCategory();

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new CustomFields($id, Category::$categoryLastId, $value);
                        $CustomFields->addCustomField();
                    }
                }
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Categoría creada'), 0, $doActionOnClose);
        } else if ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_EDIT) {
            try {
                Category::updateCategory($itemId);

                if (is_array($customFields)) {
                    foreach ($customFields as $id => $value) {
                        $CustomFields = new CustomFields($id, $itemId, $value);
                        $CustomFields->updateCustomField();
                    }
                }
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Categoría actualizada'), 0, $doActionOnClose);
        }

    } elseif ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_DELETE) {
        try {
            Category::deleteCategory($itemId);
            CustomFields::deleteCustomFieldForItem($itemId, ActionsInterface::ACTION_MGM_CATEGORIES);
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Categoría eliminada'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_APITOKENS_NEW
    || $actionId === ActionsInterface::ACTION_MGM_APITOKENS_EDIT
    || $actionId === ActionsInterface::ACTION_MGM_APITOKENS_DELETE
) {
    $ApiTokens = new \SP\Api\ApiTokens();
    $ApiTokens->setTokenId($itemId);
    $ApiTokens->setUserId(Request::analyze('users', 0));
    $ApiTokens->setActionId(Request::analyze('actions', 0));
    $ApiTokens->setRefreshToken(Request::analyze('refreshtoken', false, false, true));

    if ($actionId === ActionsInterface::ACTION_MGM_APITOKENS_NEW
        || $actionId === ActionsInterface::ACTION_MGM_APITOKENS_EDIT
    ) {
        if ($ApiTokens->getUserId() === 0 || $ApiTokens->getActionId() === 0) {
            Response::printJSON(_('Usuario o acción no indicado'), 2);
        }

        if ($actionId === ActionsInterface::ACTION_MGM_APITOKENS_NEW) {
            try {
                $ApiTokens->addToken();
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Autorización creada'), 0, $doActionOnClose);
        } elseif ($actionId === ActionsInterface::ACTION_MGM_APITOKENS_EDIT) {
            try {
                $ApiTokens->updateToken();
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Autorización actualizada'), 0, $doActionOnClose);
        }

    } elseif ($actionId === ActionsInterface::ACTION_MGM_APITOKENS_DELETE) {
        try {
            $ApiTokens->deleteToken();
        } catch (SPException $e) {
            Response::printJSON($e->getMessage(), 2);
        }

        Response::printJSON(_('Autorización eliminada'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW
    || $actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT
    || $actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE
) {
    // Variables POST del formulario
    $frmFieldName = Request::analyze('name');
    $frmFieldType = Request::analyze('type', 0);
    $frmFieldModule = Request::analyze('module', 0);
    $frmFieldHelp = Request::analyze('help');
    $frmFieldRequired = Request::analyze('required', false, false, true);

    if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW
        || $actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT
    ) {
        if (!$frmFieldName) {
            Response::printJSON(_('Nombre del campo no indicado'), 2);
        } elseif ($frmFieldType === 0) {
            Response::printJSON(_('Tipo del campo no indicado'), 2);
        } elseif ($frmFieldModule === 0) {
            Response::printJSON(_('Módulo del campo no indicado'), 2);
        }

        $CustomFieldDef = new CustomFieldDef($frmFieldName, $frmFieldType, $frmFieldModule);
        $CustomFieldDef->setHelp($frmFieldHelp);
        $CustomFieldDef->setRequired($frmFieldRequired);

        if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW) {
            try {
                $CustomFieldDef->addCustomField();
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Campo creado'), 0, $doActionOnClose);
        } elseif ($actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT) {
            try {
                $CustomFieldDef->setId($itemId);
                $CustomFieldDef->updateCustomField();
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Campo actualizado'), 0, $doActionOnClose);
        }

    } elseif ($actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE) {
        try {
            CustomFieldDef::deleteCustomField($itemId);
        } catch (SPException $e) {
            Response::printJSON($e->getMessage(), 2);
        }

        Response::printJSON(_('Campo eliminado'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW
    || $actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_DELETE
    || $actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH
) {
    if ($actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW) {
        $frmFieldNotify = Request::analyze('notify', false, false, true);
        $doActionOnClose = "sysPassUtil.Common.doAction(" . ActionsInterface::ACTION_ACC_VIEW . ",'',$itemId);";

        try {
            $PublicLink = new PublicLink($itemId, PublicLink::TYPE_ACCOUNT);
            $PublicLink->setNotify($frmFieldNotify);
            $PublicLink->newLink();
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Enlace creado'), 0, $doActionOnClose);
    } elseif ($actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_DELETE) {
        try {
            $PublicLink = new PublicLink($itemId, PublicLink::TYPE_ACCOUNT);
            $PublicLink->deleteLink();
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Enlace eliminado'), 0, $doActionOnClose);
    } elseif ($actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH) {
        try {
            $PublicLink = PublicLink::getLinkById($itemId);
            $PublicLink->refreshLink();
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Enlace actualizado'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_FILES_DELETE) {
    // Verificamos que el ID sea numérico
    if ($itemId === 0) {
        Response::printJSON(_('No es un ID de archivo válido'));
    } elseif (Files::fileDelete($itemId)) {
        Response::printJSON(_('Archivo eliminado'), 0, $doActionOnClose);
    }

    Response::printJSON(_('Error al eliminar el archivo'));
} elseif ($actionId === ActionsInterface::ACTION_MGM_ACCOUNTS_DELETE) {
    $Account = new \SP\Account\Account();
    $Account->setAccountId($itemId);

    // Eliminar cuenta
    if ($Account->deleteAccount()
        && CustomFields::deleteCustomFieldForItem($itemId, ActionsInterface::ACTION_ACC_NEW)
    ) {
        Response::printJSON(_('Cuenta eliminada'), 0, $doActionOnClose);
    }

    Response::printJSON(_('Error al eliminar la cuenta'));
} else {
    Response::printJSON(_('Acción Inválida'));
}