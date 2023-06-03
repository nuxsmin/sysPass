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

namespace SP\Http;

use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\SPException;
use function SP\__;
use function SP\__u;
use function SP\logger;

/**
 * Class Address
 *
 * @package SP\Http
 */
final class Address
{
    public const PATTERN_IP_ADDRESS = '#^(?<address>[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3})(?:/(?:(?<mask>[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3})|(?<cidr>[\d]{1,2})))?$#';

    /**
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public static function toBinary(string $address): string
    {
        if (!filter_var($address, FILTER_VALIDATE_IP)
            || ($binAddress = @inet_pton($address)) === false
        ) {
            logger(sprintf('%s : %s', __('Invalid IP'), $address));

            throw new InvalidArgumentException(__u('Invalid IP'), SPException::ERROR, $address);
        }

        return $binAddress;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function fromBinary(string $address): string
    {
        $stringAddress = @inet_ntop($address);

        if ($stringAddress === false) {
            logger(sprintf('%s : %s', __('Invalid IP'), $address));

            throw new InvalidArgumentException(__u('Invalid IP'), SPException::ERROR, $address);
        }

        return $stringAddress;
    }

    /**
     * Parses an IPv4 address from either "192.168.0.1", "192.168.0.0/255.255.255.0" or "192.168.0.0/24" formats
     *
     * @throws InvalidArgumentException
     */
    public static function parse4(string $address): array
    {
        if (preg_match(self::PATTERN_IP_ADDRESS, $address, $matches)) {
            return $matches;
        }

        throw new InvalidArgumentException(__u('Invalid IP'), SPException::ERROR, $address);
    }

    /**
     * Checks whether an IP address is included within $inAddress and $inMask
     *
     * @throws InvalidArgumentException
     */
    public static function check(
        string $address,
        string $inAddress,
        string $inMask
    ): bool {
        if (!filter_var($address, FILTER_VALIDATE_IP)
            || !filter_var($inAddress, FILTER_VALIDATE_IP)
            || !filter_var($inMask, FILTER_VALIDATE_IP)
        ) {
            throw new InvalidArgumentException(__u('Invalid IP'), SPException::ERROR, $address);
        }

        // Obtains subnets based on mask ie.: subnet === subnet
        return (ip2long($address) & ip2long($inMask)) === (ip2long($inAddress) & ip2long($inMask));
    }

    /**
     * Converts a CIDR mask into decimal
     */
    public static function cidrToDec(int $bits): string
    {
        return long2ip(-1 << (32 - $bits));
    }
}
