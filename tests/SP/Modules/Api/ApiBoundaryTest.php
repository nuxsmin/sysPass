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

namespace SP\Tests\Modules\Api;

use DI\ContainerBuilder;
use Klein\Request;
use Klein\Response;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
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
 * DB-backed characterization tests for JSON-RPC API boundaries:
 * token lookup/scope, disabled users, denied ACL, vault errors, Track LIMIT 1,
 * request ID coercion, notifications, malformed payloads, and unknown methods.
 */
#[Group('integration')]
final class ApiBoundaryTest extends TestCase
{
    use DatabaseTrait;

    private const TOKEN_PASS = 'synthetic-api-token-pass-2026';
    private const MASTER_PASS = '12345678900';

    private static ?array $moduleDefinitions = null;

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

        parent::setUp();
    }

    /**
     * Characterization of Api::setup() missing-token and wrong-action-scope handling:
     * 1. Unknown authToken triggers NoSuchItemException from AuthTokenRepository::getTokenByToken()
     *    and returns "Internal error" (-32603) WITHOUT adding a Track row.
     * 2. Valid authToken belonging to ACCOUNT_SEARCH used against tag/search (TAG_SEARCH) also fails
     *    getTokenByToken() because SQL filters `WHERE actionId = :actionId AND token = :token`,
     *    returning "Internal error" WITHOUT adding a Track row (making line 133 actionId check unreachable).
     */
    #[Test]
    public function unknownTokenAndWrongActionScopeReturnInternalErrorWithoutRecordingTrack(): void
    {
        $pdo = getDbHandler()->getConnection();
        $accountSearchToken = $this->seedTokenForUser(1, AclActionsInterface::ACCOUNT_SEARCH);

        // 1. Unknown token
        $unknownRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'tag/search',
            'params' => ['authToken' => 'nonexistent-synthetic-token-2026'],
            'id' => 11,
        ]);

        self::assertSame(200, $unknownRes['code']);
        self::assertSame(11, $unknownRes['json']['id'] ?? null);
        self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $unknownRes['json']['error']['code'] ?? null);
        self::assertSame('Internal error', $unknownRes['json']['error']['message'] ?? null);
        self::assertNull($unknownRes['json']['error']['data'] ?? null);

        // 2. Valid token scoped to ACCOUNT_SEARCH called on tag/search
        $wrongScopeRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'tag/search',
            'params' => ['authToken' => $accountSearchToken->getToken()],
            'id' => 12,
        ]);

        self::assertSame(200, $wrongScopeRes['code']);
        self::assertSame(12, $wrongScopeRes['json']['id'] ?? null);
        self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $wrongScopeRes['json']['error']['code'] ?? null);
        self::assertSame('Internal error', $wrongScopeRes['json']['error']['message'] ?? null);
        self::assertNull($wrongScopeRes['json']['error']['data'] ?? null);

        // Independent DB read: neither call persisted a Track row
        self::assertSame(0, $this->countApiTracks($pdo));
    }

    /**
     * Characterization:
     * 1. Disabled user with valid authToken receives "Unauthorized access" (-32603) and records Track.
     * 2. Enabled non-admin user lacking TAG_SEARCH profile permission receives "Unauthorized access" (-32603)
     *    and ALSO records Track (post-authentication ACL denial is counted in Track).
     */
    #[Test]
    public function disabledUserAndDeniedAclReturnUnauthorizedAccessAndRecordTrack(): void
    {
        $pdo = getDbHandler()->getConnection();

        // Seed a non-admin profile without TAG_SEARCH
        $profileData = new ProfileData();
        $profileData->setAccView(true);
        $profileStmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (:name, :profile)');
        $profileStmt->execute([
            'name' => 'Synthetic Restricted API Profile',
            'profile' => Serde::serialize($profileData),
        ]);
        $restrictedProfileId = (int)$pdo->lastInsertId();

        $disabledUserId = $this->insertUser($pdo, 'api_disabled_' . bin2hex(random_bytes(4)), $restrictedProfileId, true);
        $restrictedUserId = $this->insertUser($pdo, 'api_restricted_' . bin2hex(random_bytes(4)), $restrictedProfileId, false);

        $disabledToken = $this->seedTokenForUser($disabledUserId, AclActionsInterface::TAG_SEARCH);
        $restrictedToken = $this->seedTokenForUser($restrictedUserId, AclActionsInterface::TAG_SEARCH);

        // Disabled user
        $disabledRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'tag/search',
            'params' => ['authToken' => $disabledToken->getToken()],
            'id' => 21,
        ]);
        self::assertSame(200, $disabledRes['code']);
        self::assertSame('Unauthorized access', $disabledRes['json']['error']['message'] ?? null);
        self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $disabledRes['json']['error']['code'] ?? null);
        self::assertSame(1, $this->countApiTracks($pdo));

        // Enabled user denied by ACL (TAG_SEARCH missing in profile)
        $aclRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'tag/search',
            'params' => ['authToken' => $restrictedToken->getToken()],
            'id' => 22,
        ]);
        self::assertSame(200, $aclRes['code']);
        self::assertSame('Unauthorized access', $aclRes['json']['error']['message'] ?? null);
        self::assertSame(JsonRpcErrorCode::INTERNAL_ERROR, $aclRes['json']['error']['code'] ?? null);
        self::assertSame(2, $this->countApiTracks($pdo));
    }

    /**
     * Characterization of secured-action vault & Track behavior:
     * 1. Wrong tokenPass returns "Unauthorized access", adds Track, and leaves Account.countView unchanged.
     * 2. Empty/null vault returns "Internal error" with data.help = ["Invalid data"].
     * 3. Corrupt serialized vault exposes the caught deserialization message in error.data.help.
     */
    #[Test]
    public function wrongTokenPassTracksAndVaultFailuresExposeHintWithoutSideEffects(): void
    {
        $pdo = getDbHandler()->getConnection();
        $token = $this->seedTokenForUser(1, AclActionsInterface::ACCOUNT_VIEW_PASS);

        $countBefore = (int)$pdo->query('SELECT countView FROM Account WHERE id = 1')->fetchColumn();

        // 1. Wrong tokenPass
        $wrongPassRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => $token->getToken(),
                'tokenPass' => 'wrong-synthetic-token-pass',
                'id' => 1,
            ],
            'id' => 31,
        ]);
        self::assertSame(200, $wrongPassRes['code']);
        self::assertSame('Unauthorized access', $wrongPassRes['json']['error']['message'] ?? null);
        self::assertSame(1, $this->countApiTracks($pdo));

        // Account.countView unchanged
        self::assertSame($countBefore, (int)$pdo->query('SELECT countView FROM Account WHERE id = 1')->fetchColumn());

        // 2. Empty vault with matching tokenPass hash -> error.data.help = ["Invalid data"]
        $pdo->prepare('UPDATE AuthToken SET vault = NULL WHERE id = :id')->execute(['id' => $token->getId()]);

        $emptyVaultRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => $token->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => 1,
            ],
            'id' => 32,
        ]);
        self::assertSame('Internal error', $emptyVaultRes['json']['error']['message'] ?? null);
        self::assertSame(['Invalid data'], $emptyVaultRes['json']['error']['data']['help'] ?? null);
        self::assertSame(1, $this->countApiTracks($pdo));

        // 3. Corrupt serialized vault -> exposes exception detail in error.data.help
        $pdo->prepare('UPDATE AuthToken SET vault = :vault WHERE id = :id')->execute([
            'vault' => 'not-a-valid-serialized-vault',
            'id' => $token->getId(),
        ]);

        $corruptVaultRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => $token->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => 1,
            ],
            'id' => 33,
        ]);
        self::assertSame('Internal error', $corruptVaultRes['json']['error']['message'] ?? null);
        $help = $corruptVaultRes['json']['error']['data']['help'] ?? [];
        self::assertIsArray($help);
        self::assertNotEmpty($help);
        self::assertSame(
            ["Couldn't rewrite the serialized payload: Malformed serialized payload at offset 0"],
            $help
        );
        self::assertSame(1, $this->countApiTracks($pdo));
    }

    /**
     * Characterization of TrackRepository LIMIT 1 defect on the API boundary:
     * 12 consecutive denied requests persist 12 rows in MariaDB `Track` (source=Api::class),
     * yet the 11th and 12th requests still return "Unauthorized access" rather than "Attempts exceeded".
     */
    #[Test]
    public function repeatedApiAccessDenialsPersistOverTenTracksWithoutTriggeringThrottle(): void
    {
        $pdo = getDbHandler()->getConnection();
        $token = $this->seedTokenForUser(1, AclActionsInterface::ACCOUNT_VIEW_PASS);

        for ($i = 1; $i <= 12; $i++) {
            $res = $this->dispatchJsonRpc([
                'jsonrpc' => '2.0',
                'method' => 'account/viewPass',
                'params' => [
                    'authToken' => $token->getToken(),
                    'tokenPass' => 'wrong-pass-' . $i,
                    'id' => 1,
                ],
                'id' => $i,
            ]);

            self::assertSame('Unauthorized access', $res['json']['error']['message'] ?? null);
        }

        self::assertSame(12, $this->countApiTracks($pdo));
    }

    /**
     * Characterization of JSON-RPC ID coercion at the full API dispatch boundary
     * (`ApiRequestAdapter` casts via `filter_var($id, FILTER_VALIDATE_INT) ?: 1`).
     *
     * @param int|string|float $sentId
     */
    #[DataProvider('provideSuccessRequestIds')]
    #[Test]
    public function validApiRequestsCoerceZeroStringAndFloatIdsToOne(int|string|float $sentId, int $expectedId): void
    {
        $token = $this->seedTokenForUser(1, AclActionsInterface::TAG_SEARCH);

        $res = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'tag/search',
            'params' => ['authToken' => $token->getToken()],
            'id' => $sentId,
        ]);

        self::assertSame(200, $res['code']);
        self::assertSame(0, $res['json']['result']['resultCode'] ?? null);
        self::assertSame($expectedId, $res['json']['id'] ?? null);
    }

    public static function provideSuccessRequestIds(): array
    {
        return [
            'zero coerced to 1' => [0, 1],
            'negative int preserved' => [-5, -5],
            'large int preserved' => [9007199254740991, 9007199254740991],
            'string id coerced to 1' => ['req-xyz', 1],
            'numeric string coerced to int' => ['77', 77],
            'float coerced to 1' => [2.5, 1],
        ];
    }

    /**
     * Characterization of JSON-RPC null ID, notifications (omitted ID), malformed JSON,
     * empty body, and unknown method behavior through Api\Bootstrap.
     */
    #[Test]
    public function invalidJsonNullIdNotificationAndUnknownMethodReturnCurrentErrorEnvelopes(): void
    {
        $pdo = getDbHandler()->getConnection();
        $token = $this->seedTokenForUser(1, AclActionsInterface::TAG_CREATE);

        // 1. Notification (omitted id) returns HTTP 500 JSON-RPC INVALID_REQUEST with id=0 (not silent)
        // and does NOT execute tag/create
        $notificationRes = $this->dispatchRawBody(json_encode([
            'jsonrpc' => '2.0',
            'method' => 'tag/create',
            'params' => [
                'authToken' => $token->getToken(),
                'name' => 'notification-tag-should-not-exist',
            ],
        ], JSON_THROW_ON_ERROR));

        self::assertSame(500, $notificationRes['code']);
        self::assertSame(0, $notificationRes['json']['id'] ?? null);
        self::assertSame(JsonRpcErrorCode::INVALID_REQUEST, $notificationRes['json']['error']['code'] ?? null);
        self::assertSame('Invalid format', $notificationRes['json']['error']['message'] ?? null);
        self::assertSame(
            0,
            (int)$pdo->query("SELECT COUNT(*) FROM Tag WHERE name = 'notification-tag-should-not-exist'")->fetchColumn()
        );

        // 2. Null ID returns HTTP 500 INVALID_REQUEST with id=0
        $nullIdRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'tag/search',
            'params' => ['authToken' => $token->getToken()],
            'id' => null,
        ]);
        self::assertSame(500, $nullIdRes['code']);
        self::assertSame(0, $nullIdRes['json']['id'] ?? null);
        self::assertSame(JsonRpcErrorCode::INVALID_REQUEST, $nullIdRes['json']['error']['code'] ?? null);
        self::assertSame('Invalid format', $nullIdRes['json']['error']['message'] ?? null);

        // 3. Malformed JSON returns HTTP 500 PARSE_ERROR with id=0 and exposes JsonException message in data.help
        $malformedRes = $this->dispatchRawBody('{invalid-json');
        self::assertSame(500, $malformedRes['code']);
        self::assertSame(0, $malformedRes['json']['id'] ?? null);
        self::assertSame(JsonRpcErrorCode::PARSE_ERROR, $malformedRes['json']['error']['code'] ?? null);
        self::assertSame('Invalid data', $malformedRes['json']['error']['message'] ?? null);
        self::assertSame(['Syntax error'], $malformedRes['json']['error']['data']['help'] ?? null);

        // 4. Empty body returns HTTP 500 PARSE_ERROR with id=0
        $emptyRes = $this->dispatchRawBody('');
        self::assertSame(500, $emptyRes['code']);
        self::assertSame(0, $emptyRes['json']['id'] ?? null);
        self::assertSame(JsonRpcErrorCode::PARSE_ERROR, $emptyRes['json']['error']['code'] ?? null);
        self::assertSame('Invalid data', $emptyRes['json']['error']['message'] ?? null);

        // 5. Unknown method returns HTTP 404 METHOD_NOT_FOUND with request ID preserved
        $unknownMethodRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'nonexistent/action',
            'params' => ['authToken' => 'any-token'],
            'id' => 55,
        ]);
        self::assertSame(404, $unknownMethodRes['code']);
        self::assertSame(55, $unknownMethodRes['json']['id'] ?? null);
        self::assertSame(JsonRpcErrorCode::METHOD_NOT_FOUND, $unknownMethodRes['json']['error']['code'] ?? null);
        self::assertSame(
            'Oops, it looks like this content does not exist...',
            $unknownMethodRes['json']['error']['message'] ?? null
        );

        // 6. Existing public non-Action method on a real controller returns HTTP 404 METHOD_NOT_FOUND
        $nonActionMethodRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/isAuthenticated',
            'params' => ['authToken' => 'any-token'],
            'id' => 56,
        ]);
        self::assertSame(404, $nonActionMethodRes['code']);
        self::assertSame(56, $nonActionMethodRes['json']['id'] ?? null);
        self::assertSame(JsonRpcErrorCode::METHOD_NOT_FOUND, $nonActionMethodRes['json']['error']['code'] ?? null);
    }

    /**
     * Missing required parameters on Account/ViewController returns INVALID_PARAMS (-32602)
     * with AccountHelp details in error.data.help and leaves Account.countView unchanged.
     */
    #[Test]
    public function missingRequiredParameterReturnsHelpHintWithoutModifyingAccountCountView(): void
    {
        $pdo = getDbHandler()->getConnection();
        $token = $this->seedTokenForUser(1, AclActionsInterface::ACCOUNT_VIEW);
        $countBefore = (int)$pdo->query('SELECT countView FROM Account WHERE id = 1')->fetchColumn();

        $res = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/view',
            'params' => ['authToken' => $token->getToken()],
            'id' => 61,
        ]);

        self::assertSame(200, $res['code']);
        self::assertSame(61, $res['json']['id'] ?? null);
        self::assertSame(JsonRpcErrorCode::INVALID_PARAMS, $res['json']['error']['code'] ?? null);
        self::assertSame('Wrong parameters', $res['json']['error']['message'] ?? null);
        self::assertIsArray($res['json']['error']['data']['help'] ?? null);
        self::assertSame($countBefore, (int)$pdo->query('SELECT countView FROM Account WHERE id = 1')->fetchColumn());
    }

    private function seedTokenForUser(int $userId, int $actionId): AuthTokenModel
    {
        $databaseConnectionData = DatabaseConnectionData::getFromEnvironment();

        $dic = (new ContainerBuilder())
            ->addDefinitions(array_merge(
                CoreDefinitions::getDefinitions(REAL_APP_ROOT, 'api'),
                DomainDefinitions::getDefinitions(),
                self::$moduleDefinitions,
                [
                    DatabaseConnectionData::class => $databaseConnectionData,
                    ConfigDataInterface::class => static function (ConfigFileService $config) use ($databaseConnectionData) {
                        $configData = $config->getConfigData()
                            ->setDbHost($databaseConnectionData->getDbHost())
                            ->setDbName($databaseConnectionData->getDbName())
                            ->setDbUser($databaseConnectionData->getDbUser())
                            ->setDbPass($databaseConnectionData->getDbPass())
                            ->setInstalled(true);
                        $config->save($configData, false);

                        return $configData;
                    },
                ]
            ))
            ->build();

        $context = $dic->get(Context::class);
        $context->initialize();
        $context->setUserData(new UserDto(
            id: 1,
            login: 'admin',
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

    private function insertUser(PDO $pdo, string $login, int $profileId, bool $isDisabled): int
    {
        $stmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, 1, :login, :pass, :email, :profileId, 0, 0, :disabled, :salt, 0, 0, 0, 0)'
        );
        $stmt->execute([
            'name' => 'Synthetic ' . $login,
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
        return $this->dispatchRawBody(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{code: int, body: string, json: array<string, mixed>}
     */
    private function dispatchRawBody(string $rawBody): array
    {
        $databaseConnectionData = DatabaseConnectionData::getFromEnvironment();
        $requestFile = tempnam(TMP_PATH, 'api-boundary-');
        if ($requestFile === false) {
            throw new RuntimeException('Unable to create temporary API request file');
        }

        try {
            file_put_contents($requestFile, $rawBody);

            $dic = (new ContainerBuilder())
                ->addDefinitions(array_merge(
                    CoreDefinitions::getDefinitions(REAL_APP_ROOT, 'api'),
                    DomainDefinitions::getDefinitions(),
                    self::$moduleDefinitions,
                    [
                        DatabaseConnectionData::class => $databaseConnectionData,
                        ConfigDataInterface::class => static function (ConfigFileService $config) use ($databaseConnectionData) {
                            $configData = $config->getConfigData()
                                ->setDbHost($databaseConnectionData->getDbHost())
                                ->setDbName($databaseConnectionData->getDbName())
                                ->setDbUser($databaseConnectionData->getDbUser())
                                ->setDbPass($databaseConnectionData->getDbPass())
                                ->setInstalled(true);
                            $config->save($configData, false);

                            return $configData;
                        },
                        ApiRequestService::class => static fn() => ApiRequestAdapter::buildFromRequest($requestFile),
                        Request::class => new Request(
                            [],
                            [],
                            [],
                            [
                                'HTTP_HOST' => 'localhost:8080',
                                'HTTP_ACCEPT' => 'application/json',
                                'HTTP_USER_AGENT' => 'ApiBoundaryTest/1.0',
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
                ))
                ->build();

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

            return [
                'code' => (int)$response->code(),
                'body' => $effectiveBody,
                'json' => json_decode($effectiveBody, true, 512, JSON_THROW_ON_ERROR),
            ];
        } finally {
            @unlink($requestFile);
        }
    }
}
