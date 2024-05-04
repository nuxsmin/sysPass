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

namespace SP\Domain\ItemPreset\Models;

use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Http\Adapters\Address;

/**
 * Class SessionTimeout
 *
 * TODO: serde using JSON
 */
readonly class SessionTimeout
{
    private string $address;
    private string $mask;

    /**
     * SessionTimeout constructor.
     *
     * @param string $address IP address and/or mask
     * @param int $timeout Timeout in seconds
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $address, private int $timeout)
    {
        $parse = Address::parse4($address);

        $this->address = $parse['address'];

        $this->setMask($parse);
    }

    /**
     * @param array $parse
     */
    private function setMask(array $parse): void
    {
        if (isset($parse['cidr'])) {
            $this->mask = Address::cidrToDec($parse['cidr']);
        } elseif (isset($parse['mask'])) {
            $this->mask = $parse['mask'];
        } else {
            $this->mask = '255.255.255.255';
        }
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getMask(): string
    {
        return $this->mask;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
