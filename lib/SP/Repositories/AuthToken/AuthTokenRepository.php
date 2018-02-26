<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
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

namespace SP\Repositories\AuthToken;

use SP\Core\Acl\Acl;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AuthTokenData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class AuthTokenRepository
 *
 * @package SP\Repositories\ApiToken
 */
class AuthTokenRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Deletes an item
     *
     * @param $id
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AuthToken WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error interno'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id,
            userId,
            actionId,
            createdBy,
            startDate,
            token 
            FROM AuthToken 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(AuthTokenData::class);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return void
     */
    public function getByIdBatch(array $ids)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AuthToken WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error interno'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
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
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $query = /** @lang SQL */
            'SELECT AT.id,
            AT.userId,
            AT.actionId, 
            AT.token,
            CONCAT(U.name, \' (\', U.login, \')\') AS userLogin 
            FROM AuthToken AT 
            INNER JOIN User U ON userid = U.id';

        $queryData = new QueryData();

        if ($SearchData->getSeachString() !== '') {
            $search = '%' . $SearchData->getSeachString() . '%';
            $query .= ' WHERE U.login LIKE ?';

            $queryData->addParam($search);
        }

        $query .= ' ORDER BY U.login';
        $query .= ' LIMIT ?, ?';

        $queryData->addParam($SearchData->getLimitStart());
        $queryData->addParam($SearchData->getLimitCount());

        $queryData->setQuery($query);

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($queryData, $this->db);

        foreach ($queryRes as $token) {
            $token->actionId = Acl::getActionInfo($token->actionId);
        }

        $queryRes['count'] = $queryData->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Creates an item
     *
     * @param AuthTokenData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(__u('La autorización ya existe'), SPException::WARNING);
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
        $queryData->addParam($itemData->getUserId());
        $queryData->addParam($itemData->getActionId());
        $queryData->addParam($itemData->getCreatedBy());
        $queryData->addParam($itemData->getToken());
        $queryData->addParam($itemData->getVault());
        $queryData->addParam($itemData->getHash());
        $queryData->setOnErrorMessage(__u('Error interno'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param AuthTokenData $itemData
     * @return bool
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT id FROM AuthToken 
            WHERE userId = ? 
            AND actionId = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($itemData->getUserId());
        $queryData->addParam($itemData->getActionId());

        DbWrapper::getResults($queryData, $this->db);

        return $queryData->getQueryNumRows() === 1;
    }

    /**
     * Obtener el token de la API de un usuario
     *
     * @param $id
     * @return string
     */
    public function getTokenByUserId($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT token FROM AuthToken WHERE userId = ? AND token <> \'\' LIMIT 1');
        $queryData->addParam($id);

        $queryRes = DbWrapper::getResults($queryData, $this->db);

        return $queryData->getQueryNumRows() === 1 ? $queryRes->token : null;
    }

    /**
     * Updates an item
     *
     * @param AuthTokenData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(__u('La autorización ya existe'), SPException::WARNING);
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
        $queryData->addParam($itemData->getUserId());
        $queryData->addParam($itemData->getActionId());
        $queryData->addParam($itemData->getCreatedBy());
        $queryData->addParam($itemData->getToken());
        $queryData->addParam($itemData->getVault());
        $queryData->addParam($itemData->getHash());
        $queryData->addParam($itemData->getId());
        $queryData->setOnErrorMessage(__u('Error interno'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param AuthTokenData $itemData
     * @return bool
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $query = /** @lang SQL */
            'SELECT id FROM AuthToken 
            WHERE userId = ? 
            AND actionId = ? 
            AND id <> ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($itemData->getUserId());
        $queryData->addParam($itemData->getActionId());
        $queryData->addParam($itemData->getId());

        DbWrapper::getResults($queryData, $this->db);

        return $queryData->getQueryNumRows() === 1;
    }

    /**
     * Regenerar el hash de los tokens de un usuario
     *
     * @param int    $id
     * @param string $token
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refreshTokenByUserId($id, $token)
    {
        $query = /** @lang SQL */
            'UPDATE AuthToken 
            SET token = ?,
            startDate = UNIX_TIMESTAMP() 
            WHERE userId = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($token);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error interno'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Regenerar el hash de los tokens de un usuario
     *
     * @param int $id
     * @param     $vault
     * @param     $hash
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refreshVaultByUserId($id, $vault, $hash)
    {
        $query = /** @lang SQL */
            'UPDATE AuthToken 
            SET vault = ?,
            `hash` = ?,
            startDate = UNIX_TIMESTAMP() 
            WHERE userId = ? AND vault IS NOT NULL';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($vault);
        $queryData->addParam($hash);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error interno'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Obtener el usuario a partir del token
     *
     * @param $token string El token de autorización
     * @return bool|mixed
     */
    public function getUserIdForToken($token)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userId FROM AuthToken WHERE token = ? LIMIT 1');
        $queryData->addParam($token);

        $queryRes = DbWrapper::getResults($queryData, $this->db);

        return $queryData->getQueryNumRows() === 1 ? $queryRes->userId : false;
    }

    /**
     * Devolver los datos de un token
     *
     * @param $actionId int El id de la accion
     * @param $token    string El token de seguridad
     * @return false|AuthTokenData
     */
    public function getTokenByToken($actionId, $token)
    {
        $query = /** @lang SQL */
            'SELECT userId, vault, `hash` 
            FROM AuthToken
            WHERE actionId = ? 
            AND token = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(AuthTokenData::class);
        $queryData->setQuery($query);
        $queryData->addParam($actionId);
        $queryData->addParam($token);

        $queryRes = DbWrapper::getResults($queryData, $this->db);

        return $queryData->getQueryNumRows() === 1 ? $queryRes : false;
    }
}