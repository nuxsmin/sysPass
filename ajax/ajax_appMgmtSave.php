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

use SP\Account\Account;
use SP\DataModel\AccountData;
use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;
use SP\DataModel\CustomFieldData;
use SP\DataModel\CustomFieldDefData;
use SP\DataModel\GroupData;
use SP\DataModel\ProfileData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\TagData;
use SP\DataModel\UserData;
use SP\Http\Request;
use SP\Core\SessionUtil;
use SP\Http\Response;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\CustomFields\CustomFieldDef;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\CustomFields\CustomFieldsUtil;
use SP\Mgmt\Files\File;
use SP\Mgmt\PublicLinks\PublicLink;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Tags\Tag;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserUtil;
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

    $UserData = new UserData();
    $UserData->setUserId($itemId);
    $UserData->setUserName(Request::analyze('name'));
    $UserData->setUserLogin(Request::analyze('login'));
    $UserData->setUserEmail(Request::analyze('email'));
    $UserData->setUserNotes(Request::analyze('notes'));
    $UserData->setUserGroupId(Request::analyze('groupid', 0));
    $UserData->setUserProfileId(Request::analyze('profileid', 0));
    $UserData->setUserIsAdminApp(Request::analyze('adminapp', false, false, true));
    $UserData->setUserIsAdminAcc(Request::analyze('adminacc', false, false, true));
    $UserData->setUserIsDisabled(Request::analyze('disabled', false, false, true));
    $UserData->setUserIsChangePass(Request::analyze('changepass', false, false, true));
    $UserData->setUserPass(Request::analyzeEncrypted('pass'));

    // Nuevo usuario o editar
    if ($actionId === ActionsInterface::ACTION_USR_USERS_NEW
        || $actionId === ActionsInterface::ACTION_USR_USERS_EDIT
    ) {
        if (!$UserData->getUserName() && !$isLdap) {
            Response::printJSON(_('Es necesario un nombre de usuario'), 2);
        } elseif (!$UserData->getUserLogin() && !$isLdap) {
            Response::printJSON(_('Es necesario un login'), 2);
        } elseif (!$UserData->getUserProfileId()) {
            Response::printJSON(_('Es necesario un perfil'), 2);
        } elseif (!$UserData->getUserGroupId()) {
            Response::printJSON(_('Es necesario un grupo'), 2);
        } elseif (!$UserData->getUserEmail() && !$isLdap) {
            Response::printJSON(_('Es necesario un email'), 2);
        } elseif (Checks::demoIsEnabled() && !Session::getUserIsAdminApp() && $UserData->getUserLogin() == 'demo') {
            Response::printJSON(_('Ey, esto es una DEMO!!'));
        }

        $CustomFieldData = new CustomFieldData();
        $CustomFieldData->setId($itemId);
        $CustomFieldData->setModule(ActionsInterface::ACTION_USR_USERS);

        if ($actionId === ActionsInterface::ACTION_USR_USERS_NEW) {
            if (!$UserData->getUserPass() || !$userPassR) {
                Response::printJSON(_('La clave no puede estar en blanco'), 2);
            } elseif ($UserData->getUserPass() != $userPassR) {
                Response::printJSON(_('Las claves no coinciden'), 2);
            }

            try {
                User::getItem($UserData)->add();

                if (is_array($customFields)) {
                    $CustomFieldData->setId($UserData->getUserId());
                    CustomFieldsUtil::addItemCustomFields($customFields, $CustomFieldData);
                }
            } catch (SPException $e){
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Usuario creado'), 0, $doActionOnClose);
        } elseif ($actionId === ActionsInterface::ACTION_USR_USERS_EDIT) {
            try {
                User::getItem($UserData)->update();

                if (is_array($customFields)) {
                    $CustomFieldData->setId($UserData->getUserId());
                    CustomFieldsUtil::addItemCustomFields($customFields, $CustomFieldData);
                }
            } catch (SPException $e){
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Usuario actualizado'), 0, $doActionOnClose);
        }
    } elseif ($actionId === ActionsInterface::ACTION_USR_USERS_EDITPASS) {
        if (Checks::demoIsEnabled() && UserUtil::getUserLoginById($itemId) == 'demo') {
            Response::printJSON(_('Ey, esto es una DEMO!!'));
        } elseif (!$UserData->getUserPass() || !$userPassR) {
            Response::printJSON(_('La clave no puede estar en blanco'), 2);
        } elseif ($UserData->getUserPass() != $userPassR) {
            Response::printJSON(_('Las claves no coinciden'), 2);
        }

        try {
            User::getItem($UserData)->updatePass();
        } catch (SPException $e){
            Response::printJSON($e->getMessage(), 2);
        }

        Response::printJSON(_('Clave actualizada'), 0);

    // Eliminar usuario
    } elseif ($actionId === ActionsInterface::ACTION_USR_USERS_DELETE) {
        if (Checks::demoIsEnabled() && UserUtil::getUserLoginById($itemId) == 'demo') {
            Response::printJSON(_('Ey, esto es una DEMO!!'));
        } elseif ($UserData->getUserId() == Session::getUserId()) {
            Response::printJSON(_('No es posible eliminar, usuario en uso'));
        }

        try {
            User::getItem()->delete($itemId);
            CustomField::getItem($CustomFieldData)->delete($itemId);
        } catch (SPException $e){
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Usuario eliminado'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_USR_GROUPS_NEW
    || $actionId === ActionsInterface::ACTION_USR_GROUPS_EDIT
    || $actionId === ActionsInterface::ACTION_USR_GROUPS_DELETE
) {
    $GroupData = new GroupData();
    $GroupData->setUsergroupId($itemId);
    $GroupData->setUsergroupName(Request::analyze('name'));
    $GroupData->setUsergroupDescription(Request::analyze('description'));
    $GroupData->setUsers(Request::analyze('users', 0));

    if ($actionId === ActionsInterface::ACTION_USR_GROUPS_NEW
        || $actionId === ActionsInterface::ACTION_USR_GROUPS_EDIT
    ) {
        if (!$GroupData->getUsergroupName()) {
            Response::printJSON(_('Es necesario un nombre de grupo'), 2);
        }

        $CustomFieldData = new CustomFieldData();
        $CustomFieldData->setId($itemId);
        $CustomFieldData->setModule(ActionsInterface::ACTION_USR_GROUPS);

        if ($actionId === ActionsInterface::ACTION_USR_GROUPS_NEW) {
            try {
                Group::getItem($GroupData)->add();

                if (is_array($customFields)) {
                    $CustomFieldData->setId($itemId);  //FIXME
                    CustomFieldsUtil::addItemCustomFields($customFields, $CustomFieldData);
                }

                Response::printJSON(_('Grupo creado'), 0, $doActionOnClose);
            } catch (SPException $e) {
                Response::printJSON($e->getMessage());
            }
        } elseif ($actionId === ActionsInterface::ACTION_USR_GROUPS_EDIT) {
            try {
                Group::getItem($GroupData)->update();

                if (is_array($customFields)) {
                    $CustomFieldData->setId($itemId);  //FIXME
                    CustomFieldsUtil::updateItemCustomFields($customFields, $CustomFieldData);
                }

                Response::printJSON(_('Grupo actualizado'), 0, $doActionOnClose);
            } catch (SPException $e) {
                Response::printJSON($e->getMessage());
            }
        }
    } elseif ($actionId === ActionsInterface::ACTION_USR_GROUPS_DELETE) {
        try {
            Group::getItem($GroupData)->delete($itemId);
            CustomField::getItem($CustomFieldData)->delete($itemId);

            Response::printJSON(_('Grupo eliminado'), 0, $doActionOnClose);
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }
    }
} elseif ($actionId === ActionsInterface::ACTION_USR_PROFILES_NEW
    || $actionId === ActionsInterface::ACTION_USR_PROFILES_EDIT
    || $actionId === ActionsInterface::ACTION_USR_PROFILES_DELETE
) {
    if ($actionId === ActionsInterface::ACTION_USR_PROFILES_NEW
        || $actionId === ActionsInterface::ACTION_USR_PROFILES_EDIT
    ) {
        $ProfileData = new ProfileData();
        $ProfileData->setUserprofileName(Request::analyze('profile_name'));
        $ProfileData->setUserprofileId(Request::analyze('itemId', 0));
        $ProfileData->setAccAdd(Request::analyze('profile_accadd', 0, false, 1));
        $ProfileData->setAccView(Request::analyze('profile_accview', 0, false, 1));
        $ProfileData->setAccViewPass(Request::analyze('profile_accviewpass', 0, false, 1));
        $ProfileData->setAccViewHistory(Request::analyze('profile_accviewhistory', 0, false, 1));
        $ProfileData->setAccEdit(Request::analyze('profile_accedit', 0, false, 1));
        $ProfileData->setAccEditPass(Request::analyze('profile_acceditpass', 0, false, 1));
        $ProfileData->setAccDelete(Request::analyze('profile_accdel', 0, false, 1));
        $ProfileData->setAccFiles(Request::analyze('profile_accfiles', 0, false, 1));
        $ProfileData->setAccPublicLinks(Request::analyze('profile_accpublinks', 0, false, 1));
        $ProfileData->setConfigGeneral(Request::analyze('profile_config', 0, false, 1));
        $ProfileData->setConfigEncryption(Request::analyze('profile_configmpw', 0, false, 1));
        $ProfileData->setConfigBackup(Request::analyze('profile_configback', 0, false, 1));
        $ProfileData->setConfigImport(Request::analyze('profile_configimport', 0, false, 1));
        $ProfileData->setMgmCategories(Request::analyze('profile_categories', 0, false, 1));
        $ProfileData->setMgmCustomers(Request::analyze('profile_customers', 0, false, 1));
        $ProfileData->setMgmCustomFields(Request::analyze('profile_customfields', 0, false, 1));
        $ProfileData->setMgmUsers(Request::analyze('profile_users', 0, false, 1));
        $ProfileData->setMgmGroups(Request::analyze('profile_groups', 0, false, 1));
        $ProfileData->setMgmProfiles(Request::analyze('profile_profiles', 0, false, 1));
        $ProfileData->setMgmApiTokens(Request::analyze('profile_apitokens', 0, false, 1));
        $ProfileData->setMgmPublicLinks(Request::analyze('profile_publinks', 0, false, 1));
        $ProfileData->setEvl(Request::analyze('profile_eventlog', 0, false, 1));

        if (!$ProfileData->getUserprofileName()) {
            Response::printJSON(_('Es necesario un nombre de perfil'), 2);
        }

        try {
            switch ($actionId) {
                case ActionsInterface::ACTION_USR_PROFILES_NEW:
                    Profile::getItem($ProfileData)->add();
                    Response::printJSON(_('Perfil creado'), 0, $doActionOnClose);
                    break;
                case ActionsInterface::ACTION_USR_PROFILES_EDIT:
                    Profile::getItem($ProfileData)->update();
                    Response::printJSON(_('Perfil actualizado'), 0, $doActionOnClose);
            }
        } catch (SPException $e) {
            Response::printJSON($e->getMessage(), 2);
        }

    } elseif ($actionId === ActionsInterface::ACTION_USR_PROFILES_DELETE) {
        try {
            Profile::getItem()->delete($itemId);
            Response::printJSON(_('Perfil eliminado'), 0, $doActionOnClose);
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
    || $actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    || $actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE
) {
    $CustomerData = new CustomerData();
    $CustomerData->setCustomerId($itemId);
    $CustomerData->setCustomerName(Request::analyze('name'));
    $CustomerData->setCustomerDescription(Request::analyze('description'));

    $Customer = new Customer($CustomerData);

    $CustomFieldData = new CustomFieldData();
    $CustomFieldData->setId($itemId);
    $CustomFieldData->setModule(ActionsInterface::ACTION_MGM_CUSTOMERS);

    if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_NEW
        || $actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT
    ) {
        if (!$CustomerData->getCustomerName()) {
            Response::printJSON(_('Es necesario un nombre de cliente'), 2);
        }

        if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_NEW) {
            try {
                $Customer->add();

                if (is_array($customFields)) {
                    $CustomFieldData->setId($CustomerData->getCustomerId());
                    CustomFieldsUtil::addItemCustomFields($customFields, $CustomFieldData);
                }
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Cliente creado'), 0, $doActionOnClose);
        } else if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_EDIT) {
            try {
                $Customer->update();

                if (is_array($customFields)) {
                    CustomFieldsUtil::updateItemCustomFields($customFields, $CustomFieldData);
                }
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Cliente actualizado'), 0, $doActionOnClose);
        }
    } elseif ($actionId === ActionsInterface::ACTION_MGM_CUSTOMERS_DELETE) {
        try {
            $Customer->delete($itemId);
            CustomField::getItem($CustomFieldData)->delete($itemId);
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Cliente eliminado'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_NEW
    || $actionId === ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    || $actionId === ActionsInterface::ACTION_MGM_CATEGORIES_DELETE
) {
    $CategoryData = new CategoryData();
    $CategoryData->setCategoryId($itemId);
    $CategoryData->setCategoryName(Request::analyze('name'));
    $CategoryData->setCategoryDescription(Request::analyze('description'));

    $Category = new Category($CategoryData);

    $CustomFieldData = new CustomFieldData();
    $CustomFieldData->setId($itemId);
    $CustomFieldData->setModule(ActionsInterface::ACTION_MGM_CATEGORIES);

    if ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_NEW
        || $actionId === ActionsInterface::ACTION_MGM_CATEGORIES_EDIT
    ) {
        if (!$CategoryData->getCategoryName()) {
            Response::printJSON(_('Es necesario un nombre de categoría'), 2);
        }

        if ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_NEW) {
            try {
                $Category->add();

                if (is_array($customFields)) {
                    $CustomFieldData->setId($CategoryData->getCategoryId());
                    CustomFieldsUtil::addItemCustomFields($customFields, $CustomFieldData);
                }
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Categoría creada'), 0, $doActionOnClose);
        } else if ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_EDIT) {
            try {
                $Category->update();

                if (is_array($customFields)) {
                    CustomFieldsUtil::updateItemCustomFields($customFields, $CustomFieldData);
                }
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Categoría actualizada'), 0, $doActionOnClose);
        }

    } elseif ($actionId === ActionsInterface::ACTION_MGM_CATEGORIES_DELETE) {
        try {
            $Category->delete($itemId);
            CustomField::getItem($CustomFieldData)->delete($itemId);
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
    $CustomFieldDefData = new CustomFieldDefData();
    $CustomFieldDefData->setId($itemId);
    $CustomFieldDefData->setName(Request::analyze('name'));
    $CustomFieldDefData->setType(Request::analyze('type', 0));
    $CustomFieldDefData->setModule(Request::analyze('module', 0));
    $CustomFieldDefData->setHelp(Request::analyze('help'));
    $CustomFieldDefData->setRequired(Request::analyze('required', false, false, true));

    $CustomFieldDef = new CustomFieldDef($CustomFieldDefData);

    if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW
        || $actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT
    ) {
        if (!$CustomFieldDefData->getName()) {
            Response::printJSON(_('Nombre del campo no indicado'), 2);
        } elseif ($CustomFieldDefData->getType() === 0) {
            Response::printJSON(_('Tipo del campo no indicado'), 2);
        } elseif ($CustomFieldDefData->getModule() === 0) {
            Response::printJSON(_('Módulo del campo no indicado'), 2);
        }

        if ($actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_NEW) {
            try {
                $CustomFieldDef->add();
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Campo creado'), 0, $doActionOnClose);
        } elseif ($actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_EDIT) {
            try {
                $CustomFieldDef->update();
            } catch (SPException $e) {
                Response::printJSON($e->getMessage(), 2);
            }

            Response::printJSON(_('Campo actualizado'), 0, $doActionOnClose);
        }

    } elseif ($actionId === ActionsInterface::ACTION_MGM_CUSTOMFIELDS_DELETE) {
        try {
            $CustomFieldDef->delete($itemId);
        } catch (SPException $e) {
            Response::printJSON($e->getMessage(), 2);
        }

        Response::printJSON(_('Campo eliminado'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW
    || $actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_DELETE
    || $actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH
) {
    $PublicLinkData = new PublicLinkData();
    $PublicLinkData->setItemId($itemId);
    $PublicLinkData->setTypeId(PublicLink::TYPE_ACCOUNT);

    if ($actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW) {
        $doActionOnClose = "sysPassUtil.Common.doAction(" . ActionsInterface::ACTION_ACC_VIEW . ",'',$itemId);";

        $PublicLinkData->setNotify(Request::analyze('notify', false, false, true));

        try {
            PublicLink::getItem($PublicLinkData)->add();
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Enlace creado'), 0, $doActionOnClose);
    } elseif ($actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_DELETE) {
        try {
            PublicLink::getItem()->delete($itemId);
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Enlace eliminado'), 0, $doActionOnClose);
    } elseif ($actionId === ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH) {
        try {
            PublicLink::getItem($PublicLinkData)->update();
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Enlace actualizado'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_TAGS_NEW
    || $actionId === ActionsInterface::ACTION_MGM_TAGS_EDIT
    || $actionId === ActionsInterface::ACTION_MGM_TAGS_DELETE
) {
    $TagData = new TagData();
    $TagData->setTagId($itemId);
    $TagData->setTagName(Request::analyze('name'));

    if ($actionId === ActionsInterface::ACTION_MGM_TAGS_NEW) {
        try {
            Tag::getItem($TagData)->add();
        } catch (SPException $e) {
            Response::printJSON($e->getMessage(), 2);
        }

        Response::printJSON(_('Etiqueta creada'), 0, $doActionOnClose);
    } elseif ($actionId === ActionsInterface::ACTION_MGM_TAGS_DELETE) {
        try {
            Tag::getItem()->delete($itemId);
        } catch (SPException $e) {
            Response::printJSON($e->getMessage());
        }

        Response::printJSON(_('Etiqueta eliminada'), 0, $doActionOnClose);
    } elseif ($actionId === ActionsInterface::ACTION_MGM_TAGS_EDIT) {
        try {
            Tag::getItem($TagData)->update();
        } catch (SPException $e) {
            Response::printJSON($e->getMessage(), 2);
        }

        Response::printJSON(_('Etiqueta actualizada'), 0, $doActionOnClose);
    }
} elseif ($actionId === ActionsInterface::ACTION_MGM_FILES_DELETE) {
    // Verificamos que el ID sea numérico
    if ($itemId === 0) {
        Response::printJSON(_('No es un ID de archivo válido'));
    } elseif (File::getItem()->delete($itemId)) {
        Response::printJSON(_('Archivo eliminado'), 0, $doActionOnClose);
    }

    Response::printJSON(_('Error al eliminar el archivo'));
} elseif ($actionId === ActionsInterface::ACTION_MGM_ACCOUNTS_DELETE) {
    $Account = new Account(new AccountData($itemId));

    // Eliminar cuenta
    if ($Account->deleteAccount()
        && CustomField::getItem(new CustomFieldData(ActionsInterface::ACTION_ACC_NEW))->delete($itemId)
    ) {
        Response::printJSON(_('Cuenta eliminada'), 0, $doActionOnClose);
    }

    Response::printJSON(_('Error al eliminar la cuenta'));
} else {
    Response::printJSON(_('Acción Inválida'));
}