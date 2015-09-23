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
use SP\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$accountId = SP\Request::analyze('accountid', false);
$isHistory = SP\Request::analyze('isHistory', false);

if (!$accountId) {
    return;
}

$account = (!$isHistory) ? new SP\Account() : new SP\AccountHistory();

$account->setAccountParentId(\SP\Session::getAccountParentId());
$account->setAccountId($accountId);

$accountData = $account->getAccountPassData();

if ($isHistory && !$account->checkAccountMPass()) {
    SP\Common::printJSON(_('La clave maestra no coincide'));
}

if (!SP\Acl::checkAccountAccess(SP\Acl::ACTION_ACC_VIEW_PASS, $account->getAccountDataForACL()) || !SP\Acl::checkUserAccess(SP\Acl::ACTION_ACC_VIEW_PASS)) {
    SP\Common::printJSON(_('No tiene permisos para acceder a esta cuenta'));
} elseif (!UserUtil::checkUserUpdateMPass()) {
    SP\Common::printJSON(_('Clave maestra actualizada') . '<br>' . _('Reinicie la sesión para cambiarla'));
}

$accountClearPass = SP\Crypt::getDecrypt($accountData->pass, $accountData->iv);

if (!$isHistory) {
    $account->incrementDecryptCounter();

    $log = new \SP\Log(_('Ver Clave'));
    $log->addDescription(_('ID') . ': ' . $accountId);
    $log->addDescription(_('Cuenta') . ': ' . $accountData->customer_name . " / " . $accountData->name);
    $log->writeLog();
}

//$accountPass = htmlspecialchars(trim($accountClearPass));

$useImage = intval(\SP\Util::accountPassToImageIsEnabled());

$data = array(
    'title' => _('Clave de Cuenta'),
    'acclogin' => $accountData->login,
    'accpass' => (!$useImage) ? trim($accountClearPass) : \SP\ImageUtil::convertText($accountClearPass),
    'useimage' => $useImage
);

SP\Common::printJSON($data, 0);