<?php

declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests;

use org\bovigo\vfs\vfsStream;
use RuntimeException;
use SP\Core\UI\ThemeIcons;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\Database\MysqlHandler;
use SP\Infrastructure\File\FileSystem;

use function SP\logger;

define('DEBUG', true);
define('IS_TESTING', true);
define('REAL_APP_ROOT', dirname(__DIR__, 2));

$testDirectory = vfsStream::setup(
    'test',
    755,
    [
        'res' => [
            'cache' => [
                'secure_session' => [],
                'icons.cache' => serialize(new ThemeIcons()),
                'config.cache' => serialize(new ConfigData())
            ]
        ],
        'tmp' => [
            'test.log' => ''
        ],
        'schemas' => [],
        'app' => [
            'locales' => [],
            'modules' => [],
            'resources' => []
        ]
    ]
);

vfsStream::copyFromFileSystem(dirname(__DIR__) . '/res', $testDirectory->getChild('res'));
vfsStream::copyFromFileSystem(REAL_APP_ROOT . '/schemas', $testDirectory->getChild('schemas'));
vfsStream::copyFromFileSystem(REAL_APP_ROOT . '/app/resources', $testDirectory->getChild('app/resources'));

define('TEST_ROOT', $testDirectory->url());
define('APP_ROOT', $testDirectory->getChild('app')->url());
define('RESOURCE_PATH', $testDirectory->getChild('res')->url());
define('TMP_PATH', $testDirectory->getChild('tmp')->url());

define('VIEW_PATH', RESOURCE_PATH . DIRECTORY_SEPARATOR . 'view');
define('CACHE_PATH', RESOURCE_PATH . DIRECTORY_SEPARATOR . 'cache');
define('CONFIG_PATH', RESOURCE_PATH . DIRECTORY_SEPARATOR . 'config');
define('LOCALES_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'locales');
define('MODULES_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'modules');
define('BACKUP_PATH', TMP_PATH);
define('PLUGINS_PATH', TMP_PATH);

define('CONFIG_FILE', CONFIG_PATH . DIRECTORY_SEPARATOR . 'config.xml');
define('ACTIONS_FILE', CONFIG_PATH . DIRECTORY_SEPARATOR . 'actions.xml');
define('MIMETYPES_FILE', CONFIG_PATH . DIRECTORY_SEPARATOR . 'mime.xml');
define('SQL_PATH', TEST_ROOT . DIRECTORY_SEPARATOR . 'schemas');
define('PUBLIC_PATH', TEST_ROOT . DIRECTORY_SEPARATOR . 'public');
define('XML_SCHEMA', TEST_ROOT . DIRECTORY_SEPARATOR . 'schemas' . DIRECTORY_SEPARATOR . 'syspass.xsd');
define('LOG_FILE', TMP_PATH . DIRECTORY_SEPARATOR . 'test.log');
define('FIXTURE_FILES', [
    RESOURCE_PATH . DIRECTORY_SEPARATOR . 'datasets' . DIRECTORY_SEPARATOR . 'truncate.sql',
    RESOURCE_PATH . DIRECTORY_SEPARATOR . 'datasets' . DIRECTORY_SEPARATOR . 'syspass.sql',
]);
define('SELF_IP_ADDRESS', getRealIpAddress());
define('SELF_HOSTNAME', gethostbyaddr(SELF_IP_ADDRESS));

require_once REAL_APP_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once REAL_APP_ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'BaseFunctions.php';

logger('APP_ROOT=' . APP_ROOT);
logger('TEST_ROOT=' . TEST_ROOT);
logger('SELF_IP_ADDRESS=' . SELF_IP_ADDRESS);

if (is_dir(CONFIG_PATH)
    && decoct(fileperms(CONFIG_PATH) & 0777) !== '750'
) {
    print 'Setting permissions for ' . CONFIG_PATH . PHP_EOL;

    chmod(CONFIG_PATH, 0750);
}

/**
 * Función para llamadas a gettext
 */
if (!function_exists('\gettext')) {
    function gettext(string $str): string
    {
        return $str;
    }
}

function getRealIpAddress(): string
{
    return trim(shell_exec('ip a s eth0 | awk \'$1 == "inet" {print $2}\' | cut -d"/" -f1')) ?: '127.0.0.1';
}

function getDbHandler(?DatabaseConnectionData $connectionData = null): MysqlHandler
{
    if ($connectionData === null) {
        // Establecer configuración de conexión con la BBDD
        $connectionData = DatabaseConnectionData::getFromEnvironment();
    }

    return new MysqlHandler($connectionData);
}

function getResource(string $dir, string $file): string
{
    return file_get_contents(RESOURCE_PATH . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file) ?: '';
}

function saveResource(string $dir, string $file, string $data): bool|int
{
    return file_put_contents(RESOURCE_PATH . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file, $data);
}

/**
 * @throws FileNotFoundException
 */
function recreateDir(string $dir): void
{
    if (is_dir($dir)) {
        logger('Deleting ' . $dir);

        FileSystem::rmdirRecursive($dir);
    }

    logger('Creating ' . $dir);

    if (!mkdir($dir) && !is_dir($dir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
    }
}
