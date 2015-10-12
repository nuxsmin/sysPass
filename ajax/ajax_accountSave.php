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
//$frmSaveType = SP_Request::analyze('savetyp', 0);
$actionId = \SP\Http\Request::analyze('actionId', 0);
$accountId = \SP\Http\Request::analyze('accountid', 0);
$customerId = \SP\Http\Request::analyze('customerId', 0);
$newCustomer = \SP\Http\Request::analyze('customer_new');
$accountName = \SP\Http\Request::analyze('name');
$accountLogin = \SP\Http\Request::analyze('login');
$accountPassword = \SP\Http\Request::analyzeEncrypted('pass');
$accountPasswordR = \SP\Http\Request::analyzeEncrypted('passR');
$categoryId = \SP\Http\Request::analyze('categoryId', 0);
$accountOtherGroups = \SP\Http\Request::analyze('othergroups');
$accountOtherUsers = \SP\Http\Request::analyze('otherusers');
$accountNotes = \SP\Http\Request::analyze('notes');
$accountUrl = \SP\Http\Request::analyze('url');
$accountGroupEditEnabled = \SP\Http\Request::analyze('geditenabled', 0, false, 1);
$accountUserEditEnabled = \SP\Http\Request::analyze('ueditenabled', 0, false, 1);
$accountMainGroupId = \SP\Http\Request::analyze('mainGroupId', 0);
$accountChangesHash = \SP\Http\Request::analyze('hash');
$customFields = \SP\Http\Request::analyze('customfield');

// Datos del Usuario
$currentUserId = \SP\Core\Session::getUserId();

if (!$accountMainGroupId === 0) {
    $accountMainGroupId = \SP\Core\Session::getUserGroupId();
}

if ($actionId === \SP\Core\ActionsInterface::ACTION_ACC_NEW
    || $actionId === \SP\Core\ActionsInterface::ACTION_ACC_COPY
) {
    // Comprobaciones para nueva cuenta
    if (!$accountName) {
        \SP\Http\Response::printJSON(_('Es necesario un nombre de cuenta'));
    } elseif (!$customerId && !$newCustomer) {
        \SP\Http\Response::printJSON(_('Es necesario un nombre de cliente'));
    } elseif (!$accountLogin) {
        \SP\Http\Response::printJSON(_('Es necesario un usuario'));
    } elseif (!$accountPassword || !$accountPasswordR) {
        \SP\Http\Response::printJSON(_('Es necesaria una clave'));
    } elseif (!$categoryId) {
        \SP\Http\Response::printJSON(_('Es necesario una categoría'));
    }
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_ACC_EDIT) {
    // Comprobaciones para modificación de cuenta
    if (!$customerId && !$newCustomer) {
        \SP\Http\Response::printJSON(_('Es necesario un nombre de cliente'));
    } elseif (!$accountName) {
        \SP\Http\Response::printJSON(_('Es necesario un nombre de cuenta'));
    } elseif (!$accountLogin) {
        \SP\Http\Response::printJSON(_('Es necesario un usuario'));
    } elseif (!$categoryId) {
        \SP\Http\Response::printJSON(_('Es necesario una categoría'));
    }
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_ACC_DELETE) {
    if (!$accountId) {
        \SP\Http\Response::printJSON(_('Id inválido'));
    }
} elseif ($actionId == \SP\Core\ActionsInterface::ACTION_ACC_EDIT_PASS) {
    // Comprobaciones para modficación de clave
    if (!$accountPassword || !$accountPasswordR) {
        \SP\Http\Response::printJSON(_('Es necesaria una clave'));
    }
} elseif ($actionId == \SP\Core\ActionsInterface::ACTION_ACC_EDIT_RESTORE) {
    if (!$accountId) {
        \SP\Http\Response::printJSON(_('Id inválido'));
    }
} else {
    \SP\Http\Response::printJSON(_('Acción Inválida'));
}

if ($actionId == \SP\Core\ActionsInterface::ACTION_ACC_NEW
    || $actionId == \SP\Core\ActionsInterface::ACTION_ACC_COPY
    || $actionId === \SP\Core\ActionsInterface::ACTION_ACC_EDIT_PASS
) {
    if ($accountPassword != $accountPasswordR) {
        \SP\Http\Response::printJSON(_('Las claves no coinciden'));
    }

    // Encriptar clave de cuenta
    try {
        $accountEncPass = \SP\Core\Crypt::encryptData($accountPassword);
    } catch (\SP\Core\SPException $e) {
        \SP\Http\Response::printJSON($e->getMessage());
    }
}

$Account = new \SP\Account\Account;

switch ($actionId) {
    case \SP\Core\ActionsInterface::ACTION_ACC_NEW:
    case \SP\Core\ActionsInterface::ACTION_ACC_COPY:
        \SP\Mgmt\Customer::$customerName = $newCustomer;

        // Comprobar si se ha introducido un nuevo cliente
        if ($newCustomer) {
            try {
                \SP\Mgmt\Customer::addCustomer();
                $customerId = \SP\Mgmt\Customer::$customerLastId;
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage());
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
                    $CustomFields = new \SP\Mgmt\CustomFields($id, $Account->getAccountId(), $value);
                    $CustomFields->addCustomField();
                }
            }

            \SP\Http\Response::printJSON(_('Cuenta creada'), 0);
        }

        \SP\Http\Response::printJSON(_('Error al crear la cuenta'), 0);
        break;
    case \SP\Core\ActionsInterface::ACTION_ACC_EDIT:
        \SP\Mgmt\Customer::$customerName = $newCustomer;

        // Comprobar si se ha introducido un nuevo cliente
        if ($newCustomer) {
            try {
                \SP\Mgmt\Customer::addCustomer();
                $customerId = \SP\Mgmt\Customer::$customerLastId;
            } catch (\SP\Core\SPException $e) {
                \SP\Http\Response::printJSON($e->getMessage());
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
        if (\SP\Core\Session::getUserIsAdminApp() || \SP\Core\Session::getUserIsAdminAcc()) {
            $Account->setAccountUserGroupId($accountMainGroupId);
        }

        // Comprobar si han habido cambios
        if ($accountChangesHash == $Account->calcChangesHash()) {
            \SP\Http\Response::printJSON(_('Sin cambios'), 0);
        }

        // Actualizar cuenta
        if ($Account->updateAccount()) {
            if (is_array($customFields)) {
                foreach ($customFields as $id => $value) {
                    $CustomFields = new \SP\Mgmt\CustomFields($id, $accountId, $value);
                    $CustomFields->updateCustomField();
                }
            }

            \SP\Http\Response::printJSON(_('Cuenta actualizada'), 0);
        }

        \SP\Http\Response::printJSON(_('Error al modificar la cuenta'));
        break;
    case \SP\Core\ActionsInterface::ACTION_ACC_DELETE:
        $Account->setAccountId($accountId);

        // Eliminar cuenta
        if ($Account->deleteAccount() && \SP\Mgmt\CustomFields::deleteCustomFieldForItem($accountId, \SP\Core\ActionsInterface::ACTION_ACC_NEW)) {
            \SP\Http\Response::printJSON(_('Cuenta eliminada'), 0, "sysPassUtil.Common.doAction('" . \SP\Core\ActionsInterface::ACTION_ACC_SEARCH . "');");
        }

        \SP\Http\Response::printJSON(_('Error al eliminar la cuenta'));
        break;
    case \SP\Core\ActionsInterface::ACTION_ACC_EDIT_PASS:
        $Account->setAccountId($accountId);
        $Account->setAccountPass($accountEncPass['data']);
        $Account->setAccountIV($accountEncPass['iv']);
        $Account->setAccountUserEditId($currentUserId);

        // Actualizar clave de cuenta
        if ($Account->updateAccountPass()) {
            \SP\Http\Response::printJSON(_('Clave actualizada'), 0);
        }

        \SP\Http\Response::printJSON(_('Error al actualizar la clave'));
        break;
    case \SP\Core\ActionsInterface::ACTION_ACC_EDIT_RESTORE:
        $Account->setAccountId(\SP\Account\AccountHistory::getAccountIdFromId($accountId));
        $Account->setAccountUserEditId($currentUserId);

        if ($Account->restoreFromHistory($accountId)) {
            \SP\Http\Response::printJSON(_('Cuenta restaurada'), 0);
        }

        \SP\Http\Response::printJSON(_('Error al restaurar cuenta'));

        break;
    default:
        \SP\Http\Response::printJSON(_('Acción Inválida'));
}