<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

define('APP_ROOT', '..');
require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'init.php';

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP_Common::parseParams('p', 'sk', false);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printJSON(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$frmSaveType = SP_Common::parseParams('p', 'savetyp', 0);
$frmAccountId = SP_Common::parseParams('p', 'accountid', 0);
$frmSelCustomer = SP_Common::parseParams('p', 'customerId', 0);
$frmNewCustomer = SP_Common::parseParams('p', 'customer_new');
$frmName = SP_Common::parseParams('p', 'name');
$frmLogin = SP_Common::parseParams('p', 'login');
$frmPassword = SP_Common::parseParams('p', 'password', '', false, false, false);
$frmPasswordV = SP_Common::parseParams('p', 'password2', '', false, false, false);
$frmCategoryId = SP_Common::parseParams('p', 'categoryId', 0);
$frmOtherGroups = SP_Common::parseParams('p', 'othergroups');
$frmOtherUsers = SP_Common::parseParams('p', 'otherusers');
$frmNotes = SP_Common::parseParams('p', 'notice');
$frmUrl = SP_Common::parseParams('p', 'url');
$frmGroupEditEnabled = SP_Common::parseParams('p', 'geditenabled', 0, false, 1);
$frmUserEditEnabled = SP_Common::parseParams('p', 'ueditenabled', 0, false, 1);
$frmChangesHash = SP_Common::parseParams('p', 'hash');

// Datos del Usuario
$userId = SP_Common::parseParams('s', 'uid', 0);
$groupId = SP_Common::parseParams('s', 'ugroup', 0);

if ($frmSaveType == 1) {
    // Comprobaciones para nueva cuenta
    if (!$frmName) {
        SP_Common::printJSON(_('Es necesario un nombre de cuenta'));
    }

    if (!$frmSelCustomer && !$frmNewCustomer) {
        SP_Common::printJSON(_('Es necesario un nombre de cliente'));
    }

    if (!$frmLogin) {
        SP_Common::printJSON(_('Es necesario un usuario'));
    }

    if (!$frmPassword) {
        SP_Common::printJSON(_('Es necesario una clave'));
    }

    if ($frmPassword != $frmPasswordV) {
        SP_Common::printJSON(_('Las claves no coinciden'));
    }
} elseif ($frmSaveType == 2) {
    // Comprobaciones para modificación de cuenta
    if (!$frmSelCustomer && !$frmNewCustomer) {
        SP_Common::printJSON(_('Es necesario un nombre de cliente'));
    }

    if (!$frmName) {
        SP_Common::printJSON(_('Es necesario un nombre de cuenta'));
    }

    if (!$frmLogin) {
        SP_Common::printJSON(_('Es necesario un usuario'));
    }
} elseif ($frmSaveType == 3) {
    if (!$frmAccountId) {
        SP_Common::printJSON(_('Id inválido'));
    }
} elseif ($frmSaveType == 4) {
    // Comprobaciones para modficación de clave
    if (!$frmPassword && !$frmPasswordV) {
        SP_Common::printJSON(_('La clave no puede estar en blanco'));
    }

    if ($frmPassword != $frmPasswordV) {
        SP_Common::printJSON(_('Las claves no coinciden'));
    }
} elseif ($frmSaveType == 5) {
    if (!$frmAccountId) {
        SP_Common::printJSON(_('Id inválido'));
    }
} else {
    SP_Common::printJSON(_('Acción Inválida'));
}

if ($frmSaveType == 1 || $frmSaveType == 4) {
    // Comprobar el módulo de encriptación
    if (!SP_Crypt::checkCryptModule()) {
        SP_Common::printJSON(_('No se puede usar el módulo de encriptación'));
    }

    // Encriptar clave de cuenta
    $accountPass = SP_Crypt::mkEncrypt($frmPassword);

    if ($accountPass === false || is_null($accountPass)) {
        SP_Common::printJSON(_('Error al generar datos cifrados'));
    }

    $accountIV = SP_Crypt::$strInitialVector;
}

$account = new SP_Account;

switch ($frmSaveType) {
    case 1:
        SP_Customer::$customerName = $frmNewCustomer;

        // Comprobar si se ha introducido un nuevo cliente
        if ($frmNewCustomer) {
            if (!SP_Customer::checkDupCustomer()) {
                SP_Common::printJSON(_('Cliente duplicado'));
            }

            if (!SP_Customer::addCustomer()) {
                SP_Common::printJSON(_('Error al crear el cliente'));
            }

            $account->accountCustomerId = SP_Customer::$customerLastId;
        } else {
            $account->accountCustomerId = $frmSelCustomer;
        }

        $account->accountName = $frmName;
        $account->accountCategoryId = $frmCategoryId;
        $account->accountLogin = $frmLogin;
        $account->accountUrl = $frmUrl;
        $account->accountPass = $accountPass;
        $account->accountIV = $accountIV;
        $account->accountNotes = $frmNotes;
        $account->accountUserId = $userId;
        $account->accountUserGroupId = $groupId;
        $account->accountUserGroupsId = $frmOtherGroups;
        $account->accountUsersId = $frmOtherUsers;
        $account->accountOtherUserEdit = $frmUserEditEnabled;
        $account->accountOtherGroupEdit = $frmGroupEditEnabled;

        // Crear cuenta
        if ($account->createAccount()) {
            SP_Common::printJSON(_('Cuenta creada'), 0);
        }
        SP_Common::printJSON(_('Error al crear la cuenta'), 0);
        break;
    case 2:
        SP_Customer::$customerName = $frmNewCustomer;
        $account->accountId = $frmAccountId;
        $account->accountName = $frmName;
        $account->accountCategoryId = $frmCategoryId;
        $account->accountLogin = $frmLogin;
        $account->accountUrl = $frmUrl;
        $account->accountNotes = $frmNotes;
        $account->accountUserEditId = $userId;
        $account->accountUserGroupsId = $frmOtherGroups;
        $account->accountUsersId = $frmOtherUsers;
        $account->accountOtherUserEdit = $frmUserEditEnabled;
        $account->accountOtherGroupEdit = $frmGroupEditEnabled;

        // Comprobar si se ha introducido un nuevo cliente
        if ($frmNewCustomer) {
            if (!SP_Customer::checkDupCustomer()) {
                SP_Common::printJSON(_('Cliente duplicado'));
            }

            if (!SP_Customer::addCustomer()) {
                SP_Common::printJSON(_('Error al crear el cliente'));
            }

            $account->accountCustomerId = SP_Customer::$customerLastId;
        } else {
            $account->accountCustomerId = $frmSelCustomer;
        }

        // Comprobar si han habido cambios
        if ($frmChangesHash == $account->calcChangesHash()) {
            SP_Common::printJSON(_('Sin cambios'), 0);
        }

        // Actualizar cuenta
        if ($account->updateAccount()) {
            SP_Common::printJSON(_('Cuenta actualizada'), 0);
        }
        SP_Common::printJSON(_('Error al modificar la cuenta'));
        break;
    case 3:
        $account->accountId = $frmAccountId;

        // Eliminar cuenta
        if ($account->deleteAccount()) {
            SP_Common::printJSON(_('Cuenta eliminada'), 0, "doAction('accsearch');");
        }
        SP_Common::printJSON(_('Error al eliminar la cuenta'));
        break;
    case 4:
        $account->accountId = $frmAccountId;
        $account->accountPass = $accountPass;
        $account->accountIV = $accountIV;
        $account->accountUserEditId = $userId;

        // Actualizar clave de cuenta
        if ($account->updateAccountPass()) {
            SP_Common::printJSON(_('Clave actualizada'), 0);
        }
        SP_Common::printJSON(_('Error al actualizar la clave'));
        break;
    case 5:
        $account->accountId = $frmAccountId;
        $accountHistData = $account->getAccountHistory();

        $account->accountId = $accountHistData->account_id;
        $account->accountName = $accountHistData->account_name;
        $account->accountCategoryId = $accountHistData->account_categoryId;
        $account->accountCustomerId = $accountHistData->account_customerId;
        $account->accountLogin = $accountHistData->account_login;
        $account->accountUrl = $accountHistData->account_url;
        $account->accountPass = $accountHistData->account_pass;
        $account->accountIV = $accountHistData->account_IV;
        $account->accountNotes = $accountHistData->account_notes;
        $account->accountUserId = $accountHistData->account_userId;
        $account->accountUserGroupId = $accountHistData->account_userGroupId;
        $account->accountOtherUserEdit = $accountHistData->account_otherUserEdit;
        $account->accountOtherGroupEdit = $accountHistData->account_otherGroupEdit;
        $account->accountUserEditId = $userId;

        // Restaurar cuenta y clave
        if ($account->updateAccount(true) && $account->updateAccountPass(false,true)) {
            SP_Common::printJSON(_('Cuenta restaurada'), 0);
        }

        SP_Common::printJSON(_('Error al restaurar cuenta'));
        break;
    default:
        SP_Common::printJSON(_('Acción Inválida'));
}