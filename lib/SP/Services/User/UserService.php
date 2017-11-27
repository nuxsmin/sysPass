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

namespace SP\Services\User;


use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class UserService
 *
 * @package SP\Services\User
 */
class UserService extends Service implements ServiceItemInterface
{

    /**
     * Creates an item
     *
     * @return mixed
     */
    public function create()
    {
        // TODO: Implement create() method.
    }

    /**
     * Updates an item
     *
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        // TODO: Implement update() method.
    }

    /**
     * Deletes an item
     *
     * @param $id
     */
    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT user_id,
            user_name,
            user_groupId,
            usergroup_name,
            user_login,
            user_ssoLogin,
            user_email,
            user_notes,
            user_count,
            user_profileId,
            user_count,
            user_lastLogin,
            user_lastUpdate,
            user_lastUpdateMPass,
            user_preferences,
            user_pass,
            user_hashSalt,
            user_mPass,
            user_mKey,            
            BIN(user_isAdminApp) AS user_isAdminApp,
            BIN(user_isAdminAcc) AS user_isAdminAcc,
            BIN(user_isLdap) AS user_isLdap,
            BIN(user_isDisabled) AS user_isDisabled,
            BIN(user_isChangePass) AS user_isChangePass,
            BIN(user_isChangedPass) AS user_isChangedPass,
            BIN(user_isMigrate) AS user_isMigrate
            FROM usrData
            JOIN usrGroups ON usergroup_id = user_groupId
            WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(UserData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al obtener los datos del usuario', false));
        }

        return $queryRes;
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        // TODO: Implement getByIdBatch() method.
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return $this
     */
    public function deleteByIdBatch(array $ids)
    {
        // TODO: Implement deleteByIdBatch() method.
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return bool
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @return bool
     */
    public function checkDuplicatedOnAdd()
    {
        // TODO: Implement checkDuplicatedOnAdd() method.
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return array
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('user_id,
            user_name, 
            user_login,
            userprofile_name,
            usergroup_name,
            BIN(user_isAdminApp) AS user_isAdminApp,
            BIN(user_isAdminAcc) AS user_isAdminAcc,
            BIN(user_isLdap) AS user_isLdap,
            BIN(user_isDisabled) AS user_isDisabled,
            BIN(user_isChangePass) AS user_isChangePass');
        $Data->setFrom('usrData LEFT JOIN usrProfiles ON user_profileId = userprofile_id LEFT JOIN usrGroups ON usrData.user_groupId = usergroup_id');
        $Data->setOrder('user_name');

        if ($SearchData->getSeachString() !== '') {
            if ($this->session->getUserData()->isUserIsAdminApp()) {
                $Data->setWhere('user_name LIKE ? OR user_login LIKE ?');
            } else {
                $Data->setWhere('user_name LIKE ? OR user_login LIKE ? AND user_isAdminApp = 0');
            }

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        } elseif (!$this->session->getUserData()->isUserIsAdminApp()) {
            $Data->setWhere('user_isAdminApp = 0');
        }

        $Data->setLimit('?, ?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}