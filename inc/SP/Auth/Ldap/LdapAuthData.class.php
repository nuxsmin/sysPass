<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Auth\Ldap;

/**
 * Class LdapUserData
 *
 * @package SP\Auth\Ldap
 */
class LdapAuthData
{
    /**
     * @var string
     */
    protected $dn;
    /**
     * @var string
     */
    protected $groupDn;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $email;
    /**
     * @var array
     */
    protected $groups = [];
    /**
     * @var int
     */
    protected $expire = 0;
    /**
     * @var bool
     */
    protected $inGroup = false;
    /**
     * @var string
     */
    protected $server;
    /**
     * @var int
     */
    protected $ldapStatus;

    /**
     * @return string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * @param string $dn
     */
    public function setDn($dn)
    {
        $this->dn = $dn;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param array|string $groups
     */
    public function setGroups($groups)
    {
        $this->groups = is_string($groups) ? [$groups] : $groups;
    }

    /**
     * @return int
     */
    public function getExpire()
    {
        return (int)$this->expire;
    }

    /**
     * @param int $expire
     */
    public function setExpire($expire)
    {
        $this->expire = (int)$expire;
    }

    /**
     * @return boolean
     */
    public function isInGroup()
    {
        return $this->inGroup;
    }

    /**
     * @param boolean $inGroup
     */
    public function setInGroup($inGroup)
    {
        $this->inGroup = $inGroup;
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
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return int
     */
    public function getLdapStatus()
    {
        return $this->ldapStatus;
    }

    /**
     * @param int $ldapStatus
     */
    public function setLdapStatus($ldapStatus)
    {
        $this->ldapStatus = $ldapStatus;
    }

    /**
     * @return string
     */
    public function getGroupDn()
    {
        return $this->groupDn;
    }

    /**
     * @param string $groupDn
     */
    public function setGroupDn($groupDn)
    {
        $this->groupDn = $groupDn;
    }
}