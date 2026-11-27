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

namespace SP\Tests\Modules\Web\Presenters\Helpers\Account;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Dtos\AccountCacheDto;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountCacheService;
use SP\Domain\Account\Ports\AccountToFavoriteService;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Common\Models\Item;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Core\Exceptions\FileException;
use SP\Modules\Web\Presenters\Helpers\Account\AccountSearchData;
use SP\Tests\Generators\AccountDataGenerator;
use SP\Tests\UnitaryTestCase;

use function PHPUnit\Framework\exactly;
use function PHPUnit\Framework\once;

/**
 * Class AccountSearchDataTest
 *
 */
#[Group('unitary')]
class AccountSearchDataTest extends UnitaryTestCase
{

    private AccountSearchData                   $accountSearchDataBuilder;
    private AccountAclService|MockObject        $accountAclService;
    private AccountCacheService|MockObject      $accountCacheService;
    private AccountToTagRepository|MockObject   $accountToTagRepository;
    private AccountToFavoriteService|MockObject $accountToFavoriteService;
    private MockObject|FileCacheService         $fileCache;

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    #[Test]
    public function testBuildFrom(): void
    {
        $accountSearchView = $this->buildUniqueAccountSearchViews();
        $numResults = count($accountSearchView);

        $queryResult = new QueryResult($accountSearchView);

        $this->accountToFavoriteService
            ->expects(once())
            ->method('getForUserId')
            ->with($this->context->getUserData()->id);

        $this->accountCacheService
            ->expects(once())
            ->method('getForAccounts')
            ->willReturnCallback(static fn(array $ids): array => array_fill_keys(
                $ids, new AccountCacheDto(100, [new Item(['id' => 200])], [new Item(['id' => 300])])
            ));

        $invokedCount = new InvokedCount($numResults);
        $this->accountAclService
            ->expects($invokedCount)
            ->method('getAcl')
            ->with(
                AclActionsInterface::ACCOUNT_SEARCH,
                self::callback(static function (AccountAclDto $current) use ($accountSearchView, $invokedCount) {
                    return $current->getAccountId() ===
                           $accountSearchView[$invokedCount->numberOfInvocations() - 1]->getId()
                           && $current->getUsersId() == [new Item(['id' => 200])]
                           && $current->getUserGroupsId() == [new Item(['id' => 300])];
                })
            );

        $this->accountToTagRepository
            ->expects(exactly($numResults))
            ->method('getTagsByAccountId')
            ->willReturn(new QueryResult([1, 2, 3]));

        $this->fileCache
            ->expects(exactly($numResults))
            ->method('save');

        $this->accountSearchDataBuilder->buildFrom($queryResult);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    #[Test]
    public function testBuildFromWithColorCacheException(): void
    {
        $accountSearchVData = $this->buildUniqueAccountSearchViews();
        $numResults = count($accountSearchVData);

        $queryResult = new QueryResult($accountSearchVData);

        $this->accountToFavoriteService
            ->expects(once())
            ->method('getForUserId')
            ->with($this->context->getUserData()->id);

        $this->accountCacheService
            ->expects(once())
            ->method('getForAccounts')
            ->willReturnCallback(static fn(array $ids): array => array_fill_keys($ids, new AccountCacheDto(0, [], [])));

        $this->accountAclService
            ->expects(exactly($numResults))
            ->method('getAcl');

        $this->accountToTagRepository
            ->expects(exactly($numResults))
            ->method('getTagsByAccountId')
            ->willReturn(new QueryResult([1, 2, 3]));

        $this->fileCache
            ->expects(exactly($numResults))
            ->method('save')
            ->willThrowException(new FileException('test'));

        $this->accountSearchDataBuilder->buildFrom($queryResult);
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    #[Test]
    public function testBuildFromRedactsDiscoveryRowsWhenResultViewIsFalse(): void
    {
        $raw = new AccountSearchView([
            'id' => 42,
            'name' => 'Secret Service Account',
            'clientId' => 1,
            'clientName' => 'Google',
            'categoryId' => 2,
            'categoryName' => 'Web',
            'login' => 'secret-login',
            'url' => 'https://secret.example.test',
            'notes' => 'secret-notes',
            'parentId' => 99,
            'num_files' => 3,
            'publicLinkHash' => 'pub-hash',
            'userName' => 'Hidden Owner',
            'userGroupName' => 'Hidden Group',
        ]);

        $this->accountToFavoriteService->method('getForUserId')->willReturn([42 => 1]);
        $this->accountCacheService->method('getForAccounts')
            ->willReturn([42 => new AccountCacheDto(42, [new Item(['id' => 200])], [new Item(['id' => 300])])]);
        $this->accountAclService->method('getAcl')
            ->willReturn(new \SP\Domain\Account\Adapters\AccountPermission(AclActionsInterface::ACCOUNT_SEARCH));
        $this->accountToTagRepository->method('getTagsByAccountId')
            ->willReturn(new QueryResult([new Item(['id' => 1, 'name' => 'tag1'])]));

        $result = $this->accountSearchDataBuilder->buildFrom(new QueryResult([$raw]))->getDataAsArray();
        self::assertCount(1, $result);

        $item = $result[0];
        $view = $item->getAccountSearchView();

        self::assertFalse($item->isShow());
        self::assertSame(42, $view->getId());
        self::assertSame('Secret Service Account', $view->getName());
        self::assertSame('Google', $view->getClientName());
        self::assertSame('Web', $view->getCategoryName());
        self::assertSame([], $item->getTags());
        self::assertFalse($item->isFavorite());
        self::assertSame(['(G*) <em></em>', '(U*) <em></em>'], $item->getAccesses());
        self::assertSame('', (string)$view->getLogin());
        self::assertSame('', (string)$view->getUrl());
        self::assertSame('', (string)$view->getNotes());
        self::assertSame(0, (int)$view->getParentId());
        self::assertSame(0, (int)$view->getNumFiles());
        self::assertSame('', (string)$view->getPublicLinkHash());
        self::assertSame('', (string)$view->getUserName());
        self::assertSame('', (string)$view->getUserGroupName());
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function testInitializeWithException(): void
    {
        $fileCache = $this->createMock(FileCacheService::class);

        $fileCache
            ->expects(once())
            ->method('load')
            ->willThrowException(new FileException('test'));

        new AccountSearchData(
            $this->context,
            $this->accountAclService,
            $this->accountToTagRepository,
            $this->accountToFavoriteService,
            $this->accountCacheService,
            $fileCache,
            $this->config->getConfigData(),
            $this->createStub(UriContextInterface::class)
        );
    }


    /**
     * Unique client ids keep the color cache save count deterministic.
     *
     * @return AccountSearchView[]
     */
    private function buildUniqueAccountSearchViews(): array
    {
        return array_map(
            static function (int $index): AccountSearchView {
                $account = AccountDataGenerator::factory()->buildAccountSearchView();

                return new AccountSearchView(
                    array_merge($account->toArray(null, null, true), ['clientId' => $index + 1])
                );
            },
            range(0, 4)
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountAclService = $this->createMock(AccountAclService::class);
        $this->accountToTagRepository = $this->createMock(AccountToTagRepository::class);
        $this->accountToFavoriteService = $this->createMock(AccountToFavoriteService::class);
        $this->accountCacheService = $this->createMock(AccountCacheService::class);
        $this->fileCache = $this->createMock(FileCacheService::class);
        $this->fileCache
            ->expects(self::once())
            ->method('load');

        $uriContext = $this->createStub(UriContextInterface::class);

        $this->accountSearchDataBuilder =
            new AccountSearchData(
                $this->context,
                $this->accountAclService,
                $this->accountToTagRepository,
                $this->accountToFavoriteService,
                $this->accountCacheService,
                $this->fileCache,
                $this->config->getConfigData(),
                $uriContext
            );
    }
}
