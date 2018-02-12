<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories\User;

use SP\Core\Acl\Acl;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\Log\Log;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Services\User\UpdatePassRequest;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class UserRepository
 *
 * @package SP\Repositories\User
 */
class UserRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Updates an item
     *
     * @param UserData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(__u('Login/email de usuario duplicados'), SPException::INFO);
        }

        $query = /** @lang SQL */
            'UPDATE User SET
            name = ?,
            login = ?,
            ssoLogin = ?,
            email = ?,
            notes = ?,
            userGroupId = ?,
            userProfileId = ?,
            isAdminApp = ?,
            isAdminAcc = ?,
            isDisabled = ?,
            isChangePass = ?,
            isLdap = ?,
            lastUpdate = NOW()
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getLogin());
        $Data->addParam($itemData->getSsoLogin());
        $Data->addParam($itemData->getEmail());
        $Data->addParam($itemData->getNotes());
        $Data->addParam($itemData->getUserGroupId());
        $Data->addParam($itemData->getUserProfileId());
        $Data->addParam($itemData->isIsAdminApp());
        $Data->addParam($itemData->isIsAdminAcc());
        $Data->addParam($itemData->isIsDisabled());
        $Data->addParam($itemData->isIsChangePass());
        $Data->addParam($itemData->isIsLdap());
        $Data->addParam($itemData->getId());
        $Data->setOnErrorMessage(__u('Error al actualizar el usuario'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() > 0) {
            $itemData->setId(DbWrapper::getLastId());
        }

        return $this;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param UserData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $query = /** @lang SQL */
            'SELECT login, email
            FROM User
            WHERE id <> ? AND (UPPER(login) = UPPER(?) 
            OR (ssoLogin <> "" AND UPPER(ssoLogin) = UPPER(?)) 
            OR UPPER(email) = UPPER(?))';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getLogin());
        $Data->addParam($itemData->getSsoLogin());
        $Data->addParam($itemData->getEmail());

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Updates an user's pass
     *
     * @param int               $id
     * @param UpdatePassRequest $passRequest
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function updatePassById($id, UpdatePassRequest $passRequest)
    {
        $query = /** @lang SQL */
            'UPDATE User SET
            pass = ?,
            hashSalt = \'\',
            isChangePass = ?,
            isChangedPass = ?,
            isMigrate = 0,
            lastUpdate = NOW()
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($passRequest->getPass());
        $Data->addParam($passRequest->getisChangePass());
        $Data->addParam($passRequest->getisChangedPass());
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al modificar la clave'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return UserRepository
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $query = 'DELETE FROM User WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar el usuario'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(__u('Usuario no encontrado'), SPException::INFO);
        }

        return $this;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT U.id,
            U.name,
            U.userGroupId,
            UG.name AS userGroupName,
            U.login,
            U.ssoLogin,
            U.email,
            U.notes,
            U.loginCount,
            U.userProfileId,
            U.lastLogin,
            U.lastUpdate,
            U.lastUpdateMPass,
            U.preferences,
            U.pass,
            U.hashSalt,
            U.mPass,
            U.mKey,            
            U.isAdminApp,
            U.isAdminAcc,
            U.isLdap,
            U.isDisabled,
            U.isChangePass,
            U.isChangedPass,
            U.isMigrate
            FROM User U
            INNER JOIN UserGroup UG ON U.userGroupId = UG.id
            WHERE U.id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(UserData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(__u('Error al obtener los datos del usuario'), SPException::ERROR);
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
        $query = /** @lang SQL */
            'SELECT U.id,
            U.name,
            U.userGroupId,
            U.login,
            U.ssoLogin,
            U.email,
            U.notes,
            U.loginCount,
            U.userProfileId,
            U.lastLogin,
            U.lastUpdate,
            U.lastUpdateMPass,
            U.preferences,
            U.pass,
            U.hashSalt,
            U.mPass,
            U.mKey,            
            U.isAdminApp,
            U.isAdminAcc,
            U.isLdap,
            U.isDisabled,
            U.isChangePass,
            U.isChangedPass,
            U.isMigrate
            FROM User U';

        $Data = new QueryData();
        $Data->setMapClassName(UserData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT U.id,
            U.name,
            U.userGroupId,
            UG.name AS userGroupName,
            U.login,
            U.ssoLogin,
            U.email,
            U.notes,
            U.loginCount,
            U.userProfileId,
            U.lastLogin,
            U.lastUpdate,
            U.lastUpdateMPass,
            U.preferences,
            U.pass,
            U.hashSalt,
            U.mPass,
            U.mKey,            
            U.isAdminApp,
            U.isAdminAcc,
            U.isLdap,
            U.isDisabled,
            U.isChangePass,
            U.isChangedPass,
            U.isMigrate
            FROM User U
            INNER JOIN UserGroup UG ON U.userGroupId = UG.id
            WHERE U.id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(UserData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     */
    public function deleteByIdBatch(array $ids)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return void
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
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
        $Data->setSelect('U.id,
            U.name, 
            U.login,
            UP.name AS userProfileName,
            UG.name AS userGroupName,
            U.isAdminApp,
            U.isAdminAcc,
            U.isLdap,
            U.isDisabled,
            U.isChangePass');
        $Data->setFrom('User U INNER JOIN UserProfile UP ON U.userProfileId = UP.id INNER JOIN UserGroup UG ON U.userGroupId = UG.id');
        $Data->setOrder('U.name');

        if ($SearchData->getSeachString() !== '') {
            if ($this->session->getUserData()->getIsAdminApp()) {
                $Data->setWhere('U.name LIKE ? OR U.login LIKE ?');
            } else {
                $Data->setWhere('U.name LIKE ? OR U.login LIKE ? AND U.isAdminApp = 0');
            }

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        } elseif (!$this->session->getUserData()->getIsAdminApp()) {
            $Data->setWhere('U.isAdminApp = 0');
        }

        $Data->setLimit('?, ?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Logs user action
     *
     * @param int $id
     * @param int $actionId
     * @return \SP\Core\Messages\LogMessage
     */
    public function logAction($id, $actionId)
    {
        $query = /** @lang SQL */
            'SELECT id, login, name FROM User WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $user = DbWrapper::getResults($Data, $this->db);

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(Acl::getActionInfo($actionId));
        $LogMessage->addDetails(__u('Usuario'), sprintf('%s (%s)', $user->name, $user->login));
        $LogMessage->addDetails(__u('ID'), $id);
        $Log->writeLog();

        return $LogMessage;
    }

    /**
     * Creates an item
     *
     * @param UserData $itemData
     * @return int
     * @throws SPException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(__u('Login/email de usuario duplicados'), SPException::INFO);
        }

        $query = /** @lang SQL */
            'INSERT INTO User SET
            name = ?,
            login = ?,
            ssoLogin = ?,
            email = ?,
            notes = ?,
            userGroupId = ?,
            userProfileId = ?,
            mPass = ?,
            mKey = ?,
            lastUpdateMPass = ?,
            isAdminApp = ?,
            isAdminAcc = ?,
            isDisabled = ?,
            isChangePass = ?,
            isLdap = ?,
            pass = ?,
            hashSalt = \'\'';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getLogin());
        $Data->addParam($itemData->getSsoLogin());
        $Data->addParam($itemData->getEmail());
        $Data->addParam($itemData->getNotes());
        $Data->addParam($itemData->getUserGroupId());
        $Data->addParam($itemData->getUserProfileId());
        $Data->addParam($itemData->getMPass());
        $Data->addParam($itemData->getMKey());
        $Data->addParam($itemData->getLastUpdateMPass());
        $Data->addParam($itemData->isIsAdminApp());
        $Data->addParam($itemData->isIsAdminAcc());
        $Data->addParam($itemData->isIsDisabled());
        $Data->addParam($itemData->isIsChangePass());
        $Data->addParam($itemData->isIsLdap());
        $Data->addParam($itemData->getPass());
        $Data->setOnErrorMessage(__u('Error al crear el usuario'));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param UserData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT login, email
            FROM User
            WHERE UPPER(login) = UPPER(?) 
            OR UPPER(ssoLogin) = UPPER(?) 
            OR UPPER(email) = UPPER(?)';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getLogin());
        $Data->addParam($itemData->getSsoLogin());
        $Data->addParam($itemData->getEmail());

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @param $login string
     * @return UserData
     * @throws SPException
     */
    public function getByLogin($login)
    {
        $query = /** @lang SQL */
            'SELECT U.id,
            U.name,
            U.userGroupId,
            UG.name AS userGroupName,
            U.login,
            U.ssoLogin,
            U.email,
            U.notes,
            U.loginCount,
            U.userProfileId,
            U.lastLogin,
            U.lastUpdate,
            U.lastUpdateMPass,
            U.preferences,
            U.pass,
            U.hashSalt,
            U.mPass,
            U.mKey,            
            U.isAdminApp,
            U.isAdminAcc,
            U.isLdap,
            U.isDisabled,
            U.isChangePass,
            U.isChangedPass,
            U.isMigrate
            FROM User U
            INNER JOIN UserGroup UG ON U.userGroupId = UG.id
            WHERE U.login = ? OR U.ssoLogin = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(UserData::class);
        $Data->setQuery($query);
        $Data->addParam($login);
        $Data->addParam($login);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(__u('Error al obtener los datos del usuario'), SPException::ERROR);
        }

        if ($Data->getQueryNumRows() === 0) {
            throw new NoSuchItemException(__u('El usuario no existe'));
        }

        return $queryRes;
    }

    /**
     * Returns items' basic information
     *
     * @return mixed
     */
    public function getBasicInfo()
    {
        $query = /** @lang SQL */
            'SELECT U.id,
            U.name,
            U.login,
            U.email,
            U.userGroupId,
            U.userProfileId,
            U.isAdminApp,
            U.isAdminAcc,
            U.isLdap,
            U.isDisabled
            FROM User U';

        $Data = new QueryData();
        $Data->setMapClassName(UserData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Updates user's master password
     *
     * @param $id
     * @param $pass
     * @param $key
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function updateMasterPassById($id, $pass, $key)
    {
        $query = /** @lang SQL */
            'UPDATE User SET 
              mPass = ?,
              mKey = ?,
              lastUpdateMPass = UNIX_TIMESTAMP(),
              isMigrate = 0,
              isChangedPass = 0 
              WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($pass);
        $Data->addParam($key);
        $Data->addParam($id);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @param $id int El id del usuario
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function updateLastLoginById($id)
    {
        $Data = new QueryData();
        $Data->setQuery('UPDATE User SET lastLogin = NOW(), loginCount = loginCount + 1 WHERE id = ? LIMIT 1');
        $Data->addParam($id);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * @param $login
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkExistsByLogin($login)
    {
        $Data = new QueryData();
        $Data->setQuery('SELECT id FROM User WHERE UPPER(login) = UPPER(?) OR UPPER(ssoLogin) = UPPER(?) LIMIT 1');
        $Data->addParam($login);
        $Data->addParam($login);

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @param UserData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function updateOnLogin(UserData $itemData)
    {
        $query = 'UPDATE User SET 
            pass = ?,
            hashSalt = \'\',
            name = ?,
            email = ?,
            lastUpdate = NOW(),
            lastLogin = NOW(),
            isLdap = ? 
            WHERE UPPER(login) = UPPER(?) OR UPPER(ssoLogin) = UPPER(?) LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getPass());
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getEmail());
        $Data->addParam($itemData->isIsLdap());
        $Data->addParam($itemData->getLogin());
        $Data->addParam($itemData->getLogin());
        $Data->setOnErrorMessage(__u('Error al actualizar el usuario'));

        return DbWrapper::getQuery($Data, $this->db);
    }
}