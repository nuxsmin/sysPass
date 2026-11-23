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

namespace SP\Tests\Modules\Web\Controllers\AuthToken;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Auth\Models\AuthToken;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\User\Dtos\UserDto;
use SP\Tests\BodyChecker;
use SP\Tests\Generators\AuthTokenGenerator;
use SP\Tests\IntegrationTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class AuthTokenTest
 */
#[Group('integration')]
class AuthTokenTest extends IntegrationTestCase
{
    private const USER_ID       = 100;
    private const OTHER_USER_ID = 200;

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerCreate')]
    public function create()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'authToken/create'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function deleteMultiple()
    {
        $this->databaseQueryResolver = function ($queryData) {
            if ($queryData->getMapClassName() === AuthToken::class) {
                return new QueryResult([
                    AuthTokenGenerator::factory()->buildAuthToken()->mutate(['userId' => self::USER_ID]),
                ]);
            }

            return new QueryResult([], 1, 100);
        };

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'authToken/delete', 'items' => [100, 200, 300]]
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Authorizations deleted","data":null}');
    }

    #[Test]
    public function deleteMultipleMixedPartialSuccess()
    {
        $this->databaseQueryResolver = function ($queryData) {
            if ($queryData->getMapClassName() === AuthToken::class) {
                $id = (int)($queryData->getQuery()->getBindValues()['id'] ?? 0);

                return match ($id) {
                    100 => new QueryResult([
                        AuthTokenGenerator::factory()->buildAuthToken()->mutate([
                            'id' => 100,
                            'userId' => self::USER_ID,
                            'login' => 'secret-owner-name',
                        ]),
                    ]),
                    200 => new QueryResult([
                        AuthTokenGenerator::factory()->buildAuthToken()->mutate([
                            'id' => 200,
                            'userId' => self::OTHER_USER_ID,
                            'login' => 'other-secret-owner',
                        ]),
                    ]),
                    default => new QueryResult([]),
                };
            }

            return new QueryResult([], 1, 100);
        };

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'authToken/delete', 'items' => [100, 200, 999]]
            )
        );

        $this->expectOutputString(
            '{"status":"WARNING","description":"Some authorizations could not be deleted","data":{"deleted":[100],"failed":{"200":"You don\'t have permission to do this operation","999":"Token not found"}}}'
        );

        IntegrationTestCase::runApp($container);
    }

    #[Test]
    public function deleteMultipleAllUnauthorizedIsForbidden()
    {
        $this->databaseQueryResolver = function ($queryData) {
            if ($queryData->getMapClassName() === AuthToken::class) {
                return new QueryResult([
                    AuthTokenGenerator::factory()->buildAuthToken()->mutate([
                        'id' => 200,
                        'userId' => self::OTHER_USER_ID,
                    ]),
                ]);
            }

            return new QueryResult([], 1, 100);
        };

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'authToken/delete', 'items' => [200]]
            )
        );

        $this->expectOutputString(
            '{"status":"ERROR","description":"You don\'t have permission to do this operation","data":"Please contact to the administrator"}'
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function deleteBatchEmptyReturnsError()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'get',
                'index.php',
                ['r' => 'authToken/delete']
            )
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"ERROR","description":"Error while removing the tokens","data":null}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function deleteSingle()
    {
        $this->addDatabaseMapperResolver(
            AuthToken::class,
            new QueryResult([
                AuthTokenGenerator::factory()->buildAuthToken()->mutate(['id' => 100, 'userId' => self::USER_ID]),
            ])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'authToken/delete/100'])
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Authorization deleted","data":null}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerEdit')]
    public function edit()
    {
        $this->addDatabaseMapperResolver(
            AuthToken::class,
            new QueryResult([
                AuthTokenGenerator::factory()->buildAuthToken()->mutate(['userId' => self::USER_ID]),
            ])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'authToken/edit/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerSaveWithToken')]
    public function saveCreate()
    {
        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => self::$faker->sha1()
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveCreate'], $data)
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveCreateForAnotherUserIsDenied()
    {
        $data = [
            'users' => self::OTHER_USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => self::$faker->sha1()
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveCreate'], $data)
        );

        $this->expectOutputRegex('/You don\'t have permission to do this operation/');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveCreateForActionWithoutPermissionIsDenied()
    {
        $acl = $this->createMock(AclInterface::class);
        $acl->method('checkUserAccess')->willReturnCallback(
            static fn(int $actionId): bool => $actionId === AclActionsInterface::AUTHTOKEN_CREATE
        );
        $acl->method('getRouteFor')->willReturnCallback(static fn(int $actionId): string => (string)$actionId);

        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => self::$faker->sha1()
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveCreate'], $data),
            [AclInterface::class => $acl]
        );

        $this->expectOutputRegex('/You don\'t have permission to do this operation/');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveCreateForKeyDependentActionWithBlankPassIsDenied()
    {
        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_VIEW,
            'pass' => ''
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveCreate'], $data)
        );

        $this->expectOutputRegex('/Password cannot be blank/');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveEditScopeChangeToKeyDependentActionWithBlankPassIsDenied()
    {
        $this->addDatabaseMapperResolver(
            AuthToken::class,
            new QueryResult([
                AuthTokenGenerator::factory()->buildAuthToken()->mutate([
                    'id' => 100,
                    'userId' => self::USER_ID,
                    'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                ]),
            ])
        );

        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::CATEGORY_VIEW,
            'pass' => ''
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveEdit/100'], $data)
        );

        $this->expectOutputRegex('/Password cannot be blank/');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveEdit()
    {
        $this->addDatabaseMapperResolver(
            AuthToken::class,
            new QueryResult([
                AuthTokenGenerator::factory()->buildAuthToken()->mutate([
                    'id' => 100,
                    'userId' => self::USER_ID,
                    'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                ]),
            ])
        );

        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => self::$faker->sha1()
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveEdit/100'], $data)
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Authorization updated","data":null}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerSaveWithToken')]
    public function saveEditWithRefresh()
    {
        $this->addDatabaseMapperResolver(
            AuthToken::class,
            new QueryResult([
                AuthTokenGenerator::factory()->buildAuthToken()->mutate([
                    'id' => 100,
                    'userId' => self::USER_ID,
                    'actionId' => AclActionsInterface::ACCOUNT_SEARCH,
                ]),
            ])
        );

        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => self::$faker->sha1(),
            'refreshtoken' => 'on',
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveEdit/100'], $data)
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveEditForAnotherUserIsDenied()
    {
        $data = [
            'users' => self::OTHER_USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => self::$faker->sha1()
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveEdit/100'], $data)
        );

        $this->expectOutputRegex('/You don\'t have permission to do this operation/');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerSearch')]
    public function search()
    {
        $authTokenGenerator = AuthTokenGenerator::factory();

        $this->addDatabaseMapperResolver(
            AuthToken::class,
            QueryResult::withTotalNumRows(
                [
                    $authTokenGenerator->buildAuthToken()->mutate([
                        'login' => 'alice_login',
                        'token' => null,
                        'expireDate' => time() - 3600,
                    ]),
                    $authTokenGenerator->buildAuthToken()->mutate([
                        'login' => 'bob_login',
                        'token' => null,
                        'expireDate' => null,
                    ]),
                ],
                2
            )
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'authToken/search', 'search' => 'test'])
        );

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[BodyChecker('outputCheckerView')]
    public function view()
    {
        $this->addDatabaseMapperResolver(
            AuthToken::class,
            new QueryResult([
                AuthTokenGenerator::factory()->buildAuthToken()->mutate([
                    'userId' => self::USER_ID,
                    'token' => 'secret-view-token-2026',
                    'expireDate' => time() + 86400,
                ]),
            ])
        );

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'authToken/view/100'])
        );

        IntegrationTestCase::runApp($container);
    }

    #[Test]
    #[BodyChecker('outputCheckerSaveWithToken')]
    public function saveCreateWithValidExpireDate(): void
    {
        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => '',
            'expire_date' => date('Y-m-d', strtotime('+30 days')),
            'expire_date_initial' => '__new__',
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveCreate'], $data)
        );

        IntegrationTestCase::runApp($container);
    }

    #[Test]
    public function saveCreateWithPastExpireDateIsDenied(): void
    {
        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => '',
            'expire_date' => date('Y-m-d', strtotime('-1 day')),
            'expire_date_initial' => '__new__',
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveCreate'], $data)
        );

        $this->expectOutputRegex('/Invalid expiration date/');

        IntegrationTestCase::runApp($container);
    }

    #[Test]
    public function saveCreateWithOverMaxExpireDateIsDenied(): void
    {
        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => '',
            'expire_date' => date('Y-m-d', strtotime('+400 days')),
            'expire_date_initial' => '__new__',
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveCreate'], $data)
        );

        $this->expectOutputRegex('/Expiration date exceeds the maximum allowed lifetime/');

        IntegrationTestCase::runApp($container);
    }

    #[Test]
    public function saveCreateNoExpiryForbiddenForNonAdmin(): void
    {
        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => '',
            'no_expiry' => 'on',
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveCreate'], $data)
        );

        $this->expectOutputRegex('/You don\'t have permission to do this operation/');

        IntegrationTestCase::runApp($container);
    }

    #[Test]
    #[BodyChecker('outputCheckerSaveWithToken')]
    public function saveCreateNoExpiryAllowedForAdmin(): void
    {
        $adminContext = self::createStub(\SP\Domain\Core\Context\SessionContext::class);
        $adminContext->method('isLoggedIn')->willReturn(true);
        $adminContext->method('getAuthCompleted')->willReturn(true);
        $adminContext->method('getUserData')->willReturn($this->getUserDataDto()->mutate(['isAdminApp' => true]));
        $adminContext->method('getUserProfile')->willReturn(new \SP\Domain\User\Models\ProfileData());

        $data = [
            'users' => self::USER_ID,
            'actions' => AclActionsInterface::ACCOUNT_SEARCH,
            'pass' => '',
            'no_expiry' => 'on',
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'authToken/saveCreate'], $data),
            [\SP\Domain\Core\Context\Context::class => $adminContext]
        );

        IntegrationTestCase::runApp($container);
    }

    protected function getUserDataDto(): UserDto
    {
        return parent::getUserDataDto()->mutate(['id' => self::USER_ID]);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerCreate(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->html);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]//form[@name="frmTokens"]//select|//input'
        )->extract(['_name']);

        self::assertCount(7, $filter);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerEdit(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->html);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]//form[@name="frmTokens"]//select|//input'
        )->extract(['_name']);

        self::assertCount(7, $filter);
        self::assertEquals('OK', $json->status);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerSearch(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->html);
        $filter = $crawler->filterXPath(
            '//table/tbody[@id="data-rows-tblTokens"]//tr[string-length(@data-item-id) > 0]'
        )
                          ->extract(['data-item-id']);

        self::assertCount(2, $filter);
        self::assertEquals('OK', $json->status);
        self::assertStringContainsString('alice_login', $json->data->html);
        self::assertStringContainsString('bob_login', $json->data->html);
        self::assertStringContainsString('Expired', $json->data->html);
        self::assertStringContainsString('Never', $json->data->html);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerView(string $output): void
    {
        $json = json_decode($output);

        $crawler = new Crawler($json->data->html);
        $filter = $crawler->filterXPath(
            '//div[@id="box-popup"]//form[@name="frmTokens"]//select|//input'
        )->extract(['_name']);

        self::assertCount(3, $filter);
        self::assertEquals('OK', $json->status);
        self::assertStringContainsString('***', $json->data->html);
        self::assertStringContainsString('Expiration', $json->data->html);
        self::assertStringNotContainsString('secret-view-token-2026', $json->data->html);
    }

    /**
     * @param string $output
     * @return void
     */
    private function outputCheckerSaveWithToken(string $output): void
    {
        $json = json_decode($output);

        self::assertEquals('OK', $json->status);
        self::assertIsObject($json->data);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string)($json->data->token ?? ''));
    }
}
