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

use SP\Core\Exceptions\SPException;
use SP\DataModel\UserGroupData;
use SP\DataModel\UserToUserGroupData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de realizar las operaciones sobre los grupos de usuarios.
 *
 * @property UserGroupData $itemData
 */
class Group extends GroupBase implements ItemInterface, ItemSelectInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(__('Nombre de grupo duplicado', false), SPException::INFO);
        }

        $query = /** @lang SQL */
            'INSERT INTO usrGroups SET usergroup_name = ?, usergroup_description = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getName());
        $Data->addParam($this->itemData->getDescription());
        $Data->setOnErrorMessage(__('Error al crear el grupo', false));

        DbWrapper::getQuery($Data);

        $this->itemData->setId(DbWrapper::$lastId);

        $GroupUsers = new UserToUserGroupData();
        $GroupUsers->setUserGroupId($this->itemData->getId());
        $GroupUsers->setUsers($this->itemData->getUsers());

        GroupUsers::getItem($GroupUsers)->add();

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
        $Data->addParam($this->itemData->getName());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @param $id int
     * @return $this
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if ($this->checkInUse($id)) {
            throw new SPException(__('Grupo en uso', false), SPException::WARNING);
        }

        $query = /** @lang SQL */
            'DELETE FROM usrGroups WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar el grupo', false));

        DbWrapper::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(__('Grupo no encontrado', false), SPException::INFO);
        }

        GroupUsers::getItem()->delete($id);

        return $this;
    }

    /**
     * @param $id int
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT user_groupId as groupId
            FROM usrData WHERE user_groupId = ?
            UNION ALL
            SELECT userGroupId as groupId
            FROM UserToGroup WHERE userGroupId = ?
            UNION ALL
            SELECT userGroupId as groupId
            FROM AccountToGroup WHERE userGroupId = ?
            UNION ALL
            SELECT account_userGroupId as groupId
            FROM Account WHERE account_userGroupId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->addParam($id);
        $Data->addParam($id);
        $Data->addParam($id);

        DbWrapper::getQuery($Data);

        return ($Data->getQueryNumRows() > 1);
    }

    /**
     * @param $id int
     * @return UserGroupData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT usergroup_id, usergroup_name, usergroup_description FROM usrGroups WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data);
    }

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()) {
            throw new SPException(__('Nombre de grupo duplicado', false), SPException::INFO);
        }

        $query = /** @lang SQL */
            'UPDATE usrGroups SET usergroup_name = ?, usergroup_description = ? WHERE usergroup_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getName());
        $Data->addParam($this->itemData->getDescription());
        $Data->addParam($this->itemData->getId());
        $Data->setOnErrorMessage(__('Error al actualizar el grupo', false));

        DbWrapper::getQuery($Data);

        $GroupUsers = new UserToUserGroupData();
        $GroupUsers->setUserGroupId($this->itemData->getId());
        $GroupUsers->setUsers($this->itemData->getUsers());

        GroupUsers::getItem($GroupUsers)->update();

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT usergroup_name FROM usrGroups WHERE UPPER(usergroup_name) = ? AND usergroup_id <> ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getName());
        $Data->addParam($this->itemData->getId());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @return UserGroupData[]
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

        return DbWrapper::getResultsArray($Data);
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return UserGroupData[]
     */
    public function getByIdBatch(array $ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT usergroup_id, usergroup_name, usergroup_description FROM usrGroups WHERE usergroup_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data);
    }
}
