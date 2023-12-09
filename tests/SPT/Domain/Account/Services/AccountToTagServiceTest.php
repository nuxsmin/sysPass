<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Domain\Account\Services;

use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemData;
use SP\Domain\Account\Ports\AccountToTagRepositoryInterface;
use SP\Domain\Account\Services\AccountToTagService;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Database\QueryResult;
use SPT\UnitaryTestCase;

/**
 * Class AccountToTagServiceTest
 *
 * @group unitary
 */
class AccountToTagServiceTest extends UnitaryTestCase
{

    private AccountToTagService                        $accountToTagService;
    private AccountToTagRepositoryInterface|MockObject $accountToTagRepository;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetTagsByAccountId()
    {
        $accountId = self::$faker->randomNumber();

        $result =
            new QueryResult([new Simple(['id' => self::$faker->randomNumber(), 'name' => self::$faker->colorName])]);

        $this->accountToTagRepository
            ->expects(self::once())
            ->method('getTagsByAccountId')
            ->with($accountId)
            ->willReturn($result);

        $actual = $this->accountToTagService->getTagsByAccountId($accountId);
        $expected = $result->getData(Simple::class)->toArray(null, null, true);

        $this->assertTrue($actual[0] instanceof ItemData);
        $this->assertEquals($expected, $actual[0]->toArray());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetTagsByAccountIdWithNotags()
    {
        $accountId = self::$faker->randomNumber();

        $result = new QueryResult([]);

        $this->accountToTagRepository
            ->expects(self::once())
            ->method('getTagsByAccountId')
            ->with($accountId)
            ->willReturn($result);

        $actual = $this->accountToTagService->getTagsByAccountId($accountId);

        $this->assertEmpty($actual);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToTagRepository = $this->createMock(AccountToTagRepositoryInterface::class);

        $this->accountToTagService =
            new AccountToTagService($this->application, $this->accountToTagRepository);
    }
}
