<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Repositories\ApiToken;

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ApiTokenData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class ApiTokenRepository
 *
 * @package SP\Repositories\ApiToken
 */
class ApiTokenRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Deletes an item
     *
     * @param $id
     * @return $this
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM AuthToken WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error interno'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Token no encontrado'));
        }

        return $this;
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

        $Data = new QueryData();
        $Data->setMapClassName(ApiTokenData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data, $this->db);
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
        $query = /** @lang SQL */
            'DELETE FROM AuthToken WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setParams($ids);
        $Data->setOnErrorMessage(__u('Error interno'));

        return DbWrapper::getQuery($Data, $this->db);
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

        $Data = new QueryData();

        if ($SearchData->getSeachString() !== '') {
            $search = '%' . $SearchData->getSeachString() . '%';
            $query .= ' WHERE U.login LIKE ?';

            $Data->addParam($search);
        }

        $query .= ' ORDER BY U.login';
        $query .= ' LIMIT ?, ?';

        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        $Data->setQuery($query);

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        foreach ($queryRes as $token) {
            $token->actionId = Acl::getActionInfo($token->actionId);
        }

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Creates an item
     *
     * @param ApiTokenData $itemData
     * @return mixed
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_WARNING, __u('La autorización ya existe'));
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

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUserId());
        $Data->addParam($itemData->getActionId());
        $Data->addParam($this->session->getUserData()->getId());

        $token = $this->getTokenByUserId($itemData->getUserId());
        $Data->addParam($token);

        $action = $itemData->getActionId();

        if ($action === ActionsInterface::ACCOUNT_VIEW_PASS
            || $action === ActionsInterface::ACCOUNT_CREATE
        ) {
            $Data->addParam(serialize($this->getSecureData($token, $itemData)));
        } else {
            $Data->addParam(null);
        }

        $Data->addParam(Hash::hashKey($itemData->getHash()));
        $Data->setOnErrorMessage(__u('Error interno'));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return bool
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT id FROM AuthToken 
            WHERE userId = ? 
            AND actionId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getAuthtokenUserId());
        $Data->addParam($itemData->getAuthtokenActionId());

        DbWrapper::getResults($Data, $this->db);

        return $Data->getQueryNumRows() === 1;
    }

    /**
     * Obtener el token de la API de un usuario
     *
     * @param $id
     * @return string
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function getTokenByUserId($id)
    {
        $query = /** @lang SQL */
            'SELECT token FROM AuthToken WHERE userId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        return $Data->getQueryNumRows() === 1 ? $queryRes->token : $this->generateToken();
    }

    /**
     * Generar un token de acceso
     *
     * @return string
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function generateToken()
    {
        return Util::generateRandomBytes(32);
    }

    /**
     * Generar la llave segura del token
     *
     * @param string       $token
     * @param ApiTokenData $itemData
     * @return Vault
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    private function getSecureData($token, ApiTokenData $itemData)
    {
        $Vault = new Vault();
        $Vault->saveData(CryptSession::getSessionKey(), $itemData->getHash() . $token);

        return $Vault;
    }

    /**
     * Updates an item
     *
     * @param ApiTokenData $itemData
     * @return mixed
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(SPException::SP_WARNING, __u('La autorización ya existe'));
        }

        $token = $this->getTokenByUserId($itemData->getUserId());

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

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUserId());
        $Data->addParam($itemData->getActionId());
        $Data->addParam($this->session->getUserData()->getId());
        $Data->addParam($token);

        $action = $itemData->getActionId();

        if ($action === ActionsInterface::ACCOUNT_VIEW_PASS
            || $action === ActionsInterface::ACCOUNT_CREATE
        ) {
            $Data->addParam(serialize($this->getSecureData($token, $itemData)));
        } else {
            $Data->addParam(null);
        }

        $Data->addParam(Hash::hashKey($itemData->getHash()));
        $Data->addParam($itemData->getId());
        $Data->setOnErrorMessage(__u('Error interno'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param ApiTokenData $itemData
     * @return bool
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $query = /** @lang SQL */
            'SELECT id FROM AuthToken 
            WHERE userId = ? 
            AND actionId = ? 
            AND id <> ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUserId());
        $Data->addParam($itemData->getActionId());
        $Data->addParam($itemData->getId());

        DbWrapper::getResults($Data, $this->db);

        return $Data->getQueryNumRows() === 1;
    }

    /**
     * Regenerar el hash de los tokens de un usuario
     *
     * @param ApiTokenData $itemData
     * @return bool
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refreshToken(ApiTokenData $itemData)
    {
        $query = /** @lang SQL */
            'UPDATE AuthToken 
            SET token = ?,
            `hash` = ?,
            vault = ?,
            startDate = UNIX_TIMESTAMP() 
            WHERE userId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);

        $token = $this->generateToken();
        $Data->addParam($token);
        $Data->addParam(Hash::hashKey($itemData->getHash()));

        $action = $itemData->getActionId();

        if ($action === ActionsInterface::ACCOUNT_VIEW_PASS
            || $action === ActionsInterface::ACCOUNT_CREATE
        ) {
            $Data->addParam(serialize($this->getSecureData($token, $itemData)));
        } else {
            $Data->addParam(null);
        }

        $Data->addParam($itemData->getUserId());
        $Data->setOnErrorMessage(__u('Error interno'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Obtener el usuario a partir del token
     *
     * @param $token string El token de autorización
     * @return bool|mixed
     */
    public function getUserIdForToken($token)
    {
        $query = /** @lang SQL */
            'SELECT userId FROM AuthToken WHERE token = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($token);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        return $Data->getQueryNumRows() === 1 ? $queryRes->userId : false;
    }

    /**
     * Devolver los datos de un token
     *
     * @param $actionId int El id de la accion
     * @param $token    string El token de seguridad
     * @return false|ApiTokenData
     */
    public function getTokenByToken($actionId, $token)
    {
        $query = /** @lang SQL */
            'SELECT userId, vault, `hash` 
            FROM AuthToken
            WHERE actionId = ? 
            AND token = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(ApiTokenData::class);
        $Data->setQuery($query);
        $Data->addParam($actionId);
        $Data->addParam($token);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        return $Data->getQueryNumRows() === 1 ? $queryRes : false;
    }
}