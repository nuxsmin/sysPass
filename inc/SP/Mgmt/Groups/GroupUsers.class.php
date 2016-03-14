<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\Mgmt\Groups;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\SPException;
use SP\DataModel\GroupUsersData;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class GroupUser
 *
 * @package SP\Mgmt\Groups
 */
class GroupUsers extends GroupUsersBase implements ItemInterface
{
    /**
     * @return $this
     * @throws SPException
     */
    public function add()
    {
        if (!is_array($this->itemData->getUsers())
            || count($this->itemData->getUsers()) === 0
        ) {
            return $this;
        }

        $params = array_fill(0, count($this->itemData->getUsers()), '(?,?)');

        $query = /** @lang SQL */
            'INSERT INTO usrToGroups (usertogroup_userId, usertogroup_groupId) VALUES ' . implode(',', $params);

        $Data = new QueryData();
        $Data->setQuery($query);

        foreach ($this->itemData->getUsers() as $user){
            $Data->addParam($user);
            $Data->addParam($this->itemData->getUsertogroupGroupId());
        }

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al asignar los usuarios al grupo'));
        }

        return $this;
    }

    /**
     * @param $id int
     * @return $this
     * @throws SPException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM usrToGroups WHERE usertogroup_groupId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al eliminar los usuarios del grupo'));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function update()
    {
        $this->delete($this->itemData->getUsertogroupGroupId());
        $this->add();

        return $this;
    }

    /**
     * @param $id int
     * @return GroupUsersData[]
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT usertogroup_groupId, usertogroup_userId FROM usrToGroups WHERE usertogroup_groupId = ?';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\GroupUsersData');
        $Data->setQuery($query);
        $Data->addParam($id);

        DB::setReturnArray();

        return DB::getResults($Data);
    }

    /**
     * Devolver los usuarios que están asociados al grupo
     *
     * @return mixed
     */
    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT usertogroup_groupId FROM usrToGroups WHERE usertogroup_groupId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DB::getResults($Data);

        return (DB::$lastNumRows > 1);
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnAdd()
    {
        // TODO: Implement checkDuplicatedOnAdd() method.
    }
}