<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

use SP\Core\Exceptions\SPException;
use SP\Core\Installer;
use SP\DataModel\InstallData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Util\Json;

define('APP_ROOT', '..');
define('IS_INSTALLER', 1);

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkReferer('POST');

$Json = new JsonResponse();

$InstallData = new InstallData();
$InstallData->setSiteLang(Request::analyze('sitelang', 'en_US'));
$InstallData->setAdminLogin(Request::analyze('adminlogin', 'admin'));
$InstallData->setAdminPass(Request::analyzeEncrypted('adminpass'));
$InstallData->setMasterPassword(Request::analyzeEncrypted('masterpassword'));
$InstallData->setDbAdminUser(Request::analyze('dbuser', 'root'));
$InstallData->setDbAdminPass(Request::analyzeEncrypted('dbpass'));
$InstallData->setDbName(Request::analyze('dbname', 'syspass'));
$InstallData->setDbHost(Request::analyze('dbhost', 'localhost'));
$InstallData->setHostingMode(Request::analyze('hostingmode', false));

try {
    $Installer = new Installer($InstallData);
    $Installer->checkData();
    $Installer->install();

    $Json->setStatus(0);
    $Json->setDescription(__('Instalación finalizada'));
} catch (SPException $e) {
    $Json->setDescription($e->getMessage());
    $Json->addMessage($e->getHint());
}

Json::returnJson($Json);
