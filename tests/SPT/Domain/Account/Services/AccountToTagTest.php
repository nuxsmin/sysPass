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

namespace SPT\Domain\Account\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Account\Services\AccountToTag;
use SP\Domain\Common\Models\Item;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Database\QueryResult;
use SPT\UnitaryTestCase;

/**
 * Class AccountToTagServiceTest
 *
 */
#[Group('unitary')]
class AccountToTagTest extends UnitaryTestCase
{

    private AccountToTag                      $accountToTag;
    private AccountToTagRepository|MockObject $accountToTagRepository;

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

        $actual = $this->accountToTag->getTagsByAccountId($accountId);
        $expected = $result->getData(Simple::class)->toArray(null, null, true);

        $this->assertTrue($actual[0] instanceof Item);
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

        $actual = $this->accountToTag->getTagsByAccountId($accountId);

        $this->assertEmpty($actual);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountToTagRepository = $this->createMock(AccountToTagRepository::class);

        $this->accountToTag =
            new AccountToTag($this->application, $this->accountToTagRepository);
    }
}
