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

namespace SP\Domain\Crypt\Adapters;

use SP\Domain\Crypt\Ports\HashService;
use SP\Domain\Crypt\Services\Hash;

/**
 * Adapter for the static Hash helper.
 */
final class HashServiceAdapter implements HashService
{
    public function checkHashKey(string $key, string $hash): bool
    {
        return Hash::checkHashKey($key, $hash);
    }

    public function hashKey(string $key): string
    {
        return Hash::hashKey($key);
    }

    public function checkMessage(string $message, string $key, string $hash): bool
    {
        return Hash::checkMessage($message, $key, $hash);
    }

    public function signMessage(string $message, string $key): string
    {
        return Hash::signMessage($message, $key);
    }
}
