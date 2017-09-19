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
    public $track_id;
    /**
     * @var int
     */
    public $track_userId = 0;
    /**
     * @var string
     */
    public $track_source = '';
    /**
     * @var int
     */
    public $track_time = 0;
    /**
     * @var string
     */
    public $track_ipv4 = '';
    /**
     * @var string
     */
    public $track_ipv6 = '';

    /**
     * @return int
     */
    public function getTrackId()
    {
        return (int)$this->track_id;
    }

    /**
     * @param int $track_id
     */
    public function setTrackId($track_id)
    {
        $this->track_id = (int)$track_id;
    }

    /**
     * @return int
     */
    public function getTrackUserId()
    {
        return (int)$this->track_userId;
    }

    /**
     * @param int $track_userId
     */
    public function setTrackUserId($track_userId)
    {
        $this->track_userId = (int)$track_userId;
    }

    /**
     * @return string
     */
    public function getTrackSource()
    {
        return $this->track_source;
    }

    /**
     * @param string $track_source
     */
    public function setTrackSource($track_source)
    {
        $this->track_source = $track_source;
    }

    /**
     * @return int
     */
    public function getTrackTime()
    {
        return (int)$this->track_time;
    }

    /**
     * @param int $track_time
     */
    public function setTrackTime($track_time)
    {
        $this->track_time = (int)$track_time;
    }

    /**
     * @return string
     */
    public function getTrackIpv4()
    {
        return @inet_ntop($this->track_ipv4);
    }

    /**
     * @param string $track_ipv4
     */
    public function setTrackIpv4($track_ipv4)
    {
        $this->track_ipv4 = @inet_pton($track_ipv4);
    }

    /**
     * @param string $track_ip
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function setTrackIp($track_ip)
    {
        $ip = @inet_pton($track_ip);

        if (strlen($ip) === 4) {
            $this->track_ipv4 = $ip;
        } elseif (strlen($ip) > 4) {
            $this->track_ipv6 = $ip;
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
        return $this->track_ipv4;
    }

    /**
     * @return string
     */
    public function getTrackIpv6()
    {
        return @inet_ntop($this->track_ipv6);
    }

    /**
     * @param string $track_ipv6
     */
    public function setTrackIpv6($track_ipv6)
    {
        $this->track_ipv6 = @inet_pton($track_ipv6);
    }

    /**
     * @return string
     */
    public function getTrackIpv6Bin()
    {
        return $this->track_ipv6;
    }
}