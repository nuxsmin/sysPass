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

namespace SPT\Domain\Import\Services;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Domain\Import\Dtos\LdapImportParamsDto;
use SP\Domain\Import\Services\LdapImport;
use SP\Domain\Providers\Ldap\LdapException;
use SP\Domain\Providers\Ldap\LdapParams;
use SP\Domain\Providers\Ldap\LdapResults;
use SP\Domain\Providers\Ldap\LdapTypeEnum;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserService;
use SPT\UnitaryTestCase;

/**
 * Class LdapImportTest
 *
 */
#[Group('unitary')]
class LdapImportTest extends UnitaryTestCase
{

    private LdapImport                  $ldapImport;
    private UserService|MockObject      $userService;
    private UserGroupService|MockObject $userGroupService;
    private LdapActionsService|MockObject   $ldapActionsService;
    private LdapConnectionInterface|MockObject   $ldapConnection;

    public static function emptyNameOrLoginProvider(): array
    {
        return [
            [
                [
                    'test_login' => [
                        'Test Login'
                    ],
                    'mail' => [
                        'me@email.com'
                    ]
                ]
            ],
            [
                [
                    'test_user' => [
                        'Test User'
                    ],
                    'mail' => [
                        'me@email.com'
                    ]
                ]
            ]
        ];
    }

    public static function userFilterByTypeProvider(): array
    {
        return [
            [
                LdapTypeEnum::STD,
                '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))'
            ],
            [
                LdapTypeEnum::ADS,
                '(&(!(UserAccountControl:1.2.840.113556.1.4.804:=32))(|(objectCategory=person)(objectClass=user)))'
            ],
        ];
    }

    public static function groupFilterByTypeProvider(): array
    {
        return [
            [
                LdapTypeEnum::STD,
                '(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group))'
            ],
            [
                LdapTypeEnum::ADS,
                '(objectCategory=group)'
            ],
        ];
    }

    /**
     * @throws LdapException
     */
    public function testImportUsers()
    {
        $ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::STD,
            self::$faker->userName,
            self::$faker->password
        );
        $ldapImportParams = new LdapImportParamsDto(100, 200, 'test_login', 'test_user', 'test_group', 'test_filter');
        $entry = [
            [
                'test_user' => [
                    'Test User'
                ],
                'test_login' => [
                    'Test Login'
                ],
                'mail' => [
                    'me@email.com'
                ]
            ]
        ];

        $this->ldapActionsService
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapActionsService);

        $this->ldapConnection
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapConnection);

        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->with('test_filter')
            ->willReturn(new LdapResults(100, new ArrayIterator($entry)));

        $user = new User(
            [
                'name' => 'Test User',
                'login' => 'Test Login',
                'email' => 'me@email.com',
                'notes' => 'Imported from LDAP',
                'userGroupId' => 100,
                'userProfileId' => 200,
                'isLdap' => true
            ]
        );

        $this->userService
            ->expects($this->once())
            ->method('create')
            ->with($user);

        $out = $this->ldapImport->importUsers($ldapParams, $ldapImportParams);

        $this->assertEquals(1, $out->getSyncedObjects());
        $this->assertEquals(100, $out->getTotalObjects());
        $this->assertEquals(0, $out->getErrorObjects());
    }

    /**
     * @throws LdapException
     */
    #[DataProvider('userFilterByTypeProvider')]
    public function testImportUsersWithFilterByType(LdapTypeEnum $ldapTypeEnum, string $filter)
    {
        $ldapParams = new LdapParams(
            self::$faker->domainName,
            $ldapTypeEnum,
            self::$faker->userName,
            self::$faker->password
        );
        $ldapImportParams = new LdapImportParamsDto(100, 200, 'test_login', 'test_user', 'test_group');
        $entry = [
            [
                'test_user' => [
                    'Test User'
                ],
                'test_login' => [
                    'Test Login'
                ],
                'mail' => [
                    'me@email.com'
                ]
            ]
        ];

        $this->ldapActionsService
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapActionsService);

        $this->ldapConnection
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapConnection);

        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->with($filter)
            ->willReturn(new LdapResults(100, new ArrayIterator($entry)));

        $user = new User(
            [
                'name' => 'Test User',
                'login' => 'Test Login',
                'email' => 'me@email.com',
                'notes' => 'Imported from LDAP',
                'userGroupId' => 100,
                'userProfileId' => 200,
                'isLdap' => true
            ]
        );

        $this->userService
            ->expects($this->once())
            ->method('create')
            ->with($user);

        $out = $this->ldapImport->importUsers($ldapParams, $ldapImportParams);

        $this->assertEquals(1, $out->getSyncedObjects());
        $this->assertEquals(100, $out->getTotalObjects());
        $this->assertEquals(0, $out->getErrorObjects());
    }

    /**
     * @throws LdapException
     */
    #[DataProvider('emptyNameOrLoginProvider')]
    public function testImportUsersWithEmptyNameOrLogin(array $entry)
    {
        $ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::STD,
            self::$faker->userName,
            self::$faker->password
        );
        $ldapImportParams = new LdapImportParamsDto(100, 200, 'test_login', 'test_user', 'test_group', 'test_filter');

        $this->ldapActionsService
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapActionsService);

        $this->ldapConnection
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapConnection);

        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->with('test_filter')
            ->willReturn(new LdapResults(100, new ArrayIterator($entry)));

        $this->userService
            ->expects($this->never())
            ->method('create');

        $this->ldapImport->importUsers($ldapParams, $ldapImportParams);
    }

    /**
     * @throws LdapException
     */
    public function testImportUsersWithException()
    {
        $ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::STD,
            self::$faker->userName,
            self::$faker->password
        );
        $ldapImportParams = new LdapImportParamsDto(100, 200, 'test_login', 'test_user', 'test_group', 'test_filter');
        $entry = [
            [
                'test_user' => [
                    'Test User'
                ],
                'test_login' => [
                    'Test Login'
                ],
                'mail' => [
                    'me@email.com'
                ]
            ]
        ];

        $this->ldapActionsService
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapActionsService);

        $this->ldapConnection
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapConnection);

        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->with('test_filter')
            ->willReturn(new LdapResults(100, new ArrayIterator($entry)));

        $user = new User(
            [
                'name' => 'Test User',
                'login' => 'Test Login',
                'email' => 'me@email.com',
                'notes' => 'Imported from LDAP',
                'userGroupId' => 100,
                'userProfileId' => 200,
                'isLdap' => true
            ]
        );

        $this->userService
            ->expects($this->once())
            ->method('create')
            ->with($user)
            ->willThrowException(new RuntimeException('test'));

        $out = $this->ldapImport->importUsers($ldapParams, $ldapImportParams);

        $this->assertEquals(0, $out->getSyncedObjects());
        $this->assertEquals(100, $out->getTotalObjects());
        $this->assertEquals(1, $out->getErrorObjects());
    }

    /**
     * @throws LdapException
     */
    public function testImportGroups()
    {
        $ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::STD,
            self::$faker->userName,
            self::$faker->password
        );
        $ldapImportParams = new LdapImportParamsDto(100, 200, 'test_login', 'test_user', 'test_group', 'test_filter');
        $entry = [
            [
                'test_group' => [
                    'Test Group'
                ]
            ]
        ];

        $this->ldapActionsService
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapActionsService);

        $this->ldapConnection
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapConnection);

        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->with('test_filter')
            ->willReturn(new LdapResults(100, new ArrayIterator($entry)));

        $group = new UserGroup(
            [
                'name' => 'Test Group',
                'description' => 'Imported from LDAP'
            ]
        );

        $this->userGroupService
            ->expects($this->once())
            ->method('create')
            ->with($group);

        $out = $this->ldapImport->importGroups($ldapParams, $ldapImportParams);

        $this->assertEquals(1, $out->getSyncedObjects());
        $this->assertEquals(100, $out->getTotalObjects());
    }

    /**
     * @throws LdapException
     */
    #[DataProvider('groupFilterByTypeProvider')]
    public function testImportGroupsWithFilterByType(LdapTypeEnum $ldapTypeEnum, string $filter)
    {
        $ldapParams = new LdapParams(
            self::$faker->domainName,
            $ldapTypeEnum,
            self::$faker->userName,
            self::$faker->password
        );
        $ldapImportParams = new LdapImportParamsDto(100, 200, 'test_login', 'test_user', 'test_group');
        $entry = [
            [
                'test_group' => [
                    'Test Group'
                ]
            ]
        ];

        $this->ldapActionsService
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapActionsService);

        $this->ldapConnection
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapConnection);

        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->with($filter)
            ->willReturn(new LdapResults(100, new ArrayIterator($entry)));

        $group = new UserGroup(
            [
                'name' => 'Test Group',
                'description' => 'Imported from LDAP'
            ]
        );

        $this->userGroupService
            ->expects($this->once())
            ->method('create')
            ->with($group);

        $out = $this->ldapImport->importGroups($ldapParams, $ldapImportParams);

        $this->assertEquals(1, $out->getSyncedObjects());
        $this->assertEquals(100, $out->getTotalObjects());
    }

    /**
     * @throws LdapException
     */
    public function testImportGroupsWithEmptyName()
    {
        $ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::STD,
            self::$faker->userName,
            self::$faker->password
        );
        $ldapImportParams = new LdapImportParamsDto(100, 200, 'test_login', 'test_user', 'test_group', 'test_filter');
        $entry = [[]];

        $this->ldapActionsService
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapActionsService);

        $this->ldapConnection
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapConnection);

        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->with('test_filter')
            ->willReturn(new LdapResults(100, new ArrayIterator($entry)));

        $this->userGroupService
            ->expects($this->never())
            ->method('create');

        $out = $this->ldapImport->importGroups($ldapParams, $ldapImportParams);

        $this->assertEquals(0, $out->getSyncedObjects());
        $this->assertEquals(100, $out->getTotalObjects());
        $this->assertEquals(0, $out->getErrorObjects());
    }

    /**
     * @throws LdapException
     */
    public function testImportGroupsWithException()
    {
        $ldapParams = new LdapParams(
            self::$faker->domainName,
            LdapTypeEnum::STD,
            self::$faker->userName,
            self::$faker->password
        );
        $ldapImportParams = new LdapImportParamsDto(100, 200, 'test_login', 'test_user', 'test_group', 'test_filter');
        $entry = [
            [
                'test_group' => [
                    'Test Group'
                ]
            ]
        ];

        $this->ldapActionsService
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapActionsService);

        $this->ldapConnection
            ->expects($this->once())
            ->method('mutate')
            ->with($ldapParams)
            ->willReturn($this->ldapConnection);

        $this->ldapActionsService
            ->expects($this->once())
            ->method('getObjects')
            ->with('test_filter')
            ->willReturn(new LdapResults(100, new ArrayIterator($entry)));

        $group = new UserGroup(
            [
                'name' => 'Test Group',
                'description' => 'Imported from LDAP'
            ]
        );

        $this->userGroupService
            ->expects($this->once())
            ->method('create')
            ->with($group)
            ->willThrowException(new RuntimeException('test'));

        $out = $this->ldapImport->importGroups($ldapParams, $ldapImportParams);

        $this->assertEquals(0, $out->getSyncedObjects());
        $this->assertEquals(100, $out->getTotalObjects());
        $this->assertEquals(1, $out->getErrorObjects());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = $this->createMock(UserService::class);
        $this->userGroupService = $this->createMock(UserGroupService::class);
        $this->ldapActionsService = $this->createMock(LdapActionsService::class);
        $this->ldapConnection = $this->createMock(LdapConnectionInterface::class);

        $this->ldapImport = new LdapImport(
            $this->application,
            $this->userService,
            $this->userGroupService,
            $this->ldapActionsService,
            $this->ldapConnection
        );
    }


}
