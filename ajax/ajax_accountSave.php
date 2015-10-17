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
use SP\Core\ActionsInterface;
use SP\Core\Crypt;
use SP\Core\Init;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\SPException;
use SP\Http\Request;
use SP\Http\Response;
use SP\Mgmt\Customer;
use SP\Mgmt\CustomFields;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJSON(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$actionId = Request::analyze('actionId', 0);
$accountId = Request::analyze('accountid', 0);
$customerId = Request::analyze('customerId', 0);
$newCustomer = Request::analyze('customer_new');
$accountName = Request::analyze('name');
$accountLogin = Request::analyze('login');
$accountPassword = Request::analyzeEncrypted('pass');
$accountPasswordR = Request::analyzeEncrypted('passR');
$categoryId = Request::analyze('categoryId', 0);
$accountOtherGroups = Request::analyze('othergroups');
$accountOtherUsers = Request::analyze('otherusers');
$accountNotes = Request::analyze('notes');
$accountUrl = Request::analyze('url');
$accountGroupEditEnabled = Request::analyze('geditenabled', 0, false, 1);
$accountUserEditEnabled = Request::analyze('ueditenabled', 0, false, 1);
$accountMainGroupId = Request::analyze('mainGroupId', 0);
$accountChangesHash = Request::analyze('hash');
$customFields = Request::analyze('customfield');

// Datos del Usuario
$currentUserId = Session::getUserId();

if ($accountMainGroupId === 0) {
    $accountMainGroupId = Session::getUserGroupId();
}

if ($actionId === ActionsInterface::ACTION_ACC_NEW
    || $actionId === ActionsInterface::ACTION_ACC_COPY
) {
    // Comprobaciones para nueva cuenta
    if (!$accountName) {
        Response::printJSON(_('Es necesario un nombre de cuenta'));
    } elseif (!$customerId && !$newCustomer) {
        Response::printJSON(_('Es necesario un nombre de cliente'));
    } elseif (!$accountLogin) {
        Response::printJSON(_('Es necesario un usuario'));
    } elseif (!$accountPassword || !$accountPasswordR) {
        Response::printJSON(_('Es necesaria una clave'));
    } elseif (!$categoryId) {
        Response::printJSON(_('Es necesario una categoría'));
    }
} elseif ($actionId === ActionsInterface::ACTION_ACC_EDIT) {
    // Comprobaciones para modificación de cuenta
    if (!$customerId && !$newCustomer) {
        Response::printJSON(_('Es necesario un nombre de cliente'));
    } elseif (!$accountName) {
        Response::printJSON(_('Es necesario un nombre de cuenta'));
    } elseif (!$accountLogin) {
        Response::printJSON(_('Es necesario un usuario'));
    } elseif (!$categoryId) {
        Response::printJSON(_('Es necesario una categoría'));
    }
} elseif ($actionId === ActionsInterface::ACTION_ACC_DELETE) {
    if (!$accountId) {
        Response::printJSON(_('Id inválido'));
    }
} elseif ($actionId == ActionsInterface::ACTION_ACC_EDIT_PASS) {
    // Comprobaciones para modficación de clave
    if (!$accountPassword || !$accountPasswordR) {
        Response::printJSON(_('Es necesaria una clave'));
    }
} elseif ($actionId == ActionsInterface::ACTION_ACC_EDIT_RESTORE) {
    if (!$accountId) {
        Response::printJSON(_('Id inválido'));
    }
} else {
    Response::printJSON(_('Acción Inválida'));
}

if ($actionId == ActionsInterface::ACTION_ACC_NEW
    || $actionId == ActionsInterface::ACTION_ACC_COPY
    || $actionId === ActionsInterface::ACTION_ACC_EDIT_PASS
) {
    if ($accountPassword != $accountPasswordR) {
        Response::printJSON(_('Las claves no coinciden'));
    }

    try {
        // Encriptar clave de cuenta
        $accountEncPass = Crypt::encryptData($accountPassword);
    } catch (SPException $e) {
        Response::printJSON($e->getMessage());
    }
}

$Account = new Account;

switch ($actionId) {
    case ActionsInterface::ACTION_ACC_NEW:
    case ActionsInterface::ACTION_ACC_COPY:
        Customer::$customerName = $newCustomer;

        // Comprobar si se ha introducido un nuevo cliente
        if ($newCustomer) {
            try {
                Customer::addCustomer();
                $customerId = Customer::$customerLastId;
            } catch (SPException $e) {
                Response::printJSON($e->getMessage());
            }
        }

        $Account->setAccountName($accountName);
        $Account->setAccountCategoryId($categoryId);
        $Account->setAccountCustomerId($customerId);
        $Account->setAccountLogin($accountLogin);
        $Account->setAccountUrl($accountUrl);
        $Account->setAccountPass($accountEncPass['data']);
        $Account->setAccountIV($accountEncPass['iv']);
        $Account->setAccountNotes($accountNotes);
        $Account->setAccountUserId($currentUserId);
        $Account->setAccountUserGroupId($accountMainGroupId);
        $Account->setAccountUsersId($accountOtherUsers);
        $Account->setAccountUserGroupsId($accountOtherGroups);
        $Account->setAccountOtherUserEdit($accountUserEditEnabled);
        $Account->setAccountOtherGroupEdit($accountGroupEditEnabled);

        // Crear cuenta
        if ($Account->createAccount()) {
            if (is_array($customFields)) {
                foreach ($customFields as $id => $value) {
                    $CustomFields = new CustomFields($id, $Account->getAccountId(), $value);
                    $CustomFields->addCustomField();
                }
            }

            Response::printJSON(_('Cuenta creada'), 0);
        }

        Response::printJSON(_('Error al crear la cuenta'), 0);
        break;
    case ActionsInterface::ACTION_ACC_EDIT:
        Customer::$customerName = $newCustomer;

        // Comprobar si se ha introducido un nuevo cliente
        if ($newCustomer) {
            try {
                Customer::addCustomer();
                $customerId = Customer::$customerLastId;
            } catch (SPException $e) {
                Response::printJSON($e->getMessage());
            }
        }

        $Account->setAccountId($accountId);
        $Account->setAccountName($accountName);
        $Account->setAccountCategoryId($categoryId);
        $Account->setAccountCustomerId($customerId);
        $Account->setAccountLogin($accountLogin);
        $Account->setAccountUrl($accountUrl);
        $Account->setAccountNotes($accountNotes);
        $Account->setAccountUserEditId($currentUserId);
        $Account->setAccountUsersId($accountOtherUsers);
        $Account->setAccountUserGroupsId($accountOtherGroups);
        $Account->setAccountOtherUserEdit($accountUserEditEnabled);
        $Account->setAccountOtherGroupEdit($accountGroupEditEnabled);

        // Cambiar el grupo principal si el usuario es Admin
        if (Session::getUserIsAdminApp() || Session::getUserIsAdminAcc()) {
            $Account->setAccountUserGroupId($accountMainGroupId);
        }

        // Comprobar si han habido cambios
        if ($accountChangesHash == $Account->calcChangesHash()) {
            Response::printJSON(_('Sin cambios'), 0);
        }

        // Actualizar cuenta
        if ($Account->updateAccount()) {
            if (is_array($customFields)) {
                foreach ($customFields as $id => $value) {
                    $CustomFields = new CustomFields($id, $accountId, $value);
                    $CustomFields->updateCustomField();
                }
            }

            Response::printJSON(_('Cuenta actualizada'), 0);
        }

        Response::printJSON(_('Error al modificar la cuenta'));
        break;
    case ActionsInterface::ACTION_ACC_DELETE:
        $Account->setAccountId($accountId);

        // Eliminar cuenta
        if ($Account->deleteAccount()
            && CustomFields::deleteCustomFieldForItem($accountId, ActionsInterface::ACTION_ACC_NEW)
        ) {
            Response::printJSON(_('Cuenta eliminada'), 0, "sysPassUtil.Common.doAction('" . ActionsInterface::ACTION_ACC_SEARCH . "');");
        }

        Response::printJSON(_('Error al eliminar la cuenta'));
        break;
    case ActionsInterface::ACTION_ACC_EDIT_PASS:
        $Account->setAccountId($accountId);
        $Account->setAccountPass($accountEncPass['data']);
        $Account->setAccountIV($accountEncPass['iv']);
        $Account->setAccountUserEditId($currentUserId);

        // Actualizar clave de cuenta
        if ($Account->updateAccountPass()) {
            Response::printJSON(_('Clave actualizada'), 0);
        }

        Response::printJSON(_('Error al actualizar la clave'));
        break;
    case ActionsInterface::ACTION_ACC_EDIT_RESTORE:
        $Account->setAccountId(\SP\Account\AccountHistory::getAccountIdFromId($accountId));
        $Account->setAccountUserEditId($currentUserId);

        if ($Account->restoreFromHistory($accountId)) {
            Response::printJSON(_('Cuenta restaurada'), 0);
        }

        Response::printJSON(_('Error al restaurar cuenta'));

        break;
    default:
        Response::printJSON(_('Acción Inválida'));
}