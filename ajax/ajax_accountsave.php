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

$sk = SP_Common::parseParams('p', 'sk', FALSE);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printXML(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$frmSaveType = SP_Common::parseParams('p', 'savetyp', 0);
$frmAccountId = SP_Common::parseParams('p', 'accountid', 0);
$frmSelCustomer = SP_Common::parseParams('p', 'customerId', 0);
$frmNewCustomer = SP_Common::parseParams('p', 'customer_new');
$frmName = SP_Common::parseParams('p', 'name');
$frmLogin = SP_Common::parseParams('p', 'login');
$frmPassword = SP_Common::parseParams('p', 'password');
$frmPasswordV = SP_Common::parseParams('p', 'password2');
$frmCategoryId = SP_Common::parseParams('p', 'categoryId', 0);
$frmUGroups = SP_Common::parseParams('p', 'ugroups');
$frmNotes = SP_Common::parseParams('p', 'notice');
$frmUrl = SP_Common::parseParams('p', 'url');
$frmChangesHash = SP_Common::parseParams('p', 'hash');

// Datos del Usuario
$userId = SP_Common::parseParams('s', 'uid', 0);
$groupId = SP_Common::parseParams('s', 'ugroup', 0);

if ($frmSaveType == 1) {
    // Comprobaciones para nueva cuenta
    if (!$frmName) {
        SP_Common::printXML(_('Es necesario un nombre de cuenta'));
    }

    if (!$frmSelCustomer && !$frmNewCustomer) {
        SP_Common::printXML(_('Es necesario un nombre de cliente'));
    }

    if (!$frmLogin) {
        SP_Common::printXML(_('Es necesario un usuario'));
    }

    if (!$frmPassword) {
        SP_Common::printXML(_('Es necesario una clave'));
    }

    if ($frmPassword != $frmPasswordV) {
        SP_Common::printXML(_('Las claves no coinciden'));
    }
} elseif ($frmSaveType == 2) {
    // Comprobaciones para modificación de cuenta
    if (!$frmSelCustomer && !$frmNewCustomer) {
        SP_Common::printXML(_('Es necesario un nombre de cliente'));
    }

    if (!$frmName) {
        SP_Common::printXML(_('Es necesario un nombre de cuenta'));
    }

    if (!$frmLogin) {
        SP_Common::printXML(_('Es necesario un usuario'));
    }
} elseif ($frmSaveType == 3) {
    if (!$frmAccountId) {
        SP_Common::printXML(_('Id inválido'));
    }
} elseif ($frmSaveType == 4) {
    // Comprobaciones para modficación de clave
    if (!$frmPassword && !$frmPasswordV) {
        SP_Common::printXML(_('La clave no puede estar en blanco'));
    }

    if ($frmPassword != $frmPasswordV) {
        SP_Common::printXML(_('Las claves no coinciden'));
    }
} else {
    $SP_Common::printXML(_('Acción Inválida'));
}

if ($frmSaveType == 1 || $frmSaveType == 4) {
    $crypt = new SP_Crypt;

    // Comprobar el módulo de encriptación
    if (!SP_Crypt::checkCryptModule()) {
        SP_Common::printXML(_('No se puede usar el módulo de encriptación'));
    }

    // Encriptar clave de cuenta
    $accountPass = $crypt->mkEncrypt($frmPassword);
    //$accountURL = $crypt->mkEncrypt($frmUrl, $crypt->getSessionMasterPass());
    //$accountNotes = $crypt->mkEncrypt($frmNotes, $crypt->getSessionMasterPass());

    if ($accountPass === FALSE || is_null($accountPass)) {
        SP_Common::printXML(_('Error al generar datos cifrados'));
    }

    $accountIV = $crypt->strInitialVector;
}

$account = new SP_Account;
$customer = new SP_Customer;

switch ($frmSaveType) {
    case 1:
        $customer->customerId = $frmSelCustomer;
        $customer->customerName = $frmNewCustomer;

        // Comprobar si se ha introducido un nuevo cliente
        if ($frmNewCustomer) {
            if (!$customer->chekDupCustomer()) {
                SP_Common::printXML(_('Cliente duplicado'));
            }

            if (!$customer->customerAdd()) {
                SP_Common::printXML(_('Error al crear cliente'));
            }

            $account->accountCustomerId = $customer->customerLastId;
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
        $account->accountUserGroupsId = $frmUGroups;

        // Crear cuenta
        if ($account->createAccount()) {
            SP_Common::printXML(_('Cuenta creada'), 0);
        }
        SP_Common::printXML(_('Error al crear la cuenta'), 0);
        break;
    case 2:
        $customer->customerId = $frmSelCustomer;
        $customer->customerName = $frmNewCustomer;
        $account->accountId = $frmAccountId;
        $account->accountName = $frmName;
        $account->accountCategoryId = $frmCategoryId;
        $account->accountLogin = $frmLogin;
        $account->accountUrl = $frmUrl;
        $account->accountNotes = $frmNotes;
        $account->accountUserEditId = $userId;
        $account->accountUserGroupsId = $frmUGroups;

        // Comprobar si se ha introducido un nuevo cliente
        if ($frmNewCustomer) {
            if (!$customer->chekDupCustomer()) {
                SP_Common::printXML(_('Cliente duplicado'));
            }

            if (!$customer->customerAdd()) {
                SP_Common::printXML(_('Error al crear cliente'));
            }

            $account->accountCustomerId = $customer->customerLastId;
        } else {
            $account->accountCustomerId = $frmSelCustomer;
        }

        // Comprobar si han habido cambios
        if ($frmChangesHash == $account->calcChangesHash()) {
            SP_Common::printXML(_('Sin cambios'), 0);
        }

        // Actualizar cuenta
        if ($account->updateAccount()) {
            SP_Common::printXML(_('Cuenta actualizada'), 0);
        }
        SP_Common::printXML(_('Error al modificar la cuenta'));
        break;
    case 3:
        $account->accountId = $frmAccountId;

        // Eliminar cuenta
        if ($account->deleteAccount()) {
            SP_Common::printXML(_('Cuenta eliminada'), 0);
        }
        SP_Common::printXML(_('Error al eliminar la cuenta'));
        break;
    case 4:
        $account->accountId = $frmAccountId;
        $account->accountPass = $accountPass;
        $account->accountIV = $accountIV;
        $account->accountUserEditId = $userId;

        // Actualizar clave de cuenta
        if ($account->updateAccountPass()) {
            SP_Common::printXML(_('Clave actualizada'), 0);
        }
        SP_Common::printXML(_('Error al actualizar la clave'));
        break;
    default:
        SP_Common::printXML(_('Acción Inválida'));
}