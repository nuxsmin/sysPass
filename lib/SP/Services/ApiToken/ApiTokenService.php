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

namespace SP\Services\ApiToken;

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ApiTokenData;
use SP\DataModel\ItemSearchData;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class ApiTokenService
 *
 * @package SP\Services\ApiToken
 */
class ApiTokenService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

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
            'DELETE FROM authTokens WHERE authtoken_id = ? LIMIT 1';

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
            'SELECT authtoken_id,
            authtoken_userId,
            authtoken_actionId,
            authtoken_createdBy,
            authtoken_startDate,
            authtoken_token 
            FROM authTokens 
            WHERE authtoken_id = ? LIMIT 1';

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
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'DELETE FROM authTokens WHERE authtoken_id IN (' . $this->getParamsFromArray($ids) . ')';

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
     * @return bool
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
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
            'SELECT authtoken_id,
            authtoken_userId,
            authtoken_actionId, 
            authtoken_token,
            CONCAT(user_name, \' (\', user_login, \')\') AS user_login 
            FROM authTokens 
            LEFT JOIN usrData ON user_id = authtoken_userId ';

        $Data = new QueryData();

        if ($SearchData->getSeachString() !== '') {
            $search = '%' . $SearchData->getSeachString() . '%';
            $query .= ' WHERE user_login LIKE ?';

            $Data->addParam($search);
        }

        $query .= ' ORDER BY user_login';
        $query .= ' LIMIT ?, ?';

        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        $Data->setQuery($query);

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        foreach ($queryRes as $token) {
            $token->authtoken_actionId = Acl::getActionInfo($token->authtoken_actionId);
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
            'INSERT INTO authTokens 
            SET authtoken_userId = ?,
            authtoken_actionId = ?,
            authtoken_createdBy = ?,
            authtoken_token = ?,
            authtoken_vault = ?,
            authtoken_hash = ?,
            authtoken_startDate = UNIX_TIMESTAMP()';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getAuthtokenUserId());
        $Data->addParam($itemData->getAuthtokenActionId());
        $Data->addParam($this->session->getUserData()->getUserId());

        $token = $this->getTokenByUserId($itemData->getAuthtokenUserId());
        $Data->addParam($token);

        $action = $itemData->getAuthtokenActionId();

        if ($action === ActionsInterface::ACCOUNT_VIEW_PASS
            || $action === ActionsInterface::ACCOUNT_CREATE
        ) {
            $Data->addParam(serialize($this->getSecureData($token, $itemData)));
        } else {
            $Data->addParam(null);
        }

        $Data->addParam(Hash::hashKey($itemData->getAuthtokenHash()));
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
            'SELECT authtoken_id FROM authTokens 
            WHERE authtoken_userId = ? 
            AND authtoken_actionId = ? LIMIT 1';

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
            'SELECT authtoken_token FROM authTokens WHERE authtoken_userId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        return $Data->getQueryNumRows() === 1 ? $queryRes->authtoken_token : $this->generateToken();
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
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function getSecureData($token, ApiTokenData $itemData)
    {
        $Vault = new Vault();
        $Vault->saveData(CryptSession::getSessionKey(), $itemData->getAuthtokenHash() . $token);

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

        $token = $this->getTokenByUserId($itemData->getAuthtokenUserId());

        $query = /** @lang SQL */
            'UPDATE authTokens 
            SET authtoken_userId = ?,
            authtoken_actionId = ?,
            authtoken_createdBy = ?,
            authtoken_token = ?,
            authtoken_vault = ?,
            authtoken_hash = ?,
            authtoken_startDate = UNIX_TIMESTAMP() 
            WHERE authtoken_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getAuthtokenUserId());
        $Data->addParam($itemData->getAuthtokenActionId());
        $Data->addParam($this->session->getUserData()->getUserId());
        $Data->addParam($token);

        $action = $itemData->getAuthtokenActionId();

        if ($action === ActionsInterface::ACCOUNT_VIEW_PASS
            || $action === ActionsInterface::ACCOUNT_CREATE
        ) {
            $Data->addParam(serialize($this->getSecureData($token, $itemData)));
        } else {
            $Data->addParam(null);
        }

        $Data->addParam(Hash::hashKey($itemData->getAuthtokenHash()));
        $Data->addParam($itemData->getAuthtokenId());
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
            'SELECT authtoken_id FROM authTokens 
            WHERE authtoken_userId = ? 
            AND authtoken_actionId = ? 
            AND authtoken_id <> ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getAuthtokenUserId());
        $Data->addParam($itemData->getAuthtokenActionId());
        $Data->addParam($itemData->getAuthtokenId());

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
            'UPDATE authTokens 
            SET authtoken_token = ?,
            authtoken_hash = ?,
            authtoken_vault = ?,
            authtoken_startDate = UNIX_TIMESTAMP() 
            WHERE authtoken_userId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);

        $token = $this->generateToken();
        $Data->addParam($token);
        $Data->addParam(Hash::hashKey($itemData->getAuthtokenHash()));

        $action = $itemData->getAuthtokenActionId();

        if ($action === ActionsInterface::ACCOUNT_VIEW_PASS
            || $action === ActionsInterface::ACCOUNT_CREATE
        ) {
            $Data->addParam(serialize($this->getSecureData($token, $itemData)));
        } else {
            $Data->addParam(null);
        }

        $Data->addParam($itemData->getAuthtokenUserId());
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
            'SELECT authtoken_userId FROM authTokens WHERE authtoken_token = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($token);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        return $Data->getQueryNumRows() === 1 ? $queryRes->authtoken_userId : false;
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
            'SELECT authtoken_userId,
            authtoken_vault,
            authtoken_hash 
            FROM authTokens
            WHERE authtoken_actionId = ? 
            AND authtoken_token = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(ApiTokenData::class);
        $Data->setQuery($query);
        $Data->addParam($actionId);
        $Data->addParam($token);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        return $Data->getQueryNumRows() === 1 ? $queryRes : false;
    }
}