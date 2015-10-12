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

define('APP_ROOT', '..');

require APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';
require APP_ROOT . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'strings.js.php';

Request::checkReferer('GET');

$CryptPKI = new \SP\Core\CryptPKI();
$publicKey = (\SP\Core\Session::getPublicKey()) ? \SP\Core\Session::getPublicKey() : $CryptPKI->getPublicKey();

$data = array(
    'lang' => $stringsJsLang,
    'app_root' => \SP\Core\Init::$WEBURI,
    'pk' => $publicKey
);

\SP\Http\Response::printJSON($data, 0);