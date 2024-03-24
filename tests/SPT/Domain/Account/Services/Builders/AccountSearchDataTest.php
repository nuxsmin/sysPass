<?php
/*
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

namespace SPT\Domain\Account\Services\Builders;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountCacheService;
use SP\Domain\Account\Ports\AccountToFavoriteService;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Account\Services\Builders\AccountSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SPT\Generators\AccountDataGenerator;
use SPT\UnitaryTestCase;

use function PHPUnit\Framework\exactly;
use function PHPUnit\Framework\once;

/**
 * Class AccountSearchDataBuilderTest
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
    public function testBuildFrom(): void
    {
        $accountSearchVData =
            array_map(static fn() => AccountDataGenerator::factory()->buildAccountSearchView(), range(0, 4));
        $numResults = count($accountSearchVData);

        $queryResult = new QueryResult($accountSearchVData);

        $this->accountToFavoriteService
            ->expects(once())
            ->method('getForUserId')
            ->with($this->context->getUserData()->getId());

        $this->accountCacheService
            ->expects(exactly($numResults))
            ->method('getCacheForAccount');

        $this->accountAclService
            ->expects(exactly($numResults))
            ->method('getAcl');

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
    public function testBuildFromWithColorCacheException(): void
    {
        $accountSearchVData =
            array_map(static fn() => AccountDataGenerator::factory()->buildAccountSearchView(), range(0, 4));
        $numResults = count($accountSearchVData);

        $queryResult = new QueryResult($accountSearchVData);

        $this->accountToFavoriteService
            ->expects(once())
            ->method('getForUserId')
            ->with($this->context->getUserData()->getId());

        $this->accountCacheService
            ->expects(exactly($numResults))
            ->method('getCacheForAccount');

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

    public function testInitializeWithException(): void
    {
        $fileCache = $this->createMock(FileCacheService::class);

        $fileCache
            ->expects(once())
            ->method('load')
            ->willThrowException(new FileException('test'));

        new AccountSearchData(
            $this->application,
            $this->accountAclService,
            $this->accountToTagRepository,
            $this->accountToFavoriteService,
            $this->accountCacheService,
            $fileCache,
            $this->config->getConfigData()
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

        $this->accountSearchDataBuilder =
            new AccountSearchData(
                $this->application,
                $this->accountAclService,
                $this->accountToTagRepository,
                $this->accountToFavoriteService,
                $this->accountCacheService,
                $this->fileCache,
                $this->config->getConfigData()
            );
    }

}
