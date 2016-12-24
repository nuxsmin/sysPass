<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Controller\ActionsInterface;

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
    private $_tokenId = 0;
    /**
     * @var int
     */
    private $_userId = 0;
    /**
     * @var int
     */
    private $_actionId = 0;
    /**
     * @var string
     */
    private $_token = '';
    /**
     * @var bool
     */
    private $_refreshToken = false;

    /**
     * Obtener los tokens de la API
     *
     * @param int  $tokenId       opcional, con el Id del token a consultar
     * @param bool $returnRawData Devolver la consulta tal cual
     * @return array|object con la lista de tokens
     */
    public static function getTokens($tokenId = null, $returnRawData = false)
    {
        $query = 'SELECT authtoken_id,' .
            'authtoken_userId,' .
            'authtoken_actionId, ' .
            'authtoken_token, ' .
            'user_login ' .
            'FROM authTokens ' .
            'LEFT JOIN usrData ON user_id = authtoken_userId ';

        $data = null;

        if (!is_null($tokenId)) {
            $query .= "WHERE authtoken_id = :id LIMIT 1";
            $data['id'] = $tokenId;
        } else {
            $query .= "ORDER BY user_login";
        }

        if (!$returnRawData) {
            DB::setReturnArray();
        }

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return array();
        }

        if (!$returnRawData) {
            foreach ($queryRes as &$token) {
                $token->authtoken_actionId = Acl::getActionName($token->authtoken_actionId);
            }
        }

        return $queryRes;
    }

    /**
     * Devuelver un array de acciones posibles para los tokens
     *
     * @return array
     */
    public static function getTokenActions()
    {
        $actions = array(
            ActionsInterface::ACTION_ACC_SEARCH => Acl::getActionName(ActionsInterface::ACTION_ACC_SEARCH),
            ActionsInterface::ACTION_ACC_VIEW => Acl::getActionName(ActionsInterface::ACTION_ACC_VIEW),
            ActionsInterface::ACTION_ACC_VIEW_PASS => Acl::getActionName(ActionsInterface::ACTION_ACC_VIEW_PASS),
            ActionsInterface::ACTION_ACC_DELETE => Acl::getActionName(ActionsInterface::ACTION_ACC_DELETE),
            ActionsInterface::ACTION_CFG_BACKUP => Acl::getActionName(ActionsInterface::ACTION_CFG_BACKUP),
            ActionsInterface::ACTION_CFG_EXPORT => Acl::getActionName(ActionsInterface::ACTION_CFG_EXPORT),
        );

        return $actions;
    }

    /**
     * Obtener el usuario a partir del token
     *
     * @param $token string El token de autorización
     * @return bool|mixed
     * @throws SPException
     */
    public static function getUserIdForToken($token)
    {
        $query = 'SELECT authtoken_userId FROM authTokens WHERE authtoken_token = :token LIMIT 1';

        $data['token'] = $token;

        try {
            $queryRes = DB::getResults($query, __FUNCTION__, $data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'));
        }

        if (DB::$lastNumRows === 0) {
            return false;
        }

        return $queryRes->authtoken_userId;
    }

    /**
     * @param boolean $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->_refreshToken = $refreshToken;
    }

    /**
     * Añadir un nuevo token
     *
     * @throws SPException
     */
    public function addToken()
    {
        $this->checkTokenExist();

        if ($this->_refreshToken) {
            $this->refreshToken();
        }

        $query = 'INSERT INTO authTokens ' .
            'SET authtoken_userId = :userid,' .
            'authtoken_actionId = :actionid,' .
            'authtoken_createdBy = :createdby,' .
            'authtoken_token = :token,' .
            'authtoken_startDate = UNIX_TIMESTAMP()';

        $data['userid'] = $this->_userId;
        $data['actionid'] = $this->_actionId;
        $data['createdby'] = Session::getUserId();
        $data['token'] = ($this->getUserToken()) ? $this->_token : sha1(uniqid() . time());

        try {
            DB::getQuery($query, __FUNCTION__, $data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'));
        }

        $Log = new Log(_('Nueva Autorización'));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Usuario')), UserUtil::getUserLoginById($this->_userId)));
        $Log->writeLog();

        Email::sendEmail($Log);
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

        $data['id'] = $this->_tokenId;
        $data['userid'] = $this->_userId;
        $data['actionid'] = $this->_actionId;

        try {
            DB::getResults($query, __FUNCTION__, $data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'));
        }

        if (DB::$lastNumRows === 1) {
            throw new SPException(SPException::SP_WARNING, _('La autorización ya existe'));
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

        $data['userid'] = $this->_userId;
        $data['token'] = sha1(uniqid() . time());

        try {
            DB::getQuery($query, __FUNCTION__, $data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'));
        }
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

        $data['userid'] = $this->_userId;

        try {
            $queryRes = DB::getResults($query, __FUNCTION__, $data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'));
        }

        if (DB::$lastNumRows === 0) {
            return false;
        }

        $this->_token = $queryRes->authtoken_token;

        return true;
    }

    /**
     * Actualizar un token
     *
     * @throws SPException
     */
    public function updateToken()
    {
        $this->checkTokenExist();

        if ($this->_refreshToken) {
            $this->refreshToken();
        }

        $query = 'UPDATE authTokens ' .
            'SET authtoken_userId = :userid,' .
            'authtoken_actionId = :actionid,' .
            'authtoken_createdBy = :createdby,' .
            'authtoken_token = :token,' .
            'authtoken_startDate = UNIX_TIMESTAMP() ' .
            'WHERE authtoken_id = :id LIMIT 1';

        $data['id'] = $this->_tokenId;
        $data['userid'] = $this->_userId;
        $data['actionid'] = $this->_actionId;
        $data['createdby'] = Session::getUserId();
        $data['token'] = ($this->getUserToken()) ? $this->_token : sha1(uniqid() . time());

        try {
            DB::getQuery($query, __FUNCTION__, $data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'));
        }

        $Log = new Log(_('Actualizar Autorización'));
        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Usuario')), UserUtil::getUserLoginById($this->_userId)));
        $Log->writeLog();

        Email::sendEmail($Log);
    }

    /**
     * Eliminar token
     *
     * @throws SPException
     */
    public function deleteToken()
    {
        $query = 'DELETE FROM authTokens WHERE authtoken_id = :id LIMIT 1';

        $data['id'] = $this->_tokenId;

        try {
            DB::getQuery($query, __FUNCTION__, $data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'));
        }

        $Log = new Log(_('Eliminar Autorización'));
        $Log->addDescription(sprintf('%d', $this->_tokenId));
        $Log->writeLog();

        Email::sendEmail($Log);
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }

    /**
     * @return int
     */
    public function getTokenId()
    {
        return $this->_tokenId;
    }

    /**
     * @param int $tokenId
     */
    public function setTokenId($tokenId)
    {
        $this->_tokenId = $tokenId;
    }

    /**
     * @return int
     */
    public function getActionId()
    {
        return $this->_actionId;
    }

    /**
     * @param int $actionId
     */
    public function setActionId($actionId)
    {
        $this->_actionId = $actionId;
    }
}