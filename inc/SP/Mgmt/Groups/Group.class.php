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

namespace SP\Mgmt\Groups;

use SP\Core\Exceptions\SPException;
use SP\DataModel\GroupData;
use SP\DataModel\GroupUsersData;
use SP\Log\Log;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de realizar las operaciones sobre los grupos de usuarios.
 */
class Group extends GroupBase implements ItemInterface, ItemSelectInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_INFO, __('Nombre de grupo duplicado', false));
        }

        $query = /** @lang SQL */
            'INSERT INTO usrGroups SET usergroup_name = ?, usergroup_description = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUsergroupName());
        $Data->addParam($this->itemData->getUsergroupDescription());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al crear el grupo', false));
        }

        $this->itemData->setUsergroupId(DB::$lastId);

        $GroupUsers = new GroupUsersData();
        $GroupUsers->setUsertogroupGroupId($this->itemData->getUsergroupId());
        $GroupUsers->setUsers($this->itemData->getUsers());

        try {
            GroupUsers::getItem($GroupUsers)->add();
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, __('Error al añadir los usuarios del grupo', false), Log::ERROR);
        }

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT usergroup_name FROM usrGroups WHERE UPPER(usergroup_name) = ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUsergroupName());

        return (DB::getQuery($Data) === false || $Data->getQueryNumRows() >= 1);
    }

    /**
     * @param $id int|array
     * @return $this
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if (is_array($id)) {
            foreach ($id as $itemId){
                $this->delete($itemId);
            }

            return $this;
        }

        if ($this->checkInUse($id)) {
            throw new SPException(SPException::SP_WARNING, __('Grupo en uso', false));
        }

        $query = /** @lang SQL */
            'DELETE FROM usrGroups WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, __('Error al eliminar el grupo', false));
        }

        try {
            GroupUsers::getItem()->delete($id);
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, __('Error al eliminar los usuarios del grupo', false), Log::ERROR);
        }

        return $this;
    }

    /**
     * @param $id int
     * @return bool
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT user_groupId as groupId
            FROM usrData WHERE user_groupId = ?
            UNION ALL
            SELECT usertogroup_groupId as groupId
            FROM usrToGroups WHERE usertogroup_groupId = ?
            UNION ALL
            SELECT accgroup_groupId as groupId
            FROM accGroups WHERE accgroup_groupId = ?
            UNION ALL
            SELECT account_userGroupId as groupId
            FROM accounts WHERE account_userGroupId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->addParam($id);
        $Data->addParam($id);
        $Data->addParam($id);

        DB::getQuery($Data);

        return ($Data->getQueryNumRows() > 1);
    }

    /**
     * @param $id int
     * @return GroupData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT usergroup_id, usergroup_name, usergroup_description FROM usrGroups WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        return DB::getResults($Data);
    }

    /**
     * @return $this
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()) {
            throw new SPException(SPException::SP_INFO, __('Nombre de grupo duplicado', false));
        }

        $query = /** @lang SQL */
            'UPDATE usrGroups SET usergroup_name = ?, usergroup_description = ? WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUsergroupName());
        $Data->addParam($this->itemData->getUsergroupDescription());
        $Data->addParam($this->itemData->getUsergroupId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al actualizar el grupo', false));
        }

        $GroupUsers = new GroupUsersData();
        $GroupUsers->setUsertogroupGroupId($this->itemData->getUsergroupId());
        $GroupUsers->setUsers($this->itemData->getUsers());

        try {
            GroupUsers::getItem($GroupUsers)->update();
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, __('Error al actualizar los usuarios del grupo', false), Log::ERROR);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT usergroup_name FROM usrGroups WHERE UPPER(usergroup_name) = ? AND usergroup_id <> ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUsergroupName());
        $Data->addParam($this->itemData->getUsergroupId());

        return (DB::getQuery($Data) === false || $Data->getQueryNumRows() >= 1);
    }

    /**
     * @return GroupData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT usergroup_id,
            usergroup_name,
            usergroup_description
            FROM usrGroups
            ORDER BY usergroup_name';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);

        return DB::getResultsArray($Data);
    }
}
