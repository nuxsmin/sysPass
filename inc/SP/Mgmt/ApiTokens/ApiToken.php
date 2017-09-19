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

namespace SP\Mgmt\ApiTokens;

use SP\Core\ActionsInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\DataModel\ApiTokenData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class ApiToken
 *
 * @package SP\Mgmt\ApiTokens
 * @property ApiTokenData $itemData
 */
class ApiToken extends ApiTokenBase implements ItemInterface
{
    use ItemTrait;

    /**
     * @return mixed
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_WARNING, __('La autorización ya existe', false));
        }

        $token = $this->getTokenByUserId($this->itemData->getAuthtokenUserId());

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
        $Data->addParam($this->itemData->getAuthtokenUserId());
        $Data->addParam($this->itemData->getAuthtokenActionId());
        $Data->addParam(Session::getUserData()->getUserId());
        $Data->addParam($token);

        $action = $this->itemData->getAuthtokenActionId();

        if ($action === ActionsInterface::ACTION_ACC_VIEW_PASS
            || $action === ActionsInterface::ACTION_ACC_NEW
        ) {
            $Data->addParam(serialize($this->getSecureData($token)));
        } else {
            $Data->addParam(null);
        }

        $Data->addParam(Hash::hashKey($this->itemData->getAuthtokenHash()));
        $Data->setOnErrorMessage(__('Error interno', false));

        DB::getQuery($Data);

        return $this;
    }

    /**
     * @return bool
     * @throws SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT authtoken_id FROM authTokens 
            WHERE authtoken_userId = ? 
            AND authtoken_actionId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getAuthtokenUserId());
        $Data->addParam($this->itemData->getAuthtokenActionId());

        DB::getResults($Data);

        return $Data->getQueryNumRows() === 1;
    }

    /**
     * Obtener el token de la API de un usuario
     *
     * @param $id
     * @return bool
     */
    private function getTokenByUserId($id)
    {
        $query = /** @lang SQL */
            'SELECT authtoken_token FROM authTokens WHERE authtoken_userId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DB::getResults($Data);

        return $Data->getQueryNumRows() === 1 ? $queryRes->authtoken_token : $this->generateToken();
    }

    /**
     * Generar un token de acceso
     *
     * @return string
     */
    private function generateToken()
    {
        return Util::generateRandomBytes(32);
    }

    /**
     * Generar la llave segura del token
     *
     * @param $token
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @return Vault
     */
    private function getSecureData($token)
    {
        $Vault = new Vault();
        $Vault->saveData(CryptSession::getSessionKey(), $this->itemData->getAuthtokenHash() . $token);

        return $Vault;
    }

    /**
     * @param $id int
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM authTokens WHERE authtoken_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error interno', false));

        DB::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __('Token no encontrado', false));
        } else {
            $Data->addParam(null);
        }

        return $this;
    }

    /**
     * @return mixed
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()) {
            throw new SPException(SPException::SP_WARNING, __('La autorización ya existe', false));
        }

        $token = $this->getTokenByUserId($this->itemData->getAuthtokenUserId());
        $this->getSecureData($token);

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
        $Data->addParam($this->itemData->getAuthtokenUserId());
        $Data->addParam($this->itemData->getAuthtokenActionId());
        $Data->addParam(Session::getUserData()->getUserId());
        $Data->addParam($token);

        $action = $this->itemData->getAuthtokenActionId();

        if ($action === ActionsInterface::ACTION_ACC_VIEW_PASS
            || $action === ActionsInterface::ACTION_ACC_NEW
        ) {
            $Data->addParam(serialize($this->getSecureData($token)));
        } else {
            $Data->addParam(null);
        }

        $Data->addParam(Hash::hashKey($this->itemData->getAuthtokenHash()));
        $Data->addParam($this->itemData->getAuthtokenId());
        $Data->setOnErrorMessage(__('Error interno', false));

        DB::getQuery($Data);

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT authtoken_id FROM authTokens 
            WHERE authtoken_userId = ? 
            AND authtoken_actionId = ? 
            AND authtoken_id <> ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getAuthtokenUserId());
        $Data->addParam($this->itemData->getAuthtokenActionId());
        $Data->addParam($this->itemData->getAuthtokenId());

        DB::getResults($Data);

        return $Data->getQueryNumRows() === 1;
    }

    /**
     * Regenerar el hash de los tokens de un usuario
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public function refreshToken()
    {
        $token = $this->generateToken();
        $this->getSecureData($token);

        $query = /** @lang SQL */
            'UPDATE authTokens 
            SET authtoken_token = ?,
            authtoken_hash = ?,
            authtoken_vault = ?,
            authtoken_startDate = UNIX_TIMESTAMP() 
            WHERE authtoken_userId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($token);
        $Data->addParam(Hash::hashKey($this->itemData->getAuthtokenHash()));

        if ($this->itemData->getAuthtokenActionId() === ActionsInterface::ACTION_ACC_VIEW_PASS) {
            $Data->addParam(serialize($this->getSecureData($token)));
        } else {
            $Data->addParam(null);
        }

        $Data->addParam($this->itemData->getAuthtokenUserId());
        $Data->setOnErrorMessage(__('Error interno', false));

        DB::getQuery($Data);

        return $this;
    }

    /**
     * @param $id int
     * @return ApiTokenData
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
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        return DB::getResults($Data);
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
        // TODO: Implement checkInUse() method.
    }

    /**
     * Eliminar elementos en lote
     *
     * @param array $ids
     * @return $this
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function deleteBatch(array $ids)
    {
        $query = /** @lang SQL */
            'DELETE FROM authTokens WHERE authtoken_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setParams($ids);
        $Data->setOnErrorMessage(__('Error interno', false));

        DB::getQuery($Data);

        return $this;
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

    /**
     * Obtener el usuario a partir del token
     *
     * @param $token string El token de autorización
     * @return bool|mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getUserIdForToken($token)
    {
        $query = /** @lang SQL */
            'SELECT authtoken_userId FROM authTokens WHERE authtoken_token = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($token);

        $queryRes = DB::getResults($Data);

        return $Data->getQueryNumRows() === 1 ? $queryRes->authtoken_userId : false;
    }

    /**
     * Devolver los datos de un token
     *
     * @param $actionId int El id de la accion
     * @param $token    string El token de seguridad
     * @return false|ApiTokenData
     * @throws \SP\Core\Exceptions\SPException
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
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($actionId);
        $Data->addParam($token);

        $queryRes = DB::getResults($Data);

        return $Data->getQueryNumRows() === 1 ? $queryRes : false;
    }
}