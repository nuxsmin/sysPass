<?php

namespace SP\Repositories\Track;

use SP\Core\Exceptions\InvalidArgumentException;

/**
 * Class TrackRequest
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