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

namespace SP\Domain\Crypt\Ports;

use Defuse\Crypto\Key;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\UuidCookieInterface;

/**
 * Class SecureSessionService
 *
 * @package SP\Domain\Crypt\Services
 */
interface SecureSessionService
{
    /**
     * Returns an unique filename from a browser cookie
     *
     * @throws ServiceException
     */
    public static function getFileNameFrom(UuidCookieInterface $cookie, string $seed): string;

    /**
     * Returns the encryption key
     *
     *
     * @return Key|false
     */
    public function getKey(): Key|bool;
}
