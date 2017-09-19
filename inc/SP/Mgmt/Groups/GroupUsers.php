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

namespace SP\Mgmt\Groups;

defined('APP_ROOT') || die();

use SP\Core\Exceptions\SPException;
use SP\DataModel\GroupUsersData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class GroupUser
 *
 * @package SP\Mgmt\Groups
 * @property GroupUsersData $itemData
 */
class GroupUsers extends GroupUsersBase implements ItemInterface, ItemSelectInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        $this->delete($this->itemData->getUsertogroupGroupId());
        $this->add();

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
        $Data->setOnErrorMessage(__('Error al eliminar los usuarios del grupo', false));

        DB::getQuery($Data);

        return $this;
    }

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

        $query = /** @lang SQL */
            'INSERT INTO usrToGroups (usertogroup_userId, usertogroup_groupId) VALUES ' . $this->getParamsFromArray($this->itemData->getUsers(), '(?,?)');

        $Data = new QueryData();
        $Data->setQuery($query);

        foreach ($this->itemData->getUsers() as $user) {
            $Data->addParam($user);
            $Data->addParam($this->itemData->getUsertogroupGroupId());
        }

        $Data->setOnErrorMessage(__('Error al asignar los usuarios al grupo', false));

        DB::getQuery($Data);

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
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        return DB::getResultsArray($Data);
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

        return ($Data->getQueryNumRows() > 1);
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

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return mixed
     */
    public function getByIdBatch(array $ids)
    {
        // TODO: Implement getByIdBatch() method.
    }

    /**
     * Comprobar si un usuario está en el grupo
     *
     * @param $userId
     * @param $groupId
     * @return bool
     */
    public function checkUserInGroup($groupId, $userId)
    {
        $query = /** @lang SQL */
            'SELECT usertogroup_groupId FROM usrToGroups WHERE usertogroup_groupId = ? AND usertogroup_userId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($groupId);
        $Data->addParam($userId);

        DB::getResults($Data);

        return ($Data->getQueryNumRows() === 1);
    }

    /**
     * Devolver los grupos a los que pertenece el usuario
     *
     * @param $userId
     * @return array
     */
    public function getGroupsForUser($userId)
    {
        $query = /** @lang SQL */
            'SELECT usertogroup_groupId AS groupId FROM usrToGroups WHERE usertogroup_userId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userId);

        return DB::getResultsArray($Data);
    }
}