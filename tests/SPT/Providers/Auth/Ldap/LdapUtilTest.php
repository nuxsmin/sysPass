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

namespace SPT\Providers\Auth\Ldap;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SP\Domain\Providers\Ldap\LdapUtil;
use SPT\UnitaryTestCase;

/**
 * Class LdapUtilTest
 *
 */
#[Group('unitary')]
class LdapUtilTest extends UnitaryTestCase
{

    public static function groupNameProvider(): array
    {
        return [
            ['cn=TestGroup,ou=Test,dc=foor,dc=bar', 'TestGroup'],
            ['sn=TestGroup,dc=foor,dc=bar', null],
            ['uid=TestGroup,dc=foor,dc=bar', null],
            ['ou=TestGroup,dc=foor,dc=bar', null],
            ['cn=TestGroup', null]
        ];
    }

    public function testGetAttributesForFilter()
    {
        $attributes = ['memberOf', 'uid', 'sn', 'cn'];
        $value = self::$faker->name;

        $out = LdapUtil::getAttributesForFilter($attributes, $value);

        $expected = sprintf('(memberOf=%s)(uid=%s)(sn=%s)(cn=%s)', $value, $value, $value, $value);

        self::assertEquals($expected, $out);
    }

    /**
     * @param string $group
     * @param string|null $expected
     * @return void
     */
    #[DataProvider('groupNameProvider')]
    public function testGetGroupName(string $group, ?string $expected)
    {
        self::assertEquals($expected, LdapUtil::getGroupName($group));
    }
}
