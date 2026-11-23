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

namespace SP\Tests\Modules\Web\Controllers\PublicLink;

use DI\ContainerBuilder;
use Klein\Request;
use Klein\Response;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Core\UI\ThemeContext;
use SP\Domain\Account\Dtos\PublicLinkKey;
use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Domain\Account\Services\PublicLink as PublicLinkServiceImpl;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Database\Dtos\DatabaseConnectionData;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Web\Bootstrap;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\Generators\AccountDataGenerator;

use function DI\autowire;
use function DI\factory;
use function SP\Tests\getDbHandler;

/**
 * DB-backed characterization tests for concurrent last-view Public Link access,
 * persisted failed-decryption counters, missing hashes, and generated URL dispatch.
 */
#[Group('integration')]
final class PublicLinkConcurrencyDbTest extends DatabaseIntegrationTestCase
{
    private const SYNTHETIC_SECRET = 'concurrent-synthetic-secret-2026';
    private const SYNTHETIC_ACCOUNT_NAME = 'Concurrent Synthetic Account';

    /**
     * With atomic addLinkView UPDATE (WHERE hash = :hash AND countViews < maxCountViews
     * AND dateExpire > :now) and return-value checking in ViewLinkController, releasing
     * two concurrent PHP workers at countViews=2 (< maxCountViews=3) lets strictly
     * one visitor win the slot and disclose the secret while the loser sees permission error.
     */
    #[Test]
    public function concurrentLastViewRequestsEnforceAtomicViewLimitAgainstMariaDb(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE PublicLink');
        $pdo->exec('UPDATE Account SET countView = 0, countDecrypt = 0 WHERE id = 1');

        $publicLinkKey = new PublicLinkKey($this->passwordSalt);
        $accountModel = AccountDataGenerator::factory()->buildAccount()->mutate([
            'id' => 1,
            'name' => self::SYNTHETIC_ACCOUNT_NAME,
            'pass' => self::SYNTHETIC_SECRET,
            'userName' => 'Synthetic Owner',
            'userEditName' => 'Synthetic Editor',
            'userEditLogin' => 'synthetic_editor',
            'userGroupName' => 'Synthetic Group',
            'clientName' => 'Synthetic Client',
            'categoryName' => 'Synthetic Category',
            'dateAdd' => '2026-01-01 00:00:00',
        ]);
        $vault = Vault::factory(new Crypt())->saveData(
            Serde::serialize(Simple::buildFromSimpleModel($accountModel)),
            $publicLinkKey->getKey()
        );

        $stmt = $pdo->prepare(
            'INSERT INTO PublicLink (itemId, hash, data, userId, typeId, notify, dateAdd, dateExpire, maxCountViews, countViews, totalCountViews, useInfo)
             VALUES (1, :hash, :data, 1, 1, 0, UNIX_TIMESTAMP(), :dateExpire, 3, 2, 2, :useInfo)'
        );
        $stmt->execute([
            'hash' => $publicLinkKey->getHash(),
            'data' => $vault->getSerialized(),
            'dateExpire' => time() + 600,
            'useInfo' => Serde::serialize([]),
        ]);
        $linkId = (int) $pdo->lastInsertId();

        $runDir = TMP_PATH . DIRECTORY_SEPARATOR . 'pl-race-' . bin2hex(random_bytes(6));
        mkdir($runDir, 0777, true);
        $workerScript = $runDir . DIRECTORY_SEPARATOR . 'worker.php';
        $goFile = $runDir . DIRECTORY_SEPARATOR . 'go.flag';

        file_put_contents($workerScript, $this->buildWorkerScript());

        $results = [];
        $processes = [];
        $pipes = [];

        try {
            for ($i = 0; $i < 2; $i++) {
                $readyFile = $runDir . DIRECTORY_SEPARATOR . 'ready.' . $i;
                $resultFile = $runDir . DIRECTORY_SEPARATOR . 'result.' . $i . '.json';
                $cmd = sprintf(
                    '%s %s %s %s %s %s %s',
                    escapeshellarg(PHP_BINARY),
                    escapeshellarg($workerScript),
                    escapeshellarg($publicLinkKey->getHash()),
                    escapeshellarg($this->passwordSalt),
                    escapeshellarg($readyFile),
                    escapeshellarg($goFile),
                    escapeshellarg($resultFile)
                );

                $spec = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];
                $proc = proc_open($cmd, $spec, $procPipes, REAL_APP_ROOT);
                self::assertIsResource($proc, 'Failed to launch PHP worker ' . $i);
                fclose($procPipes[0]);
                $processes[$i] = $proc;
                $pipes[$i] = $procPipes;
            }

            $deadline = microtime(true) + 15.0;
            while (
                (!file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.0')
                    || !file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.1'))
                && microtime(true) < $deadline
            ) {
                $status0 = proc_get_status($processes[0]);
                $status1 = proc_get_status($processes[1]);
                if (!$status0['running'] && !file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.0')) {
                    break;
                }
                if (!$status1['running'] && !file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.1')) {
                    break;
                }
                usleep(5000);
            }

            if (!file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.0')
                || !file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.1')
            ) {
                $diag = '';
                foreach ($processes as $i => $proc) {
                    $stdout = (string) stream_get_contents($pipes[$i][1]);
                    $stderr = (string) stream_get_contents($pipes[$i][2]);
                    fclose($pipes[$i][1]);
                    fclose($pipes[$i][2]);
                    $code = proc_close($proc);
                    $diag .= "Worker $i (exit $code) stderr: $stderr | stdout: $stdout\n";
                }
                self::fail("Workers failed before barrier:\n" . $diag);
            }

            // Release both workers simultaneously
            file_put_contents($goFile, '1');

            foreach ($processes as $i => $proc) {
                $stdout = (string) stream_get_contents($pipes[$i][1]);
                $stderr = (string) stream_get_contents($pipes[$i][2]);
                fclose($pipes[$i][1]);
                fclose($pipes[$i][2]);
                $exitCode = proc_close($proc);

                $resultFile = $runDir . DIRECTORY_SEPARATOR . 'result.' . $i . '.json';
                self::assertSame(0, $exitCode, "Worker $i exited with $exitCode. stderr: $stderr\nstdout: $stdout");
                self::assertFileExists($resultFile, "Worker $i missing result file. stderr: $stderr\nstdout: $stdout");
                $decoded = json_decode((string) file_get_contents($resultFile), true, 512, JSON_THROW_ON_ERROR);
                $results[$i] = $decoded;
            }
        } finally {
            foreach (glob($runDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($runDir);
        }

        // Independent PDO read-back after both processes finish
        $linkRow = $pdo->query(
            'SELECT countViews, totalCountViews, maxCountViews FROM PublicLink WHERE id = ' . $linkId
        )->fetch(PDO::FETCH_ASSOC);
        $accountRow = $pdo->query(
            'SELECT countView, countDecrypt FROM Account WHERE id = 1'
        )->fetch(PDO::FETCH_ASSOC);

        self::assertIsArray($linkRow);
        self::assertIsArray($accountRow);

        $disclosures = array_filter(
            $results,
            static fn(array $r): bool => $r['code'] === 200 && str_contains($r['body'], self::SYNTHETIC_SECRET)
        );
        $denied = array_filter(
            $results,
            static fn(array $r): bool => str_contains($r['body'], 'permission to access this page')
        );

        $disclosureCount = count($disclosures);
        $summary = json_encode([
            'r0_code' => $results[0]['code'],
            'r0_len' => strlen($results[0]['body']),
            'r0_head' => substr($results[0]['body'], 0, 500),
            'r1_code' => $results[1]['code'],
            'r1_len' => strlen($results[1]['body']),
            'r1_head' => substr($results[1]['body'], 0, 500),
            'linkRow' => $linkRow,
            'accountRow' => $accountRow,
        ]);
        self::assertSame(1, $disclosureCount, 'Strictly one concurrent last-view visitor must succeed: ' . $summary);
        self::assertCount(1, $denied, 'Losing concurrent visitor must receive permission error: ' . $summary);
        self::assertSame(3, (int) $linkRow['countViews']);
        self::assertSame((int) $linkRow['maxCountViews'], (int) $linkRow['countViews']);
        self::assertSame(3, (int) $linkRow['totalCountViews']);
        self::assertSame(1, (int) $accountRow['countView']);
        self::assertSame(1, (int) $accountRow['countDecrypt']);
    }

    /**
     * When vault decryption fails (wrong key), ViewLinkController must not increment
     * PublicLink.countViews, PublicLink.totalCountViews, Account.countView, or Account.countDecrypt.
     */
    #[Test]
    public function viewLinkWithWrongKeyDoesNotIncrementCountersInDbOnFailure(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE PublicLink');
        $pdo->exec('UPDATE Account SET countView = 0, countDecrypt = 0 WHERE id = 1');

        $correctKey = new PublicLinkKey($this->passwordSalt);
        $wrongKey = new PublicLinkKey($this->passwordSalt);
        $payload = Serde::serialize(new Simple(['name' => 'Wrong Key Marker', 'pass' => self::SYNTHETIC_SECRET]));
        $vault = Vault::factory(new Crypt())->saveData($payload, $wrongKey->getKey());

        $stmt = $pdo->prepare(
            'INSERT INTO PublicLink (itemId, hash, data, userId, typeId, notify, dateAdd, dateExpire, maxCountViews, countViews, totalCountViews, useInfo)
             VALUES (1, :hash, :data, 1, 1, 0, UNIX_TIMESTAMP(), :dateExpire, 3, 0, 0, :useInfo)'
        );
        $stmt->execute([
            'hash' => $correctKey->getHash(),
            'data' => $vault->getSerialized(),
            'dateExpire' => time() + 600,
            'useInfo' => Serde::serialize([]),
        ]);
        $linkId = (int) $pdo->lastInsertId();

        $result = $this->dispatch(
            'get',
            'account/viewLink/' . $correctKey->getHash(),
            [],
            [],
            [Context::class => $this->getUnauthenticatedContext()]
        );

        self::assertSame(500, $result['code']);
        self::assertStringNotContainsString(self::SYNTHETIC_SECRET, $result['body']);

        $linkRow = $pdo->query(
            'SELECT countViews, totalCountViews FROM PublicLink WHERE id = ' . $linkId
        )->fetch(PDO::FETCH_ASSOC);
        $accountRow = $pdo->query(
            'SELECT countView, countDecrypt FROM Account WHERE id = 1'
        )->fetch(PDO::FETCH_ASSOC);

        self::assertSame(0, (int) $linkRow['countViews']);
        self::assertSame(0, (int) $linkRow['totalCountViews']);
        self::assertSame(0, (int) $accountRow['countView']);
        self::assertSame(0, (int) $accountRow['countDecrypt']);
    }

    #[Test]
    public function viewLinkWithMissingHashReturns500AgainstRealDb(): void
    {
        $result = $this->dispatch(
            'get',
            'account/viewLink/' . str_repeat('f', 64),
            [],
            [],
            [Context::class => $this->getUnauthenticatedContext()]
        );

        self::assertSame(500, $result['code']);
        self::assertStringContainsString('Link not found', $result['body']);
    }

    /**
     * PublicLink::getLinkForHash() generates ?r=account/viewLink/<hash>, so
     * dispatching the generated URL with a subpath base URL as an anonymous visitor
     * reaches ViewLinkController hash lookup instead of hitting login SessionTimeout.
     */
    #[Test]
    public function generatedPublicLinkWithSubpathBaseUrlTargetsPublicViewerRoute(): void
    {
        $hash = str_repeat('b', 64);
        $url = PublicLinkServiceImpl::getLinkForHash('https://example.test/subpath', $hash);

        self::assertSame('https://example.test/subpath/index.php?r=account/viewLink/' . $hash, $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);

        $anonRequest = self::buildRequest('get', '/subpath/index.php', $params);
        $anonContainer = $this->buildContainer($anonRequest, [
            Context::class => $this->getUnauthenticatedContext(),
        ]);
        ob_start();
        try {
            self::runApp($anonContainer);
        } finally {
            $anonBody = (string) ob_get_clean();
        }
        $anonResponse = $anonContainer->get(Response::class);
        self::assertSame(500, $anonResponse->code());
        self::assertStringContainsString('Link not found', $anonBody !== '' ? $anonBody : (string) $anonResponse->body());
    }

    private function getUnauthenticatedContext(): SessionContext
    {
        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getAuthCompleted')->willReturn(false);
        $context->method('getCSRF')->willReturn(null);
        $context->method('getUserData')->willReturn(new \SP\Domain\User\Dtos\UserDto());

        return $context;
    }

    private function buildWorkerScript(): string
    {
        $template = <<<'PHP'
<?php
declare(strict_types=1);

require '__ROOT__/vendor/autoload.php';
require '__BOOTSTRAP__';

use DI\ContainerBuilder;
use Klein\Request;
use Klein\Response;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Context\Stateless;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Core\UI\ThemeContext;
use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Database\Dtos\DatabaseConnectionData;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Web\Bootstrap;
use function DI\autowire;
use function DI\factory;
use function SP\Tests\getDbHandler;

$hash = $argv[1];
$salt = $argv[2];
$readyFile = $argv[3];
$goFile = $argv[4];
$resultFile = $argv[5];

$moduleDefs = FileSystem::require(FileSystem::buildPath('__ROOT__', 'app', 'modules', 'web', 'module.php'));
$coreDefs = CoreDefinitions::getDefinitions('__ROOT__', 'web');
$domainDefs = DomainDefinitions::getDefinitions();

$connData = DatabaseConnectionData::getFromEnvironment();
$version = Version::getVersionStringNormalized();
$configData = (new ConfigData())
    ->setInstalled(true)
    ->setMaintenance(false)
    ->setAppVersion($version)
    ->setDatabaseVersion($version)
    ->setConfigVersion($version)
    ->setDbHost($connData->getDbHost())
    ->setDbPort($connData->getDbPort())
    ->setDbName($connData->getDbName())
    ->setDbUser($connData->getDbUser())
    ->setDbPass($connData->getDbPass())
    ->setPasswordSalt($salt)
    ->setSiteLang('en_US')
    ->setSessionTimeout(3600);

$configFileService = new class($configData) implements ConfigFileService {
    public function __construct(private ConfigDataInterface $data) {}
    public function getConfigData(): ConfigDataInterface { return $this->data; }
    public function save(ConfigDataInterface $configData, ?bool $commit = true): ConfigFileService { $this->data = $configData; return $this; }
    public function reload(): ConfigDataInterface { return $this->data; }
    public function generateUpgradeKey(): ConfigFileService { return $this; }
};

$dbUtil = new class implements DatabaseUtilInterface {
    public function checkDatabaseTables(string $dbName): bool { return true; }
    public function checkDatabaseConnection(): bool { return true; }
    public function getDBinfo(): array { return []; }
    public function escape(string $str): string { return $str; }
};

$sessionContext = new class extends Stateless implements SessionContext {
    public function getTheme(): string { return (string) $this->getContextKey('theme', 'material-blue'); }
    public function setTheme(string $theme) { $this->setContextKey('theme', $theme); }
    public function getSearchFilters(): ?AccountSearchFilterDto { return $this->getContextKey('searchFilters'); }
    public function setSearchFilters(AccountSearchFilterDto $searchFilters): void { $this->setContextKey('searchFilters', $searchFilters); }
    public function resetAccountAcl() { $this->setContextKey('accountAcl', []); }
    public function setAuthCompleted(bool $bool): void { $this->setContextKey('authCompleted', $bool); }
    public function getAuthCompleted() { return (bool) $this->getContextKey('authCompleted', false); }
    public function getTemporaryMasterPass(): ?string { return null; }
    public function getPublicKey(): ?string { return $this->getContextKey('publicKey'); }
    public function setPublicKey(string $key): void { $this->setContextKey('publicKey', $key); }
    public function getSessionTimeout(): ?int { return $this->getContextKey('sessionTimeout'); }
    public function setSessionTimeout(int $timeout): int { $this->setContextKey('sessionTimeout', $timeout); return $timeout; }
    public function getLastActivity(): int { return (int) $this->getContextKey('lastActivity', 0); }
    public function setLastActivity(int $time): void { $this->setContextKey('lastActivity', $time); }
    public function getStartActivity(): int { return (int) $this->getContextKey('startActivity', 0); }
    public function getAccountColor(): string { return (string) $this->getContextKey('accountColor', ''); }
    public function setAccountColor(array $color): void { $this->setContextKey('accountColor', json_encode($color)); }
    public function getCSRF(): ?string { return $this->getContextKey('csrf'); }
    public function setCSRF(string $csrf): void { $this->setContextKey('csrf', $csrf); }
    public function getVault(): ?VaultInterface { return $this->getContextKey('vault'); }
    public function setVault(VaultInterface $vault): void { $this->setContextKey('vault', $vault); }
    public function getSidStartTime(): int { return (int) $this->getContextKey('sidStartTime', 0); }
    public function setSidStartTime(int $time): int { $this->setContextKey('sidStartTime', $time); return $time; }
    public function setStartActivity(int $time): int { $this->setContextKey('startActivity', $time); return $time; }
};

$request = new Request(
    ['r' => 'account/viewLink/' . $hash],
    [],
    [],
    ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => 'index.php', 'HTTP_HOST' => 'localhost', 'HTTP_USER_AGENT' => 'RaceWorker/1.0'],
    [],
    null
);

$builder = new ContainerBuilder();
$builder->addDefinitions($domainDefs, $coreDefs, $moduleDefs, [
    DatabaseConnectionData::class => $connData,
    DatabaseInterface::class => static fn(EventDispatcherInterface $ev) => new Database(getDbHandler($connData), $ev),
    ConfigFileService::class => $configFileService,
    ConfigDataInterface::class => $configData,
    DatabaseUtilInterface::class => $dbUtil,
    Context::class => $sessionContext,
    Request::class => $request,
    ThemeContextInterface::class => autowire(ThemeContext::class)
        ->constructorParameter('basePath', factory(static fn(PathsContext $p) => $p[Path::VIEW]))
        ->constructorParameter('baseUri', factory([UriContextInterface::class, 'getWebRoot']))
        ->constructorParameter('module', 'web')
        ->constructorParameter('name', 'material-blue'),
]);

$container = $builder->build();
// Warm database connection and container singletons before barrier
$container->get(DatabaseInterface::class);
$container->get(BootstrapInterface::class);
$container->get(ModuleInterface::class);

file_put_contents($readyFile, (string) getmypid());
$deadline = microtime(true) + 15.0;
while (!file_exists($goFile) && microtime(true) < $deadline) {
    usleep(500);
}

ob_start();
try {
    Bootstrap::run($container->get(BootstrapInterface::class), $container->get(ModuleInterface::class));
} finally {
    $echoed = (string) ob_get_clean();
}

/** @var Response $response */
$response = $container->get(Response::class);
$body = (string) $response->body();

file_put_contents($resultFile, json_encode([
    'code' => (int) $response->code(),
    'body' => $body !== '' ? $body : $echoed,
], JSON_THROW_ON_ERROR));
PHP;

        return str_replace(
            ['__ROOT__', '__BOOTSTRAP__'],
            [
                addslashes(REAL_APP_ROOT),
                addslashes(REAL_APP_ROOT . '/tests/SP/bootstrap.php'),
            ],
            $template
        );
    }
}
