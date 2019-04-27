<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
use Exception;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AuthTokenData;
use SP\Modules\Api\Controllers\Help\HelpInterface;
use SP\Repositories\Track\TrackRequest;
use SP\Services\AuthToken\AuthTokenService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\Track\TrackService;
use SP\Services\User\UserService;
use SP\Services\UserProfile\UserProfileService;
use SP\Util\Filter;

/**
 * Class ApiService
 *
 * @package SP\Services\ApiService
 */
final class ApiService extends Service
{
    /**
     * @var AuthTokenService
     */
    private $authTokenService;
    /**
     * @var TrackService
     */
    private $trackService;
    /**
     * @var ApiRequest
     */
    private $apiRequest;
    /**
     * @var TrackRequest
     */
    private $trackRequest;
    /**
     * @var AuthTokenData
     */
    private $authTokenData;
    /**
     * @var HelpInterface
     */
    private $helpClass;
    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * Sets up API
     *
     * @param $actionId
     *
     * @throws ServiceException
     * @throws SPException
     * @throws Exception
     */
    public function setup($actionId)
    {
        $this->initialized = false;
        $this->apiRequest = $this->dic->get(ApiRequest::class);

        if ($this->trackService->checkTracking($this->trackRequest)) {
            $this->addTracking();

            throw new ServiceException(
                __u('Attempts exceeded'),
                ServiceException::ERROR,
                null,
                JsonRpcResponse::INTERNAL_ERROR
            );
        }

        $this->authTokenData = $this->authTokenService->getTokenByToken($actionId, $this->getParam('authToken'));

        if ($this->authTokenData->getActionId() !== $actionId) {
            $this->accessDenied();
        }

        $this->setupUser();

        if (AuthTokenService::isSecuredAction($actionId)) {
            $this->context->setTrasientKey('_masterpass', $this->getMasterPassFromVault());
        }

        $this->initialized = true;
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
        } catch (Exception $e) {
            throw new ServiceException(
                __u('Internal error'),
                ServiceException::ERROR,
                null,
                JsonRpcResponse::INTERNAL_ERROR
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
     * @return mixed
     * @throws ServiceException
     */
    public function getParam($param, $required = false, $default = null)
    {
        if ($this->apiRequest === null
            || ($required && !$this->apiRequest->exists($param))) {
            throw new ServiceException(
                __u('Wrong parameters'),
                ServiceException::ERROR,
                $this->getHelp($this->apiRequest->getMethod()),
                JsonRpcResponse::INVALID_PARAMS
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
        if ($this->helpClass !== null) {
            return call_user_func([$this->helpClass, 'getHelpFor'], $action);
        }

        return [];
    }

    /**
     * @throws ServiceException
     */
    private function accessDenied()
    {
        $this->addTracking();

        throw new ServiceException(
            __u('Unauthorized access'),
            ServiceException::ERROR,
            null,
            JsonRpcResponse::INTERNAL_ERROR
        );
    }

    /**
     * Sets up user's data in context and performs some user checks
     *
     * @throws SPException
     */
    private function setupUser()
    {
        $userLoginResponse = UserService::mapUserLoginResponse($this->dic->get(UserService::class)
            ->getById($this->authTokenData->getUserId()));
        $userLoginResponse->getIsDisabled() && $this->accessDenied();

        $this->context->setUserData($userLoginResponse);
        $this->context->setUserProfile($this->dic->get(UserProfileService::class)
            ->getById($userLoginResponse->getUserProfileId())->getProfile());
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
                    __u('Internal error'),
                    ServiceException::ERROR,
                    __u('Invalid data'),
                    JsonRpcResponse::INTERNAL_ERROR
                );
            }
        } catch (CryptoException $e) {
            throw new ServiceException(
                __u('Internal error'),
                ServiceException::ERROR,
                $e->getMessage(),
                JsonRpcResponse::INTERNAL_ERROR
            );
        }
    }

    /**
     * @param string $param
     * @param bool   $required
     * @param mixed  $default
     *
     * @return int
     * @throws ServiceException
     */
    public function getParamInt($param, $required = false, $default = null)
    {
        return Filter::getInt($this->getParam($param, $required, $default));
    }

    /**
     * @param string $param
     * @param bool   $required
     * @param mixed  $default
     *
     * @return string
     * @throws ServiceException
     */
    public function getParamString($param, $required = false, $default = null)
    {
        return Filter::getString($this->getParam($param, $required, $default));
    }

    /**
     * @param string $param
     * @param bool   $required
     * @param mixed  $default
     *
     * @return array
     * @throws ServiceException
     */
    public function getParamArray($param, $required = false, $default = null)
    {
        $array = $this->getParam($param, $required, $default);

        if ($array !== null) {
            return Filter::getArray($array);
        }

        return $array;
    }

    /**
     * @param string $param
     * @param bool   $required
     * @param mixed  $default
     *
     * @return int|string
     * @throws ServiceException
     */
    public function getParamEmail($param, $required = false, $default = null)
    {
        return Filter::getEmail($this->getParam($param, $required, $default));
    }

    /**
     * @param string $param
     * @param bool   $required
     * @param mixed  $default
     *
     * @return string
     * @throws ServiceException
     */
    public function getParamRaw($param, $required = false, $default = null)
    {
        return Filter::getRaw($this->getParam($param, $required, $default));
    }

    /**
     * @return string
     * @throws ServiceException
     */
    public function getMasterPass()
    {
        return $this->getMasterKeyFromContext();
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
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * @param string $helpClass
     *
     * @throws InvalidClassException
     */
    public function setHelpClass(string $helpClass)
    {
        if (class_exists($helpClass)) {
            $this->helpClass = $helpClass;
            return;
        }

        throw new InvalidClassException('Invalid class for helper');
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function initialize()
    {
        $this->authTokenService = $this->dic->get(AuthTokenService::class);
        $this->trackService = $this->dic->get(TrackService::class);
        $this->trackRequest = $this->trackService->getTrackRequest(__CLASS__);
    }
}