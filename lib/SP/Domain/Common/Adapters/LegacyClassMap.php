<?php

declare(strict_types=1);
/**
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

namespace SP\Domain\Common\Adapters;

/**
 * Class LegacyClassMap
 *
 * Maps legacy FQCNs of persisted classes to their current FQCNs. Append-only: entries are kept
 * for as long as legacy backups may be restored. Private property names are not mapped because
 * no persisted property has been renamed; mangled keys are rewritten using the class entry.
 */
final class LegacyClassMap
{
    public const MAP = [
        'SP\Core\Crypt\Vault' => 'SP\Domain\Core\Crypt\Vault',
        'SP\Core\Crypt\Crypt' => 'SP\Domain\Core\Crypt\Crypt',
    ];

    /**
     * @return array<string, string>
     */
    public static function map(): array
    {
        return self::MAP;
    }
}
