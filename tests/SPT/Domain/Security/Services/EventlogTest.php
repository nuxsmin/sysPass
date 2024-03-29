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

namespace SPT\Domain\Security\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Security\Models\Eventlog as EventlogModel;
use SP\Domain\Security\Ports\EventlogRepository;
use SP\Domain\Security\Services\Eventlog;
use SP\Infrastructure\Database\QueryResult;
use SPT\UnitaryTestCase;

/**
 * Class EventlogTest
 */
#[Group('unitary')]
class EventlogTest extends UnitaryTestCase
{

    private EventlogRepository|MockObject $eventlogRepository;
    private RequestInterface|MockObject   $request;
    private Eventlog                      $eventlog;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData('test');

        $this->eventlogRepository
            ->expects($this->once())
            ->method('search')
            ->with($itemSearchData)
            ->willReturn(new QueryResult());

        $this->eventlog->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $eventlog = new EventlogModel(
            [
                'date' => time(),
                'login' => self::$faker->userName,
                'userId' => self::$faker->randomNumber(3),
                'ipAddress' => self::$faker->ipv4(),
                'action' => self::$faker->colorName(),
                'description' => self::$faker->text(),
                'level' => self::$faker->colorName(),
            ]
        );

        $this->request
            ->expects($this->once())
            ->method('getClientAddress')
            ->willReturn('192.168.0.1');

        $this->eventlogRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(function (EventlogModel $eventlog) {
                    $userData = $this->context->getUserData();

                    return $eventlog->getUserId() == $userData->getId()
                           && $eventlog->getLogin() == $userData->getLogin()
                           && $eventlog->getIpAddress() == '192.168.0.1';
                })
            )
            ->willReturn(new QueryResult(null, 0, 100));

        $this->assertEquals(100, $this->eventlog->create($eventlog));
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testClear()
    {
        $this->eventlogRepository
            ->expects($this->once())
            ->method('clear');

        $this->eventlog->clear();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventlogRepository = $this->createMock(EventlogRepository::class);
        $this->request = $this->createMock(RequestInterface::class);

        $this->eventlog = new Eventlog($this->application, $this->eventlogRepository, $this->request);
    }

}
