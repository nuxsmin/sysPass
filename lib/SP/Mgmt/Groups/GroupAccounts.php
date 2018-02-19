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

use SP\DataModel\AccountToUserGroupData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class GroupAccounts
 *
 * @package SP\Mgmt\Groups
 * @property AccountToUserGroupData $itemData
 */
class GroupAccounts extends GroupAccountsBase implements ItemInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        $this->delete($this->itemData->getAccountId());
        $this->add();

        return $this;
    }

    /**
     * @param $id int
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM AccountToUserGroup WHERE accountId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar grupos asociados a la cuenta', false));

        DbWrapper::getQuery($Data);

        return $this;
    }

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function add()
    {
        if (!is_array($this->itemData->getGroups())
            || count($this->itemData->getGroups()) === 0
        ) {
            return $this;
        }

        $query = /** @lang SQL */
            'INSERT INTO AccountToUserGroup (accountId, userGroupId) VALUES ' . $this->getParamsFromArray($this->itemData->getGroups(), '(?,?)');

        $Data = new QueryData();
        $Data->setQuery($query);

        foreach ($this->itemData->getGroups() as $group) {
            $Data->addParam($this->itemData->getAccountId());
            $Data->addParam($group);
        }

        $Data->setOnErrorMessage(__('Error al actualizar los grupos secundarios', false));

        DbWrapper::getQuery($Data);

        return $this;
    }

    /**
     * @param $id int
     * @return array
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT userGroupId, accountId FROM AccountToUserGroup WHERE userGroupId = ?';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResultsArray($Data);
    }

    /**
     * @return mixed
     */
    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    /**
     * @param $id int
     * @return bool
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT userGroupId FROM AccountToUserGroup WHERE userGroupId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DbWrapper::getResults($Data);

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
     * @param $id int
     * @return AccountToUserGroupData[]
     */
    public function getByAccountId($id)
    {
        $query = /** @lang SQL */
            'SELECT userGroupId, accountId FROM AccountToUserGroup WHERE accountId = ?';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResultsArray($Data);
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
}