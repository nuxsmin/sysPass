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

namespace SP\Tests\Domain\Auth\Providers\Ldap;

use EmptyIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapConnectionHandler;
use SP\Domain\Auth\Providers\Ldap\LdapException;
use SP\Domain\Auth\Providers\Ldap\LdapMsAds;
use SP\Domain\Auth\Providers\Ldap\LdapParams;
use SP\Domain\Auth\Providers\Ldap\LdapResults;
use SP\Domain\Auth\Providers\Ldap\LdapTypeEnum;
use SP\Domain\Auth\Providers\Ldap\LdapUtil;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Tests\UnitaryTestCase;

/**
 * Class LdapMsAdsTest
 *
 */
#[Group('unitary')]
class LdapMsAdsTest extends UnitaryTestCase
{

    private LdapConnectionHandler|MockObject $ldapConnection;
    private LdapActionsService|MockObject    $ldapActions;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private LdapMsAds                           $ldap;
    private LdapParams                          $ldapParams;

    public static function groupDataProvider(): array
    {
        return [
            [''],
            ['*'],
            ['cn=TestGroup,dc=groups,dc=syspass,dc=org']
        ];
    }

    /**
     * @throws LdapException
     */
    public function testConnect()
    {
        $user = self::$faker->userName;
        $password = self::$faker->password;

        $this->ldapConnection->expects(self::once())->method('connect')->with($this->ldapParams, $user, $password);

        $this->ldap->connect($user, $password);
    }

    /**
     * @throws LdapException
     */
    public function testConnectWithNull()
    {
        $this->ldapConnection->expects(self::once())->method('connect')->with($this->ldapParams, null, null);

        $this->ldap->connect();
    }

    /**
     * @throws LdapException
     */
    #[DataProvider('groupDataProvider')]
    public function testIsUserInGroup(string $group)
    {
        $this->ldapParams->setGroup($group);

        $userDn = 'cn=TestUser,dc=syspass,dc=org';
        $userLogin = self::$faker->userName;
        $groupsDn = [
            'cn=TestGroup,dc=groups,dc=syspass,dc=org'
        ];

        $this->eventDispatcher
            ->expects(self::once())
            ->method('notify')
            ->with('ldap.check.group', self::anything());

        $out = $this->ldap->isUserInGroup($userDn, $userLogin, $groupsDn);

        self::assertTrue($out);
    }

    /**
     * @throws LdapException
     */
    public function testIsUserInGroupWithSearchGroupDn()
    {
        $this->ldapParams->setGroup('TestGroup');

        $userDn = 'cn=TestUser,dc=syspass,dc=org';
        $userLogin = self::$faker->userName;
        $groupsDn = [
            'cn=TestGroup,dc=groups,dc=syspass,dc=org'
        ];

        $this->ldapActions->expects(self::once())
                          ->method('searchGroupsDn')
                          ->with($this->ldap->getGroupObjectFilter())
                          ->willReturn($groupsDn);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('notify')
            ->with('ldap.check.group', self::anything());

        $out = $this->ldap->isUserInGroup($userDn, $userLogin, $groupsDn);

        self::assertTrue($out);
    }

    /**
     * @throws LdapException
     */
    public function testIsUserInGroupWithCheckFilter()
    {
        $this->ldapParams->setGroup('TestGroup');

        $userDn = 'cn=TestUser,dc=syspass,dc=org';
        $userLogin = self::$faker->userName;
        $groupDn = 'cn=TestGroup,dc=groups,dc=syspass,dc=org';

        $this->ldapActions->expects(self::exactly(3))
                          ->method('searchGroupsDn')
                          ->with($this->ldap->getGroupObjectFilter())
                          ->willReturnOnConsecutiveCalls([], [], [$groupDn]);

        $groupsFilter = '(|(memberOf=cn=TestGroup,dc=groups,dc=syspass,dc=org)(groupMembership=cn=TestGroup,dc=groups,dc=syspass,dc=org)(memberof:1.2.840.113556.1.4.1941:=cn=TestGroup,dc=groups,dc=syspass,dc=org))';

        $this->ldapActions
            ->expects(self::once())
            ->method('getObjects')
            ->with($groupsFilter, ['dn'], $userDn)
            ->willReturn(new LdapResults(1, new EmptyIterator()));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('notify')
            ->with('ldap.check.group', self::anything());

        $out = $this->ldap->isUserInGroup($userDn, $userLogin, [$groupDn]);

        self::assertTrue($out);
    }

    /**
     * @throws LdapException
     */
    public function testIsUserInGroupWithCheckFilterAndZeroResults()
    {
        $this->ldapParams->setGroup('TestGroup');

        $userDn = 'cn=TestUser,dc=syspass,dc=org';
        $userLogin = self::$faker->userName;
        $groupDn = 'cn=TestGroup,dc=groups,dc=syspass,dc=org';

        $this->ldapActions->expects(self::exactly(3))
                          ->method('searchGroupsDn')
                          ->with($this->ldap->getGroupObjectFilter())
                          ->willReturnOnConsecutiveCalls([], [], [$groupDn]);

        $groupsFilter = '(|(memberOf=cn=TestGroup,dc=groups,dc=syspass,dc=org)(groupMembership=cn=TestGroup,dc=groups,dc=syspass,dc=org)(memberof:1.2.840.113556.1.4.1941:=cn=TestGroup,dc=groups,dc=syspass,dc=org))';

        $this->ldapActions
            ->expects(self::once())
            ->method('getObjects')
            ->with($groupsFilter, ['dn'], $userDn)
            ->willReturn(new LdapResults(0, new EmptyIterator()));

        $this->eventDispatcher
            ->expects(self::once())
            ->method('notify')
            ->with('ldap.check.group', self::anything());

        $out = $this->ldap->isUserInGroup($userDn, $userLogin, [$groupDn]);

        self::assertFalse($out);
    }

    /**
     * @throws SPException
     */
    public function testGetGroupMembershipIndirectFilter()
    {
        $groupDn = 'cn=TestGroup,dc=groups,dc=syspass,dc=org';
        $this->ldapParams->setGroup('TestGroup');

        $this->ldapActions->expects(self::once())
                          ->method('searchGroupsDn')
                          ->with($this->ldap->getGroupObjectFilter())
                          ->willReturn([$groupDn]);

        $out = $this->ldap->getGroupMembershipIndirectFilter();

        $expected = '(&(|'
                    . LdapUtil::getAttributesForFilter(LdapMsAds::DEFAULT_FILTER_GROUP_ATTRIBUTES, $groupDn)
                    . ')'
                    . LdapMsAds::DEFAULT_FILTER_USER_OBJECT
                    . ')';

        self::assertEquals($expected, $out);
    }

    /**
     * @throws SPException
     */
    public function testGetGroupMembershipIndirectFilterWithEmptyGroup()
    {
        $this->ldapActions->expects(self::never())
                          ->method('searchGroupsDn');

        $out = $this->ldap->getGroupMembershipIndirectFilter();

        self::assertEquals(LdapMsAds::DEFAULT_FILTER_USER_OBJECT, $out);
    }

    /**
     * @throws SPException
     */
    public function testGetGroupMembershipIndirectFilterWithAttributes()
    {
        $groupDn = 'cn=TestGroup,dc=groups,dc=syspass,dc=org';
        $this->ldapParams->setGroup('TestGroup');
        $this->ldapParams->setFilterGroupAttributes(['testAttribute']);

        $this->ldapActions->expects(self::once())
                          ->method('searchGroupsDn')
                          ->with($this->ldap->getGroupObjectFilter())
                          ->willReturn([$groupDn]);

        $out = $this->ldap->getGroupMembershipIndirectFilter();

        $expected = '(&(|'
                    . LdapUtil::getAttributesForFilter(['testAttribute'], $groupDn)
                    . ')'
                    . LdapMsAds::DEFAULT_FILTER_USER_OBJECT
                    . ')';

        self::assertEquals($expected, $out);
    }

    public function testGetUserDnFilter()
    {
        $user = self::$faker->userName;

        $out = $this->ldap->getUserDnFilter($user);

        $expected = '(&(|'
                    . LdapUtil::getAttributesForFilter(LdapMsAds::DEFAULT_FILTER_USER_ATTRIBUTES, $user)
                    . ')'
                    . LdapMsAds::DEFAULT_FILTER_USER_OBJECT
                    . ')';

        self::assertEquals($expected, $out);
    }

    public function testGetUserDnFilterWithAttributes()
    {
        $this->ldapParams->setFilterUserAttributes(['memberOf']);
        $user = self::$faker->userName;

        $out = $this->ldap->getUserDnFilter($user);

        $expected = '(&(|'
                    . LdapUtil::getAttributesForFilter(['memberOf'], $user)
                    . ')'
                    . LdapMsAds::DEFAULT_FILTER_USER_OBJECT
                    . ')';

        self::assertEquals($expected, $out);
    }

    public function testGetGroupObjectFilter()
    {
        $out = $this->ldap->getGroupObjectFilter();

        self::assertEquals(LdapMsAds::DEFAULT_FILTER_GROUP_OBJECT, $out);
    }

    public function testGetGroupObjectFilterWithFilter()
    {
        $this->ldapParams->setFilterGroupObject('test');

        $out = $this->ldap->getGroupObjectFilter();

        self::assertEquals('test', $out);
    }

    public function testGetServer()
    {
        self::assertEquals($this->ldapParams->getServer(), $this->ldap->getServer());
    }

    /**
     * @throws LdapException
     */
    public function testGetGroupMembershipDirectFilter()
    {
        $groupDn = 'cn=TestGroup,dc=groups,dc=syspass,dc=org';
        $this->ldapParams->setGroup('TestGroup');

        $this->ldapActions->expects(self::once())
                          ->method('searchGroupsDn')
                          ->with($this->ldap->getGroupObjectFilter())
                          ->willReturn([$groupDn]);

        $out = $this->ldap->getGroupMembershipDirectFilter();

        $expected = '(|'
                    . LdapUtil::getAttributesForFilter(LdapMsAds::DEFAULT_FILTER_GROUP_ATTRIBUTES, $groupDn)
                    . ')';

        self::assertEquals($expected, $out);
    }

    /**
     * @throws LdapException
     */
    public function testGetGroupMembershipDirectFilterWithAttributes()
    {
        $groupDn = 'cn=TestGroup,dc=groups,dc=syspass,dc=org';
        $this->ldapParams->setGroup('TestGroup');
        $this->ldapParams->setFilterGroupAttributes(['testAttribute']);

        $this->ldapActions->expects(self::once())
                          ->method('searchGroupsDn')
                          ->with($this->ldap->getGroupObjectFilter())
                          ->willReturn([$groupDn]);

        $out = $this->ldap->getGroupMembershipDirectFilter();

        $expected = '(|'
                    . LdapUtil::getAttributesForFilter(['testAttribute'], $groupDn)
                    . ')';

        self::assertEquals($expected, $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ldapConnection = $this->createMock(LdapConnectionHandler::class);
        $this->ldapActions = $this->createMock(LdapActionsService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::ADS,
            self::$faker->userName,
            self::$faker->password
        );

        $this->ldap = new LdapMsAds(
            $this->ldapConnection,
            $this->ldapActions,
            $this->ldapParams,
            $this->eventDispatcher
        );
    }
}
