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

namespace SP\Tests\Providers\Auth\Ldap;

use Laminas\Ldap\Collection;
use Laminas\Ldap\Ldap;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcherInterface;
use SP\Providers\Auth\Ldap\AttributeCollection;
use SP\Providers\Auth\Ldap\LdapActions;
use SP\Providers\Auth\Ldap\LdapCodeEnum;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Auth\Ldap\LdapTypeEnum;
use SP\Tests\UnitaryTestCase;

/**
 * Class LdapActionsTest
 *
 * @group unitary
 */
class LdapActionsTest extends UnitaryTestCase
{

    private Ldap|MockObject                     $ldap;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private LdapActions                         $ldapActions;

    /**
     * @throws LdapException
     * @throws Exception
     */
    public function testGetObjects(): void
    {
        $filter = 'test';
        $collection = $this->createMock(Collection::class);
        $result = array_map(fn() => self::$faker->randomNumber(), range(0, 9));
        $attributes = array_map(fn() => self::$faker->colorName, range(0, 9));
        $searchBase = self::$faker->colorName;

        $this->ldap->expects(self::once())
                   ->method('search')
                   ->with(
                       $filter,
                       $searchBase,
                       Ldap::SEARCH_SCOPE_SUB,
                       $attributes,
                   )
                   ->willReturn($collection);

        $collection->expects(self::once())->method('toArray')->willReturn($result);

        $out = $this->ldapActions->getObjects($filter, $attributes, $searchBase);

        self::assertEquals($result, $out);
    }

    /**
     * @throws LdapException
     */
    public function testGetObjectsError(): void
    {
        $this->expectGetResultsError();

        $this->ldapActions->getObjects('test');
    }

    /**
     * @return void
     */
    private function expectGetResultsError(): void
    {
        $message = 'test';
        $code = self::$faker->randomNumber();
        $exception = new \Laminas\Ldap\Exception\LdapException(null, $message, $code);

        $this->ldap->expects(self::once())
                   ->method('search')
                   ->willThrowException($exception);

        $this->eventDispatcher->expects(self::once())
                              ->method('notify')
                              ->with('exception', new Event($exception));

        $this->expectException(LdapException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($code);
    }

    /**
     * @throws LdapException
     * @throws Exception
     */
    public function testGetAttributes(): void
    {
        $filter = 'test';
        $collection = $this->createMock(Collection::class);
        $attributes = $this->buildAttributes();

        $this->ldap->expects(self::once())
                   ->method('search')
                   ->with(
                       $filter,
                       null,
                       Ldap::SEARCH_SCOPE_SUB,
                       [],
                   )
                   ->willReturn($collection);

        $collection->expects(self::once())->method('getFirst')->willReturn($attributes);

        $out = $this->ldapActions->getAttributes($filter);

        $expected = new AttributeCollection([
            'dn'       => $attributes['dn'],
            'group'    => array_filter(
                $attributes['memberof'],
                fn($key) => $key !== 'count',
                ARRAY_FILTER_USE_KEY
            ),
            'fullname' => $attributes['displayname'],
            'name'     => $attributes['givenname'],
            'sn'       => $attributes['sn'],
            'mail'     => $attributes['mail'],
            'expire'   => $attributes['lockouttime'],
        ]);

        self::assertEquals($expected, $out);
    }

    /**
     * @return array
     */
    private function buildAttributes(): array
    {
        return [
            'dn'          => self::$faker->userName,
            'memberof'    => [
                'count' => 3,
                self::$faker->company,
                self::$faker->company,
                self::$faker->company,
            ],
            'displayname' => self::$faker->name,
            'givenname'   => self::$faker->firstName,
            'sn'          => self::$faker->lastName,
            'mail'        => self::$faker->email,
            'lockouttime' => self::$faker->unixTime,
        ];
    }

    /**
     * @throws \SP\Providers\Auth\Ldap\LdapException
     */
    public function testGetAttributesError(): void
    {
        $this->expectGetResultsError();

        $this->ldapActions->getAttributes('test');
    }

    public function testMutate(): void
    {
        $ldapParams =
            new LdapParams(self::$faker->domainName, LdapTypeEnum::ADS, self::$faker->company, self::$faker->password);

        $this->ldapActions->mutate($ldapParams);

        $this->assertTrue(true);
    }

    /**
     * @throws \SP\Providers\Auth\Ldap\LdapException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testSearchGroupsDn(): void
    {
        $filter = 'test';
        $collection = $this->createMock(Collection::class);

        $this->ldap->expects(self::once())
                   ->method('search')
                   ->with(
                       '(&(cn=\2a)test)',
                       null,
                       Ldap::SEARCH_SCOPE_SUB,
                       ['dn'],
                   )
                   ->willReturn($collection);

        $collection->expects(self::once())->method('count')->willReturn(1);

        $expected = [
            [],
            [
                'dn' => self::$faker->name,
            ],
        ];
        $collection->expects(self::once())->method('toArray')->willReturn($expected);

        $out = $this->ldapActions->searchGroupsDn($filter);

        self::assertEquals($expected[1]['dn'], $out[0]);
    }

    /**
     * @throws \SP\Providers\Auth\Ldap\LdapException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testSearchGroupsDnNoGroups(): void
    {
        $filter = 'test';
        $collection = $this->createMock(Collection::class);

        $this->ldap->expects(self::once())
                   ->method('search')
                   ->with(
                       '(&(cn=\2a)test)',
                       null,
                       Ldap::SEARCH_SCOPE_SUB,
                       ['dn'],
                   )
                   ->willReturn($collection);

        $this->eventDispatcher->expects(self::once())
                              ->method('notify')
                              ->with('ldap.search.group');

        $collection->expects(self::once())->method('count')->willReturn(0);

        $this->expectException(LdapException::class);
        $this->expectExceptionMessage('Error while searching the group RDN');
        $this->expectExceptionCode(LdapCodeEnum::NO_SUCH_OBJECT->value);

        $this->ldapActions->searchGroupsDn($filter);
    }

    /**
     * @throws \SP\Providers\Auth\Ldap\LdapException
     */
    public function testSearchGroupsDnError(): void
    {
        $this->expectGetResultsError();

        $this->ldapActions->searchGroupsDn('test');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ldap = $this->createMock(Ldap::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $ldapParams =
            new LdapParams(self::$faker->domainName, LdapTypeEnum::STD, self::$faker->userName, self::$faker->password);

        $this->ldapActions = new LdapActions($this->ldap, $ldapParams, $this->eventDispatcher);
    }

}
