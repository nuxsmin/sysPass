<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Api;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Auth\Auth;
use SP\Auth\AuthDataBase;
use SP\Auth\AuthResult;
use SP\Auth\AuthUtil;
use SP\Core\Acl;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserPass;
use SP\Util\Json;

/**
 * Class ApiBase
 *
 * @package SP\Api
 */
abstract class ApiBase implements ApiInterface
{
    /**
     * El ID de la acción
     *
     * @var int
     */
    protected $actionId = 0;
    /**
     * El ID de usuario resuelto
     *
     * @var int
     */
    protected $userId = 0;
    /**
     * Indica si la autentificación es correcta
     *
     * @var bool
     */
    protected $auth = false;
    /**
     * Los parámetros de la acción a ejecutar
     *
     * @var mixed
     */
    protected $params;
    /**
     * @var string
     */
    protected $mPass = '';

    /**
     * @param $params
     * @throws \SP\Core\Exceptions\SPException
     */
    public function __construct($params)
    {
        if (!AuthUtil::checkAuthToken($this->getActionId($params->action), $params->authToken)) {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }

        $this->params = $params;
        $this->userId = ApiTokensUtil::getUserIdForToken($this->getParam('authToken', true));
        $this->actionId = $this->getActionId($this->getParam('action', true));

        if ($this->getParam('userPass') !== null) {
            $this->doAuth();
        }

        Session::setSessionType(Session::SESSION_API);
    }

    /**
     * Devolver el código de acción a realizar a partir del nombre
     *
     * @param $action string El nombre de la acción
     * @return int
     */
    protected function getActionId($action)
    {
        $actions = $this->getActions();

        return isset($actions[$action]) ? $actions[$action] : 0;
    }

    /**
     * Comprobar el acceso a la acción
     *
     * @param $action
     * @throws SPException
     */
    protected function checkActionAccess($action)
    {
        if ($this->actionId !== $action) {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }
    }

    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * @param string $data Los datos a devolver
     * @return bool
     * @throws SPException
     */
    protected function wrapJSON(&$data)
    {
        $json = [
            'action' => Acl::getActionName($this->actionId, true),
            'data' => $data
        ];

        return Json::getJson($json);
    }

    /**
     * Devolver el valor de un parámetro
     *
     * @param string $name     Nombre del parámetro
     * @param bool   $required Si es requerido
     * @param mixed  $default  Valor por defecto
     * @return int|string
     * @throws SPException
     */
    protected function getParam($name, $required = false, $default = null)
    {
        if ($required === true && !isset($this->params->$name)) {
            debugLog(__FUNCTION__ . ':' . $name);

            throw new SPException(SPException::SP_WARNING, _('Parámetros incorrectos'));
        }

        if (isset($this->params->$name)) {
            return $this->params->$name;
        }

        return $default;
    }

    /**
     * Realizar la autentificación del usuario
     *
     * @throws SPException
     */
    protected function doAuth()
    {
        $UserData = new UserData();
        $UserData->setUserId($this->userId);
        $UserData->setUserPass($this->getParam('userPass'));

        $UserData = User::getItem($UserData)->getById($this->userId);

        $Auth = new Auth($UserData);
        $resAuth = $Auth->doAuth();

        if ($resAuth !== false) {
            /** @var AuthResult $AuthResult */
            foreach ($resAuth as $AuthResult) {
                $data = $AuthResult->getData();

                if ($data->getAuthenticated() && $data->getStatusCode() === 0) {
                    break;
                }
            }
        } else {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }

        $UserPass = UserPass::getItem($UserData);

        if (!$UserData->isUserIsDisabled()
            && $UserPass->checkUserUpdateMPass()
            && $UserPass->loadUserMPass()
        ) {
            $this->auth = true;
            $this->mPass = $UserPass->getClearUserMPass();
            SessionUtil::loadUserSession($UserData);
        } else {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }
    }

    /**
     * Comprobar si se ha realizado la autentificación
     *
     * @throws SPException
     */
    protected function checkAuth()
    {
        if ($this->auth === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }
    }
}