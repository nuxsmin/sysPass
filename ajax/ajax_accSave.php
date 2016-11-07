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
use SP\Forms\AccountForm;
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\ValidationException;
use SP\Core\Init;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\DataModel\AccountExtData;
use SP\DataModel\CustomFieldData;
use SP\Http\Request;
use SP\Http\Response;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\CustomFields\CustomFieldsUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!Init::isLoggedIn()) {
    Response::printJson(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    Response::printJson(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$actionId = Request::analyze('actionId', 0);
$accountId = Request::analyze('accountId', 0);
$customerId = Request::analyze('customerId', 0);
$categoryId = Request::analyze('categoryId', 0);
$accountMainGroupId = Request::analyze('mainGroupId', 0);
$accountName = Request::analyze('name');
$accountLogin = Request::analyze('login');
$accountPassword = Request::analyzeEncrypted('pass');
$accountNotes = Request::analyze('notes');
$accountUrl = Request::analyze('url');
$accountPassDateChange = Request::analyze('passworddatechange_unix', 0);

// Checks
$accountGroupEditEnabled = Request::analyze('groupEditEnabled', 0, false, 1);
$accountUserEditEnabled = Request::analyze('userEditEnabled', 0, false, 1);
$accountPrivateEnabled = Request::analyze('privateEnabled', 0, false, 1);

// Arrays
$accountOtherGroups = Request::analyze('otherGroups', 0);
$accountOtherUsers = Request::analyze('otherUsers', 0);
$accountTags = Request::analyze('tags');
$customFields = Request::analyze('customfield');

if ($accountMainGroupId === 0) {
    $accountMainGroupId = Session::getUserGroupId();
}

$AccountData = new AccountExtData();
$AccountData->setAccountId($accountId);
$AccountData->setAccountName($accountName);
$AccountData->setAccountCustomerId($customerId);
$AccountData->setAccountCategoryId($categoryId);
$AccountData->setAccountLogin($accountLogin);
$AccountData->setAccountUrl($accountUrl);
$AccountData->setAccountNotes($accountNotes);
$AccountData->setAccountUserEditId(Session::getUserId());
$AccountData->setAccountOtherUserEdit($accountUserEditEnabled);
$AccountData->setAccountOtherGroupEdit($accountGroupEditEnabled);
$AccountData->setAccountPass($accountPassword);
$AccountData->setAccountIsPrivate($accountPrivateEnabled);
$AccountData->setAccountPassDateChange($accountPassDateChange);

if (is_array($accountOtherUsers)) {
    $AccountData->setUsersId($accountOtherUsers);
}

if (is_array($accountOtherGroups)) {
    $AccountData->setUserGroupsId($accountOtherGroups);
}

if (is_array($accountTags)) {
    $AccountData->setTags($accountTags);
}

try {
    $AccountForm = new AccountForm($AccountData);
    $AccountForm->validate($actionId);
} catch (ValidationException $e) {
    Response::printJson($e->getMessage());
}

$CustomFieldData = new CustomFieldData();
$CustomFieldData->setId($accountId);
$CustomFieldData->setModule(ActionsInterface::ACTION_ACC);

$Account = new Account($AccountData);

switch ($actionId) {
    case ActionsInterface::ACTION_ACC_NEW:
    case ActionsInterface::ACTION_ACC_COPY:
        $AccountData->setAccountUserId(Session::getUserId());
        $AccountData->setAccountUserGroupId($accountMainGroupId);

        // Crear cuenta
        if ($Account->createAccount()) {
            if (is_array($customFields)) {
                $CustomFieldData->setId($AccountData->getAccountId());
                CustomFieldsUtil::addItemCustomFields($customFields, $CustomFieldData);
            }

            Response::printJson(_('Cuenta creada'), 0);
        }

        Response::printJson(_('Error al crear la cuenta'), 0);
        break;
    case ActionsInterface::ACTION_ACC_EDIT:
        // Cambiar el grupo principal si el usuario es Admin
        if (Session::getUserIsAdminApp() || Session::getUserIsAdminAcc()) {
            $AccountData->setAccountUserGroupId($accountMainGroupId);
        }

        // Actualizar cuenta
        if ($Account->updateAccount()) {
            if (is_array($customFields)) {
                CustomFieldsUtil::updateItemCustomFields($customFields, $CustomFieldData);
            }

            Response::printJson(_('Cuenta actualizada'), 0);
        }

        Response::printJson(_('Error al modificar la cuenta'));
        break;
    case ActionsInterface::ACTION_ACC_DELETE:
        // Eliminar cuenta
        if ($Account->deleteAccount()
            && CustomField::getItem($CustomFieldData)->delete($accountId)
        ) {
            Response::printJson(_('Cuenta eliminada'), 0);
        }

        Response::printJson(_('Error al eliminar la cuenta'));
        break;
    case ActionsInterface::ACTION_ACC_EDIT_PASS:
        // Actualizar clave de cuenta
        if ($Account->updateAccountPass()) {
            Response::printJson(_('Clave actualizada'), 0);
        }

        Response::printJson(_('Error al actualizar la clave'));
        break;
    case ActionsInterface::ACTION_ACC_EDIT_RESTORE:
        $AccountData->setAccountId(\SP\Account\AccountHistory::getAccountIdFromId($accountId));

        if ($Account->restoreFromHistory($accountId)) {
            Response::printJson(_('Cuenta restaurada'), 0);
        }

        Response::printJson(_('Error al restaurar cuenta'));

        break;
    default:
        Response::printJson(_('Acción Inválida'));
}