<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories\Track;

use SP\Core\Exceptions\InvalidArgumentException;

/**
 * Class TrackRequest
 *
 * @package SP\Repositories\Track
 */
class TrackRequest
{
    public $time;
    public $source;
    public $userId;
    protected $ipv6;
    protected $ipv4;

    /**
     * @param string $address
     *
     * @throws InvalidArgumentException
     */
    public function setTrackIp($address)
    {
        $ip = @inet_pton($address);

        if (strlen($ip) === 4) {
            $this->ipv4 = $ip;
        } elseif (strlen($ip) > 4) {
            $this->ipv6 = $ip;
        } elseif ($ip === false) {
            debugLog(sprintf('%s : %s', __('IP inválida'), $address));

            throw new InvalidArgumentException(__u('IP inválida'), InvalidArgumentException::ERROR, $address);
        }
    }

    /**
     * @return string
     */
    public function getIpv6()
    {
        return $this->ipv6;
    }

    /**
     * @return string
     */
    public function getIpv4()
    {
        return $this->ipv4;
    }
}