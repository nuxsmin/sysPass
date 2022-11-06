<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Infrastructure\User\Repositories;

use RuntimeException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Domain\User\In\UserRepositoryInterface;
use SP\Domain\User\Services\UpdatePassRequest;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class UserRepository
 *
 * @package SP\Infrastructure\User\Repositories
 */
final class UserRepository extends Repository implements UserRepositoryInterface
{
    use RepositoryItemTrait;

    /**
     * Updates an item
     *
     * @param  UserData  $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update($itemData): int
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new DuplicatedItemException(__u('Duplicated user login/email'));
        }

        $query = /** @lang SQL */
            'UPDATE User SET
            `name` = ?,
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

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getLogin(),
            $itemData->getSsoLogin(),
            $itemData->getEmail(),
            $itemData->getNotes(),
            $itemData->getUserGroupId(),
            $itemData->getUserProfileId(),
            $itemData->isAdminApp(),
            $itemData->isAdminAcc(),
            $itemData->isDisabled(),
            $itemData->isChangePass(),
            $itemData->isLdap(),
            $itemData->getId(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the user'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param  UserData  $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnUpdate($itemData): bool
    {
        $query = /** @lang SQL */
            'SELECT id
            FROM User
            WHERE id <> ? AND (UPPER(login) = UPPER(?) 
            OR (UPPER(?) = ssoLogin AND ssoLogin IS NOT NULL AND ssoLogin <> \'\')
            OR (UPPER(?) = email AND email IS NOT NULL AND email <> \'\'))';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getId(),
            $itemData->getLogin(),
            $itemData->getSsoLogin(),
            $itemData->getEmail(),
        ]);

        return $this->db->doSelect($queryData)->getNumRows() > 0;
    }

    /**
     * Updates an user's pass
     *
     * @param  int  $id
     * @param  UpdatePassRequest  $passRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassById(
        int $id,
        UpdatePassRequest $passRequest
    ): int {
        $query = /** @lang SQL */
            'UPDATE User SET
            pass = ?,
            hashSalt = \'\',
            isChangePass = ?,
            isChangedPass = ?,
            isMigrate = 0,
            lastUpdate = NOW()
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $passRequest->getPass(),
            (int)$passRequest->getisChangePass(),
            (int)$passRequest->getisChangedPass(),
            $id,
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param  int  $id
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM User WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the user'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getById(int $id): QueryResult
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

        $queryData = new QueryData();
        $queryData->setMapClassName(UserData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while retrieving the user\'s data'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return UserData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAll(): array
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

        $queryData = new QueryData();
        $queryData->setMapClassName(UserData::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData)->getDataAsArray();
    }

    /**
     * Returns all the items for given ids
     *
     * @param  array  $ids
     *
     * @return \SP\Infrastructure\Database\QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByIdBatch(array $ids): QueryResult
    {
        if (count($ids) === 0) {
            return new QueryResult();
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
            WHERE U.id IN ('.$this->buildParamsFromArray($ids).')';

        $queryData = new QueryData();
        $queryData->setMapClassName(UserData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM User WHERE id IN ('.$this->buildParamsFromArray($ids).')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the users'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     *
     * @return void
     */
    public function checkInUse(int $id): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setSelect(
            'User.id,
            User.name, 
            User.login,
            UserProfile.name AS userProfileName,
            UserGroup.name AS userGroupName,
            User.isAdminApp,
            User.isAdminAcc,
            User.isLdap,
            User.isDisabled,
            User.isChangePass'
        );
        $queryData->setFrom(
            'User
        INNER JOIN UserProfile ON User.userProfileId = UserProfile.id 
        INNER JOIN UserGroup ON User.userGroupId = UserGroup.id'
        );
        $queryData->setOrder('User.name');

        $isAdminApp = $this->context->getUserData()->getIsAdminApp();

        if (!empty($itemSearchData->getSeachString())) {
            if ($isAdminApp) {
                $queryData->setWhere('User.name LIKE ? OR User.login LIKE ?');
            } else {
                $queryData->setWhere('User.name LIKE ? OR User.login LIKE ? AND User.isAdminApp = 0');
            }

            $search = '%'.$itemSearchData->getSeachString().'%';
            $queryData->addParam($search);
            $queryData->addParam($search);
        } elseif (!$isAdminApp) {
            $queryData->setWhere('User.isAdminApp = 0');
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Creates an item
     *
     * @param  UserData  $itemData
     *
     * @return int
     * @throws SPException
     */
    public function create($itemData): int
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new DuplicatedItemException(__u('Duplicated user login/email'));
        }

        $query = /** @lang SQL */
            'INSERT INTO User SET
            `name` = ?,
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

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getName(),
            $itemData->getLogin(),
            $itemData->getSsoLogin(),
            $itemData->getEmail(),
            $itemData->getNotes(),
            $itemData->getUserGroupId(),
            $itemData->getUserProfileId(),
            $itemData->getMPass(),
            $itemData->getMKey(),
            $itemData->getLastUpdateMPass(),
            $itemData->isAdminApp(),
            $itemData->isAdminAcc(),
            $itemData->isDisabled(),
            $itemData->isChangePass(),
            $itemData->isLdap(),
            $itemData->getPass(),

        ]);
        $queryData->setOnErrorMessage(__u('Error while creating the user'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param  UserData  $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnAdd($itemData): bool
    {
        $query = /** @lang SQL */
            'SELECT id
            FROM User
            WHERE UPPER(login) = UPPER(?) 
            OR (UPPER(?) = ssoLogin AND ssoLogin IS NOT NULL AND ssoLogin <> \'\')
            OR (UPPER(?) = email AND email IS NOT NULL AND email <> \'\')';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getLogin(),
            $itemData->getSsoLogin(),
            $itemData->getEmail(),
        ]);

        return $this->db->doSelect($queryData)->getNumRows() > 0;
    }

    /**
     * @param $login string
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByLogin(string $login): QueryResult
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

        $queryData = new QueryData();
        $queryData->setMapClassName(UserData::class);
        $queryData->setQuery($query);
        $queryData->setParams([$login, $login]);
        $queryData->setOnErrorMessage(__u('Error while retrieving the user\'s data'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns items' basic information
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getBasicInfo(): QueryResult
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

        $queryData = new QueryData();
        $queryData->setMapClassName(UserData::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Updates user's master password
     *
     * @param  int  $id
     * @param  string  $pass
     * @param  string  $key
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function updateMasterPassById(
        int $id,
        string $pass,
        string $key
    ): int {
        $query = /** @lang SQL */
            'UPDATE User SET 
              mPass = ?,
              mKey = ?,
              lastUpdateMPass = UNIX_TIMESTAMP(),
              isMigrate = 0,
              isChangedPass = 0 
              WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$pass, $key, $id]);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @param $id int El id del usuario
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function updateLastLoginById(int $id): int
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE User SET lastLogin = NOW(), loginCount = loginCount + 1 WHERE id = ? LIMIT 1');
        $queryData->addParam($id);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * @param  string  $login
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkExistsByLogin(string $login): bool
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM User WHERE UPPER(login) = UPPER(?) OR UPPER(ssoLogin) = UPPER(?) LIMIT 1');
        $queryData->setParams([$login, $login]);

        return $this->db->doSelect($queryData)->getNumRows() > 0;
    }

    /**
     * @param  UserData  $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateOnLogin(UserData $itemData): int
    {
        $query = 'UPDATE User SET 
            pass = ?,
            hashSalt = \'\',
            `name` = ?,
            email = ?,
            lastUpdate = NOW(),
            lastLogin = NOW(),
            isLdap = ? 
            WHERE UPPER(login) = UPPER(?) OR UPPER(ssoLogin) = UPPER(?) LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getPass(),
            $itemData->getName(),
            $itemData->getEmail(),
            $itemData->isLdap(),
            $itemData->getLogin(),
            $itemData->getLogin(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while updating the user'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Updates an user's pass
     *
     * @param  int  $id
     * @param  UserPreferencesData  $userPreferencesData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePreferencesById(
        int $id,
        UserPreferencesData $userPreferencesData
    ): int {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE User SET preferences = ? WHERE id = ? LIMIT 1');
        $queryData->setParams([serialize($userPreferencesData), $id]);
        $queryData->setOnErrorMessage(__u('Error while updating the preferences'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @param  int  $groupId
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getUserEmailForGroup(int $groupId): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT U.id, U.login, U.name, U.email 
            FROM User U
            INNER JOIN UserGroup UG ON U.userGroupId = UG.id
            LEFT JOIN UserToUserGroup UUG ON U.id = UUG.userId
            WHERE U.email IS NOT NULL 
            AND U.userGroupId = ? OR UUG.userGroupId = ?
            AND U.isDisabled = 0
            ORDER BY U.login';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$groupId, $groupId]);

        return $this->db->doSelect($queryData);
    }

    /**
     * Obtener el email de los usuarios
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     *
     * @TODO create unit test
     */
    public function getUserEmail(): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id, login, `name`, email 
            FROM User
            WHERE email IS NOT NULL 
            AND isDisabled = 0
            ORDER BY login';

        $queryData = new QueryData();
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Return the email of the given user's id
     *
     * @param  int[]  $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @TODO create unit test
     */
    public function getUserEmailById(array $ids): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id, login, `name`, email 
            FROM User
            WHERE email IS NOT NULL 
            AND isDisabled = 0
            AND id IN ('.$this->buildParamsFromArray($ids).')
            ORDER BY login';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the usage of the given user's id
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUsageForUser(int $id): QueryResult
    {
        $query = 'SELECT * FROM (SELECT
                  A.id,
                  CONCAT(A.name, " (", C.name, ")") AS name,
                  \'Account\'                         AS ref
                FROM Account A
                  INNER JOIN Client C on A.clientId = C.id
                WHERE A.userId = ? OR A.userEditId = ?
                UNION ALL
                SELECT
                  AU.accountId                        AS id,
                  CONCAT(A.name, " (", C.name, ")") AS name,
                  \'Account\'                           AS ref
                FROM AccountToUser AU
                  INNER JOIN Account A on AU.accountId = A.id
                  INNER JOIN Client C on A.clientId = C.id
                WHERE AU.userId = ?
                UNION ALL
                SELECT
                  UUG.userGroupId AS id,
                  G.name,
                  \'UserGroup\'     AS ref
                FROM
                  UserToUserGroup UUG
                  INNER JOIN UserGroup G on UUG.userGroupId = G.id
                WHERE UUG.userId = ?
                UNION ALL
                SELECT
                  PL.id,
                  CONCAT(A.name, " (", C.name, ")") AS name,
                  \'PublicLink\' AS ref
                FROM
                  PublicLink PL
                  INNER JOIN Account A ON A.id = PL.itemId
                  INNER JOIN Client C on A.clientId = C.id
                WHERE PL.userId = ?) Items
                ORDER BY Items.ref';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams(array_fill(0, 5, (int)$id));

        return $this->db->doSelect($queryData);
    }
}