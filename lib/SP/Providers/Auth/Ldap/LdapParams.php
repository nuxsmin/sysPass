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

namespace SP\Providers\Auth\Ldap;

/**
 * Class LdapParams
 *
 * @package SP\Providers\Auth\Ldap
 */
final class LdapParams
{
    const REGEX_SERVER = '(?<server>(?:(?<proto>ldap|ldaps):\/\/)?[\w\.\-]+)(?::(?<port>\d+))?';

    /**
     * @var string
     */
    protected $server;
    /**
     * @var int
     */
    protected $port = 389;
    /**
     * @var string
     */
    protected $searchBase;
    /**
     * @var string
     */
    protected $bindDn;
    /**
     * @var string
     */
    protected $bindPass;
    /**
     * @var string
     */
    protected $group;
    /**
     * @var int
     */
    protected $type;
    /**
     * @var bool
     */
    protected $tlsEnabled = false;

    /**
     * Devolver el puerto del servidor si está establecido
     *
     * @param $server
     *
     * @return array|false
     */
    public static function getServerAndPort($server)
    {
        return preg_match('#' . self::REGEX_SERVER . '#i', $server, $matches) ? $matches : false;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return LdapParams
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearchBase()
    {
        return $this->searchBase;
    }

    /**
     * @param string $searchBase
     *
     * @return LdapParams
     */
    public function setSearchBase($searchBase)
    {
        $this->searchBase = $searchBase;
        return $this;
    }

    /**
     * @return string
     */
    public function getBindDn()
    {
        return $this->bindDn;
    }

    /**
     * @param string $bindDn
     *
     * @return LdapParams
     */
    public function setBindDn($bindDn)
    {
        $this->bindDn = $bindDn;
        return $this;
    }

    /**
     * @return string
     */
    public function getBindPass()
    {
        return $this->bindPass;
    }

    /**
     * @param string $bindPass
     *
     * @return LdapParams
     */
    public function setBindPass($bindPass)
    {
        $this->bindPass = $bindPass;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     *
     * @return LdapParams
     */
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param string $server
     *
     * @return LdapParams
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return LdapParams
     */
    public function setType($type)
    {
        $this->type = (int)$type;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTlsEnabled(): bool
    {
        return $this->tlsEnabled;
    }

    /**
     * @param bool $tlsEnabled
     *
     * @return LdapParams
     */
    public function setTlsEnabled(bool $tlsEnabled)
    {
        $this->tlsEnabled = $tlsEnabled;

        return $this;
    }

}