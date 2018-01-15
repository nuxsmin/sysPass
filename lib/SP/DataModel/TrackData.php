<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\DataModel;

use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\SPException;

/**
 * Class TrackData
 *
 * @package SP\DataModel
 */
class TrackData extends DataModelBase
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $userId = 0;
    /**
     * @var string
     */
    public $source = '';
    /**
     * @var int
     */
    public $time = 0;
    /**
     * @var string
     */
    public $ipv4 = '';
    /**
     * @var string
     */
    public $ipv6 = '';

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return (int)$this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = (int)$userId;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return (int)$this->time;
    }

    /**
     * @param int $time
     */
    public function setTime($time)
    {
        $this->time = (int)$time;
    }

    /**
     * @return string
     */
    public function getIpv4()
    {
        return @inet_ntop($this->ipv4);
    }

    /**
     * @param string $ipv4
     */
    public function setIpv4($ipv4)
    {
        $this->ipv4 = @inet_pton($ipv4);
    }

    /**
     * @param string $track_ip
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function setTrackIp($track_ip)
    {
        $ip = @inet_pton($track_ip);

        if (strlen($ip) === 4) {
            $this->ipv4 = $ip;
        } elseif (strlen($ip) > 4) {
            $this->ipv6 = $ip;
        } elseif ($ip === false) {
            debugLog(sprintf('%s : %s', __('IP inválida', true), $track_ip));

            throw new InvalidArgumentException(SPException::SP_ERROR, __('IP inválida'), $track_ip);
        }
    }

    /**
     * @return int
     */
    public function getTrackIpv4Bin()
    {
        return $this->ipv4;
    }

    /**
     * @return string
     */
    public function getIpv6()
    {
        return @inet_ntop($this->ipv6);
    }

    /**
     * @param string $ipv6
     */
    public function setIpv6($ipv6)
    {
        $this->ipv6 = @inet_pton($ipv6);
    }

    /**
     * @return string
     */
    public function getTrackIpv6Bin()
    {
        return $this->ipv6;
    }
}