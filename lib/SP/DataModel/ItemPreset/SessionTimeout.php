<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\DataModel\ItemPreset;

use SP\Core\Exceptions\InvalidArgumentException;
use SP\Http\Address;

/**
 * Class SessionTimeout
 *
 * @package SP\DataModel\ItemPreset
 */
class SessionTimeout
{
    /**
     * @var string
     */
    private $address;
    /**
     * @var string
     */
    private $mask;
    /**
     * @var int
     */
    private $timeout;

    /**
     * SessionTimeout constructor.
     *
     * @param string $address IP address and/or mask
     * @param int    $timeout Timeout in seconds
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $address, int $timeout)
    {
        $parse = Address::parse4($address);

        $this->address = $parse['address'];
        $this->timeout = $timeout;

        $this->setMask($parse);
    }

    /**
     * @param array $parse
     */
    private function setMask(array $parse)
    {
        if (isset($parse['cidr'])) {
            $this->mask = Address::cidrToDec($parse['cidr']);
        } elseif (isset($parse['mask'])) {
            $this->mask = $parse['mask'];
        } else {
            $this->mask = '255.255.255.255';
        }
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getMask(): string
    {
        return $this->mask;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}