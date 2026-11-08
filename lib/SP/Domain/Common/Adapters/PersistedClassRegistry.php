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
 * Class PersistedClassRegistry
 *
 * Current FQCNs of classes whose layout is stored inside serialized payloads.
 * Renaming or moving one of these classes requires a matching entry in LegacyClassMap.
 */
final class PersistedClassRegistry
{
    public const CLASSES = [
        'SP\Domain\Core\Crypt\Vault',
        'SP\Domain\Core\Crypt\Crypt',
        'SP\Domain\Config\Adapters\ConfigData',
        'SP\Domain\User\Models\ProfileData',
        'SP\Domain\User\Models\UserPreferences',
        'SP\Domain\ItemPreset\Models\Password',
        'SP\Domain\ItemPreset\Models\AccountPrivate',
        'SP\Domain\ItemPreset\Models\AccountPermission',
        'SP\Domain\ItemPreset\Models\SessionTimeout',
    ];

    /**
     * @return string[]
     */
    public static function classes(): array
    {
        return self::CLASSES;
    }
}
