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

namespace SP\Tests\Modules\Api\Controllers;

use DI\ContainerBuilder;
use Klein\Request;
use Klein\Response;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SP\Core\Bootstrap\Path;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Domain\Api\Enums\JsonRpcErrorCode;
use SP\Domain\Api\Ports\ApiRequestService;
use SP\Domain\Api\Services\Api as ApiServiceClass;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Auth\Services\AuthToken as AuthTokenService;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Database\Dtos\DatabaseConnectionData;
use SP\Domain\Export\Ports\XmlVerifyService;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Api\Adapters\ApiRequestAdapter;
use SP\Modules\Api\Bootstrap;
use SP\Tests\DatabaseTrait;

use function DI\create;
use function DI\decorate;
use function SP\Tests\getDbHandler;

defined('APP_MODULE') || define('APP_MODULE', 'api');

/**
 * DB-backed characterization tests for JSON-RPC config/backup and config/export:
 * - Auth token lookup/scope, disabled user, and ACL denials without creating backup/export files
 * - Full server path disclosure in files.{app,db,xml}
 * - Demo mode non-enforcement on API backup/export
 * - Uncompressed/unverified raw XML export retention, exportHash mutation, encrypted vs plaintext modes
 * - Cross-owner/private account export via AccountRepository::getAllForExport()
 */
#[Group('integration')]
final class ConfigBoundaryDbIntegrationTest extends TestCase
{
    use DatabaseTrait;

    private const TOKEN_PASS = 'synthetic-config-api-pass-2026';
    private const MASTER_PASS = '12345678900';

    private static ?array $moduleDefinitions = null;
    private string $backupDir;
    private bool $demoEnabled = false;
    private ?ConfigDataInterface $lastConfigData = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$moduleDefinitions === null) {
            self::$moduleDefinitions = FileSystem::require(
                FileSystem::buildPath(REAL_APP_ROOT, 'app', 'modules', 'api', 'module.php')
            );
        }
    }

    protected function setUp(): void
    {
        self::loadFixtures();
        self::truncateTable('AuthToken');
        self::truncateTable('Track');

        $this->backupDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'syspass-api-backup-' . bin2hex(random_bytes(6));
        mkdir($this->backupDir, 0755, true);
        $this->demoEnabled = false;
        $this->lastConfigData = null;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->backupDir);

        parent::tearDown();
    }

    /**
     * config/backup and config/export reject unknown tokens, mismatched action tokens,
     * disabled users, and users whose profile lacks isConfigBackup without creating
     * .sql, .tar.gz, or .xml artifacts on disk.
     */
    #[Test]
    public function configBackupAndExportRejectDeniedTokensDisabledUsersAndMissingAcl(): void
    {
        $pdo = getDbHandler()->getConnection();

        $noBackupProfileId = $this->insertProfile($pdo, 'NoBackupProfile', new ProfileData());
        $fullBackupProfileId = $this->insertProfile($pdo, 'BackupOnlyProfile', new ProfileData(['configBackup' => true]));

        $unprivilegedBackupUserId = $this->insertUser($pdo, 'api_nobackup', $noBackupProfileId, 1, false);
        $unprivilegedExportUserId = $this->insertUser($pdo, 'api_noexport', $noBackupProfileId, 1, false);
        $disabledBackupUserId = $this->insertUser($pdo, 'api_disabled_backup', $fullBackupProfileId, 1, true);
        $disabledExportUserId = $this->insertUser($pdo, 'api_disabled_export', $fullBackupProfileId, 1, true);

        $backupTokenWrongScope = $this->seedTokenForUser(1, AclActionsInterface::CONFIG_BACKUP_RUN);
        $unprivilegedBackupToken = $this->seedTokenForUser($unprivilegedBackupUserId, AclActionsInterface::CONFIG_BACKUP_RUN);
        $unprivilegedExportToken = $this->seedTokenForUser($unprivilegedExportUserId, AclActionsInterface::CONFIG_EXPORT_RUN);
        $disabledBackupToken = $this->seedTokenForUser($disabledBackupUserId, AclActionsInterface::CONFIG_BACKUP_RUN);
        $disabledExportToken = $this->seedTokenForUser($disabledExportUserId, AclActionsInterface::CONFIG_EXPORT_RUN);

        // 1. Unknown token -> -32603 Internal error, no Track row
        $unknownRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'config/backup',
            'params' => ['authToken' => 'nonexistent-config-token'],
            'id' => 101,
        ]);
        self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $unknownRes['json']['error']['code']);
        self::assertSame('Internal error', $unknownRes['json']['error']['message']);
        self::assertSame(0, $this->countApiTracks($pdo));

        // 2. Valid CONFIG_BACKUP_RUN token sent to config/export -> wrong actionId in SQL -> Internal error, no Track row
        $wrongScopeRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'config/export',
            'params' => ['authToken' => $backupTokenWrongScope->getToken()],
            'id' => 102,
        ]);
        self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $wrongScopeRes['json']['error']['code']);
        self::assertSame('Internal error', $wrongScopeRes['json']['error']['message']);
        self::assertSame(0, $this->countApiTracks($pdo));

        // 3. Disabled user tokens for both backup & export -> Unauthorized access + Track increment
        foreach ([
            ['config/backup', $disabledBackupToken->getToken(), 103],
            ['config/export', $disabledExportToken->getToken(), 104],
        ] as [$method, $token, $id]) {
            $res = $this->dispatchJsonRpc([
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => ['authToken' => $token],
                'id' => $id,
            ]);
            self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $res['json']['error']['code']);
            self::assertSame('Unauthorized access', $res['json']['error']['message']);
        }
        self::assertSame(2, $this->countApiTracks($pdo));

        // 4. Unprivileged user (missing isConfigBackup) -> Unauthorized access + Track increment
        foreach ([
            ['config/backup', $unprivilegedBackupToken->getToken(), 105],
            ['config/export', $unprivilegedExportToken->getToken(), 106],
        ] as [$method, $token, $id]) {
            $res = $this->dispatchJsonRpc([
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => ['authToken' => $token],
                'id' => $id,
            ]);
            self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $res['json']['error']['code']);
            self::assertSame('Unauthorized access', $res['json']['error']['message']);
        }
        self::assertSame(4, $this->countApiTracks($pdo));

        // Denied config/backup dispatches leave zero .sql, .tar.gz, or .xml files on disk
        // because CoreDefinitions injects LazyFileHandler instead of eagerly opening BackupFileService.
        $remaining = glob($this->backupDir . DIRECTORY_SEPARATOR . '*') ?: [];
        self::assertSame([], $remaining);
    }

    /**
     * Characterization:
     * - API config/backup discloses full server paths in files.{app,db}, deletes prior archives
     *   before completing, updates backupHash, and has NO demo-mode check.
     */
    #[Test]
    public function configBackupDisclosesFullServerPathsReplacesPriorArchivesAndIgnoresDemoMode(): void
    {
        $pdo = getDbHandler()->getConnection();
        $backupProfileId = $this->insertProfile($pdo, 'ApiBackupProfile', new ProfileData(['configBackup' => true]));
        $operatorId = $this->insertUser($pdo, 'api_backup_op', $backupProfileId, 1, false);
        $token = $this->seedTokenForUser($operatorId, AclActionsInterface::CONFIG_BACKUP_RUN);

        $priorApp = $this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_app-priorapihash.tar.gz';
        $priorDb = $this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_db-priorapihash.tar.gz';
        file_put_contents($priorApp, 'old-app');
        file_put_contents($priorDb, 'old-db');

        // Enable demo mode to confirm API BackupController has no isDemoEnabled() guard
        $this->demoEnabled = true;

        $res = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'config/backup',
            'params' => ['authToken' => $token->getToken()],
            'id' => 201,
        ]);

        self::assertSame(200, $res['code']);
        self::assertArrayHasKey('result', $res['json']);
        self::assertSame(0, $res['json']['result']['resultCode']);
        self::assertSame('Backup process finished', $res['json']['result']['resultMessage']);

        $files = $res['json']['result']['result']['files'];
        self::assertStringStartsWith($this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_app-', $files['app']);
        self::assertStringStartsWith($this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_db-', $files['db']);
        self::assertStringEndsWith('.tar.gz', $files['app']);
        self::assertStringEndsWith('.tar.gz', $files['db']);
        self::assertFileExists($files['app']);
        self::assertFileExists($files['db']);

        // Prior archives were removed
        self::assertFileDoesNotExist($priorApp);
        self::assertFileDoesNotExist($priorDb);
    }

    /**
     * Characterization:
     * - API config/export skips XmlVerify and ArchiveHandler compression, leaving an uncompressed
     *   raw .xml file on disk while disclosing its full server path in files.xml and mutating exportHash.
     * - Non-admin permitted user (isConfigBackup=true, isAdminApp=false, distinct userGroupId) exports
     *   ALL Accounts via AccountRepository::getAllForExport(), including another user's isPrivate=1
     *   and isPrivateGroup=1 Accounts.
     * - Providing password produces an encrypted XML verifiable by XmlVerifyService, still uncompressed.
     */
    #[Test]
    public function configExportLeavesRawXmlDisclosesFullPathExportsCrossOwnerPrivateAccountsAndSupportsPassword(): void
    {
        $pdo = getDbHandler()->getConnection();
        $isolatedGroupId = $this->insertUserGroup($pdo, 'IsolatedBackupGroup');
        $exportProfileId = $this->insertProfile($pdo, 'ApiExportProfile', new ProfileData(['configBackup' => true]));
        $nonAdminExporterId = $this->insertUser($pdo, 'nonadmin_exporter', $exportProfileId, $isolatedGroupId, false);
        $token = $this->seedTokenForUser($nonAdminExporterId, AclActionsInterface::CONFIG_EXPORT_RUN);

        $priorGz = $this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_export-oldapihash.tar.gz';
        file_put_contents($priorGz, 'old-export-archive');

        $this->demoEnabled = true;

        // 1. Plaintext API export (no password)
        $plainRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'config/export',
            'params' => ['authToken' => $token->getToken()],
            'id' => 301,
        ]);

        self::assertSame(200, $plainRes['code']);
        self::assertSame(0, $plainRes['json']['result']['resultCode']);
        self::assertSame('Export process finished', $plainRes['json']['result']['resultMessage']);

        $xmlPath = $plainRes['json']['result']['result']['files']['xml'];
        self::assertStringStartsWith($this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_export-', $xmlPath);
        self::assertStringEndsWith('.xml', $xmlPath);
        self::assertFileExists($xmlPath);
        self::assertFileDoesNotExist($priorGz);
        self::assertSame([], glob($this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_export-*.tar.gz') ?: []);
        self::assertNotNull($this->lastConfigData);
        self::assertStringEndsWith('-' . $this->lastConfigData->getExportHash() . '.xml', $xmlPath);

        // Verify AccountRepository::getAllForExport() included all 4 fixture accounts (incl. Github isPrivate=1 & Slack isPrivateGroup=1 owned by userId=2)
        $xmlContent = (string)file_get_contents($xmlPath);
        self::assertStringContainsString('<name>Google</name>', $xmlContent);
        self::assertStringContainsString('<name>Github</name>', $xmlContent);
        self::assertStringContainsString('<name>Slack</name>', $xmlContent);

        // 2. Password-protected API export also stays raw .xml and verifies with XmlVerify
        $this->demoEnabled = false;
        $encRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'config/export',
            'params' => [
                'authToken' => $token->getToken(),
                'password' => 'synthetic-xml-secret-2026',
            ],
            'id' => 302,
        ]);

        self::assertSame(0, $encRes['json']['result']['resultCode']);
        $encXmlPath = $encRes['json']['result']['result']['files']['xml'];
        self::assertStringEndsWith('.xml', $encXmlPath);
        self::assertFileExists($encXmlPath);
        self::assertFileDoesNotExist($xmlPath);
        self::assertSame([], glob($this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_export-*.tar.gz') ?: []);

        $encContent = (string)file_get_contents($encXmlPath);
        self::assertStringNotContainsString('<name>Github</name>', $encContent);
        self::assertStringContainsString('<Encrypted', $encContent);

        $dic = $this->buildApiContainer();
        /** @var XmlVerifyService $verify */
        $verify = $dic->get(XmlVerifyService::class);
        $verifyResult = $verify->verify($encXmlPath, 'synthetic-xml-secret-2026');
        self::assertFalse($verifyResult->isEncrypted());
        self::assertSame(4, $verifyResult->getNodes()['Account']);
    }

    private function seedTokenForUser(int $userId, int $actionId): AuthTokenModel
    {
        $dic = $this->buildApiContainer();
        $context = $dic->get(Context::class);
        $context->initialize();
        $context->setUserData(new UserDto(
            id: 1,
            login: 'admin',
            isAdminApp: true,
            preferences: new UserPreferences(['lang' => 'en_US'])
        ));
        $context->setTrasientKey('_masterpass', self::MASTER_PASS);

        /** @var AuthTokenService $service */
        $service = $dic->get(AuthTokenService::class);
        $id = $service->create(new AuthTokenModel([
            'actionId' => $actionId,
            'createdBy' => 1,
            'hash' => self::TOKEN_PASS,
            'userId' => $userId,
        ]));

        return $service->getById($id);
    }

    private function insertProfile(PDO $pdo, string $name, ProfileData $profileData): int
    {
        $stmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (:name, :profile)');
        $stmt->execute([
            'name' => $name,
            'profile' => Serde::serialize($profileData),
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function insertUserGroup(PDO $pdo, string $name): int
    {
        $stmt = $pdo->prepare('INSERT INTO UserGroup (name, description) VALUES (:name, :desc)');
        $stmt->execute([
            'name' => $name,
            'desc' => 'Synthetic group',
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function insertUser(PDO $pdo, string $login, int $profileId, int $groupId, bool $isDisabled): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, :groupId, :login, :pass, :email, :profileId, 0, 0, :disabled, :salt, 0, 0, 0, 0)'
        );
        $stmt->execute([
            'name' => 'Synthetic ' . $login,
            'groupId' => $groupId,
            'login' => $login,
            'pass' => Hash::hashKey('synthetic-pass-' . $login),
            'email' => $login . '@example.test',
            'profileId' => $profileId,
            'disabled' => $isDisabled ? 1 : 0,
            'salt' => sha1($login),
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function countApiTracks(PDO $pdo): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Track WHERE source = :source');
        $stmt->execute(['source' => ApiServiceClass::class]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{code: int, body: string, json: array<string, mixed>}
     */
    private function dispatchJsonRpc(array $payload): array
    {
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $requestFile = tempnam(TMP_PATH, 'api-cfg-boundary-');
        if ($requestFile === false) {
            throw new RuntimeException('Unable to create temporary API request file');
        }

        try {
            file_put_contents($requestFile, $rawBody);

            $dic = $this->buildApiContainer($requestFile);

            ob_start();
            try {
                Bootstrap::run($dic->get(BootstrapInterface::class), $dic->get(ModuleInterface::class));
            } finally {
                $echoed = (string)ob_get_clean();
            }

            /** @var Response $response */
            $response = $dic->get(Response::class);
            $body = (string)$response->body();
            $effectiveBody = $body !== '' ? $body : $echoed;

            $this->lastConfigData = $dic->get(ConfigFileService::class)->getConfigData();

            return [
                'code' => (int)$response->code(),
                'body' => $effectiveBody,
                'json' => json_decode($effectiveBody, true, 512, JSON_THROW_ON_ERROR),
            ];
        } finally {
            @unlink($requestFile);
        }
    }

    private function buildApiContainer(?string $requestFile = null): \Psr\Container\ContainerInterface
    {
        $databaseConnectionData = DatabaseConnectionData::getFromEnvironment();

        return (new ContainerBuilder())
            ->addDefinitions(array_merge(
                CoreDefinitions::getDefinitions(REAL_APP_ROOT, 'api'),
                DomainDefinitions::getDefinitions(),
                self::$moduleDefinitions
            ))
            ->addDefinitions([
                    'paths' => decorate(function (array $paths): array {
                        $backupDir = $this->backupDir;

                        return array_map(
                            static fn(array $spec): array => [
                                $spec[0],
                                $spec[0] === Path::BACKUP ? $backupDir : $spec[1],
                            ],
                            $paths
                        );
                    }),
                    DatabaseConnectionData::class => $databaseConnectionData,
                    ConfigDataInterface::class => function (ConfigFileService $config) use ($databaseConnectionData) {
                        $configData = $config->getConfigData()
                            ->setDbHost($databaseConnectionData->getDbHost())
                            ->setDbName($databaseConnectionData->getDbName())
                            ->setDbUser($databaseConnectionData->getDbUser())
                            ->setDbPass($databaseConnectionData->getDbPass())
                            ->setDemoEnabled($this->demoEnabled)
                            ->setInstalled(true);
                        $config->save($configData, false);

                        return $configData;
                    },
                    ApiRequestService::class => static function () use ($requestFile) {
                        if ($requestFile === null) {
                            throw new RuntimeException('API request file is required');
                        }

                        return ApiRequestAdapter::buildFromRequest($requestFile);
                    },
                    Request::class => new Request(
                        [],
                        [],
                        [],
                        [
                            'HTTP_HOST' => 'localhost:8080',
                            'HTTP_ACCEPT' => 'application/json',
                            'HTTP_USER_AGENT' => 'ConfigBoundaryDbIntegrationTest/1.0',
                            'REQUEST_URI' => '/api.php',
                            'REQUEST_METHOD' => 'POST',
                            'HTTP_CONTENT_TYPE' => 'application/json',
                            'REMOTE_ADDR' => '127.0.0.1',
                        ],
                        [],
                        null
                    ),
                    Response::class => create(Response::class),
                ]
            )
            ->build();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
