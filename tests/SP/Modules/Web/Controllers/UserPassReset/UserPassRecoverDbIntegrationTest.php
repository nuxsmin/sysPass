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

namespace SP\Tests\Modules\Web\Controllers\UserPassReset;

use Error;
use Klein\Request;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Ports\UserService;
use SP\Tests\DatabaseIntegrationTestCase;

use function SP\Tests\getDbHandler;

/**
 * DB-backed characterization tests for password-reset throttling, QueryFactory::COMMON runtime defect,
 * non-atomic used=1 consumption on password update failure, and multi-process concurrent
 * reset-token redemption against MariaDB.
 */
#[Group('integration')]
final class UserPassRecoverDbIntegrationTest extends DatabaseIntegrationTestCase
{
    protected function getConfigData(): array
    {
        return array_merge(parent::getConfigData(), [
            'getApplicationUrl' => 'https://syspass.example.com',
            'isMailEnabled' => true,
        ]);
    }

    protected static function buildRequest(
        string $method,
        string $uri,
        array $paramsGet = [],
        array $paramsPost = [],
        array $files = [],
        array $server = []
    ): Request {
        return parent::buildRequest(
            $method,
            $uri,
            $paramsGet,
            $paramsPost,
            $files,
            array_merge(['REMOTE_ADDR' => '127.0.0.1'], $server)
        );
    }

    #[Test]
    public function productionDiSaveResetUpdatesPasswordAndMarksHashUsed(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE UserPassRecover');
        $pdo->exec('TRUNCATE TABLE Track');

        $initialPassHash = Hash::hashKey('common-update-initial-2026');
        $login = 'synthetic_qf_' . bin2hex(random_bytes(4));
        $stmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, 1, :login, :pass, :email, 1, 0, 0, 0, :salt, 1, 0, 0, 0)'
        );
        $stmt->execute([
            'name' => 'Synthetic QF User',
            'login' => $login,
            'pass' => $initialPassHash,
            'email' => $login . '@example.test',
            'salt' => sha1('salt'),
        ]);
        $userId = (int)$pdo->lastInsertId();

        $hash = bin2hex(random_bytes(16));
        $pdo->prepare('INSERT INTO UserPassRecover (userId, hash, date, used) VALUES (:uid, :hash, UNIX_TIMESTAMP(), 0)')
            ->execute(['uid' => $userId, 'hash' => $hash]);

        $resetRes = $this->dispatch(
            'post',
            'userPassReset/saveReset',
            [],
            [
                'hash' => $hash,
                'password' => 'updated-synthetic-pass-2026',
                'password_repeat' => 'updated-synthetic-pass-2026',
            ],
            [Context::class => $this->getUnauthenticatedContext()]
        );
        self::assertSame(200, $resetRes['code']);
        $resetJson = json_decode($resetRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OK', $resetJson['status'] ?? null);
        self::assertSame('Password updated', $resetJson['description'] ?? null);

        $usedStmt = $pdo->prepare('SELECT used FROM UserPassRecover WHERE hash = :hash');
        $usedStmt->execute(['hash' => $hash]);
        self::assertSame(1, (int)$usedStmt->fetchColumn());

        $passStmt = $pdo->prepare('SELECT pass FROM User WHERE id = :id');
        $passStmt->execute(['id' => $userId]);
        self::assertNotSame($initialPassHash, (string)$passStmt->fetchColumn());
    }

    /**
     * Characterization against MariaDB (with Mysql QueryFactory to reach SQL execution):
     * 1. Up to 3 active (used=0) recovery tokens per hour persist; the 4th fails with
     *    "Attempts exceeded" and inserts no additional row.
     * 2. If updatePass() fails after toggleUsedByHash(), the token remains consumed (used=1)
     *    while User.pass stays unchanged, so retrying the same token fails with "Wrong hash or expired".
     */
    #[Test]
    public function recoveryThrottleLimitsToThreePerHourAndFailedUpdateLeavesTokenConsumed(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE UserPassRecover');
        $pdo->exec('TRUNCATE TABLE Track');

        $initialPassHash = Hash::hashKey('initial-synthetic-pass-2026');
        $login = 'synthetic_reset_' . bin2hex(random_bytes(4));
        $email = $login . '@example.test';

        $stmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, 1, :login, :pass, :email, 1, 0, 0, 0, :salt, 0, 0, 0, 0)'
        );
        $stmt->execute([
            'name' => 'Synthetic Reset',
            'login' => $login,
            'pass' => $initialPassHash,
            'email' => $email,
            'salt' => sha1('salt'),
        ]);
        $userId = (int)$pdo->lastInsertId();

        $anonContext = $this->getUnauthenticatedContext();

        for ($i = 0; $i < 3; $i++) {
            $res = $this->dispatch(
                'post',
                'userPassReset/saveRequest',
                [],
                ['login' => $login, 'email' => $email],
                [Context::class => $anonContext]
            );
            $this->assertJsonOk($res, 'Request sent');
        }

        // 4th active request within 1 hour hits MAX_PASS_RECOVER_LIMIT = 3
        $res4 = $this->dispatch(
            'post',
            'userPassReset/saveRequest',
            [],
            ['login' => $login, 'email' => $email],
            [Context::class => $anonContext]
        );
        $this->assertJsonDenied($res4, 'Attempts exceeded');

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM UserPassRecover WHERE userId = :id AND used = 0');
        $countStmt->execute(['id' => $userId]);
        self::assertSame(3, (int)$countStmt->fetchColumn());

        // Pick one persisted hash and simulate updatePass() failure after toggleUsedByHash()
        $hashStmt = $pdo->prepare('SELECT hash FROM UserPassRecover WHERE userId = :id ORDER BY id ASC LIMIT 1');
        $hashStmt->execute(['id' => $userId]);
        $targetHash = (string)$hashStmt->fetchColumn();
        self::assertNotSame('', $targetHash);

        $failingUserService = $this->createMock(UserService::class);
        $failingUserService->expects(self::once())
                           ->method('updatePass')
                           ->willThrowException(ServiceException::error('Error while updating the password'));

        $failedReset = $this->dispatch(
            'post',
            'userPassReset/saveReset',
            [],
            [
                'hash' => $targetHash,
                'password' => 'never-saved-password',
                'password_repeat' => 'never-saved-password',
            ],
            [
                Context::class => $anonContext,
                UserService::class => $failingUserService,
            ]
        );
        $this->assertJsonDenied($failedReset, 'Error while updating the password');

        // Independent PDO verification: token was marked used=1, password stayed unchanged
        $tokenRow = $pdo->prepare('SELECT used FROM UserPassRecover WHERE hash = :hash');
        $tokenRow->execute(['hash' => $targetHash]);
        self::assertSame(1, (int)$tokenRow->fetchColumn());

        $userPassRow = $pdo->prepare('SELECT pass FROM User WHERE id = :id');
        $userPassRow->execute(['id' => $userId]);
        self::assertSame($initialPassHash, (string)$userPassRow->fetchColumn());

        // Subsequent retry with the same hash fails with "Wrong hash or expired"
        $retryReset = $this->dispatch(
            'post',
            'userPassReset/saveReset',
            [],
            [
                'hash' => $targetHash,
                'password' => 'retry-password-2026',
                'password_repeat' => 'retry-password-2026',
            ],
            [
                Context::class => $anonContext,
            ]
        );
        $this->assertJsonDenied($retryReset, 'Wrong hash or expired');
    }

    /**
     * Multi-process concurrent redemption of a single password-reset token against MariaDB:
     * two PHP child processes synchronized at a ready/go barrier submit POST userPassReset/saveReset
     * with the same unused hash and distinct passwords. Only one process wins the atomic
     * `UPDATE UserPassRecover SET used = 1 WHERE hash = :hash AND date >= :date AND used = 0 LIMIT 1`;
     * the loser fails with "Wrong hash or expired".
     */
    #[Test]
    public function concurrentResetTokenRedemptionAllowsExactlyOneWinnerInMariaDb(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('TRUNCATE TABLE UserPassRecover');
        $pdo->exec('TRUNCATE TABLE Track');

        $initialPassHash = Hash::hashKey('concurrent-initial-pass-2026');
        $login = 'synthetic_race_' . bin2hex(random_bytes(4));
        $stmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, 1, :login, :pass, :email, 1, 0, 0, 0, :salt, 1, 0, 0, 0)'
        );
        $stmt->execute([
            'name' => 'Synthetic Race User',
            'login' => $login,
            'pass' => $initialPassHash,
            'email' => $login . '@example.test',
            'salt' => sha1('salt'),
        ]);
        $userId = (int)$pdo->lastInsertId();

        $hash = bin2hex(random_bytes(16));
        $ins = $pdo->prepare('INSERT INTO UserPassRecover (userId, hash, date, used) VALUES (:uid, :hash, UNIX_TIMESTAMP(), 0)');
        $ins->execute(['uid' => $userId, 'hash' => $hash]);

        $runDir = TMP_PATH . DIRECTORY_SEPARATOR . 'reset-race-' . bin2hex(random_bytes(6));
        mkdir($runDir, 0777, true);
        $workerScript = $runDir . DIRECTORY_SEPARATOR . 'worker.php';
        $goFile = $runDir . DIRECTORY_SEPARATOR . 'go.flag';

        file_put_contents($workerScript, $this->buildWorkerScript());

        $passwords = [
            0 => 'winner-or-loser-pass-0-2026',
            1 => 'winner-or-loser-pass-1-2026',
        ];
        $results = [];
        $processes = [];
        $pipes = [];

        try {
            for ($i = 0; $i < 2; $i++) {
                $readyFile = $runDir . DIRECTORY_SEPARATOR . 'ready.' . $i;
                $resultFile = $runDir . DIRECTORY_SEPARATOR . 'result.' . $i . '.json';
                $cmd = sprintf(
                    '%s -d display_errors=0 -d error_reporting=24575 %s %s %s %s %s %s %s',
                    escapeshellarg(PHP_BINARY),
                    escapeshellarg($workerScript),
                    escapeshellarg($hash),
                    escapeshellarg($passwords[$i]),
                    escapeshellarg($this->passwordSalt),
                    escapeshellarg($readyFile),
                    escapeshellarg($goFile),
                    escapeshellarg($resultFile)
                );

                $proc = proc_open(
                    $cmd,
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $procPipes,
                    REAL_APP_ROOT
                );
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
                if ((!$status0['running'] && !file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.0'))
                    || (!$status1['running'] && !file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.1'))
                ) {
                    break;
                }
                usleep(5000);
            }

            if (!file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.0')
                || !file_exists($runDir . DIRECTORY_SEPARATOR . 'ready.1')
            ) {
                $diag = '';
                foreach ($processes as $i => $proc) {
                    $stdout = (string)stream_get_contents($pipes[$i][1]);
                    $stderr = (string)stream_get_contents($pipes[$i][2]);
                    fclose($pipes[$i][1]);
                    fclose($pipes[$i][2]);
                    $code = proc_close($proc);
                    $diag .= "Worker $i (exit $code) stderr: $stderr | stdout: $stdout\n";
                }
                self::fail("Workers failed before barrier:\n" . $diag);
            }

            file_put_contents($goFile, '1');

            foreach ($processes as $i => $proc) {
                $stdout = (string)stream_get_contents($pipes[$i][1]);
                $stderr = (string)stream_get_contents($pipes[$i][2]);
                fclose($pipes[$i][1]);
                fclose($pipes[$i][2]);
                $exitCode = proc_close($proc);

                $resultFile = $runDir . DIRECTORY_SEPARATOR . 'result.' . $i . '.json';
                self::assertSame(0, $exitCode, "Worker $i exited with $exitCode. stderr: $stderr\nstdout: $stdout");
                self::assertFileExists($resultFile, "Worker $i missing result file. stderr: $stderr\nstdout: $stdout");
                $decoded = json_decode((string)file_get_contents($resultFile), true, 512, JSON_THROW_ON_ERROR);
                $decoded['payload'] = json_decode($decoded['body'], true, 512, JSON_THROW_ON_ERROR);
                $results[$i] = $decoded;
            }
        } finally {
            foreach (glob($runDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($runDir);
        }

        $winners = [];
        $losers = [];
        foreach ($results as $idx => $res) {
            self::assertSame(200, $res['code']);
            if (($res['payload']['status'] ?? null) === 'OK') {
                self::assertSame('Password updated', $res['payload']['description'] ?? null);
                $winners[] = $idx;
            } else {
                self::assertSame('ERROR', $res['payload']['status'] ?? null);
                self::assertSame('Wrong hash or expired', $res['payload']['description'] ?? null);
                $losers[] = $idx;
            }
        }

        self::assertCount(1, $winners, 'Expected exactly 1 winner: ' . json_encode($results));
        self::assertCount(1, $losers, 'Expected exactly 1 loser: ' . json_encode($results));

        $winnerIdx = $winners[0];
        $loserIdx = $losers[0];

        // Independent PDO verification
        $recoverRow = $pdo->prepare('SELECT used FROM UserPassRecover WHERE hash = :hash');
        $recoverRow->execute(['hash' => $hash]);
        self::assertSame(1, (int)$recoverRow->fetchColumn());

        $userRow = $pdo->prepare('SELECT pass, isChangePass, isChangedPass FROM User WHERE id = :id');
        $userRow->execute(['id' => $userId]);
        $row = $userRow->fetch(PDO::FETCH_ASSOC);

        self::assertIsArray($row);
        self::assertSame(0, (int)$row['isChangePass']);
        self::assertSame(1, (int)$row['isChangedPass']);
        self::assertTrue(Hash::checkHashKey($passwords[$winnerIdx], (string)$row['pass']));
        self::assertFalse(Hash::checkHashKey($passwords[$loserIdx], (string)$row['pass']));
    }

    private function getUnauthenticatedContext(): SessionContext
    {
        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(false);
        $context->method('getAuthCompleted')->willReturn(false);
        $context->method('getCSRF')->willReturn(null);
        $context->method('getUserData')->willReturn(new UserDto());

        return $context;
    }

    private function buildWorkerScript(): string
    {
        $template = <<<'PHP'
<?php
declare(strict_types=1);

require '__ROOT__/vendor/autoload.php';
require '__BOOTSTRAP__';

use Aura\SqlQuery\QueryFactory;
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
$newPass = $argv[2];
$salt = $argv[3];
$readyFile = $argv[4];
$goFile = $argv[5];
$resultFile = $argv[6];

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
    ->setMailEnabled(true)
    ->setApplicationUrl('https://syspass.example.com')
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
    public function getTheme(): string { return (string)$this->getContextKey('theme', 'material-blue'); }
    public function setTheme(string $theme) { $this->setContextKey('theme', $theme); }
    public function getSearchFilters(): ?AccountSearchFilterDto { return $this->getContextKey('searchFilters'); }
    public function setSearchFilters(AccountSearchFilterDto $searchFilters): void { $this->setContextKey('searchFilters', $searchFilters); }
    public function resetAccountAcl() { $this->setContextKey('accountAcl', []); }
    public function setAuthCompleted(bool $bool): void { $this->setContextKey('authCompleted', $bool); }
    public function getAuthCompleted() { return (bool)$this->getContextKey('authCompleted', false); }
    public function getTemporaryMasterPass(): ?string { return null; }
    public function getPublicKey(): ?string { return $this->getContextKey('publicKey'); }
    public function setPublicKey(string $key): void { $this->setContextKey('publicKey', $key); }
    public function getSessionTimeout(): ?int { return $this->getContextKey('sessionTimeout'); }
    public function setSessionTimeout(int $timeout): int { $this->setContextKey('sessionTimeout', $timeout); return $timeout; }
    public function getLastActivity(): int { return (int)$this->getContextKey('lastActivity', 0); }
    public function setLastActivity(int $time): void { $this->setContextKey('lastActivity', $time); }
    public function getStartActivity(): int { return (int)$this->getContextKey('startActivity', 0); }
    public function getAccountColor(): string { return (string)$this->getContextKey('accountColor', ''); }
    public function setAccountColor(array $color): void { $this->setContextKey('accountColor', json_encode($color)); }
    public function getCSRF(): ?string { return $this->getContextKey('csrf'); }
    public function setCSRF(string $csrf): void { $this->setContextKey('csrf', $csrf); }
    public function getVault(): ?VaultInterface { return $this->getContextKey('vault'); }
    public function setVault(VaultInterface $vault): void { $this->setContextKey('vault', $vault); }
    public function getSidStartTime(): int { return (int)$this->getContextKey('sidStartTime', 0); }
    public function setSidStartTime(int $time): int { $this->setContextKey('sidStartTime', $time); return $time; }
    public function setStartActivity(int $time): int { $this->setContextKey('startActivity', $time); return $time; }
};

$request = new Request(
    ['r' => 'userPassReset/saveReset'],
    [
        'hash' => $hash,
        'password' => $newPass,
        'password_repeat' => $newPass,
    ],
    [],
    [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => 'index.php',
        'HTTP_HOST' => 'localhost',
        'HTTP_USER_AGENT' => 'ResetRaceWorker/1.0',
        'REMOTE_ADDR' => '127.0.0.1',
    ],
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
$container->get(DatabaseInterface::class);
$container->get(BootstrapInterface::class);
$container->get(ModuleInterface::class);

file_put_contents($readyFile, (string)getmypid());
$deadline = microtime(true) + 15.0;
while (!file_exists($goFile) && microtime(true) < $deadline) {
    usleep(500);
}

ob_start();
try {
    Bootstrap::run($container->get(BootstrapInterface::class), $container->get(ModuleInterface::class));
} finally {
    $echoed = (string)ob_get_clean();
}

/** @var Response $response */
$response = $container->get(Response::class);
$body = (string)$response->body();

file_put_contents($resultFile, json_encode([
    'code' => (int)$response->code(),
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
