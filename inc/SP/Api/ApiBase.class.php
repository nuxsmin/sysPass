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

namespace SP\Api;

defined('APP_ROOT') || die();

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\DataModel\ApiTokenData;
use SP\DataModel\UserLoginData;
use SP\Log\Log;
use SP\Mgmt\ApiTokens\ApiToken;
use SP\Mgmt\Users\User;
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
    protected $data;
    /**
     * @var UserLoginData
     */
    protected $UserData;
    /**
     * @var Log
     */
    protected $Log;
    /**
     * @var ApiTokenData
     */
    protected $ApiTokenData;

    /**
     * @param $data
     * @throws \SP\Core\Exceptions\SPException
     */
    public function __construct($data)
    {
        $this->actionId = $this->getActionId($data->method);
        $this->ApiTokenData = ApiToken::getItem()->getTokenByToken($this->actionId, $data->params->authToken);

        if ($this->ApiTokenData === false) {
            ApiUtil::addTracking();

            throw new SPException(SPException::SP_CRITICAL, __('Acceso no permitido', false));
        }

        $this->data = $data;

        $this->userId = $this->ApiTokenData->getAuthtokenUserId();

        $this->loadUserData();

        if ($this->passIsNeeded()) {
            $this->doAuth();
        }

        Session::setSessionType(Session::SESSION_API);

        $this->Log = new Log();
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
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function loadUserData()
    {
        $this->UserData = User::getItem()->getById($this->ApiTokenData->getAuthtokenUserId());

        SessionUtil::loadUserSession($this->UserData);
    }

    /**
     * @return bool
     */
    protected abstract function passIsNeeded();

    /**
     * Realizar la autentificación del usuario
     *
     * @throws SPException
     */
    protected function doAuth()
    {
        if ($this->UserData->isUserIsDisabled()
            || !Hash::checkHashKey($this->getParam('tokenPass', true), $this->ApiTokenData->getAuthtokenHash())
        ) {
            ApiUtil::addTracking();

            throw new SPException(SPException::SP_CRITICAL, __('Acceso no permitido', false));
        }
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
        if ($required === true && !isset($this->data->params->$name)) {
            throw new InvalidArgumentException(SPException::SP_WARNING, __('Parámetros incorrectos', false), $this->getHelp($this->data->method));
        }

        if (isset($this->data->params->$name)) {
            return $this->data->params->$name;
        }

        return $default;
    }

    /**
     * Devolver la clave maestra
     *
     * @return string
     * @throws SPException
     */
    protected function getMPass()
    {
        try {
            /** @var Vault $Vault */
            $Vault = unserialize($this->ApiTokenData->getAuthtokenVault());

            if ($Vault && $pass = $Vault->getData($this->getParam('tokenPass') . $this->getParam('authToken'))) {
                return $pass;
            } else {
                throw new SPException(SPException::SP_ERROR, __('Error interno', false), __('Datos inválidos', false));
            }
        } catch (CryptoException $e) {
            throw new SPException(SPException::SP_ERROR, __('Error interno', false), $e->getMessage());
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
            ApiUtil::addTracking();

            throw new SPException(SPException::SP_CRITICAL, __('Acceso no permitido', false));
        }
    }

    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * {"jsonrpc": "2.0", "result": 19, "id": 3}
     *
     * @param string $data Los datos a devolver
     * @return string La cadena en formato JSON
     * @throws SPException
     */
    protected function wrapJSON(&$data)
    {
        $json = [
            'jsonrpc' => '2.0',
            'result' => $data,
            'id' => $this->data->id
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
            ApiUtil::addTracking();

            throw new SPException(SPException::SP_CRITICAL, __('Acceso no permitido', false));
        }
    }
}