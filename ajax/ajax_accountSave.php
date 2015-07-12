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
//$frmSaveType = SP_Request::analyze('savetyp', 0);
$actionId = SP\Request::analyze('actionId', 0);
$accountId = SP\Request::analyze('accountid', 0);
$customerId = SP\Request::analyze('customerId', 0);
$newCustomer = SP\Request::analyze('customer_new');
$accountName = SP\Request::analyze('name');
$accountLogin = SP\Request::analyze('login');
$accountPassword = SP\Request::analyze('password', '', false, false, false);
$accountPasswordV = SP\Request::analyze('password2', '', false, false, false);
$categoryId = SP\Request::analyze('categoryId', 0);
$accountOtherGroups = SP\Request::analyze('othergroups');
$accountOtherUsers = SP\Request::analyze( 'otherusers');
$accountNotes = SP\Request::analyze('notice');
$accountUrl = SP\Request::analyze('url');
$accountGroupEditEnabled = SP\Request::analyze('geditenabled', 0, false, 1);
$accountUserEditEnabled = SP\Request::analyze('ueditenabled', 0, false, 1);
$accountChangesHash = SP\Request::analyze('hash');

// Datos del Usuario
$currentUserId = SP\Session::getUserId();
$currentGroupId = SP\Session::getUserGroupId();

if ($actionId === \SP\Controller\ActionsInterface::ACTION_ACC_NEW) {
    // Comprobaciones para nueva cuenta
    if (!$accountName) {
        SP\Common::printJSON(_('Es necesario un nombre de cuenta'));
    } elseif (!$customerId && !$newCustomer) {
        SP\Common::printJSON(_('Es necesario un nombre de cliente'));
    } elseif (!$accountLogin) {
        SP\Common::printJSON(_('Es necesario un usuario'));
    } elseif (!$accountPassword) {
        SP\Common::printJSON(_('Es necesario una clave'));
    } elseif ($accountPassword != $accountPasswordV) {
        SP\Common::printJSON(_('Las claves no coinciden'));
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_ACC_EDIT) {
    // Comprobaciones para modificación de cuenta
    if (!$customerId && !$newCustomer) {
        SP\Common::printJSON(_('Es necesario un nombre de cliente'));
    } elseif (!$accountName) {
        SP\Common::printJSON(_('Es necesario un nombre de cuenta'));
    } elseif (!$accountLogin) {
        SP\Common::printJSON(_('Es necesario un usuario'));
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_ACC_DELETE) {
    if (!$accountId) {
        SP\Common::printJSON(_('Id inválido'));
    }
} elseif ($actionId == \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_PASS) {
    // Comprobaciones para modficación de clave
    if (!$accountPassword && !$accountPasswordV) {
        SP\Common::printJSON(_('La clave no puede estar en blanco'));
    } elseif ($accountPassword != $accountPasswordV) {
        SP\Common::printJSON(_('Las claves no coinciden'));
    }
} elseif ($actionId == \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_RESTORE) {
    if (!$accountId) {
        SP\Common::printJSON(_('Id inválido'));
    }
} else {
    SP\Common::printJSON(_('Acción Inválida'));
}

if ($actionId == \SP\Controller\ActionsInterface::ACTION_ACC_NEW
    || $actionId === \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_PASS
) {
    // Comprobar el módulo de encriptación
    if (!SP\Crypt::checkCryptModule()) {
        SP\Common::printJSON(_('No se puede usar el módulo de encriptación'));
    }

    // Encriptar clave de cuenta
    $accountEncPass = SP\Crypt::mkEncrypt($accountPassword);

    if ($accountEncPass === false || is_null($accountEncPass)) {
        SP\Common::printJSON(_('Error al generar datos cifrados'));
    }

    $accounEncPassIV = SP\Crypt::$strInitialVector;
}

$account = new SP\Account;

switch ($actionId) {
    case \SP\Controller\ActionsInterface::ACTION_ACC_NEW:
        SP\Customer::$customerName = $newCustomer;

        // Comprobar si se ha introducido un nuevo cliente
        if ($newCustomer) {
            if (SP\Customer::checkDupCustomer()) {
                SP\Common::printJSON(_('Cliente duplicado'));
            } elseif (!SP\Customer::addCustomer()) {
                SP\Common::printJSON(_('Error al crear el cliente'));
            }

            $account->setAccountCustomerId(SP\Customer::$customerLastId);
        } else {
            $account->setAccountCustomerId($customerId);
        }

        $account->setAccountName($accountName);
        $account->setAccountCategoryId($categoryId);
        $account->setAccountLogin($accountLogin);
        $account->setAccountUrl($accountUrl);
        $account->setAccountPass($accountEncPass);
        $account->setAccountIV($accounEncPassIV);
        $account->setAccountNotes($accountNotes);
        $account->setAccountUserId($currentUserId);
        $account->setAccountUserGroupId($currentGroupId);
        $account->setAccountUsersId($accountOtherUsers);
        $account->setAccountUserGroupsId($accountOtherGroups);
        $account->setAccountOtherUserEdit($accountUserEditEnabled);
        $account->setAccountOtherGroupEdit($accountGroupEditEnabled);

        // Crear cuenta
        if ($account->createAccount()) {
            SP\Common::printJSON(_('Cuenta creada'), 0);
        }

        SP\Common::printJSON(_('Error al crear la cuenta'), 0);
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_EDIT:
        SP\Customer::$customerName = $newCustomer;

        $account->setAccountId($accountId);
        $account->setAccountName($accountName);
        $account->setAccountCategoryId($categoryId);
        $account->setAccountLogin($accountLogin);
        $account->setAccountUrl($accountUrl);
        $account->setAccountNotes($accountNotes);
        $account->setAccountUserEditId($currentUserId);
        $account->setAccountUsersId($accountOtherUsers);
        $account->setAccountUserGroupsId($accountOtherGroups);
        $account->setAccountOtherUserEdit($accountUserEditEnabled);
        $account->setAccountOtherGroupEdit($accountGroupEditEnabled);

        // Comprobar si se ha introducido un nuevo cliente
        if ($newCustomer) {
            if (SP\Customer::checkDupCustomer()) {
                SP\Common::printJSON(_('Cliente duplicado'));
            } elseif (!SP\Customer::addCustomer()) {
                SP\Common::printJSON(_('Error al crear el cliente'));
            }

            $account->setAccountCustomerId(SP\Customer::$customerLastId);
        } else {
            $account->setAccountCustomerId($customerId);
        }

        // Comprobar si han habido cambios
        if ($accountChangesHash == $account->calcChangesHash()) {
            SP\Common::printJSON(_('Sin cambios'), 0);
        }

        // Actualizar cuenta
        if ($account->updateAccount()) {
            SP\Common::printJSON(_('Cuenta actualizada'), 0);
        }

        SP\Common::printJSON(_('Error al modificar la cuenta'));
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_DELETE:
        $account->setAccountId($accountId);

        // Eliminar cuenta
        if ($account->deleteAccount()) {
            SP\Common::printJSON(_('Cuenta eliminada'), 0, "doAction('accsearch');");
        }
        SP\Common::printJSON(_('Error al eliminar la cuenta'));
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_PASS:
        $account->setAccountId($accountId);
        $account->setAccountPass($accountEncPass);
        $account->setAccountIV($accounEncPassIV);
        $account->setAccountUserEditId($currentUserId);

        // Actualizar clave de cuenta
        if ($account->updateAccountPass()) {
            SP\Common::printJSON(_('Clave actualizada'), 0);
        }

        SP\Common::printJSON(_('Error al actualizar la clave'));
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_RESTORE:
        $account->setAccountId(SP\AccountHistory::getAccountIdFromId($accountId));
        $account->setAccountUserEditId($currentUserId);

        if ($account->restoreFromHistory($accountId)) {
            SP\Common::printJSON(_('Cuenta restaurada'), 0);
        }

        SP\Common::printJSON(_('Error al restaurar cuenta'));

        break;
    default:
        SP\Common::printJSON(_('Acción Inválida'));
}