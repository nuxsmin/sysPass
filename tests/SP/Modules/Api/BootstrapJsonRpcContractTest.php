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

namespace SP\Tests\Modules\Api;

use DI\ContainerBuilder;
use Klein\Request;
use Klein\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Domain\Api\Enums\JsonRpcErrorCode;
use SP\Domain\Api\Ports\ApiRequestService;
use SP\Domain\Api\Ports\ApiService;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Api\Adapters\ApiRequestAdapter;
use SP\Modules\Api\Bootstrap;

use function DI\create;

defined('APP_MODULE') || define('APP_MODULE', 'api');

#[Group('integration')]
final class BootstrapJsonRpcContractTest extends TestCase
{
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

    #[Test]
    public function notificationsExecuteWithoutEmittingResponseBody(): void
    {
        $executed = 0;

        $tagRepository = $this->createStub(TagRepository::class);
        $tagRepository->method('search')->willReturnCallback(
            static function () use (&$executed): QueryResult {
                $executed++;

                return new QueryResult([]);
            }
        );

        $apiService = $this->createMock(ApiService::class);
        $apiService->expects(self::once())->method('setup');
        $apiService->method('getParamInt')->willReturn(50);

        $notificationRes = $this->dispatch(
            json_encode([
                'jsonrpc' => '2.0',
                'method' => 'tag/search',
                'params' => ['authToken' => 'synthetic-token'],
            ], JSON_THROW_ON_ERROR),
            [
                ApiService::class => $apiService,
                TagRepository::class => $tagRepository,
            ]
        );

        self::assertSame(1, $executed);
        self::assertSame('', $notificationRes['body']);
        self::assertSame(204, $notificationRes['code']);

        // Unknown method sent as a notification must also produce no response body
        $unknownNotificationRes = $this->dispatch(
            json_encode([
                'jsonrpc' => '2.0',
                'method' => 'nonexistent/action',
                'params' => ['authToken' => 'synthetic-token'],
            ], JSON_THROW_ON_ERROR)
        );

        self::assertSame('', $unknownNotificationRes['body']);
        self::assertSame(204, $unknownNotificationRes['code']);
    }

    #[Test]
    public function successAndMethodNotFoundPreserveZeroFloatStringAndNullIds(): void
    {
        $tagRepository = $this->createStub(TagRepository::class);
        $tagRepository->method('search')->willReturn(new QueryResult([]));

        foreach ([0, 2.5, 'req-str-1', '0', null] as $id) {
            $apiService = $this->createStub(ApiService::class);
            $apiService->method('getParamInt')->willReturn(50);

            $okRes = $this->dispatch(
                json_encode([
                    'jsonrpc' => '2.0',
                    'method' => 'tag/search',
                    'params' => ['authToken' => 'synthetic-token'],
                    'id' => $id,
                ], JSON_THROW_ON_ERROR),
                [
                    ApiService::class => $apiService,
                    TagRepository::class => $tagRepository,
                ]
            );

            self::assertSame(200, $okRes['code']);
            $decodedOk = json_decode($okRes['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertArrayHasKey('id', $decodedOk);
            self::assertSame($id, $decodedOk['id']);

            $notFoundRes = $this->dispatch(
                json_encode([
                    'jsonrpc' => '2.0',
                    'method' => 'nonexistent/action',
                    'params' => ['authToken' => 'synthetic-token'],
                    'id' => $id,
                ], JSON_THROW_ON_ERROR)
            );

            self::assertSame(404, $notFoundRes['code']);
            $decodedErr = json_decode($notFoundRes['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertArrayHasKey('id', $decodedErr);
            self::assertSame($id, $decodedErr['id']);
            self::assertSame(JsonRpcErrorCode::METHOD_NOT_FOUND, $decodedErr['error']['code']);
        }
    }

    #[Test]
    public function parseErrorsAndMissingAuthTokenWithIdCorrelateRequestIdPerJsonRpcSpec(): void
    {
        // Parse error -> id: null (not 0)
        $parseErr = $this->dispatch('{bad-json');
        self::assertSame(400, $parseErr['code']);
        $decodedParse = json_decode($parseErr['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('id', $decodedParse);
        self::assertNull($decodedParse['id']);
        self::assertSame(JsonRpcErrorCode::PARSE_ERROR, $decodedParse['error']['code']);

        // Invalid request with valid string ID preserves string ID in error envelope
        $invalidWithStringId = $this->dispatch(
            json_encode([
                'jsonrpc' => '2.0',
                'method' => 'tag/search',
                'params' => [],
                'id' => 'corr-str-1',
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame(400, $invalidWithStringId['code']);
        $decodedStr = json_decode($invalidWithStringId['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('corr-str-1', $decodedStr['id']);
        self::assertSame(JsonRpcErrorCode::INVALID_REQUEST, $decodedStr['error']['code']);

        // Invalid request with zero ID preserves int(0) in error envelope
        $invalidWithZeroId = $this->dispatch(
            json_encode([
                'jsonrpc' => '2.0',
                'method' => 'tag/search',
                'params' => [],
                'id' => 0,
            ], JSON_THROW_ON_ERROR)
        );
        self::assertSame(400, $invalidWithZeroId['code']);
        $decodedZero = json_decode($invalidWithZeroId['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $decodedZero['id']);
    }

    #[Test]
    public function notificationsFailingRequestValidationEmitNoResponseBody(): void
    {
        foreach ([
            ['jsonrpc' => '2.0', 'method' => 'tag/search', 'params' => []],
            ['jsonrpc' => '2.0', 'method' => 'tag/search'],
        ] as $payload) {
            $res = $this->dispatch(json_encode($payload, JSON_THROW_ON_ERROR));

            self::assertSame(204, $res['code']);
            self::assertSame('', $res['body']);
        }

        // Non-notification malformed object without jsonrpc/method still yields HTTP 400 INVALID_REQUEST
        $nonRequestRes = $this->dispatch('{"test":"test"}');
        self::assertSame(400, $nonRequestRes['code']);
        $decodedNonRequest = json_decode($nonRequestRes['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($decodedNonRequest['id']);
        self::assertSame(JsonRpcErrorCode::INVALID_REQUEST, $decodedNonRequest['error']['code']);
    }

    #[Test]
    public function upgradeNeededGeneratesKeyAndReturnsInitializationError(): void
    {
        $configData = self::createConfiguredStub(
            ConfigDataInterface::class,
            [
                'isInstalled' => true,
                'isMaintenance' => false,
                'isHttpsEnabled' => false,
                'getAppVersion' => '3211.22070201',
                'getDatabaseVersion' => '3211.22070201',
                'getConfigVersion' => '3211.22070201',
                'getUpgradeKey' => null,
                'getDbName' => 'syspass_test',
                'getSiteLang' => 'en_US',
                'getPasswordSalt' => 'synthetic-salt',
            ]
        );
        $configFile = $this->createMock(ConfigFileService::class);
        $configFile->method('getConfigData')->willReturn($configData);
        $configFile->expects(self::once())->method('generateUpgradeKey')->willReturnSelf();

        $tagRepository = $this->createMock(TagRepository::class);
        $tagRepository->expects(self::never())->method('search');

        $res = $this->dispatch(
            json_encode(
                ['jsonrpc' => '2.0', 'method' => 'tag/search', 'params' => ['authToken' => 'abc'], 'id' => 1],
                JSON_THROW_ON_ERROR
            ),
            [
                ConfigDataInterface::class => $configData,
                ConfigFileService::class => $configFile,
                TagRepository::class => $tagRepository,
            ]
        );

        self::assertSame(500, $res['code']);
        $decoded = json_decode($res['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $decoded['id']);
        self::assertSame('Internal error', $decoded['error']['message'] ?? null);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array{code: int, body: string}
     */
    private function dispatch(string $rawBody, array $overrides = []): array
    {
        $requestFile = tempnam(sys_get_temp_dir(), 'api-bootstrap-contract-');
        if ($requestFile === false) {
            throw new RuntimeException('Unable to create temporary API request file');
        }

        try {
            file_put_contents($requestFile, $rawBody);

            $configData = self::createConfiguredStub(
                ConfigDataInterface::class,
                [
                    'isInstalled' => true,
                    'isMaintenance' => false,
                    'isHttpsEnabled' => false,
                    'getAppVersion' => \SP\Domain\Common\Providers\Version::getVersionStringNormalized(),
                    'getDatabaseVersion' => \SP\Domain\Common\Providers\Version::getVersionStringNormalized(),
                    'getConfigVersion' => \SP\Domain\Common\Providers\Version::getVersionStringNormalized(),
                    'getDbName' => 'syspass_test',
                    'getSiteLang' => 'en_US',
                    'getPasswordSalt' => 'synthetic-salt',
                ]
            );
            $configFile = $this->createStub(ConfigFileService::class);
            $configFile->method('getConfigData')->willReturn($configData);

            $databaseUtil = $this->createStub(DatabaseUtilInterface::class);
            $databaseUtil->method('checkDatabaseConnection')->willReturn(true);
            $databaseUtil->method('checkDatabaseTables')->willReturn(true);

            $database = $this->createStub(DatabaseInterface::class);
            $database->method('runQuery')->willReturn(new QueryResult([]));

            $dic = (new ContainerBuilder())
                ->addDefinitions(array_merge(
                    CoreDefinitions::getDefinitions(REAL_APP_ROOT, 'api'),
                    DomainDefinitions::getDefinitions(),
                    self::$moduleDefinitions,
                    [
                        ConfigDataInterface::class => $configData,
                        ConfigFileService::class => $configFile,
                        DatabaseUtilInterface::class => $databaseUtil,
                        DatabaseInterface::class => $database,
                        DbStorageHandler::class => $this->createStub(DbStorageHandler::class),
                        ApiRequestService::class => static fn() => ApiRequestAdapter::buildFromRequest($requestFile),
                        Request::class => new Request(
                            [],
                            [],
                            [],
                            [
                                'HTTP_HOST' => 'localhost',
                                'HTTP_ACCEPT' => 'application/json',
                                'HTTP_USER_AGENT' => 'BootstrapContractTest/1.0',
                                'REQUEST_URI' => '/api.php',
                                'REQUEST_METHOD' => 'POST',
                                'HTTP_CONTENT_TYPE' => 'application/json',
                                'REMOTE_ADDR' => '127.0.0.1',
                            ],
                            [],
                            null
                        ),
                        Response::class => create(Response::class),
                    ],
                    $overrides
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

            return [
                'code' => (int)$response->code(),
                'body' => $body !== '' ? $body : $echoed,
            ];
        } finally {
            @unlink($requestFile);
        }
    }
}
