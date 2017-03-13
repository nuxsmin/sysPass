<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Config\Config;
use SP\Core\CryptPKI;
use SP\Core\Init;
use SP\Core\Session;
use SP\Http\Cookies;
use SP\Http\Request;
use SP\Http\Response;
use SP\Util\Checks;

define('APP_ROOT', '..');

require APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';
require APP_ROOT . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'strings.js.php';

Request::checkReferer('GET');

$Config = Config::getConfig();

$data = [
    'lang' => $stringsJsLang,
    'locale' => $Config->getSiteLang(),
    'app_root' => Init::$WEBURI,
    'pk' => '',
    'max_file_size' => $Config->getFilesAllowedSize(),
    'check_updates' => Session::getAuthCompleted() && ($Config->isCheckUpdates() || $Config->isChecknotices()) && (Session::getUserData()->isUserIsAdminApp() || Checks::demoIsEnabled()),
    'timezone' => date_default_timezone_get(),
    'debug' => DEBUG || $Config->isDebug(),
    'cookies_enabled' => Cookies::checkCookies()
];

try {
    $CryptPKI = new CryptPKI();
    $data['pk'] = Session::getPublicKey() ?: $CryptPKI->getPublicKey();
} catch (Exception $e) {
}

Response::printJson($data, 0);