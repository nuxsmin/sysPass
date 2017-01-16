<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Api;

defined('APP_ROOT') || die();

use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class ApiTokens para la gestión de autorizaciones de acceso a la API de sysPass
 *
 * @package SP
 */
class ApiTokens
{
    /**
     * @var int
     */
    private $tokenId = 0;
    /**
     * @var int
     */
    private $userId = 0;
    /**
     * @var int
     */
    private $actionId = 0;
    /**
     * @var string
     */
    private $token = '';
    /**
     * @var bool
     */
    private $refreshToken = false;

    /**
     * @param boolean $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * Añadir un nuevo token
     *
     * @throws SPException
     */
    public function addToken()
    {
        $this->checkTokenExist();

        if ($this->refreshToken) {
            $this->refreshToken();
        }

        $query = 'INSERT INTO authTokens ' .
            'SET authtoken_userId = :userid,' .
            'authtoken_actionId = :actionid,' .
            'authtoken_createdBy = :createdby,' .
            'authtoken_token = :token,' .
            'authtoken_startDate = UNIX_TIMESTAMP()';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->userId, 'userid');
        $Data->addParam($this->actionId, 'actionid');
        $Data->addParam(Session::getUserData()->getUserId(), 'createdby');
        $Data->addParam($this->getUserToken() ? $this->token : $this->generateToken(), 'token');

        try {
            DB::getQuery($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, __('Error interno', false));
        }
    }

    /**
     * Comprobar si el token ya existe
     *
     * @return bool
     * @throws SPException
     */
    private function checkTokenExist()
    {
        $query = 'SELECT authtoken_id FROM authTokens ' .
            'WHERE authtoken_userId = :userid ' .
            'AND authtoken_actionId = :actionid ' .
            'AND authtoken_id <> :id ' .
            'LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->tokenId, 'id');
        $Data->addParam($this->userId, 'userid');
        $Data->addParam($this->actionId, 'actionid');

        try {
            DB::getResults($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, __('Error interno', false));
        }

        if ($Data->getQueryNumRows() === 1) {
            throw new SPException(SPException::SP_WARNING, __('La autorización ya existe', false));
        }
    }

    /**
     * Regenerar el hash de los tokens de un usuario
     *
     * @throws SPException
     */
    private function refreshToken()
    {
        $query = 'UPDATE authTokens SET ' .
            'authtoken_token = :token,' .
            'authtoken_startDate = UNIX_TIMESTAMP() ' .
            'WHERE authtoken_userId = :userid';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->userId, 'userid');
        $Data->addParam($this->generateToken(), 'token');

        try {
            DB::getQuery($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, __('Error interno', false));
        }
    }

    /**
     * Generar un token de acceso
     *
     * @return string
     */
    private function generateToken()
    {
        return sha1(uniqid('sysPass-API', true) . time());
    }

    /**
     * Obtener el token de la API de un usuario
     *
     * @return bool
     * @throws SPException
     */
    private function getUserToken()
    {
        $query = 'SELECT authtoken_token FROM authTokens WHERE authtoken_userId = :userid LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->userId, 'userid');

        try {
            $queryRes = DB::getResults($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, __('Error interno', false));
        }

        if ($Data->getQueryNumRows() === 0) {
            return false;
        }

        $this->token = $queryRes->authtoken_token;

        return true;
    }

    /**
     * Actualizar un token
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateToken()
    {
        $this->checkTokenExist();

        if ($this->refreshToken) {
            $this->refreshToken();
        }

        $query = 'UPDATE authTokens ' .
            'SET authtoken_userId = :userid,' .
            'authtoken_actionId = :actionid,' .
            'authtoken_createdBy = :createdby,' .
            'authtoken_token = :token,' .
            'authtoken_startDate = UNIX_TIMESTAMP() ' .
            'WHERE authtoken_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->tokenId, 'id');
        $Data->addParam($this->userId, 'userid');
        $Data->addParam($this->actionId, 'actionid');
        $Data->addParam(Session::getUserData()->getUserId(), 'createdby');
        $Data->addParam($this->getUserToken() ? $this->token : $this->generateToken(), 'token');

        try {
            DB::getQuery($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, __('Error interno', false));
        }
    }

    /**
     * Eliminar token
     *
     * @throws SPException
     */
    public function deleteToken()
    {
        $query = 'DELETE FROM authTokens WHERE authtoken_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->tokenId);

        try {
            DB::getQuery($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, __('Error interno', false));
        }
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getTokenId()
    {
        return $this->tokenId;
    }

    /**
     * @param int $tokenId
     */
    public function setTokenId($tokenId)
    {
        $this->tokenId = $tokenId;
    }

    /**
     * @return int
     */
    public function getActionId()
    {
        return $this->actionId;
    }

    /**
     * @param int $actionId
     */
    public function setActionId($actionId)
    {
        $this->actionId = $actionId;
    }
}