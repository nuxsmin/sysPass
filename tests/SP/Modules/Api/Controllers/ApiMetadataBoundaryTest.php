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
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Auth\Services\AuthToken as AuthTokenService;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Core\Events\EventDispatcher;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Database\Dtos\DatabaseConnectionData;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Api\Adapters\ApiRequestAdapter;
use SP\Modules\Api\Bootstrap;
use SP\Tests\DatabaseTrait;

use function DI\create;
use function SP\Tests\getDbHandler;

defined('APP_MODULE') || define('APP_MODULE', 'api');

/**
 * DB-backed JSON-RPC characterization tests for Category, Client, Tag, and UserGroup API endpoints:
 * - Auth denials: omitted authToken (`Invalid format` -32600), wrong action token (-32603), disabled user, and missing ACL (Track.source = Api::class)
 * - Whitespace-only `name` accepted by category/client/userGroup vs empty-string rejection on tag/create
 * - `category/edit` on non-existent ID returning resultCode=0 vs `tag/edit` returning 1001 (`Tag not found`) via post-update `GetTagUseCase`
 * - `category/delete` & `client/delete` failing FK vs `tag/delete` cascading `AccountToTag` (while triggering `CustomFieldDataRepository::deleteBatch()` warning)
 * - `userGroup/create` failing on duplicate `usersId` (`uk_UserToUserGroup_01`, `Integrity constraint`)
 * - `client/view` crashing with HTTP 500 (-32603 `TypeError` in `Filter::getString(true)`) when `customFields` is boolean `true`, vs succeeding with `customFields: '1'` (emitting `show.client` twice and resolving `customFields` via lazy `Scope::toArray()`)
 */
#[Group('integration')]
final class ApiMetadataBoundaryTest extends TestCase
{
    use DatabaseTrait;

    private const TOKEN_PASS = 'synthetic-meta-api-pass-2026';
    private const MASTER_PASS = '12345678900';

    private static ?array $moduleDefinitions = null;
    private string $tempDir;

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

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'syspass-api-meta-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    #[Test]
    public function authAndAclBoundariesRejectMissingTokenWrongScopeDisabledUserAndMissingProfilePermission(): void
    {
        $pdo = getDbHandler()->getConnection();

        $emptyProfileId = $this->insertProfile($pdo, 'EmptyMetaProfile', new ProfileData());
        $noPrivUserId = $this->insertUser($pdo, 'api_meta_nopriv', $emptyProfileId, 1, false);
        $fullUserId = 1;

        $catViewToken = $this->seedTokenForUser($fullUserId, AclActionsInterface::CATEGORY_VIEW);
        $noPrivCatCreateToken = $this->seedTokenForUser($noPrivUserId, AclActionsInterface::CATEGORY_CREATE);

        // Disabled user with admin profile
        $disabledUserId = $this->insertUser($pdo, 'api_meta_disabled', 1, 1, true);
        $disabledToken = $this->seedTokenForUser($disabledUserId, AclActionsInterface::CLIENT_VIEW);

        // 1. Omitted authToken -> rejected by ApiRequestAdapter::buildFromJson() with HTTP 400 / -32600 Invalid format
        $missingTokenRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'category/view',
            'params' => ['id' => 1],
            'id' => 1,
        ]);
        self::assertSame(400, $missingTokenRes['code']);
        self::assertSame(JsonRpcErrorCode::INVALID_REQUEST, $missingTokenRes['json']['error']['code']);
        self::assertSame('Invalid format', $missingTokenRes['json']['error']['message']);

        // 2. Wrong scope token -> SQL misses actionId -> Internal error
        $wrongScopeRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'client/view',
            'params' => ['authToken' => $catViewToken->getToken(), 'id' => 1],
            'id' => 2,
        ]);
        self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $wrongScopeRes['json']['error']['code']);
        self::assertSame('Internal error', $wrongScopeRes['json']['error']['message']);

        // 3. Disabled user & missing ACL -> Unauthorized access + Track increment
        $disabledRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'client/view',
            'params' => ['authToken' => $disabledToken->getToken(), 'id' => 1],
            'id' => 3,
        ]);
        self::assertSame('Unauthorized access', $disabledRes['json']['error']['message']);

        $noAclRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'category/create',
            'params' => ['authToken' => $noPrivCatCreateToken->getToken(), 'name' => 'Denied'],
            'id' => 4,
        ]);
        self::assertSame('Unauthorized access', $noAclRes['json']['error']['message']);
        $trackStmt = $pdo->prepare('SELECT COUNT(*) FROM Track WHERE source = :source');
        $trackStmt->execute([':source' => \SP\Domain\Api\Services\Api::class]);
        self::assertSame(2, (int)$trackStmt->fetchColumn());
    }

    #[Test]
    public function createValidationAndNonExistentEditBehaviorAcrossMetadataApis(): void
    {
        // Whitespace-only name trimmed to '' yet accepted by category/client/userGroup and rejected by tag/create
        $catCreate = $this->seedTokenForUser(1, AclActionsInterface::CATEGORY_CREATE);
        $wsCat = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'category/create',
            'params' => ['authToken' => $catCreate->getToken(), 'name' => '   '],
            'id' => 10,
        ]);
        self::assertArrayHasKey('result', $wsCat['json'], $wsCat['raw']);
        self::assertSame(0, $wsCat['json']['result']['resultCode']);
        self::assertSame('', $wsCat['json']['result']['result']['name']);

        $clientCreate = $this->seedTokenForUser(1, AclActionsInterface::CLIENT_CREATE);
        $wsClient = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'client/create',
            'params' => ['authToken' => $clientCreate->getToken(), 'name' => '   '],
            'id' => 11,
        ]);
        self::assertArrayHasKey('result', $wsClient['json'], $wsClient['raw']);
        self::assertSame(0, $wsClient['json']['result']['resultCode']);
        self::assertSame('', $wsClient['json']['result']['result']['name']);

        $groupCreate = $this->seedTokenForUser(1, AclActionsInterface::GROUP_CREATE);
        $wsGroup = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'userGroup/create',
            'params' => ['authToken' => $groupCreate->getToken(), 'name' => '   '],
            'id' => 12,
        ]);
        self::assertArrayHasKey('result', $wsGroup['json'], $wsGroup['raw']);
        self::assertSame(0, $wsGroup['json']['result']['resultCode']);
        self::assertSame('', $wsGroup['json']['result']['result']['name']);

        $tagCreate = $this->seedTokenForUser(1, AclActionsInterface::TAG_CREATE);
        $wsTag = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'tag/create',
            'params' => ['authToken' => $tagCreate->getToken(), 'name' => '   '],
            'id' => 13,
        ]);
        self::assertArrayHasKey('error', $wsTag['json'], $wsTag['raw']);
        self::assertSame('A tag name is needed', $wsTag['json']['error']['message']);

        // Edit on non-existent ID: Category/Edit returns resultCode=0 (no re-fetch), whereas Tag/Edit re-fetches via GetTagUseCase and returns 1001 'Tag not found'
        $catEdit = $this->seedTokenForUser(1, AclActionsInterface::CATEGORY_EDIT);
        $missingCatEdit = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'category/edit',
            'params' => ['authToken' => $catEdit->getToken(), 'id' => 9999, 'name' => 'Ghost Cat'],
            'id' => 14,
        ]);
        self::assertArrayHasKey('result', $missingCatEdit['json'], $missingCatEdit['raw']);
        self::assertSame(0, $missingCatEdit['json']['result']['resultCode']);

        $tagEdit = $this->seedTokenForUser(1, AclActionsInterface::TAG_EDIT);
        $missingTagEdit = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'tag/edit',
            'params' => ['authToken' => $tagEdit->getToken(), 'id' => 9999, 'name' => 'Ghost Tag'],
            'id' => 15,
        ]);
        self::assertArrayHasKey('error', $missingTagEdit['json'], $missingTagEdit['raw']);
        self::assertSame(1001, $missingTagEdit['json']['error']['code']);
        self::assertSame('Tag not found', $missingTagEdit['json']['error']['message']);
    }

    #[Test]
    public function deleteCascadeVsFkAndUserGroupDuplicateUsersAndClientViewCustomFieldsBehavior(): void
    {
        $pdo = getDbHandler()->getConnection();

        // 1. In-use Category & Client delete fail on FK constraint
        $catDel = $this->seedTokenForUser(1, AclActionsInterface::CATEGORY_DELETE);
        $catErr = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'category/delete',
            'params' => ['authToken' => $catDel->getToken(), 'id' => 1],
            'id' => 20,
        ]);
        self::assertArrayHasKey('error', $catErr['json'], $catErr['raw']);

        $clientDel = $this->seedTokenForUser(1, AclActionsInterface::CLIENT_DELETE);
        $clientErr = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'client/delete',
            'params' => ['authToken' => $clientDel->getToken(), 'id' => 1],
            'id' => 21,
        ]);
        self::assertArrayHasKey('error', $clientErr['json'], $clientErr['raw']);

        // 2. In-use Tag delete cascades AccountToTag and succeeds without CustomFieldData::deleteBatch() array-binding warning
        $tagDel = $this->seedTokenForUser(1, AclActionsInterface::TAG_DELETE);
        $warningSeen = false;
        set_error_handler(static function (int $severity, string $message) use (&$warningSeen): bool {
            if ($severity === E_WARNING && str_contains($message, 'Array to string conversion')) {
                $warningSeen = true;
                return true;
            }
            return false;
        });
        try {
            $tagOk = $this->dispatchJsonRpc([
                'jsonrpc' => '2.0',
                'method' => 'tag/delete',
                'params' => ['authToken' => $tagDel->getToken(), 'id' => 1],
                'id' => 22,
            ]);
        } finally {
            restore_error_handler();
        }
        self::assertFalse($warningSeen);
        self::assertArrayHasKey('result', $tagOk['json'], $tagOk['raw']);
        self::assertSame(0, $tagOk['json']['result']['resultCode']);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM AccountToTag WHERE tagId = 1')->fetchColumn());

        // 3. Duplicate usersId on userGroup/create fails on uk_UserToUserGroup_01 with ConstraintException ('Integrity constraint')
        $groupCreate = $this->seedTokenForUser(1, AclActionsInterface::GROUP_CREATE);
        $dupGroup = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'userGroup/create',
            'params' => [
                'authToken' => $groupCreate->getToken(),
                'name' => 'API Dup Users',
                'usersId' => [1, 1],
            ],
            'id' => 23,
        ]);
        self::assertSame('Integrity constraint', $dupGroup['json']['error']['message']);

        // 4a. client/view with boolean customFields=true crashes with TypeError in Filter::getString(true) -> HTTP 500 / -32603
        $clientView = $this->seedTokenForUser(1, AclActionsInterface::CLIENT_VIEW);
        $boolCustomFieldsRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'client/view',
            'params' => [
                'authToken' => $clientView->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => 1,
                'customFields' => true,
            ],
            'id' => 24,
        ]);
        self::assertSame(500, $boolCustomFieldsRes['code']);
        self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $boolCustomFieldsRes['json']['error']['code']);
        self::assertSame('Internal error', $boolCustomFieldsRes['json']['error']['message']);

        // 4b. client/view with string customFields='1' emits 'show.client' twice and calls parseIncludes after createData()
        $showClientEvents = 0;
        $dispatcher = new EventDispatcher();
        $dispatcher->attach(new class ($showClientEvents) implements EventReceiver {
            public function __construct(private int &$count)
            {
            }

            public function update(string $eventType, Event $event): void
            {
                if ($eventType === 'show.client') {
                    $this->count++;
                }
            }

            public function getEvents(): ?string
            {
                return 'show\.client';
            }
        });

        $viewRes = $this->dispatchJsonRpc(
            [
                'jsonrpc' => '2.0',
                'method' => 'client/view',
                'params' => [
                    'authToken' => $clientView->getToken(),
                    'tokenPass' => self::TOKEN_PASS,
                    'id' => 1,
                    'customFields' => '1',
                ],
                'id' => 25,
            ],
            [EventDispatcherInterface::class => $dispatcher]
        );

        self::assertArrayHasKey('result', $viewRes['json'], $viewRes['raw']);
        self::assertSame(0, $viewRes['json']['result']['resultCode']);
        self::assertSame(2, $showClientEvents);
        // Fractal's Scope evaluates requestedIncludes lazily during toArray(), so parseIncludes() before toArray() populates customFields
        self::assertIsArray($viewRes['json']['result']['result']['data']['customFields']['data'] ?? null);
    }

    private function seedTokenForUser(
        int $userId,
        int $actionId
    ): AuthTokenModel {
        $stmt = getDbHandler()->getConnection()->prepare('DELETE FROM AuthToken WHERE userId = :userId');
        $stmt->execute([':userId' => $userId]);

        $container = (new ContainerBuilder())
            ->addDefinitions(CoreDefinitions::getDefinitions(REAL_APP_ROOT, APP_MODULE))
            ->addDefinitions(DomainDefinitions::getDefinitions())
            ->addDefinitions(self::$moduleDefinitions)
            ->addDefinitions($this->configDefinitions())
            ->build();

        $container->get(ConfigDataInterface::class);

        $context = $container->get(Context::class);
        $context->initialize();
        $context->setUserData(new UserDto(
            id: 1,
            login: 'admin',
            userGroupId: 1,
            userGroupName: 'Admins',
            userProfileId: 1,
            isAdminApp: true,
            lastUpdateMPass: 1528236611,
            preferences: new UserPreferences(['user_id' => 1, 'lang' => 'en_US'])
        ));
        $context->setUserProfile(new ProfileData());
        $context->setTrasientKey('_masterpass', self::MASTER_PASS);

        /** @var AuthTokenService $tokenService */
        $tokenService = $container->get(AuthTokenService::class);

        $model = new AuthTokenModel([
            'actionId' => $actionId,
            'createdBy' => 1,
            'hash' => self::TOKEN_PASS,
            'userId' => $userId,
        ]);

        $id = $tokenService->create($model);

        return $tokenService->getById($id);
    }

    private function insertProfile(PDO $pdo, string $name, ProfileData $data): int
    {
        $stmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (:name, :profile)');
        $stmt->execute([
            ':name' => $name,
            ':profile' => Serde::serialize($data),
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function insertUser(
        PDO $pdo,
        string $login,
        int $profileId,
        int $groupId,
        bool $disabled
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, loginCount, userProfileId, lastUpdateMPass, isDisabled, hashSalt)
             VALUES (:name, :groupId, :login, :pass, 0, :profileId, 0, :disabled, :salt)'
        );
        $stmt->execute([
            ':name' => $login,
            ':groupId' => $groupId,
            ':login' => $login,
            ':pass' => '$2y$10$dummyhashforintegrationtest0000000000000000000000000',
            ':profileId' => $profileId,
            ':disabled' => $disabled ? 1 : 0,
            ':salt' => 'a',
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<class-string, mixed> $overrides
     * @return array{code: int, raw: string, json: array<string, mixed>}
     */
    private function dispatchJsonRpc(array $payload, array $overrides = []): array
    {
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $requestFile = tempnam($this->tempDir, 'api-meta-req-');
        if ($requestFile === false) {
            throw new RuntimeException('Unable to create temporary API request file');
        }

        file_put_contents($requestFile, $rawBody);

        $request = new Request(
            [],
            [],
            [],
            [
                'HTTP_HOST' => 'localhost:8080',
                'HTTP_ACCEPT' => 'application/json',
                'REQUEST_URI' => '/api.php',
                'REQUEST_METHOD' => 'POST',
                'HTTP_CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => '127.0.0.1',
            ],
            [],
            null
        );

        $baseDefinitions = array_merge($this->configDefinitions(), [
            Request::class => $request,
            Response::class => create(Response::class),
            ApiRequestService::class => static fn() => ApiRequestAdapter::buildFromRequest($requestFile),
        ]);

        $container = (new ContainerBuilder())
            ->addDefinitions(CoreDefinitions::getDefinitions(REAL_APP_ROOT, APP_MODULE))
            ->addDefinitions(DomainDefinitions::getDefinitions())
            ->addDefinitions(self::$moduleDefinitions)
            ->addDefinitions(array_merge($baseDefinitions, $overrides))
            ->build();

        $container->get(ConfigDataInterface::class);

        ob_start();
        try {
            Bootstrap::run($container->get(BootstrapInterface::class), $container->get(ModuleInterface::class));
            $echoed = (string)ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        } finally {
            @unlink($requestFile);
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $body = (string)$response->body();
        $raw = $body !== '' ? $body : $echoed;

        $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return [
            'code' => (int)$response->code(),
            'raw' => $raw,
            'json' => $json,
        ];
    }

    /**
     * @return array<class-string, mixed>
     */
    private function configDefinitions(): array
    {
        $dbData = DatabaseConnectionData::getFromEnvironment();

        return [
            DatabaseConnectionData::class => $dbData,
            ConfigDataInterface::class => static function (ConfigFileService $config) use ($dbData): ConfigDataInterface {
                $configData = $config->getConfigData()
                    ->setDbHost($dbData->getDbHost())
                    ->setDbPort($dbData->getDbPort())
                    ->setDbName($dbData->getDbName())
                    ->setDbUser($dbData->getDbUser())
                    ->setDbPass($dbData->getDbPass())
                    ->setInstalled(true)
                    ->setDemoEnabled(false)
                    ->setLogEnabled(false)
                    ->setDebug(false)
                    ->setMaintenance(false);

                $config->save($configData, false);

                return $configData;
            },
        ];
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
