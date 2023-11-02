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

use SP\Providers\Auth\Ldap\LdapUtil;
use SP\Tests\UnitaryTestCase;

/**
 * Class LdapUtilTest
 *
 * @group unitary
 */
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
     * @dataProvider groupNameProvider
     *
     * @param string $group
     * @param string|null $expected
     * @return void
     */
    public function testGetGroupName(string $group, ?string $expected)
    {
        self::assertEquals($expected, LdapUtil::getGroupName($group));
    }
}
