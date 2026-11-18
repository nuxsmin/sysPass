<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Controllers\Error;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Real HTTP redirect-chain characterization tests executed against an isolated
 * PHP built-in web server (`php -S`) without modifying repository config files.
 */
#[Group('integration')]
final class HttpInitializationRedirectTest extends TestCase
{
    private static string $tempDir = '';
    private static string $hostPort = '';
    /** @var resource|null */
    private static $serverProcess = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$tempDir = sys_get_temp_dir() . '/sp-http-redirect-' . bin2hex(random_bytes(6));
        mkdir(self::$tempDir . '/cache', 0777, true);
        mkdir(self::$tempDir . '/sessions', 0777, true);
        mkdir(self::$tempDir . '/config', 0777, true);

        $probe = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        self::assertNotFalse($probe, 'Failed to allocate ephemeral port: ' . $errstr);
        $boundName = stream_socket_get_name($probe, false);
        fclose($probe);

        self::assertIsString($boundName);
        self::$hostPort = $boundName;

        $routerPath = self::$tempDir . '/router.php';
        file_put_contents($routerPath, self::buildRouterScript(REAL_APP_ROOT, self::$tempDir));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', self::$tempDir . '/server-stdout.log', 'a'],
            2 => ['file', self::$tempDir . '/server-stderr.log', 'a'],
        ];

        $env = array_merge($_ENV, getenv());
        $env['CONFIG_PATH'] = self::$tempDir . '/config';
        $env['CONFIG_FILE'] = self::$tempDir . '/config/config.xml';
        $env['LOG_FILE'] = self::$tempDir . '/syspass.log';
        unset($env['ACTIONS_FILE'], $env['MIMETYPES_FILE']);

        $process = proc_open(
            [PHP_BINARY, '-d', 'display_errors=0', '-d', 'error_reporting=24575', '-S', self::$hostPort, $routerPath],
            $descriptors,
            $pipes,
            REAL_APP_ROOT,
            $env
        );
        self::assertIsResource($process, 'Failed to start php -S subprocess');
        if (isset($pipes[0]) && is_resource($pipes[0])) {
            fclose($pipes[0]);
        }

        self::$serverProcess = $process;

        $ready = false;
        $deadline = microtime(true) + 5.0;
        while (microtime(true) < $deadline) {
            $sock = @stream_socket_client('tcp://' . self::$hostPort, $eNo, $eStr, 0.2);
            if ($sock !== false) {
                fclose($sock);
                $ready = true;
                break;
            }
            usleep(50000);
        }

        self::assertTrue($ready, 'php -S failed to listen on ' . self::$hostPort);
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProcess)) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
            self::$serverProcess = null;
        }

        if (self::$tempDir !== '' && is_dir(self::$tempDir)) {
            self::removeDirRecursive(self::$tempDir);
        }

        parent::tearDownAfterClass();
    }

    #[Test]
    public function uninstalledRouteFollowsReal302RedirectToInstallPage(): void
    {
        $hop1 = $this->httpGet('/index.php?r=account/search', 'uninstalled');
        $stderr = @file_get_contents(self::$tempDir . '/server-stderr.log');
        self::assertSame(
            302,
            $hop1['status'],
            'Expected HTTP 302 from Init checkInstalled. Headers: ' . json_encode($hop1['headers'])
                . ' Body: ' . substr($hop1['body'], 0, 500)
                . ' Stderr: ' . substr((string) $stderr, 0, 800)
        );
        $location = $hop1['headers']['location'] ?? '';
        self::assertStringContainsString('r=' . rawurlencode('install'), $location);

        $hop2Target = $this->locationToPathAndQuery($location);
        $hop2 = $this->httpGet($hop2Target, 'uninstalled');
        self::assertSame(200, $hop2['status']);
        self::assertStringContainsString('id="frmInstall"', $hop2['body']);
    }

    #[Test]
    public function dbConnectionFailureFollowsReal302RedirectToDatabaseConnectionPage(): void
    {
        $hop1 = $this->httpGet('/index.php?r=account/search', 'db_connection_fail');
        self::assertSame(302, $hop1['status']);
        $location = $hop1['headers']['location'] ?? '';
        self::assertStringContainsString('r=' . rawurlencode('error/databaseConnection'), $location);

        $hop2 = $this->httpGet($this->locationToPathAndQuery($location), 'db_connection_fail');
        self::assertSame(200, $hop2['status']);
        self::assertStringContainsString('Unable to connect to DB', $hop2['body']);
    }

    #[Test]
    public function dbTablesFailureFollowsReal302RedirectToDatabaseErrorPage(): void
    {
        $hop1 = $this->httpGet('/index.php?r=account/search', 'db_tables_fail');
        self::assertSame(302, $hop1['status']);
        $location = $hop1['headers']['location'] ?? '';
        self::assertStringContainsString('r=' . rawurlencode('error/databaseError'), $location);

        $hop2 = $this->httpGet($this->locationToPathAndQuery($location), 'db_tables_fail');
        self::assertSame(200, $hop2['status']);
        self::assertStringContainsString('Error while checking the database', $hop2['body']);
    }

    /**
     * Characterization of MaintenanceErrorController missing from Init::PARTIAL_INIT:
     * Over real HTTP, requesting any non-partial route or error/maintenanceError itself
     * repeatedly returns 302 Location: ...?r=error%2FmaintenanceError in a redirect loop.
     */
    #[Test]
    public function maintenanceModeTriggersRealHttpSelfRedirectLoop(): void
    {
        $hop1 = $this->httpGet('/index.php?r=account/search', 'maintenance_loop');
        self::assertSame(302, $hop1['status']);
        $loc1 = $hop1['headers']['location'] ?? '';
        self::assertStringContainsString('r=' . rawurlencode('error/maintenanceError'), $loc1);

        $hop2 = $this->httpGet($this->locationToPathAndQuery($loc1), 'maintenance_loop');
        self::assertSame(302, $hop2['status']);
        $loc2 = $hop2['headers']['location'] ?? '';
        self::assertStringContainsString('r=' . rawurlencode('error/maintenanceError'), $loc2);

        $hop3 = $this->httpGet($this->locationToPathAndQuery($loc2), 'maintenance_loop');
        self::assertSame(302, $hop3['status']);
        $loc3 = $hop3['headers']['location'] ?? '';
        self::assertStringContainsString('r=' . rawurlencode('error/maintenanceError'), $loc3);
    }

    /**
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function httpGet(string $pathAndQuery, string $scenario): array
    {
        $client = @stream_socket_client('tcp://' . self::$hostPort, $errno, $errstr, 5.0);
        self::assertNotFalse($client, 'HTTP socket connect failed: ' . $errstr);

        stream_set_timeout($client, 5);

        $request = "GET {$pathAndQuery} HTTP/1.1\r\n"
            . 'Host: ' . self::$hostPort . "\r\n"
            . "X-Test-Scenario: {$scenario}\r\n"
            . "Connection: close\r\n\r\n";

        fwrite($client, $request);

        $raw = '';
        while (!feof($client)) {
            $chunk = fread($client, 8192);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $raw .= $chunk;
        }
        fclose($client);

        [$headerBlock, $body] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');
        $headerLines = explode("\r\n", $headerBlock);
        $statusLine = array_shift($headerLines) ?? '';
        preg_match('#^HTTP/\d+\.\d+\s+(\d+)#', $statusLine, $matches);
        $status = isset($matches[1]) ? (int) $matches[1] : 0;

        $headers = [];
        foreach ($headerLines as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        if (($headers['transfer-encoding'] ?? '') === 'chunked') {
            $body = self::decodeChunked($body);
        }

        return [
            'status' => $status,
            'headers' => $headers,
            'body' => $body,
        ];
    }

    private function locationToPathAndQuery(string $location): string
    {
        $parts = parse_url($location);
        $path = $parts['path'] ?? '/index.php';
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';

        return $path . $query;
    }

    private static function decodeChunked(string $data): string
    {
        $decoded = '';
        $offset = 0;
        $len = strlen($data);
        while ($offset < $len) {
            $pos = strpos($data, "\r\n", $offset);
            if ($pos === false) {
                break;
            }
            $chunkSize = (int) hexdec(trim(substr($data, $offset, $pos - $offset)));
            $offset = $pos + 2;
            if ($chunkSize === 0) {
                break;
            }
            $decoded .= substr($data, $offset, $chunkSize);
            $offset += $chunkSize + 2;
        }

        return $decoded;
    }

    private static function buildRouterScript(string $appRoot, string $tempDir): string
    {
        return sprintf(
            <<<'PHP'
<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use Klein\Exceptions\LockedResponseException;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Definitions\ContainerFactory;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Web\Bootstrap;
use function DI\add;
use function DI\decorate;
use function SP\initModule;

define('APP_ROOT', %s);
define('APP_MODULE', 'web');
define('DEBUG', false);

require APP_ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'bootstrap.php';

define('APP_PATH', FileSystem::buildPath(APP_ROOT, 'app'));

require APP_ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'BaseFunctions.php';

$tempDir = %s;
ini_set('session.save_path', $tempDir . '/sessions');

$dotenv = Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

$scenario = $_SERVER['HTTP_X_TEST_SCENARIO'] ?? 'uninstalled';
$configData = new ConfigData();
$configData->setSiteLang('en_US');
$configData->setSiteTheme('material-blue');
$configData->setPasswordSalt('synthetic-http-redirect-salt-2026');
$configData->setDbHost('127.0.0.1');
$configData->setDbPort(1);
$configData->setDbName('syspass_synthetic');
$configData->setDbUser('sp_synthetic');
$configData->setDbPass('sp_synthetic_pass');
$configData->setInstalled($scenario !== 'uninstalled');
$configData->setMaintenance($scenario === 'maintenance_loop');

$configFileService = new class ($configData) implements ConfigFileService {
    public function __construct(private ConfigDataInterface $data)
    {
    }
    public function getConfigData(): ConfigDataInterface
    {
        return $this->data;
    }
    public function reload(): ConfigDataInterface
    {
        return $this->data;
    }
    public function save(ConfigDataInterface $configData, ?bool $commit = true): ConfigFileService
    {
        $this->data = $configData;
        return $this;
    }
    public function generateUpgradeKey(): ConfigFileService
    {
        return $this;
    }
};

$dbConnectionOk = in_array($scenario, ['db_tables_fail', 'maintenance_loop'], true);
$dbTablesOk = $scenario === 'maintenance_loop';
$databaseUtil = new class ($dbConnectionOk, $dbTablesOk) implements DatabaseUtilInterface {
    public function __construct(private bool $conn, private bool $tables)
    {
    }
    public function checkDatabaseConnection(): bool
    {
        return $this->conn;
    }
    public function checkDatabaseTables(string $dbName): bool
    {
        return $this->tables;
    }
    public function getDBinfo(): array
    {
        return [];
    }
    public function escape(string $str): string
    {
        return addslashes($str);
    }
};

$appLock = new class implements AppLockHandler {
    public function getLock(): bool|int
    {
        return false;
    }
    public function lock(int $userId, string $subject): void
    {
    }
    public function tryLock(int $userId, string $subject): bool
    {
        return true;
    }
    public function unlock(): void
    {
    }
};

$moduleDefinitions = initModule(APP_MODULE);
$builder = ContainerFactory::createBuilder(APP_ROOT, APP_MODULE, $moduleDefinitions, null);
$builder->addDefinitions([
    PathsContext::class => decorate(static function (PathsContext $pathsContext) use ($tempDir): PathsContext {
        if ($pathsContext->offsetExists(Path::CACHE)) {
            $pathsContext->offsetUnset(Path::CACHE);
        }
        $pathsContext->addPath(Path::CACHE, $tempDir . '/cache');

        if ($pathsContext->offsetExists(Path::LOG_FILE)) {
            $pathsContext->offsetUnset(Path::LOG_FILE);
        }
        $pathsContext->addPath(Path::LOG_FILE, $tempDir . '/syspass.log');

        return $pathsContext;
    }),
    ConfigFileService::class => $configFileService,
    ConfigDataInterface::class => $configData,
    DatabaseUtilInterface::class => $databaseUtil,
    AppLockHandler::class => $appLock,
]);

$dic = $builder->build();

try {
    Bootstrap::run($dic->get(BootstrapInterface::class), $dic->get(ModuleInterface::class));
} catch (LockedResponseException) {
    // Init already emitted and locked the 302 Location response.
}
PHP,
            var_export($appRoot, true),
            var_export($tempDir, true)
        );
    }

    private static function removeDirRecursive(string $dir): void
    {
        $items = scandir($dir);
        if (!is_array($items)) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDirRecursive($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
