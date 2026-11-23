<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
 */

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$candidate = realpath($root . $requestPath);
$staticRoots = [
    $root . DIRECTORY_SEPARATOR . 'public',
    $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'web'
        . DIRECTORY_SEPARATOR . 'themes',
];

$isStaticFile = false;

if ($candidate !== false && is_file($candidate)) {
    foreach ($staticRoots as $staticRoot) {
        if ($candidate === $staticRoot || str_starts_with($candidate, $staticRoot . DIRECTORY_SEPARATOR)) {
            $isStaticFile = true;
            break;
        }
    }
}

if ($requestPath !== '/' && $isStaticFile) {
    return false;
}

$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $root . '/index.php';
