<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories\Track;

use SP\Core\Exceptions\InvalidArgumentException;
use SP\Http\Address;

/**
 * Class TrackRequest
 *
 * @package SP\Repositories\Track
 */
final class TrackRequest
{
    public int $time;
    public string $source;
    public ?int $userId = null;
    private ?string $ipv6 = null;
    private ?string $ipv4 = null;

    /**
     * @param int      $time
     * @param string   $source
     * @param int|null $userId
     */
    public function __construct(int $time, string $source, ?int $userId = null)
    {
        $this->time = $time;
        $this->source = $source;
        $this->userId = $userId;
    }

    /**
     * @param string $address
     *
     * @throws InvalidArgumentException
     */
    public function setTrackIp(string $address): void
    {
        $binary = Address::toBinary($address);
        $length = strlen($binary);

        if ($length === 4) {
            $this->ipv4 = $binary;
        } else {
            $this->ipv6 = $binary;
        }
    }

    /**
     * @return string
     */
    public function getIpv6(): ?string
    {
        return $this->ipv6;
    }

    /**
     * @return string
     */
    public function getIpv4(): ?string
    {
        return $this->ipv4;
    }
}