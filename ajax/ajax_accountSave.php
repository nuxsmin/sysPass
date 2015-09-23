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
$accountPassword = SP\Request::analyze('pass', '', false, false, false);
$accountPasswordR = SP\Request::analyze('passR', '', false, false, false);
$categoryId = SP\Request::analyze('categoryId', 0);
$accountOtherGroups = SP\Request::analyze('othergroups');
$accountOtherUsers = SP\Request::analyze('otherusers');
$accountNotes = SP\Request::analyze('notes');
$accountUrl = SP\Request::analyze('url');
$accountGroupEditEnabled = SP\Request::analyze('geditenabled', 0, false, 1);
$accountUserEditEnabled = SP\Request::analyze('ueditenabled', 0, false, 1);
$accountMainGroupId = SP\Request::analyze('mainGroupId', 0);
$accountChangesHash = SP\Request::analyze('hash');
$customFields = SP\Request::analyze('customfield');

// Datos del Usuario
$currentUserId = SP\Session::getUserId();

if ($accountMainGroupId === 0) {
    $accountMainGroupId = SP\Session::getUserGroupId();
}

if ($actionId === \SP\Controller\ActionsInterface::ACTION_ACC_NEW) {
    // Comprobaciones para nueva cuenta
    if (!$accountName) {
        SP\Common::printJSON(_('Es necesario un nombre de cuenta'));
    } elseif (!$customerId && !$newCustomer) {
        SP\Common::printJSON(_('Es necesario un nombre de cliente'));
    } elseif (!$accountLogin) {
        SP\Common::printJSON(_('Es necesario un usuario'));
    } elseif (!$accountPassword || !$accountPasswordR) {
        SP\Common::printJSON(_('Es necesaria una clave'));
    } elseif (!$categoryId) {
        SP\Common::printJSON(_('Es necesario una categoría'));
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_ACC_EDIT) {
    // Comprobaciones para modificación de cuenta
    if (!$customerId && !$newCustomer) {
        SP\Common::printJSON(_('Es necesario un nombre de cliente'));
    } elseif (!$accountName) {
        SP\Common::printJSON(_('Es necesario un nombre de cuenta'));
    } elseif (!$accountLogin) {
        SP\Common::printJSON(_('Es necesario un usuario'));
    } elseif (!$categoryId) {
        SP\Common::printJSON(_('Es necesario una categoría'));
    }
} elseif ($actionId === \SP\Controller\ActionsInterface::ACTION_ACC_DELETE) {
    if (!$accountId) {
        SP\Common::printJSON(_('Id inválido'));
    }
} elseif ($actionId == \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_PASS) {
    // Comprobaciones para modficación de clave
    if (!$accountPassword || !$accountPasswordR) {
        SP\Common::printJSON(_('Es necesaria una clave'));
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
    // Desencriptar con la clave RSA
    $CryptPKI = new \SP\CryptPKI();
    $clearAccountPass = $CryptPKI->decryptRSA(base64_decode($accountPassword));
    $clearAccountPassR = $CryptPKI->decryptRSA(base64_decode($accountPasswordR));

    if ($clearAccountPass != $clearAccountPassR) {
        SP\Common::printJSON(_('Las claves no coinciden'));
    }

    // Encriptar clave de cuenta
    try {
        $accountEncPass = SP\Crypt::encryptData($clearAccountPass);
    } catch (\SP\SPException $e) {
        SP\Common::printJSON($e->getMessage());
    }
}

$Account = new SP\Account;

switch ($actionId) {
    case \SP\Controller\ActionsInterface::ACTION_ACC_NEW:
        SP\Customer::$customerName = $newCustomer;

        // Comprobar si se ha introducido un nuevo cliente
        if ($newCustomer) {
            try {
                SP\Customer::addCustomer();
                $customerId = SP\Customer::$customerLastId;
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage());
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
                    $CustomFields = new \SP\CustomFields($id, $Account->getAccountId(), $value);
                    $CustomFields->addCustomField();
                }
            }

            SP\Common::printJSON(_('Cuenta creada'), 0);
        }

        SP\Common::printJSON(_('Error al crear la cuenta'), 0);
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_EDIT:
        SP\Customer::$customerName = $newCustomer;

        // Comprobar si se ha introducido un nuevo cliente
        if ($newCustomer) {
            try {
                SP\Customer::addCustomer();
                $customerId = SP\Customer::$customerLastId;
            } catch (\SP\SPException $e) {
                SP\Common::printJSON($e->getMessage());
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
        if (SP\Session::getUserIsAdminApp() || SP\Session::getUserIsAdminAcc()) {
            $Account->setAccountUserGroupId($accountMainGroupId);
        }

        // Comprobar si han habido cambios
        if ($accountChangesHash == $Account->calcChangesHash()) {
            SP\Common::printJSON(_('Sin cambios'), 0);
        }

        // Actualizar cuenta
        if ($Account->updateAccount()) {
            if (is_array($customFields)) {
                foreach ($customFields as $id => $value) {
                    $CustomFields = new \SP\CustomFields($id, $accountId, $value);
                    $CustomFields->updateCustomField();
                }
            }

            SP\Common::printJSON(_('Cuenta actualizada'), 0);
        }

        SP\Common::printJSON(_('Error al modificar la cuenta'));
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_DELETE:
        $Account->setAccountId($accountId);

        // Eliminar cuenta
        if ($Account->deleteAccount() && \SP\CustomFields::deleteCustomFieldForItem($accountId, \SP\Controller\ActionsInterface::ACTION_ACC_NEW)) {
            SP\Common::printJSON(_('Cuenta eliminada'), 0, "sysPassUtil.Common.doAction('" . \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH . "');");
        }

        SP\Common::printJSON(_('Error al eliminar la cuenta'));
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_PASS:
        $Account->setAccountId($accountId);
        $Account->setAccountPass($accountEncPass['data']);
        $Account->setAccountIV($accountEncPass['iv']);
        $Account->setAccountUserEditId($currentUserId);

        // Actualizar clave de cuenta
        if ($Account->updateAccountPass()) {
            SP\Common::printJSON(_('Clave actualizada'), 0);
        }

        SP\Common::printJSON(_('Error al actualizar la clave'));
        break;
    case \SP\Controller\ActionsInterface::ACTION_ACC_EDIT_RESTORE:
        $Account->setAccountId(SP\AccountHistory::getAccountIdFromId($accountId));
        $Account->setAccountUserEditId($currentUserId);

        if ($Account->restoreFromHistory($accountId)) {
            SP\Common::printJSON(_('Cuenta restaurada'), 0);
        }

        SP\Common::printJSON(_('Error al restaurar cuenta'));

        break;
    default:
        SP\Common::printJSON(_('Acción Inválida'));
}