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

namespace SP\Tests;

use Aura\SqlQuery\QueryFactory;
use Klein\Request;
use Klein\Response;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Context\Stateless;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\ItemPreset\Models\ItemPreset as ItemPresetModel;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\ItemPreset\Repositories\ItemPreset as ItemPresetRepository;
use SP\Tests\Generators\UserDataGenerator;

/**
 * Base class for HTTP-level web integration tests that run against the real database.
 *
 * The request still goes through the production web bootstrap and container, but the
 * DatabaseInterface port is bound to the real adapter over the test fixtures instead of
 * a stubbed query resolver. Repository rows are seeded through the production adapters so
 * the tests exercise the same SQL and mapping code as the application.
 *
 * The client-side getUrl() regression (empty route segments producing a trailing slash)
 * is out of scope here because a PHP HTTP response cannot observe it. It is tracked in
 * #2125 with browser/JS coverage; see ADR-0004.
 */
abstract class DatabaseIntegrationTestCase extends IntegrationTestCase
{
    use DatabaseTrait;

    protected DatabaseInterface $db;
    protected Context $dbContext;
    protected QueryFactory $queryFactory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        self::loadFixtures();

        $this->db = new Database(getDbHandler(), self::createStub(EventDispatcherInterface::class));
        $this->queryFactory = new QueryFactory('mysql');
        $this->dbContext = new Stateless();
        $this->dbContext->initialize();
        $this->dbContext->setUserData($this->getUserDataDto());
    }

    /**
     * @param array<class-string, mixed> $definitionsOverride
     */
    protected function buildContainer(Request $request, array $definitionsOverride = []): ContainerInterface
    {
        $definitionsOverride[DatabaseInterface::class] = $this->db;

        return parent::buildContainer($request, $definitionsOverride);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfigData(): array
    {
        return array_merge(
            parent::getConfigData(),
            [
                'getAccountCount' => 10,
                'getLogEvents' => ['edit.'],
                'isLogEnabled' => false,
                'isPublinksEnabled' => false,
                'getFilesAllowedSize' => 0,
                'getFilesAllowedExts' => [],
                'getApplicationUrl' => null,
                'getSessionTimeout' => 3600,
            ]
        );
    }

    /**
     * @throws Exception
     * @throws \SP\Domain\Core\Exceptions\SPException
     */
    protected function getUserDataDto(): UserDto
    {
        return UserDto::fromModel(UserDataGenerator::factory()->buildUserData())
                      ->mutate(
                          [
                              'id' => 1,
                              'userGroupId' => 1,
                              'userProfileId' => 1,
                              'login' => 'admin',
                              'ssoLogin' => 'admin',
                              'name' => 'sysPass Admin',
                              'email' => 'admin@syspass.org',
                              'lastUpdateMPass' => 1528236611,
                              'isAdminApp' => true,
                              'isAdminAcc' => true,
                              'isDisabled' => false,
                              'isChangePass' => false,
                              'isChangedPass' => false,
                              'isLdap' => false,
                              'isMigrate' => false,
                          ]
                      );
    }

    protected function getUserProfile(): ProfileData
    {
        $profile = getDbHandler()
            ->getConnection()
            ->query('SELECT profile FROM UserProfile WHERE id = 1')
            ->fetchColumn();

        return Serde::deserialize((string)$profile, ProfileData::class);
    }

    protected function getContext(): SessionContext
    {
        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(true);
        $context->method('getAuthCompleted')->willReturn(true);
        $context->method('getUserData')->willReturn($this->getUserDataDto());
        $context->method('getUserProfile')->willReturn($this->getUserProfile());
        $context->method('getCSRF')->willReturn(null);

        return $context;
    }

    /**
     * Dispatches a request through the real web bootstrap and captures the response body and code.
     *
     * @param array<string, mixed> $paramsGet
     * @param array<string, mixed> $paramsPost
     * @param array<class-string, mixed> $definitionsOverride
     * @return array{body: string, code: int, container: ContainerInterface}
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    protected function dispatch(
        string $method,
        string $route,
        array  $paramsGet = [],
        array  $paramsPost = [],
        array  $definitionsOverride = []
    ): array {
        $request = static::buildRequest(
            $method,
            'index.php',
            array_merge(['r' => $route], $paramsGet),
            $paramsPost
        );

        $container = $this->buildContainer($request, $definitionsOverride);

        ob_start();

        try {
            static::runApp($container);
        } finally {
            $echoed = (string)ob_get_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $body = (string)$response->body();

        return [
            'body' => $body !== '' ? $body : $echoed,
            'code' => (int)$response->code(),
            'container' => $container,
        ];
    }

    /**
     * @return AclInterface&\PHPUnit\Framework\MockObject\MockObject
     * @throws Exception
     */
    protected function denyAclFor(int ...$actionIds): AclInterface
    {
        $acl = $this->createMock(AclInterface::class);
        $acl->method('checkUserAccess')
            ->willReturnCallback(
                static fn(int $actionId): bool => !in_array($actionId, $actionIds, true)
            );
        $acl->method('getRouteFor')
            ->willReturnCallback(static fn(int $actionId): string => (string)$actionId);

        return $acl;
    }

    /**
     * @return ItemPresetRepository<ItemPresetModel>
     */
    protected function itemPresetRepository(): ItemPresetRepository
    {
        return new ItemPresetRepository(
            $this->db,
            $this->dbContext,
            self::createStub(EventDispatcherInterface::class),
            $this->queryFactory
        );
    }

    /**
     * Reads a preset through the production repository and returns its typed model.
     *
     * @throws \SP\Domain\Core\Exceptions\ConstraintException
     * @throws \SP\Domain\Core\Exceptions\QueryException
     */
    protected function getItemPresetModel(int $id): ItemPresetModel
    {
        /** @var ItemPresetModel $itemPreset */
        $itemPreset = $this->itemPresetRepository()->getById($id)->getData(ItemPresetModel::class);

        return $itemPreset;
    }

    /**
     * Seeds an item preset through the production repository.
     *
     * @throws \SP\Domain\Core\Exceptions\ConstraintException
     * @throws \SP\Domain\Core\Exceptions\QueryException
     */
    protected function seedItemPreset(
        string $type,
        ?int   $userId,
        ?int   $userGroupId,
        ?int   $userProfileId,
        int    $priority,
        object $presetData,
        int    $fixed = 0
    ): int {
        $preset = new ItemPresetModel(
            [
                'type' => $type,
                'userId' => $userId,
                'userGroupId' => $userGroupId,
                'userProfileId' => $userProfileId,
                'fixed' => $fixed,
                'priority' => $priority,
            ]
        );

        return $this->itemPresetRepository()
                    ->create($preset->dehydrate($presetData))
                    ->getLastId();
    }

    protected function fetchValue(string $sql): mixed
    {
        return getDbHandler()->getConnection()->query($sql)->fetchColumn();
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(string $body): array
    {
        $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($json, $body);

        return $json;
    }

    /**
     * Asserts a successful JSON response and returns the decoded payload.
     *
     * @param array{body: string, code: int, container: ContainerInterface} $result
     * @return array<string, mixed>
     */
    protected function assertJsonOk(array $result, ?string $description = null): array
    {
        self::assertLessThan(500, $result['code'], $result['body']);
        self::assertStringNotContainsString('Invalid route', $result['body']);

        $json = $this->decodeJson($result['body']);

        self::assertSame('OK', $json['status'] ?? null, $result['body']);

        if ($description !== null) {
            self::assertSame($description, $json['description'] ?? null);
        }

        return $json;
    }

    /**
     * Asserts a denied JSON response (authorization is reported as an error payload).
     *
     * @param array{body: string, code: int, container: ContainerInterface} $result
     * @return array<string, mixed>
     */
    protected function assertJsonDenied(array $result, string $description): array
    {
        self::assertLessThan(500, $result['code'], $result['body']);
        self::assertStringNotContainsString('Invalid route', $result['body']);

        $json = $this->decodeJson($result['body']);

        self::assertSame('ERROR', $json['status'] ?? null, $result['body']);
        self::assertSame($description, $json['description'] ?? null);

        return $json;
    }
}
