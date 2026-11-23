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

namespace SP\Domain\Config\Dtos;

/**
 * Normalises configuration values coming from the interface layer.
 */
final class ConfigInput
{
    /**
     * Convert a comma separated list of email addresses into an array.
     *
     * @return list<string>
     */
    public static function mailAddresses(string $mailAddresses): array
    {
        if (empty($mailAddresses)) {
            return [];
        }

        return array_filter(
            explode(',', $mailAddresses),
            static fn($value) => filter_var($value, FILTER_VALIDATE_EMAIL)
        );
    }

    /**
     * Filter a list of event names.
     *
     * @param array<string> $events
     *
     * @return list<string>
     */
    public static function events(array $events): array
    {
        return array_filter(
            $events,
            static fn($value) => preg_match('/^[a-z][a-z.]+$/i', $value)
        );
    }
}
