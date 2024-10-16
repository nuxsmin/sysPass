<?php

declare(strict_types=1);
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

namespace SP\Tests\Domain\Auth\Services;

use ArrayIterator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapConnectionHandler;
use SP\Domain\Auth\Providers\Ldap\LdapException;
use SP\Domain\Auth\Providers\Ldap\LdapParams;
use SP\Domain\Auth\Providers\Ldap\LdapResults;
use SP\Domain\Auth\Providers\Ldap\LdapTypeEnum;
use SP\Domain\Auth\Services\LdapCheck;
use SP\Tests\UnitaryTestCase;

/**
 * Class LdapCheckTest
 */
#[Group('unitary')]
class LdapCheckTest extends UnitaryTestCase
{

    private LdapCheck                        $ldapCheck;
    private LdapConnectionHandler|MockObject $ldapConnection;
    private MockObject|LdapActionsService    $ldapActionsService;

    /**
     * @throws LdapException
     */
    public function testGetObjectsByFilterWithParams()
    {
        $ldapData = $this->getLdapData();

        $ldapResults = new LdapResults(10, new ArrayIterator($ldapData));

        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->with('a_filter', ['dn'])
            ->willReturn($ldapResults);

        $out = $this->ldapCheck->getObjectsByFilter($this->getLdapParams(), 'a_filter');

        $this->assertEquals(5, $out->count());

        $results = $out->getResults();

        foreach ($ldapData as $index => $data) {
            $this->assertEquals($data['dn'], $results[0]['items'][$index]);
        }
    }

    /**
     * @return array|array[]
     */
    private function getLdapData(): array
    {
        return array_map(
            static fn() => [
                'count' => self::$faker->randomNumber(2),
                'dn' => self::$faker->userName(),
                'email' => [self::$faker->email(), self::$faker->email()],
                'member' => self::$faker->userName(),
                'memberUid' => self::$faker->uuid(),
                'uniqueMember' => self::$faker->uuid()
            ],
            range(0, 4)
        );
    }

    /**
     * @return LdapParams
     */
    private function getLdapParams(): LdapParams
    {
        return new LdapParams('a_server', LdapTypeEnum::STD, 'a_dn', 'a_pass');
    }

    /**
     * @throws LdapException
     */
    public function testGetObjectsByFilterWithConnectionException()
    {
        $this->ldapActionsService
            ->expects($this->never())
            ->method('getObjects');

        $this->ldapConnection
            ->expects($this->once())
            ->method('connect')
            ->willThrowException(LdapException::error('test'));

        $this->expectException(LdapException::class);
        $this->expectExceptionMessage('test');

        $this->ldapCheck->getObjectsByFilter($this->getLdapParams(), 'a_filter');
    }

    /**
     * @throws LdapException
     */
    public function testGetObjectsByFilterWithObjectsException()
    {
        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->willThrowException(LdapException::error('test'));

        $this->expectException(LdapException::class);
        $this->expectExceptionMessage('test');

        $this->ldapCheck->getObjectsByFilter($this->getLdapParams(), 'a_filter');
    }

    /**
     * @throws LdapException
     */
    public function testGetObjectsWithParams()
    {
        $ldapParams = $this->getLdapParams();
        $ldapParams->setFilterUserObject('a_user_filter');
        $ldapParams->setFilterGroupObject('a_group_filter');

        $ldapData = $this->getLdapData();

        $ldapResults = new LdapResults(10, new ArrayIterator($ldapData));

        $this->ldapActionsService
            ->expects($this->exactly(3))
            ->method('getObjects')
            ->with(
                ...
                self::withConsecutive(
                    ['a_user_filter', ['dn']],
                    ['a_user_filter', ['member', 'memberUid', 'uniqueMember']],
                    ['a_group_filter', ['dn']],
                )
            )
            ->willReturn($ldapResults);

        $out = $this->ldapCheck->getObjects($ldapParams);

        $this->assertEquals(10, $out->count());

        $results = $out->getResults();

        foreach ($ldapData as $index => $data) {
            $this->assertEquals($data['dn'], $results[0]['items'][$index]);
        }
    }

    /**
     * @throws LdapException
     */
    public function testGetObjectsWithNoParams()
    {
        $ldapDataUsers = $this->getLdapData();
        $ldapDataGroups = $this->getLdapData();

        $ldapResultsUsers = new LdapResults(10, new ArrayIterator($ldapDataUsers));
        $ldapResultsGroups = new LdapResults(10, new ArrayIterator($ldapDataGroups));

        $this->ldapActionsService
            ->expects($this->exactly(3))
            ->method('getObjects')
            ->with(
                ...
                self::withConsecutive(
                    ['(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))', ['dn']],
                    [
                        '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))',
                        ['member', 'memberUid', 'uniqueMember']
                    ],
                    ['(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group))', ['dn']],
                )
            )
            ->willReturn($ldapResultsUsers, $ldapResultsUsers, $ldapResultsGroups);

        $out = $this->ldapCheck->getObjects($this->getLdapParams());

        $this->assertEquals(10, $out->count());

        $results = $out->getResults();

        foreach ($ldapDataUsers as $index => $data) {
            $this->assertEquals($data['dn'], $results[0]['items'][$index]);
        }

        foreach ($ldapResultsGroups as $index => $data) {
            $this->assertEquals($data['dn'], $results[1]['items'][$index]);
        }
    }

    /**
     * @throws LdapException
     */
    public function testGetObjectsWitConnectionException()
    {
        $this->ldapActionsService
            ->expects($this->never())
            ->method('getObjects');

        $this->ldapConnection
            ->expects($this->once())
            ->method('connect')
            ->willThrowException(LdapException::error('test'));

        $this->expectException(LdapException::class);
        $this->expectExceptionMessage('test');

        $this->ldapCheck->getObjects($this->getLdapParams());
    }

    /**
     * @throws LdapException
     */
    public function testGetObjectsWitObjectsException()
    {
        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->willThrowException(LdapException::error('test'));

        $this->expectException(LdapException::class);
        $this->expectExceptionMessage('test');

        $this->ldapCheck->getObjects($this->getLdapParams());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ldapConnection = $this->createMock(LdapConnectionHandler::class);
        $this->ldapActionsService = $this->createMock(LdapActionsService::class);

        $this->ldapCheck = new LdapCheck(
            $this->ldapConnection,
            $this->ldapActionsService,
            $this->application->getEventDispatcher()
        );
    }
}
