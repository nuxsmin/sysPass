<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Auth\Ldap;

use SP\Auth\AuthDataBase;

/**
 * Class LdapUserData
 *
 * @package SP\Auth\Ldap
 */
class LdapAuthData extends AuthDataBase
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