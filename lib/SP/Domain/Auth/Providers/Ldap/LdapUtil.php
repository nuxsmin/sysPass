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

/** @noinspection PhpComposerExtensionStubsInspection */

namespace SP\Domain\Auth\Providers\Ldap;

/**
 * Class LdapUtil
 *
 * @package SP\Auth\Ldap
 */
final class LdapUtil
{
    /**
     * Obtener el nombre del grupo a partir del CN
     *
     * @param string $group
     *
     * @return string|null
     */
    public static function getGroupName(string $group): ?string
    {
        if (preg_match('/^cn=(?<groupname>[^,]+),.*/i', $group, $matches)) {
            return $matches['groupname'];
        }

        return null;
    }

    /**
     * @param array $attributes
     * @param string $value
     *
     * @return string
     */
    public static function getAttributesForFilter(array $attributes, string $value): string
    {
        $value = ldap_escape($value, null, LDAP_ESCAPE_FILTER);

        return implode(
            '',
            array_map(
                static fn($attribute) => sprintf('(%s=%s)', $attribute, $value),
                $attributes
            )
        );
    }
}
