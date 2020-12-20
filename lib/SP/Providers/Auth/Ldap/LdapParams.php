<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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
     * @var string
     */
    protected $filterUserObject;
    /**
     * @var string
     */
    protected $filterGroupObject;
    /**
     * @var array
     */
    protected $filterUserAttributes;
    /**
     * @var array
     */
    protected $filterGroupAttributes;

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
     * @return string
     */
    public function getFilterUserObject(): ?string
    {
        return $this->filterUserObject;
    }

    /**
     * @param string|null $filterUserObject
     */
    public function setFilterUserObject(?string $filterUserObject = null)
    {
        if (!empty($filterUserObject)) {
            $this->filterUserObject = $filterUserObject;
        }
    }

    /**
     * @return string
     */
    public function getFilterGroupObject(): ?string
    {
        return $this->filterGroupObject;
    }

    /**
     * @param string|null $filterGroupObject
     */
    public function setFilterGroupObject(?string $filterGroupObject = null)
    {
        if (!empty($filterGroupObject)) {
            $this->filterGroupObject = $filterGroupObject;
        }
    }

    /**
     * @return array
     */
    public function getFilterUserAttributes(): ?array
    {
        return $this->filterUserAttributes;
    }

    /**
     * @param array|null $filterUserAttributes
     */
    public function setFilterUserAttributes(?array $filterUserAttributes = null)
    {
        $this->filterUserAttributes = $filterUserAttributes;
    }

    /**
     * @return array
     */
    public function getFilterGroupAttributes(): ?array
    {
        return $this->filterGroupAttributes;
    }

    /**
     * @param array|null $filterGroupAttributes
     */
    public function setFilterGroupAttributes(?array $filterGroupAttributes = null)
    {
        $this->filterGroupAttributes = $filterGroupAttributes;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return LdapParams
     */
    public function setPort(int $port): LdapParams
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearchBase(): ?string
    {
        return $this->searchBase;
    }

    /**
     * @param string $searchBase
     *
     * @return LdapParams
     */
    public function setSearchBase(string $searchBase): LdapParams
    {
        $this->searchBase = $searchBase;
        return $this;
    }

    /**
     * @return string
     */
    public function getBindDn(): ?string
    {
        return $this->bindDn;
    }

    /**
     * @param string $bindDn
     *
     * @return LdapParams
     */
    public function setBindDn(string $bindDn): LdapParams
    {
        $this->bindDn = $bindDn;
        return $this;
    }

    /**
     * @return string
     */
    public function getBindPass(): ?string
    {
        return $this->bindPass;
    }

    /**
     * @param string $bindPass
     *
     * @return LdapParams
     */
    public function setBindPass(string $bindPass): LdapParams
    {
        $this->bindPass = $bindPass;
        return $this;
    }

    /**
     * @return string
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @param string $group
     *
     * @return LdapParams
     */
    public function setGroup(string $group): LdapParams
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getServer(): ?string
    {
        return $this->server;
    }

    /**
     * @param string $server
     *
     * @return LdapParams
     */
    public function setServer(string $server): LdapParams
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return int
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return LdapParams
     */
    public function setType($type): LdapParams
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
    public function setTlsEnabled(bool $tlsEnabled): LdapParams
    {
        $this->tlsEnabled = $tlsEnabled;

        return $this;
    }
}