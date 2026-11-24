<?php
/*
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

namespace SP\Tests\Modules\Web\Controllers\ConfigBackup;

use DOMElement;
use Klein\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Core\AppLock;
use SP\Core\Bootstrap\Path;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Domain\Crypt\Ports\TemporaryMasterPassService;
use SP\Domain\Export\Ports\BackupFileService;
use SP\Domain\Export\Ports\XmlAccountExportService;
use SP\Domain\Export\Ports\XmlCategoryExportService;
use SP\Domain\Export\Ports\XmlClientExportService;
use SP\Domain\Export\Ports\XmlTagExportService;
use SP\Domain\Export\Ports\XmlVerifyService;
use SP\Domain\Export\Services\VerifyResult;
use SP\Domain\File\Ports\ArchiveHandlerFactoryInterface;
use SP\Domain\File\Ports\ArchiveHandlerInterface;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\File\FileHandlerFactory;
use SP\Infrastructure\File\FileSystem;
use SP\Tests\IntegrationTestCase;
use Throwable;
use function DI\decorate;
use function DI\factory;

/**
 * HTTP dispatch characterization tests for Web backup, export, download, configuration save,
 * and encryption controllers:
 * - Anonymous & incomplete-authentication denials
 * - Real Acl denial behavior across JSON vs ResponseType::CALLBACK routes and ConfigWiki/save
 * - Demo-mode guards vs unguarded actions & ResponseType::CALLBACK warning suppression
 * - Shared hash download without requester ownership isolation & missing-file HTTP 500
 * - XmlExportController failed-verify unarchived XML retention & premature exportHash persistence
 * - DownloadConfigBackupController pre-validation event emission & unredacted JSON secrets
 * - ConfigEncryption stale lastUpdateMPass short-circuit & ConfigGeneral AppLock toggle
 */
#[Group('integration')]
final class ConfigOperationsBoundaryTest extends IntegrationTestCase
{
    private string $tempDir;
    private string $backupDir;
    private string $logFile;
    private string $lockFile;

    private bool $sessionLoggedIn = true;
    private bool $sessionAuthCompleted = true;
    private ?UserDto $sessionUser = null;
    private ?ProfileData $sessionProfile = null;
    private array $configOverrides = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'syspass-web-config-ops-' . bin2hex(random_bytes(6));
        $this->backupDir = $this->tempDir . DIRECTORY_SEPARATOR . 'backup';
        $this->logFile = $this->tempDir . DIRECTORY_SEPARATOR . 'syspass.log';
        $this->lockFile = $this->tempDir . DIRECTORY_SEPARATOR . 'syspass.lock';

        mkdir($this->backupDir, 0755, true);
        file_put_contents($this->logFile, "[2026-11-20 00:00:00] syspass.INFO: test-log-entry\n");

        $this->sessionLoggedIn = true;
        $this->sessionAuthCompleted = true;
        $this->sessionUser = $this->makeUserDto(1, false);
        $this->sessionProfile = $this->makeFullConfigProfile();
        $this->configOverrides = [];
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public static function protectedRoutesProvider(): array
    {
        return [
            'configBackup/fileBackup' => ['get', 'configBackup/fileBackup', true],
            'configBackup/xmlExport' => ['get', 'configBackup/xmlExport', true],
            'configBackup/downloadBackupApp' => ['get', 'configBackup/downloadBackupApp', false],
            'configBackup/downloadBackupDb' => ['get', 'configBackup/downloadBackupDb', false],
            'configBackup/downloadExport' => ['get', 'configBackup/downloadExport', false],
            'configGeneral/downloadConfigBackup/json' => ['get', 'configGeneral/downloadConfigBackup/json', false],
            'configGeneral/downloadLog' => ['get', 'configGeneral/downloadLog', false],
            'configGeneral/save' => ['post', 'configGeneral/save', true],
            'configAccount/save' => ['post', 'configAccount/save', true],
            'configWiki/save' => ['post', 'configWiki/save', true],
            'configEncryption/saveTemp' => ['get', 'configEncryption/saveTemp', true],
        ];
    }

    /**
     * Characterization: Anonymous (isLoggedIn=false) and incomplete (isLoggedIn=true, getAuthCompleted=false)
     * sessions are stopped by checks() before reaching actions, returning empty body with no mutations.
     */
    #[Test]
    #[DataProvider('protectedRoutesProvider')]
    public function anonymousAndIncompleteSessionsAreBlockedAcrossAllConfigAndBackupRoutes(
        string $method,
        string $route,
        bool $isJson
    ): void {
        foreach ([false, true] as $loggedIn) {
            $this->sessionLoggedIn = $loggedIn;
            $this->sessionAuthCompleted = false;

            $backupService = $this->createMock(BackupFileService::class);
            $backupService->expects(self::never())->method('doBackup');

            $tempPassService = $this->createMock(TemporaryMasterPassService::class);
            $tempPassService->expects(self::never())->method('create');

            $result = $this->dispatchWeb($method, $route, [], [
                BackupFileService::class => $backupService,
                TemporaryMasterPassService::class => $tempPassService,
            ]);

            self::assertSame('', $result['body'], "Expected empty SessionTimeout body for {$route}");
        }
    }

    /**
     * Characterization: Real Acl denial behavior across Config/Backup/Download/Wiki controllers:
     * - JSON routes with initialize()->checkAccess() return HTTP 403 + JSON error.
     * - ResponseType::CALLBACK routes emit HTTP 403 with EMPTY body (Bootstrap drops string error).
     * - ConfigWiki/save checks ACL inside saveAction(), returning HTTP 200 + JSON error.
     */
    #[Test]
    #[DataProvider('protectedRoutesProvider')]
    public function unprivilegedUserReceivesRouteSpecificAclDenialEnvelope(
        string $method,
        string $route,
        bool $isJson
    ): void {
        $this->sessionLoggedIn = true;
        $this->sessionAuthCompleted = true;
        $this->sessionUser = $this->makeUserDto(50, false);
        $this->sessionProfile = new ProfileData(); // No config* permissions granted

        $result = $this->dispatchWeb($method, $route);

        if ($route === 'configWiki/save') {
            self::assertSame(200, $result['code']);
            $decoded = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('ERROR', $decoded['status']);
            self::assertSame("You don't have permission to access this page", $decoded['description']);

            return;
        }

        self::assertSame(403, $result['code']);

        if ($isJson) {
            $decoded = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('ERROR', $decoded['status']);
            self::assertSame("You don't have permission to access this page", $decoded['description']);
        } else {
            // ResponseType::CALLBACK drops non-Closure ActionResponse::error() subjects
            self::assertSame('', $result['body']);
        }
    }

    #[Test]
    public function deniedBackupDispatchesDoNotCreateEmptySqlArtifacts(): void
    {
        // 1. Anonymous session denied on configBackup/fileBackup (real BackupFileService)
        $this->sessionLoggedIn = false;
        $this->sessionAuthCompleted = false;
        $anonRes = $this->dispatchWeb('get', 'configBackup/fileBackup');
        self::assertSame('', $anonRes['body']);
        self::assertSame([], glob($this->backupDir . DIRECTORY_SEPARATOR . '*') ?: []);

        // 2. Unprivileged ACL denial on configBackup/fileBackup
        $this->sessionLoggedIn = true;
        $this->sessionAuthCompleted = true;
        $this->sessionUser = $this->makeUserDto(50, false);
        $this->sessionProfile = new ProfileData();
        $aclRes = $this->dispatchWeb('get', 'configBackup/fileBackup');
        self::assertSame(403, $aclRes['code']);
        self::assertSame([], glob($this->backupDir . DIRECTORY_SEPARATOR . '*') ?: []);

        // 3. Demo mode denial on configBackup/fileBackup
        $this->sessionProfile = $this->makeFullConfigProfile();
        $this->configOverrides = ['isDemoEnabled' => true];
        $demoRes = $this->dispatchWeb('get', 'configBackup/fileBackup');
        self::assertSame(200, $demoRes['code']);
        self::assertSame('WARNING', json_decode($demoRes['body'], true, 512, JSON_THROW_ON_ERROR)['status']);
        self::assertSame([], glob($this->backupDir . DIRECTORY_SEPARATOR . '*') ?: []);
    }

    /**
     * Characterization: Demo mode behavior is inconsistent across Config/Backup/Export/Download:
     * - fileBackup and configGeneral/save return JSON WARNING.
     * - downloadBackupApp, downloadBackupDb, downloadConfigBackup/json, downloadLog emit empty HTTP 200 body.
     * - xmlExport, downloadExport, and configEncryption/saveTemp lack isDemoEnabled() checks and execute.
     */
    #[Test]
    public function demoModeEnforcesGuardsInconsistentlyAcrossBackupExportDownloadAndEncryption(): void
    {
        $this->configOverrides = [
            'isDemoEnabled' => true,
            'getExportHash' => 'demoexporthash',
            'getBackupHash' => 'demobackuphash',
        ];

        $demoExportArchive = $this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_export-demoexporthash.tar.gz';
        file_put_contents($demoExportArchive, 'demo-export-gz-content');

        // 1. JSON controllers with demo check
        foreach (['configBackup/fileBackup'] as $route) {
            $res = $this->dispatchWeb('get', $route);
            self::assertSame(200, $res['code']);
            $json = json_decode($res['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('WARNING', $json['status']);
            self::assertSame('Ey, this is a DEMO!!', $json['description']);
        }

        $genRes = $this->dispatchWeb('post', 'configGeneral/save', ['site_theme' => 'material-blue']);
        self::assertSame('WARNING', json_decode($genRes['body'], true, 512, JSON_THROW_ON_ERROR)['status']);

        // 2. ResponseType::CALLBACK controllers with demo check -> empty HTTP 200 body
        foreach ([
            'configBackup/downloadBackupApp',
            'configBackup/downloadBackupDb',
            'configGeneral/downloadConfigBackup/json',
            'configGeneral/downloadLog',
        ] as $route) {
            $res = $this->dispatchWeb('get', $route);
            self::assertSame(200, $res['code'], "Expected HTTP 200 on demo download {$route}");
            self::assertSame('', $res['body'], "Expected empty CALLBACK body for demo warning on {$route}");
        }

        // 3. DownloadExportController has NO demo check -> streams archive bytes
        $dlExportRes = $this->dispatchWeb('get', 'configBackup/downloadExport');
        self::assertSame(200, $dlExportRes['code']);
        self::assertSame('demo-export-gz-content', $dlExportRes['body']);

        // 4. ConfigEncryption/SaveTempController has NO demo check -> creates temp pass
        $tempPassService = $this->createMock(TemporaryMasterPassService::class);
        $tempPassService->expects(self::once())->method('create')->willReturn('generated-temp-key');
        $saveTempRes = $this->dispatchWeb('get', 'configEncryption/saveTemp', [], [
            TemporaryMasterPassService::class => $tempPassService,
        ]);
        self::assertSame('OK', json_decode($saveTempRes['body'], true, 512, JSON_THROW_ON_ERROR)['status']);

        // 5. XmlExportController has NO demo check -> exports and verifies
        $xmlVerify = $this->createMock(XmlVerifyService::class);
        $xmlVerify->expects(self::once())
            ->method('verify')
            ->willReturn(new VerifyResult('330.0', false, [
                'Account' => 0,
                'Client' => 0,
                'Category' => 0,
                'Tag' => 0,
            ]));

        $archiveHandler = $this->createMock(ArchiveHandlerInterface::class);
        $archiveHandler->expects(self::once())->method('compressFile')->willReturn('compressed.tar.gz');
        $archiveFactory = $this->createStub(ArchiveHandlerFactoryInterface::class);
        $archiveFactory->method('create')->willReturn($archiveHandler);

        $xmlExportRes = $this->dispatchWeb('get', 'configBackup/xmlExport', [], [
            XmlVerifyService::class => $xmlVerify,
            ArchiveHandlerFactoryInterface::class => $archiveFactory,
        ]);
        self::assertSame('OK', json_decode($xmlExportRes['body'], true, 512, JSON_THROW_ON_ERROR)['status']);
    }

    /**
     * Characterization: DownloadBackupApp/Db/Export controllers share a single config backupHash/exportHash
     * across any user with isConfigBackup=true (no requester ownership isolation), and missing files throw
     * FileException mapped to HTTP 500 empty body under ResponseType::CALLBACK.
     */
    #[Test]
    public function downloadsAreAccessibleCrossUserWithSharedHashAndMissingFileProducesHttp500EmptyBody(): void
    {
        $this->sessionUser = $this->makeUserDto(20, false);
        $this->sessionProfile = new ProfileData(['configBackup' => true]);
        $this->configOverrides = [
            'getBackupHash' => 'sharedbackup123',
            'getExportHash' => 'sharedexport123',
        ];

        $appGz = $this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_app-sharedbackup123.tar.gz';
        $dbGz = $this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_db-sharedbackup123.tar.gz';
        $expGz = $this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_export-sharedexport123.tar.gz';

        file_put_contents($appGz, 'app-bytes');
        file_put_contents($dbGz, 'db-bytes');
        file_put_contents($expGz, 'export-bytes');

        self::assertSame('app-bytes', $this->dispatchWeb('get', 'configBackup/downloadBackupApp')['body']);
        self::assertSame('db-bytes', $this->dispatchWeb('get', 'configBackup/downloadBackupDb')['body']);
        self::assertSame('export-bytes', $this->dispatchWeb('get', 'configBackup/downloadExport')['body']);

        // Remove export archive -> FileException -> HTTP 500 empty CALLBACK body
        unlink($expGz);
        $missingRes = $this->dispatchWeb('get', 'configBackup/downloadExport');
        self::assertSame(500, $missingRes['code']);
        self::assertSame('', $missingRes['body']);
    }

    /**
     * Characterization: XmlExportController password mismatch rejects before touching prior archive or hash;
     * when XmlVerify throws after XmlExport::export(), prior .tar.gz export is already deleted, exportHash
     * is already overwritten, and the unarchived raw .xml file remains on disk.
     */
    #[Test]
    public function xmlExportVerifyFailureLeavesRawXmlOnDiskAfterDeletingPriorArchiveAndUpdatingHash(): void
    {
        $priorArchive = $this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_export-priorhash.tar.gz';
        file_put_contents($priorArchive, 'prior-archive-contents');

        $configData = new ConfigData([
            ConfigDataInterface::INSTALLED => true,
            ConfigDataInterface::APP_VERSION => Version::getVersionStringNormalized(),
            ConfigDataInterface::DATABASE_VERSION => Version::getVersionStringNormalized(),
            ConfigDataInterface::DB_NAME => 'syspass_test',
            ConfigDataInterface::AUTH_BASIC_ENABLED => false,
            ConfigDataInterface::HTTPS_ENABLED => false,
            ConfigDataInterface::PASSWORD_SALT => 'salt-for-xml',
            ConfigDataInterface::EXPORT_HASH => 'priorhash',
        ]);

        $configFileService = $this->createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);
        $configFileService->method('save')->willReturn($configFileService);

        // 1. Password mismatch returns ERROR without touching prior archive or hash
        $mismatchRes = $this->dispatchWeb('post', 'configBackup/xmlExport', [
            'exportPwd' => 'secretA',
            'exportPwdR' => 'secretB',
        ], [
            ConfigFileService::class => $configFileService,
        ]);
        self::assertSame(200, $mismatchRes['code']);
        $mismatchJson = json_decode($mismatchRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $mismatchJson['status']);
        self::assertSame('Passwords do not match', $mismatchJson['description']);
        self::assertFileExists($priorArchive);
        self::assertSame('priorhash', $configData->getExportHash());

        // 2. XmlVerify failure after XmlExport::export()
        $catExport = $this->createStub(XmlCategoryExportService::class);
        $catExport->method('export')->willReturn(new DOMElement('Categories'));
        $cliExport = $this->createStub(XmlClientExportService::class);
        $cliExport->method('export')->willReturn(new DOMElement('Clients'));
        $tagExport = $this->createStub(XmlTagExportService::class);
        $tagExport->method('export')->willReturn(new DOMElement('Tags'));
        $accExport = $this->createStub(XmlAccountExportService::class);
        $accExport->method('export')->willReturn(new DOMElement('Accounts'));

        $failingVerify = $this->createMock(XmlVerifyService::class);
        $failingVerify->expects(self::once())
            ->method('verify')
            ->willThrowException(ServiceException::error('Error while checking integrity hash'));

        $archiveFactory = $this->createMock(ArchiveHandlerFactoryInterface::class);
        $archiveFactory->expects(self::never())->method('create');

        $failRes = $this->dispatchWeb('post', 'configBackup/xmlExport', [], [
            ConfigFileService::class => $configFileService,
            XmlCategoryExportService::class => $catExport,
            XmlClientExportService::class => $cliExport,
            XmlTagExportService::class => $tagExport,
            XmlAccountExportService::class => $accExport,
            XmlVerifyService::class => $failingVerify,
            ArchiveHandlerFactoryInterface::class => $archiveFactory,
        ]);

        self::assertSame(500, $failRes['code']);
        $failJson = json_decode($failRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $failJson['status']);
        self::assertSame('Error while checking integrity hash', $failJson['description']);

        self::assertFileDoesNotExist($priorArchive);
        self::assertNotSame('priorhash', $configData->getExportHash());

        $leftoverXml = glob($this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_export-*.xml') ?: [];
        self::assertCount(1, $leftoverXml);
        self::assertEmpty(glob($this->backupDir . DIRECTORY_SEPARATOR . 'sysPass_export-*.tar.gz') ?: []);
    }

    /**
     * Characterization: DownloadConfigBackupController dispatches download.configBackupFile audit event
     * before checking $type !== 'json' (which yields HTTP 500 empty CALLBACK body), and type=json
     * exports unredacted secrets from ConfigData.
     */
    #[Test]
    public function downloadConfigBackupAuditsUnsupportedTypeBeforeErrorAndExportsUnredactedSecrets(): void
    {
        $secretConfig = new ConfigData([
            ConfigDataInterface::DB_HOST => 'db.internal',
            ConfigDataInterface::DB_PASS => 'super-secret-db-pass',
            ConfigDataInterface::PASSWORD_SALT => 'super-secret-salt',
            ConfigDataInterface::LDAP_BIND_PASS => 'super-secret-ldap-bind',
            ConfigDataInterface::MAIL_PASS => 'super-secret-mail-pass',
            ConfigDataInterface::PROXY_PASS => 'super-secret-proxy-pass',
            ConfigDataInterface::UPGRADE_KEY => 'super-secret-upgrade-key',
        ]);

        $packed = bin2hex((string)gzcompress(serialize($secretConfig)));

        $configService = $this->createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnCallback(
            static fn(string $param): ?string => $param === 'config_backup' ? $packed : null
        );

        $events = [];
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('notify')->willReturnCallback(
            static function (string $name, Event $event) use (&$events): void {
                $events[] = $name;
            }
        );

        // 1. Unsupported type='xml' emits download.configBackupFile BEFORE RuntimeException('Not implemented')
        $unsupportedRes = $this->dispatchWeb('get', 'configGeneral/downloadConfigBackup/xml', [], [
            ConfigService::class => $configService,
            EventDispatcherInterface::class => $dispatcher,
        ]);
        self::assertSame(500, $unsupportedRes['code']);
        self::assertSame('', $unsupportedRes['body']);
        self::assertContains('download.configBackupFile', $events);

        // 2. type='json' returns unredacted secrets
        $jsonRes = $this->dispatchWeb('get', 'configGeneral/downloadConfigBackup/json', [], [
            ConfigService::class => $configService,
        ]);
        self::assertSame(200, $jsonRes['code']);
        $exported = json_decode($jsonRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('super-secret-db-pass', $exported[ConfigDataInterface::DB_PASS]);
        self::assertSame('super-secret-salt', $exported[ConfigDataInterface::PASSWORD_SALT]);
        self::assertSame('super-secret-ldap-bind', $exported[ConfigDataInterface::LDAP_BIND_PASS]);
        self::assertSame('super-secret-mail-pass', $exported[ConfigDataInterface::MAIL_PASS]);
        self::assertSame('super-secret-proxy-pass', $exported[ConfigDataInterface::PROXY_PASS]);
        self::assertSame('super-secret-upgrade-key', $exported[ConfigDataInterface::UPGRADE_KEY]);
    }

    /**
     * Characterization:
     * - ConfigGeneral/SaveController creates and removes AppLock according to maintenance_enabled.
     */
    #[Test]
    public function generalSaveTogglesAppLock(): void
    {
        // ConfigGeneral/SaveController maintenance lock toggle with real AppLock in tempDir
        $configData = new ConfigData([
            ConfigDataInterface::INSTALLED => true,
            ConfigDataInterface::APP_VERSION => Version::getVersionStringNormalized(),
            ConfigDataInterface::DATABASE_VERSION => Version::getVersionStringNormalized(),
            ConfigDataInterface::DB_NAME => 'syspass_test',
            ConfigDataInterface::PASSWORD_SALT => 'salt-for-general',
            ConfigDataInterface::AUTH_BASIC_ENABLED => false,
            ConfigDataInterface::HTTPS_ENABLED => false,
            ConfigDataInterface::MAINTENANCE => false,
        ]);
        $configFileService = $this->createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);

        $appLock = new AppLock($this->lockFile, new FileHandlerFactory());

        $enableRes = $this->dispatchWeb('post', 'configGeneral/save', [
            'site_theme' => 'material-blue',
            'maintenance_enabled' => 1,
        ], [
            ConfigFileService::class => $configFileService,
            AppLockHandler::class => $appLock,
        ]);
        self::assertSame('OK', json_decode($enableRes['body'], true, 512, JSON_THROW_ON_ERROR)['status']);
        self::assertSame(1, $appLock->getLock());
        self::assertFileExists($this->lockFile);

        $disableRes = $this->dispatchWeb('post', 'configGeneral/save', [
            'site_theme' => 'material-blue',
            'maintenance_enabled' => 0,
            'isAjax' => 1,
        ], [
            ConfigFileService::class => $configFileService,
            AppLockHandler::class => $appLock,
        ]);
        self::assertSame('OK', json_decode($disableRes['body'], true, 512, JSON_THROW_ON_ERROR)['status']);
        self::assertFalse($appLock->getLock());
        self::assertFileDoesNotExist($this->lockFile);
    }

    /**
     * @return array{code: int, body: string}
     */
    private function dispatchWeb(
        string $method,
        string $route,
        array $post = [],
        array $overrides = []
    ): array {
        $request = IntegrationTestCase::buildRequest(
            $method,
            'index.php',
            ['r' => $route],
            $post
        );

        $user = $this->sessionUser ?? $this->makeUserDto(1, false);
        $profile = $this->sessionProfile ?? new ProfileData();
        $loggedIn = $this->sessionLoggedIn;
        $authCompleted = $this->sessionAuthCompleted;

        $sessionStub = $this->createStub(SessionContext::class);
        $sessionStub->method('isLoggedIn')->willReturn($loggedIn);
        $sessionStub->method('getAuthCompleted')->willReturn($authCompleted);
        $sessionStub->method('getUserData')->willReturn($user);
        $sessionStub->method('getUserProfile')->willReturn($profile);
        $sessionStub->method('getLocale')->willReturn('en_US');
        $sessionStub->method('getTemporaryMasterPass')->willReturn('');
        $sessionStub->method('getTrasientKey')->willReturn('master-pass');
        $sessionStub->method('getCSRF')->willReturn(null);

        $baseOverrides = [
            Context::class => $sessionStub,
            SessionContext::class => $sessionStub,
            AclInterface::class => factory(static fn(ContainerInterface $c): Acl => new Acl(
                $c->get(Context::class),
                $c->get(EventDispatcherInterface::class),
                $c->get(ActionsInterface::class)
            )),
            'paths' => decorate(function (array $paths): array {
                $pathOverrides = [
                    Path::TMP->name => $this->tempDir,
                    Path::BACKUP->name => $this->backupDir,
                    Path::LOG_FILE->name => $this->logFile,
                ];

                $mapped = array_map(
                    static fn(array $spec): array => [$spec[0], $pathOverrides[$spec[0]->name] ?? $spec[1]],
                    $paths
                );

                $mapped[] = [Path::VIEW, FileSystem::buildPath(REAL_APP_ROOT, 'app', 'modules', 'web', 'themes')];
                $mapped[] = [Path::PLUGINS, FileSystem::buildPath(REAL_APP_ROOT, 'app', 'modules', 'web', 'plugins')];

                return $mapped;
            }),
        ];

        $container = $this->buildContainer($request, array_merge($baseOverrides, $overrides));

        ob_start();
        try {
            IntegrationTestCase::runApp($container);
            $output = (string)ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        /** @var Response $response */
        $response = $container->get(Response::class);

        return [
            'code' => $response->status()->getCode(),
            'body' => $output,
        ];
    }

    protected function getConfigData(): array
    {
        return array_merge(parent::getConfigData(), $this->configOverrides);
    }

    private function makeUserDto(int $id, bool $isAdminApp): UserDto
    {
        return new UserDto(
            id: $id,
            login: 'user_' . $id,
            userGroupId: 1,
            userGroupName: 'Admins',
            userProfileId: 1,
            isAdminApp: $isAdminApp,
            lastUpdateMPass: 1000
        );
    }

    private function makeFullConfigProfile(): ProfileData
    {
        return new ProfileData([
            'configGeneral' => true,
            'configEncryption' => true,
            'configBackup' => true,
            'configImport' => true,
        ]);
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
