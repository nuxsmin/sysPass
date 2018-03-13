<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Services\Api;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\DataModel\AuthTokenData;
use SP\Html\Html;
use SP\Repositories\Track\TrackRequest;
use SP\Services\Auth\AuthException;
use SP\Services\AuthToken\AuthTokenService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\Track\TrackService;
use SP\Services\User\UserService;

/**
 * Class ApiService
 *
 * @package SP\Services\ApiService
 */
class ApiService extends Service
{
    /**
     * @var AuthTokenService
     */
    protected $authTokenService;
    /**
     * @var TrackService
     */
    protected $trackService;
    /**
     * @var mixed
     */
    protected $requestData;
    /**
     * @var TrackRequest
     */
    protected $trackRequest;
    /**
     * @var AuthTokenData
     */
    protected $authTokenData;
    /**
     * @var bool
     */
    protected $passIsNeeded = false;

    /**
     * Obtener los datos de la petición
     *
     * Comprueba que el JSON esté bien formado
     *
     * @throws ServiceException
     */
    public static function getRequestData()
    {
        $request = file_get_contents('php://input');
        $data = json_decode(Html::sanitize($request));

        if (!is_object($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new ServiceException(__u('Datos inválidos'), ServiceException::WARNING, null, -32700);
        }

        if (!isset($data->jsonrpc, $data->method, $data->params, $data->id, $data->params->authToken)) {
            throw new ServiceException(__u('Formato incorrecto'), ServiceException::WARNING, null, -32600);
        }

        if (!isset($data->params->authToken)) {
            throw new ServiceException(__u('Formato incorrecto'), ServiceException::WARNING, null, -32602);
        }

        return $data;
    }

    /**
     * @param $actionId
     * @throws ServiceException
     * @throws \Exception
     */
    public function authenticate($actionId)
    {
        if ($this->trackService->checkTracking($this->trackRequest)) {
            $this->addTracking();

            throw new ServiceException(
                __u('Intentos excedidos'),
                AuthException::INFO,
                null,
                -32601
            );
        }

        if (($this->authTokenData = $this->authTokenService->getTokenByToken($actionId, $this->getParam('authToken'))) === false
            || $this->authTokenData->getActionId() !== $actionId
        ) {
            $this->addTracking();

            throw new ServiceException(__u('Acceso no permitido'));
        }

        $this->context->setUserData(UserService::mapUserLoginResponse($this->dic->get(UserService::class)->getById($this->authTokenData->getUserId())));

        $this->passIsNeeded = $actionId === ActionsInterface::ACCOUNT_VIEW_PASS
            || $actionId === ActionsInterface::ACCOUNT_CREATE;
    }

    /**
     * Añadir un seguimiento
     *
     * @throws ServiceException
     */
    private function addTracking()
    {
        try {
            $this->trackService->add($this->trackRequest);
        } catch (\Exception $e) {
            throw new ServiceException(
                __u('Error interno'),
                ServiceException::ERROR,
                null,
                -32601
            );
        }
    }

    /**
     * Devolver el valor de un parámetro
     *
     * @param string $param
     * @param bool   $required Si es requerido
     * @param mixed  $default  Valor por defecto
     * @return int|string
     * @throws ServiceException
     */
    public function getParam($param, $required = false, $default = null)
    {
        if (null !== $this->requestData
            && isset($this->requestData->params->{$param})
        ) {
            return $this->requestData->params->{$param};
        } elseif ($required === true) {
            throw new ServiceException(__u('Parámetros incorrectos'), ServiceException::ERROR, $this->getHelp($this->requestData->method), -32602);
        }

        return $default;
    }

    /**
     * Devuelve la ayuda para una acción
     *
     * @param string $action
     * @return array
     */
    public function getHelp($action)
    {
        return $this->getActions()[$action]['help'];
    }

    /**
     * Devuelve las acciones que implementa la API
     *
     * @return array
     */
    public function getActions()
    {
        return [
            'getAccountPassword' => [
                'help' => [
                    'id' => __('Id de la cuenta'),
                    'tokenPass' => __('Clave del token'),
                    'details' => __('Devolver detalles en la respuesta')
                ]
            ],
            'getAccountSearch' => [
                'help' => [
                    'text' => __('Texto a buscar'),
                    'count' => __('Número de resultados a mostrar'),
                    'categoryId' => __('Id de categoría a filtrar'),
                    'customerId' => __('Id de cliente a filtrar')
                ]
            ],
            'getAccountData' => [
                'help' => [
                    'id' => __('Id de la cuenta')
                ]
            ],
            'deleteAccount' => [
                'help' => [
                    'id' => __('Id de la cuenta')
                ]
            ],
            'addAccount' => [
                'help' => [
                    'tokenPass' => __('Clave del token'),
                    'name' => __('Nombre de cuenta'),
                    'categoryId' => __('Id de categoría'),
                    'customerId' => __('Id de cliente'),
                    'pass' => __('Clave'),
                    'login' => __('Usuario de acceso'),
                    'url' => __('URL o IP de acceso'),
                    'notes' => __('Notas sobre la cuenta')
                ]
            ],
            'backup' => [
                'help' => ''
            ],
            'getCategories' => [
                'help' => [
                    'name' => __('Nombre de categoría a buscar'),
                    'count' => __('Número de resultados a mostrar')
                ]
            ],
            'addCategory' => [
                'help' => [
                    'name' => __('Nombre de la categoría'),
                    'description' => __('Descripción de la categoría')
                ]
            ],
            'deleteCategory' => [
                'help' => [
                    'id' => __('Id de categoría')
                ]
            ],
            'getCustomers' => [
                'help' => [
                    'name' => __('Nombre de cliente a buscar'),
                    'count' => __('Número de resultados a mostrar')
                ]
            ],
            'addCustomer' => [
                'help' => [
                    'name' => __('Nombre del cliente'),
                    'description' => __('Descripción del cliente')
                ]
            ],
            'deleteCustomer' => [
                'help' => [
                    'id' => __('Id de cliente')
                ]
            ]
        ];
    }

    /**
     * @param mixed $requestData
     */
    public function setRequestData($requestData)
    {
        $this->requestData = $requestData;
    }

    /**
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    protected function initialize()
    {
        $this->authTokenService = $this->dic->get(AuthTokenService::class);
        $this->trackService = $this->dic->get(TrackService::class);
        $this->trackRequest = TrackService::getTrackRequest('api');
    }

    /**
     * Realizar la autentificación del usuario
     *
     * @throws ServiceException
     */
    protected function doAuth()
    {
        if ($this->context->getUserData()->getIsDisabled()
            || !Hash::checkHashKey($this->getParam('tokenPass', true), $this->authTokenData->getHash())
        ) {
            $this->addTracking();

            throw new ServiceException(__u('Acceso no permitido'), ServiceException::ERROR);
        }
    }

    /**
     * Devolver la clave maestra
     *
     * @return string
     * @throws ServiceException
     */
    private function getMasterPass()
    {
        try {
            /** @var Vault $vault */
            $vault = unserialize($this->authTokenData->getVault());

            if ($vault && ($pass = $vault->getData($this->getParam('tokenPass') . $this->getParam('authToken')))) {
                return $pass;
            } else {
                throw new ServiceException(__u('Error interno'), ServiceException::ERROR, __u('Datos inválidos'));
            }
        } catch (CryptoException $e) {
            throw new ServiceException(__u('Error interno'), ServiceException::ERROR, $e->getMessage());
        }
    }
}