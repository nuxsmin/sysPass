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
use SP\Repositories\Track\TrackRequest;
use SP\Services\AuthToken\AuthTokenService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\Track\TrackService;
use SP\Services\User\UserService;
use SP\Services\UserProfile\UserProfileService;

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
     * @var ApiRequest
     */
    protected $apiRequest;
    /**
     * @var TrackRequest
     */
    protected $trackRequest;
    /**
     * @var AuthTokenData
     */
    protected $authTokenData;

    /**
     * Sets up API
     *
     * @param $actionId
     *
     * @throws ServiceException
     * @throws \Exception
     */
    public function setup($actionId)
    {
        if ($this->trackService->checkTracking($this->trackRequest)) {
            $this->addTracking();

            throw new ServiceException(
                __u('Intentos excedidos'),
                ServiceException::ERROR,
                null,
                -32601
            );
        }

        (($this->authTokenData = $this->authTokenService->getTokenByToken($actionId, $this->getParam('authToken'))) === false
            || $this->authTokenData->getActionId() !== $actionId) && $this->accessDenied();

        $this->setupUser();

        if ($actionId === ActionsInterface::ACCOUNT_VIEW_PASS
            || $actionId === ActionsInterface::ACCOUNT_CREATE
        ) {
            $this->context->setTrasientKey('_masterpass', $this->getMasterPassFromVault());
        }
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
     *
     * @return int|string
     * @throws ServiceException
     */
    public function getParam($param, $required = false, $default = null)
    {
        if (null === $this->apiRequest
            || ($required === true && !$this->apiRequest->exists($param))
        ) {
            throw new ServiceException(
                __u('Parámetros incorrectos'),
                ServiceException::ERROR,
                $this->getHelp($this->apiRequest->getMethod()),
                -32602
            );
        }

        return $this->apiRequest->get($param, $default);
    }

    /**
     * Devuelve la ayuda para una acción
     *
     * @param string $action
     *
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
            'account/viewPass' => [
                'help' => [
                    'id' => __('Id de la cuenta'),
                    'tokenPass' => __('Clave del token'),
                    'details' => __('Devolver detalles en la respuesta')
                ]
            ],
            'account/search' => [
                'help' => [
                    'text' => __('Texto a buscar'),
                    'count' => __('Número de resultados a mostrar'),
                    'categoryId' => __('Id de categoría a filtrar'),
                    'customerId' => __('Id de cliente a filtrar')
                ]
            ],
            'account/view' => [
                'help' => [
                    'id' => __('Id de la cuenta')
                ]
            ],
            'account/delete' => [
                'help' => [
                    'id' => __('Id de la cuenta')
                ]
            ],
            'account/create' => [
                'help' => [
                    'tokenPass' => __('Clave del token'),
                    'name' => __('Nombre de cuenta'),
                    'categoryId' => __('Id de categoría'),
                    'clientId' => __('Id de cliente'),
                    'pass' => __('Clave'),
                    'login' => __('Usuario de acceso'),
                    'url' => __('URL o IP de acceso'),
                    'notes' => __('Notas sobre la cuenta'),
                    'private' => __('Cuenta Privada'),
                    'privateGroup' => __('Cuenta Privada Grupo'),
                    'expireDate' => __('Fecha Caducidad Clave'),
                    'parentId' => __('Cuenta Vinculada')
                ]
            ],
            'backup' => [
                'help' => ''
            ],
            'category/search' => [
                'help' => [
                    'text' => __('Texto a buscar'),
                    'count' => __('Número de resultados a mostrar')
                ]
            ],
            'category/create' => [
                'help' => [
                    'name' => __('Nombre de la categoría'),
                    'description' => __('Descripción de la categoría')
                ]
            ],
            'category/delete' => [
                'help' => [
                    'id' => __('Id de categoría')
                ]
            ],
            'client/search' => [
                'help' => [
                    'text' => __('Texto a buscar'),
                    'count' => __('Número de resultados a mostrar')
                ]
            ],
            'client/create' => [
                'help' => [
                    'name' => __('Nombre del cliente'),
                    'description' => __('Descripción del cliente'),
                    'global' => __('Global')
                ]
            ],
            'client/delete' => [
                'help' => [
                    'id' => __('Id de cliente')
                ]
            ],
            'tag/search' => [
                'help' => [
                    'text' => __('Texto a buscar'),
                    'count' => __('Número de resultados a mostrar')
                ]
            ],
            'tag/create' => [
                'help' => [
                    'name' => __('Nombre de la etiqueta')
                ]
            ],
            'tag/delete' => [
                'help' => [
                    'id' => __('Id de etiqueta')
                ]
            ]
        ];
    }

    /**
     * @throws ServiceException
     */
    private function accessDenied()
    {
        $this->addTracking();

        throw new ServiceException(
            __u('Acceso no permitido'),
            ServiceException::ERROR,
            null,
            -32601
        );
    }

    /**
     * Sets up user's data in context and performs some user checks
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private function setupUser()
    {
        $userLoginResponse = UserService::mapUserLoginResponse($this->dic->get(UserService::class)->getById($this->authTokenData->getUserId()));
        $userLoginResponse->getIsDisabled() && $this->accessDenied();

        $this->context->setUserData($userLoginResponse);
        $this->context->setUserProfile($this->dic->get(UserProfileService::class)->getById($userLoginResponse->getUserProfileId())->getProfile());
    }

    /**
     * Devolver la clave maestra
     *
     * @return string
     * @throws ServiceException
     */
    private function getMasterPassFromVault()
    {
        try {
            $tokenPass = $this->getParam('tokenPass', true);

            Hash::checkHashKey($tokenPass, $this->authTokenData->getHash()) || $this->accessDenied();

            /** @var Vault $vault */
            $vault = unserialize($this->authTokenData->getVault());

            if ($vault && ($pass = $vault->getData($tokenPass . $this->getParam('authToken')))) {
                return $pass;
            } else {
                throw new ServiceException(
                    __u('Error interno'),
                    ServiceException::ERROR,
                    __u('Datos inválidos'),
                    -32603
                );
            }
        } catch (CryptoException $e) {
            throw new ServiceException(
                __u('Error interno'),
                ServiceException::ERROR,
                $e->getMessage(),
                -32603
            );
        }
    }

    /**
     * @param string $param
     * @param bool   $required
     * @param null   $default
     *
     * @return int|string
     * @throws ServiceException
     */
    public function getParamInt($param, $required = false, $default = null)
    {
        return filter_var($this->getParam($param, $required, $default), FILTER_VALIDATE_INT);
    }

    /**
     * @param string $param
     * @param bool   $required
     * @param null   $default
     *
     * @return int|string
     * @throws ServiceException
     */
    public function getParamString($param, $required = false, $default = null)
    {
        return filter_var($this->getParam($param, $required, $default), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    /**
     * @param string $param
     * @param bool   $required
     * @param null   $default
     *
     * @return int|string
     * @throws ServiceException
     */
    public function getParamEmail($param, $required = false, $default = null)
    {
        return filter_var($this->getParam($param, $required, $default), FILTER_SANITIZE_EMAIL);
    }

    /**
     * @param string $param
     * @param bool   $required
     * @param null   $default
     *
     * @return int|string
     * @throws ServiceException
     */
    public function getParamRaw($param, $required = false, $default = null)
    {
        return filter_var($this->getParam($param, $required, $default), FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
    }

    /**
     * @return string
     */
    public function getMasterPass()
    {
        return $this->context->getTrasientKey('_masterpass');
    }

    /**
     * @param ApiRequest $apiRequest
     *
     * @return ApiService
     */
    public function setApiRequest(ApiRequest $apiRequest)
    {
        $this->apiRequest = $apiRequest;

        return $this;
    }

    /**
     * @return int
     */
    public function getRequestId()
    {
        return $this->apiRequest->getId();
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
}