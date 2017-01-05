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
use SP\Auth\AuthResult;
use SP\Auth\AuthUtil;
use SP\Core\Acl;
use SP\Core\Exceptions\InvalidArgumentException;
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
     * Acción a realizar
     *
     * @var
     */
    protected $action;
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
     * @var UserData
     */
    protected $UserData;

    /**
     * @param $params
     * @throws \SP\Core\Exceptions\SPException
     */
    public function __construct($params)
    {
        $this->action = $params->action;
        $this->actionId = $this->getActionId($params->action);

        if (!AuthUtil::checkAuthToken($this->actionId, $params->authToken)) {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }

        $this->params = $params;
        $this->userId = ApiTokensUtil::getUserIdForToken($params->authToken);

        $this->loadUserData();

        if ($this->getParam('userPass') !== null) {
            $this->doAuth();
        }

        Session::setSessionType(Session::SESSION_API);
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
            throw new InvalidArgumentException(SPException::SP_WARNING, _('Parámetros incorrectos'), $this->getHelp($this->action));
        }

        if (isset($this->params->$name)) {
            return $this->params->$name;
        }

        return $default;
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

        return isset($actions[$action]) ? $actions[$action]['id'] : 0;
    }

    /**
     * Cargar los datos del usuario
     *
     * @return UserData
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function loadUserData()
    {
        $UserData = new UserData();
        $UserData->setUserId($this->userId);
        $UserData->setUserPass($this->getParam('userPass'));

        $this->UserData = User::getItem($UserData)->getById($this->userId);

        SessionUtil::loadUserSession($this->UserData);
    }

    /**
     * Realizar la autentificación del usuario
     *
     * @throws SPException
     */
    protected function doAuth()
    {
        $Auth = new Auth($this->UserData);
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

        $UserPass = UserPass::getItem($this->UserData);

        if (!$this->UserData->isUserIsDisabled()
            && $UserPass->checkUserUpdateMPass()
            && $UserPass->loadUserMPass()
        ) {
            $this->auth = true;
            $this->mPass = $UserPass->getClearUserMPass();
        } else {
            throw new SPException(SPException::SP_CRITICAL, _('Acceso no permitido'));
        }
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
     * @return string La cadena en formato JSON
     * @throws SPException
     */
    protected function wrapJSON(&$data)
    {
        $json = [
            'action' => Acl::getActionName($this->actionId, true),
            'params' => $this->params,
            'data' => $data
        ];

        return Json::getJson($json);
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