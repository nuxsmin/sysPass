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
use SP\DataModel\GroupAccountsData;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class GroupAccounts
 *
 * @package SP\Mgmt\Groups
 */
class GroupAccounts extends GroupAccountsBase implements ItemInterface
{
    /**
     * @return $this
     */
    public function update()
    {
        $this->delete($this->itemData->getAccgroupAccountId());
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
            'DELETE FROM accGroups WHERE accgroup_accountId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al eliminar grupos asociados a la cuenta'));
        }

        return $this;
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function add()
    {
        if (!is_array($this->itemData->getGroups())
            || count($this->itemData->getGroups()) === 0
        ) {
            return $this;
        }

        $params = array_fill(0, count($this->itemData->getGroups()), '(?,?)');

        $query = /** @lang SQL */
            'INSERT INTO accGroups (accgroup_accountId, accgroup_groupId) VALUES ' . implode(',', $params);

        $Data = new QueryData();
        $Data->setQuery($query);

        foreach ($this->itemData->getGroups() as $group) {
            $Data->addParam($this->itemData->getAccgroupAccountId());
            $Data->addParam($group);
        }

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al actualizar los grupos secundarios'));
        }

        return $this;
    }

    /**
     * @param $id int
     * @return $this
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT accgroup_groupId, accgroup_accountId FROM accGroups WHERE accgroup_groupId = ?';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\GroupAccountsData');
        $Data->setQuery($query);
        $Data->addParam($id);

        DB::setReturnArray();

        $this->itemData = DB::getResults($Data);

        return $this;
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
     * @return mixed
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT accgroup_groupId FROM accGroups WHERE accgroup_groupId = ?';

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

    /**
     * @param $id int
     * @return GroupAccountsData[]
     */
    public function getByAccountId($id)
    {
        $query = /** @lang SQL */
            'SELECT accgroup_groupId, accgroup_accountId FROM accGroups WHERE accgroup_accountId = ?';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\GroupAccountsData');
        $Data->setQuery($query);
        $Data->addParam($id);

        DB::setReturnArray();

        return DB::getResults($Data);
    }
}