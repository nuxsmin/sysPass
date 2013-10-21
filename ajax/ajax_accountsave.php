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

if (!isset($_POST["sk"]) || !SP_Common::checkSessionKey($_POST["sk"])) {
    SP_Common::printXML(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$frmSaveType = ( isset($_POST["savetyp"]) ) ? (int) $_POST["savetyp"] : 0;
$frmAccountId = ( isset($_POST["accountid"]) ) ? (int) $_POST["accountid"] : 0;
$frmSelCustomer = ( isset($_POST["customerId"]) ) ? (int) $_POST["customerId"] : 0;
$frmNewCustomer = ( isset($_POST["customer_new"]) ) ? SP_Html::sanitize($_POST["customer_new"]) : "";
$frmName = ( isset($_POST["name"]) ) ? SP_Html::sanitize($_POST["name"]) : "";
$frmLogin = ( isset($_POST["login"]) ) ? SP_Html::sanitize($_POST["login"]) : "";
$frmPassword = ( isset($_POST["password"]) ) ? $_POST["password"] : "";
$frmPasswordV = ( isset($_POST["password2"]) ) ? $_POST["password2"] : "";
$frmCategoryId = ( isset($_POST["categoryId"]) ) ? (int) $_POST["categoryId"] : 0;
$frmUGroups = ( isset($_POST["ugroups"]) ) ? $_POST["ugroups"] : "";
$frmNotes = ( isset($_POST["notice"]) ) ? SP_Html::sanitize($_POST["notice"]) : "";
$frmUrl = ( isset($_POST["url"]) ) ? SP_Html::sanitize($_POST["url"]) : "";
$frmChangesHash = ( isset($_POST["hash"]) ) ? SP_Html::sanitize($_POST["hash"]) : "";

// Datos del Usuario
$userId = $_SESSION["uid"];
$groupId = $_SESSION["ugroup"];

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
    $SP_Common::printXML(_('No es una acción válida'));
}

if ($frmSaveType == 1 OR $frmSaveType == 4) {
    $crypt = new SP_Crypt;

    // Comprobar el módulo de encriptación
    if (!SP_Crypt::checkCryptModule()) {
        SP_Common::printXML(_('No se puede usar el módulo de encriptación'));
    }

    // Desencriptar clave maestra
    $mPass = $crypt->decrypt($_SESSION["mPass"], $_SESSION['mPassPwd'], $_SESSION['mPassIV']);

    // Encriptar clave de cuenta
    if (!$crypt->mkPassEncrypt($frmPassword, $mPass)) {
        SP_Common::printXML(_('Error al generar la contraseña cifrada'));
    }

    $pwdCrypt = $crypt->pwdCrypt;
    $strInitialVector = $crypt->strInitialVector;
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
        $account->accountPass = $pwdCrypt;
        $account->accountIV = $strInitialVector;
        $account->accountNotes = $frmNotes;
        $account->accountUserId = $userId;
        $account->accountUserGroupId = $groupId;
        $account->accountUserGroupsId = $frmUGroups;

        // Crear cuenta
        if ($account->createAccount()) {
            SP_Common::printXML(_('Cuenta creada'), 0);
        } else {
            SP_Common::printXML(_('Error al crear la cuenta'), 0);
        }
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
        } else {
            SP_Common::printXML(_('Error al modificar la cuenta'));
        }
        break;
    case 3:
        $account->accountId = $frmAccountId;

        // Eliminar cuenta
        if ($account->deleteAccount()) {
            SP_Common::printXML(_('Cuenta eliminada'), 0);
        } else {
            SP_Common::printXML(_('Error al eliminar la cuenta'));
        }
        break;
    case 4:
        $account->accountId = $frmAccountId;
        $account->accountPass = $pwdCrypt;
        $account->accountIV = $strInitialVector;
        $account->accountUserEditId = $userId;

        // Actualizar clave de cuenta
        if ($account->updateAccountPass()) {
            SP_Common::printXML(_('Clave actualizada'), 0);
        } else {
            SP_Common::printXML(_('Error al actualizar la clave'));
        }

        break;
    default:
        SP_Common::printXML(_('No es una acción válida'));
}