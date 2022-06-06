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

namespace SP\Infrastructure\Auth\Repositories;

use RuntimeException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\AuthTokenData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\In\RepositoryInterface;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AuthTokenRepository
 *
 * @package SP\Infrastructure\Common\Repositories\ApiToken
 */
final class AuthTokenRepository extends Repository implements RepositoryInterface
{
    use RepositoryItemTrait;

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
        $queryData->setQuery('DELETE FROM AuthToken WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            userId,
            actionId,
            createdBy,
            startDate,
            vault,
            token,
            `hash` 
            FROM AuthToken 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(AuthTokenData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            userId,
            actionId,
            createdBy,
            startDate,
            vault,
            token,
            `hash` 
            FROM AuthToken
            ORDER BY actionId, userId';

        $queryData = new QueryData();
        $queryData->setMapClassName(AuthTokenData::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param  array  $ids
     *
     * @return void
     */
    public function getByIdBatch(array $ids): QueryResult
    {
        throw new RuntimeException('Not implemented');
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
        $queryData->setQuery('DELETE FROM AuthToken WHERE id IN ('.$this->getParamsFromArray($ids).')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Internal error'));

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
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setSelect(
            'AuthToken.id,
            AuthToken.userId,
            AuthToken.actionId, 
            AuthToken.token,
            CONCAT(User.name, \' (\', User.login, \')\') AS userLogin'
        );
        $queryData->setFrom(
            'AuthToken 
            INNER JOIN User ON AuthToken.userid = User.id'
        );

        if (!empty($itemSearchData->getSeachString())) {
            $queryData->setWhere('User.login LIKE ? OR User.name LIKE ?');

            $search = '%'.$itemSearchData->getSeachString().'%';
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setOrder('User.login, AuthToken.actionId');
        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Creates an item
     *
     * @param  AuthTokenData  $itemData
     *
     * @return int
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData): int
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new DuplicatedItemException(__u('The authorization already exist'));
        }

        $query = /** @lang SQL */
            'INSERT INTO AuthToken 
            SET userId = ?,
            actionId = ?,
            createdBy = ?,
            token = ?,
            vault = ?,
            `hash` = ?,
            startDate = UNIX_TIMESTAMP()';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getUserId(),
            $itemData->getActionId(),
            $itemData->getCreatedBy(),
            $itemData->getToken(),
            $itemData->getVault(),
            $itemData->getHash(),
        ]);
        $queryData->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param  AuthTokenData  $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnAdd($itemData): bool
    {
        $query = /** @lang SQL */
            'SELECT id FROM AuthToken 
            WHERE (userId = ? OR token = ?)
            AND actionId = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getUserId(),
            $itemData->getToken(),
            $itemData->getActionId(),
        ]);

        return $this->db->doSelect($queryData)->getNumRows() === 1;
    }

    /**
     * Obtener el token de la API de un usuario
     *
     * @param  int  $id
     *
     * @return string
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getTokenByUserId(int $id): ?string
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT token FROM AuthToken WHERE userId = ? AND token <> \'\' LIMIT 1');
        $queryData->addParam($id);

        $result = $this->db->doSelect($queryData);

        return $result->getNumRows() === 1 ? $result->getData()->token : null;
    }

    /**
     * Updates an item
     *
     * @param  AuthTokenData  $itemData
     *
     * @return int
     * @throws DuplicatedItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update($itemData): int
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new DuplicatedItemException(__u('The authorization already exist'));
        }

        $query = /** @lang SQL */
            'UPDATE AuthToken 
            SET userId = ?,
            actionId = ?,
            createdBy = ?,
            token = ?,
            vault = ?,
            `hash` = ?,
            startDate = UNIX_TIMESTAMP() 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getUserId(),
            $itemData->getActionId(),
            $itemData->getCreatedBy(),
            $itemData->getToken(),
            $itemData->getVault(),
            $itemData->getHash(),
            $itemData->getId(),
        ]);
        $queryData->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param  AuthTokenData  $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnUpdate($itemData): bool
    {
        $query = /** @lang SQL */
            'SELECT id FROM AuthToken 
            WHERE (userId = ? OR token = ?)
            AND actionId = ?  
            AND id <> ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getUserId(),
            $itemData->getToken(),
            $itemData->getActionId(),
            $itemData->getId(),
        ]);

        return $this->db->doSelect($queryData)->getNumRows() === 1;
    }

    /**
     * Regenerar el hash de los tokens de un usuario
     *
     * @param  int  $id
     * @param  string  $token
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refreshTokenByUserId(int $id, string $token): int
    {
        $query = /** @lang SQL */
            'UPDATE AuthToken 
            SET token = ?,
            startDate = UNIX_TIMESTAMP() 
            WHERE userId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$token, $id]);
        $queryData->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Regenerar el hash de los tokens de un usuario
     *
     * @param  int  $id
     * @param  string  $vault
     * @param  string  $hash
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refreshVaultByUserId(int $id, string $vault, string $hash): int
    {
        $query = /** @lang SQL */
            'UPDATE AuthToken 
            SET vault = ?,
            `hash` = ?,
            startDate = UNIX_TIMESTAMP() 
            WHERE userId = ? AND vault IS NOT NULL';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([$vault, $hash, $id]);
        $queryData->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Obtener el usuario a partir del token
     *
     * @param $token string El token de autorización
     *
     * @return false|int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserIdForToken(string $token)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userId FROM AuthToken WHERE token = ? LIMIT 1');
        $queryData->addParam($token);

        $result = $this->db->doSelect($queryData);

        return $result->getNumRows() === 1 ? (int)$result->getData()->userId : false;
    }

    /**
     * Devolver los datos de un token
     *
     * @param $actionId int El id de la accion
     * @param $token    string El token de seguridad
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getTokenByToken(int $actionId, string $token): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id, actionId, userId, vault, `hash`, token
            FROM AuthToken
            WHERE actionId = ? 
            AND token = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(AuthTokenData::class);
        $queryData->setQuery($query);
        $queryData->setParams([$actionId, $token]);

        return $this->db->doSelect($queryData);
    }
}