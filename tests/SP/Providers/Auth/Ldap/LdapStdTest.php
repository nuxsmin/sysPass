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

use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Events\EventDispatcherInterface;
use SP\Core\Exceptions\SPException;
use SP\Domain\Auth\Ports\LdapActionsInterface;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Auth\Ldap\LdapStd;
use SP\Providers\Auth\Ldap\LdapTypeEnum;
use SP\Providers\Auth\Ldap\LdapUtil;
use SP\Tests\UnitaryTestCase;

/**
 * Class LdapStdTest
 *
 * @group unitary
 */
class LdapStdTest extends UnitaryTestCase
{

    private LdapConnectionInterface|MockObject  $ldapConnection;
    private LdapActionsInterface|MockObject     $ldapActions;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private LdapStd                             $ldap;
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

        $this->ldapConnection->expects(self::once())->method('connect')->with($user, $password);

        $this->ldap->connect($user, $password);
    }

    /**
     * @throws LdapException
     */
    public function testConnectWithNull()
    {
        $this->ldapConnection->expects(self::once())->method('connect')->with(null, null);

        $this->ldap->connect();
    }

    /**
     * @dataProvider groupDataProvider()
     * @throws LdapException
     */
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

        $this->ldapActions->expects(self::exactly(1))
                          ->method('searchGroupsDn')
                          ->with($this->ldap->getGroupObjectFilter())
                          ->willReturnOnConsecutiveCalls([], [], [$groupDn]);

        $groupsFilter = '(&(cn=TestGroup)(|(memberUid=cn=TestUser,dc=syspass,dc=org)(member=cn=TestUser,dc=syspass,dc=org)(uniqueMember=cn=TestUser,dc=syspass,dc=org))(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group)))';

        $this->ldapActions
            ->expects(self::once())
            ->method('getObjects')
            ->with($groupsFilter, ['dn']);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('notify')
            ->with('ldap.check.group', self::anything());

        $out = $this->ldap->isUserInGroup($userDn, $userLogin, []);

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

        $this->ldapActions->expects(self::exactly(1))
                          ->method('searchGroupsDn')
                          ->with($this->ldap->getGroupObjectFilter())
                          ->willReturnOnConsecutiveCalls([], [], [$groupDn]);

        $groupsFilter = '(&(cn=TestGroup)(|(memberUid=cn=TestUser,dc=syspass,dc=org)(member=cn=TestUser,dc=syspass,dc=org)(uniqueMember=cn=TestUser,dc=syspass,dc=org))(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group)))';

        $this->ldapActions
            ->expects(self::once())
            ->method('getObjects')
            ->with($groupsFilter, ['dn'])
            ->willReturn(['count' => 0]);

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

        $expected = sprintf(
            "(&(|%s)%s)",
            LdapUtil::getAttributesForFilter(LdapStd::DEFAULT_FILTER_GROUP_ATTRIBUTES, $groupDn),
            '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))'
        );

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
        $expected = '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))';

        self::assertEquals($expected, $out);
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

        $expected = sprintf(
            "(&(|%s)%s)",
            LdapUtil::getAttributesForFilter(['testAttribute'], $groupDn),
            '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))'
        );

        self::assertEquals($expected, $out);
    }

    public function testGetUserDnFilter()
    {
        $user = self::$faker->userName;

        $out = $this->ldap->getUserDnFilter($user);

        $expected = sprintf(
            "(&(|%s)%s)",
            LdapUtil::getAttributesForFilter(LdapStd::DEFAULT_FILTER_USER_ATTRIBUTES, $user),
            '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))'
        );

        self::assertEquals($expected, $out);
    }

    public function testGetUserDnFilterWithAttributes()
    {
        $this->ldapParams->setFilterUserAttributes(['memberOf']);
        $user = self::$faker->userName;

        $out = $this->ldap->getUserDnFilter($user);

        $expected = sprintf(
            "(&(|%s)%s)",
            LdapUtil::getAttributesForFilter(['memberOf'], $user),
            '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))'
        );

        self::assertEquals($expected, $out);
    }

    public function testGetGroupObjectFilter()
    {
        $out = $this->ldap->getGroupObjectFilter();
        $expected = '(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group))';

        self::assertEquals($expected, $out);
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

    public function testGetGroupMembershipDirectFilter()
    {
        $this->ldapParams->setGroup('TestGroup');
        $out = $this->ldap->getGroupMembershipDirectFilter();

        $expected = sprintf(
            '(&(cn=%s)(|(memberUid=%s)(member=%s)(uniqueMember=%s))%s)',
            'TestGroup',
            '*',
            '*',
            '*',
            '(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group))'
        );

        self::assertEquals($expected, $out);
    }

    public function testGetGroupMembershipDirectFilterWithUser()
    {
        $user = 'TestUser';
        $this->ldapParams->setGroup('TestGroup');
        $out = $this->ldap->getGroupMembershipDirectFilter($user);

        $expected = sprintf(
            '(&(cn=%s)(|(memberUid=%s)(member=%s)(uniqueMember=%s))%s)',
            'TestGroup',
            $user,
            $user,
            $user,
            '(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group))'
        );

        self::assertEquals($expected, $out);
    }

    public function testGetGroupMembershipDirectFilterWithoutGroup()
    {
        $user = 'TestUser';
        $out = $this->ldap->getGroupMembershipDirectFilter($user);

        $expected = '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))';

        self::assertEquals($expected, $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ldapConnection = $this->createMock(LdapConnectionInterface::class);
        $this->ldapActions = $this->createMock(LdapActionsInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::ADS,
            self::$faker->userName,
            self::$faker->password
        );

        $this->ldap = new LdapStd(
            $this->ldapConnection,
            $this->ldapActions,
            $this->ldapParams,
            $this->eventDispatcher
        );
    }
}
