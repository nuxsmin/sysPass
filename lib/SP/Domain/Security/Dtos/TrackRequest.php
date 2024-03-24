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

namespace SP\Domain\Security\Dtos;

use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Http\Address;

/**
 * Class TrackRequest
 *
 * @package SP\Infrastructure\Security\Repositories
 */
final class TrackRequest
{
    private ?string $ipv6 = null;
    private ?string $ipv4 = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly int    $time,
        private readonly string $source,
        string                  $address,
        private readonly ?int   $userId = null
    ) {
        $this->setTrackIp($address);
    }

    /**
     * @param string $address
     *
     * @throws InvalidArgumentException
     */
    private function setTrackIp(string $address): void
    {
        $binary = Address::toBinary($address);
        $length = strlen($binary);

        if ($length === 4) {
            $this->ipv4 = $binary;
        } else {
            $this->ipv6 = $binary;
        }
    }

    public function getIpv6(): ?string
    {
        return $this->ipv6;
    }

    public function getIpv4(): ?string
    {
        return $this->ipv4;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
}
