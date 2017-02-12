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

namespace SP\DataModel;

defined('APP_ROOT') || die();

/**
 * Class GroupAccountsData
 *
 * @package SP\DataModel
 */
class GroupAccountsData extends DataModelBase 
{
    /**
     * @var int
     */
    public $accgroup_groupId = 0;
    /**
     * @var int
     */
    public $accgroup_accountId = 0;
    /**
     * @var array
     */
    protected $groups = [];

    /**
     * @return int
     */
    public function getAccgroupGroupId()
    {
        return $this->accgroup_groupId;
    }

    /**
     * @param int $accgroup_groupId
     */
    public function setAccgroupGroupId($accgroup_groupId)
    {
        $this->accgroup_groupId = $accgroup_groupId;
    }

    /**
     * @return int
     */
    public function getAccgroupAccountId()
    {
        return $this->accgroup_accountId;
    }

    /**
     * @param int $accgroup_accountId
     */
    public function setAccgroupAccountId($accgroup_accountId)
    {
        $this->accgroup_accountId = $accgroup_accountId;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @param int $group
     */
    public function addGroup($group)
    {
        $this->groups[] = intval($group);
    }
}