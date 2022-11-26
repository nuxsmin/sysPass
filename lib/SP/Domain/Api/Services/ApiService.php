<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Domain\Api\Services;

use Defuse\Crypto\Exception\CryptoException;
use Exception;
use SP\Core\Application;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AuthTokenData;
use SP\Domain\Api\Ports\ApiServiceInterface;
use SP\Domain\Auth\Ports\AuthTokenServiceInterface;
use SP\Domain\Auth\Services\AuthTokenService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Security\Ports\TrackServiceInterface;
use SP\Domain\Security\Services\TrackService;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Domain\User\Services\UserService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Security\Repositories\TrackRequest;
use SP\Util\Filter;

/**
 * Class ApiService
 *
 * @package SP\Domain\Common\Services\ApiService
 */
final class ApiService extends Service implements ApiServiceInterface
{
    private AuthTokenService            $authTokenService;
    private TrackService                $trackService;
    private UserServiceInterface        $userService;
    private UserProfileServiceInterface $userProfileService;
    private ApiRequest                  $apiRequest;
    private TrackRequest                $trackRequest;
    private ?AuthTokenData              $authTokenData = null;
    private ?string                     $helpClass     = null;
    private                             $initialized   = false;

    public function __construct(
        Application $application,
        ApiRequest $apiRequest,
        TrackServiceInterface $trackService,
        AuthTokenServiceInterface $authTokenService,
        UserServiceInterface $userService,
        UserProfileServiceInterface $userProfileService
    ) {
        parent::__construct($application);

        $this->apiRequest = $apiRequest;
        $this->trackService = $trackService;
        $this->authTokenService = $authTokenService;
        $this->userService = $userService;
        $this->userProfileService = $userProfileService;

        $this->trackRequest = $this->trackService->getTrackRequest(__CLASS__);
    }

    /**
     * Sets up API
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws SPException
     * @throws Exception
     */
    public function setup(int $actionId): void
    {
        $this->initialized = false;

        if ($this->trackService->checkTracking($this->trackRequest)) {
            $this->addTracking();

            throw new ServiceException(
                __u('Attempts exceeded'),
                SPException::ERROR,
                null,
                JsonRpcResponse::INTERNAL_ERROR
            );
        }

        try {
            $this->authTokenData = $this->authTokenService
                ->getTokenByToken($actionId, $this->getParam('authToken'));
        } catch (NoSuchItemException $e) {
            logger($e->getMessage(), 'ERROR');

            // For security reasons there won't be any hint about a not found token...
            throw new ServiceException(
                __u('Internal error'),
                SPException::ERROR,
                null,
                JsonRpcResponse::INTERNAL_ERROR
            );
        }

        if ($this->authTokenData->getActionId() !== $actionId) {
            $this->accessDenied();
        }

        $this->setupUser();

        if (AuthTokenService::isSecuredAction($actionId)) {
            $this->requireMasterPass();
        }

        $this->initialized = true;
    }

    /**
     * Añadir un seguimiento
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function addTracking(): void
    {
        try {
            $this->trackService->add($this->trackRequest);
        } catch (Exception $e) {
            throw new ServiceException(
                __u('Internal error'),
                SPException::ERROR,
                null,
                JsonRpcResponse::INTERNAL_ERROR
            );
        }
    }

    /**
     * Devolver el valor de un parámetro
     *
     * @param  string  $param
     * @param  bool  $required  Si es requerido
     * @param  mixed  $default  Valor por defecto
     *
     * @return mixed
     * @throws ServiceException
     */
    public function getParam(string $param, bool $required = false, $default = null)
    {
        if ($required && !$this->apiRequest->exists($param)) {
            throw new ServiceException(
                __u('Wrong parameters'),
                SPException::ERROR,
                $this->getHelp($this->apiRequest->getMethod()),
                JsonRpcResponse::INVALID_PARAMS
            );
        }

        return $this->apiRequest->get($param, $default);
    }

    /**
     * Devuelve la ayuda para una acción
     *
     * @param  string  $action
     *
     * @return array
     */
    public function getHelp(string $action): array
    {
        if ($this->helpClass !== null) {
            return call_user_func([$this->helpClass, 'getHelpFor'], $action);
        }

        return [];
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function accessDenied(): void
    {
        $this->addTracking();

        throw new ServiceException(
            __u('Unauthorized access'),
            SPException::ERROR,
            null,
            JsonRpcResponse::INTERNAL_ERROR
        );
    }

    /**
     * Sets up user's data in context and performs some user checks
     *
     * @throws SPException
     */
    private function setupUser(): void
    {
        $userLoginResponse = UserService::mapUserLoginResponse(
            $this->userService->getById($this->authTokenData->getUserId())
        );
        $userLoginResponse->getIsDisabled() && $this->accessDenied();

        $this->context->setUserData($userLoginResponse);
        $this->context->setUserProfile(
            $this->userProfileService->getById($userLoginResponse->getUserProfileId())->getProfile()
        );
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws ContextException
     */
    public function requireMasterPass(): void
    {
        $this->context->setTrasientKey('_masterpass', $this->getMasterPassFromVault());
    }

    /**
     * Devolver la clave maestra
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function getMasterPassFromVault(): string
    {
        try {
            $tokenPass = $this->getParam('tokenPass', true);

            Hash::checkHashKey($tokenPass, $this->authTokenData->getHash()) || $this->accessDenied();

            /** @var Vault $vault */
            $vault = unserialize($this->authTokenData->getVault());

            if ($vault && ($pass = $vault->getData($tokenPass.$this->getParam('authToken')))) {
                return $pass;
            }

            throw new ServiceException(
                __u('Internal error'),
                SPException::ERROR,
                __u('Invalid data'),
                JsonRpcResponse::INTERNAL_ERROR
            );
        } catch (CryptoException $e) {
            throw new ServiceException(
                __u('Internal error'),
                SPException::ERROR,
                $e->getMessage(),
                JsonRpcResponse::INTERNAL_ERROR
            );
        }
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function getParamInt(string $param, bool $required = false, $default = null): ?int
    {
        $value = $this->getParam($param, $required, $default);

        if (null !== $value) {
            return Filter::getInt($value);
        }

        return $default;
    }

    /**
     * @throws ServiceException
     */
    public function getParamString(string $param, bool $required = false, $default = null): ?string
    {
        $value = $this->getParam($param, $required, $default);

        if (null !== $value) {
            return Filter::getString($value);
        }

        return $default;
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function getParamArray(string $param, bool $required = false, $default = null): ?array
    {
        $value = $this->getParam($param, $required, $default);

        if (null !== $value) {
            return Filter::getArray($value);
        }

        return null;
    }

    /**
     * @throws ServiceException
     */
    public function getParamRaw(string $param, bool $required = false, $default = null): ?string
    {
        $value = $this->getParam($param, $required, $default);

        if (null !== $value) {
            return Filter::getRaw($value);
        }

        return $default;
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function getMasterPass(): string
    {
        return $this->getMasterKeyFromContext();
    }

    public function setApiRequest(ApiRequest $apiRequest): ApiServiceInterface
    {
        $this->apiRequest = $apiRequest;

        return $this;
    }

    public function getRequestId(): int
    {
        return $this->apiRequest->getId();
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * @throws InvalidClassException
     */
    public function setHelpClass(string $helpClass): void
    {
        if (class_exists($helpClass)) {
            $this->helpClass = $helpClass;

            return;
        }

        throw new InvalidClassException('Invalid class for helper');
    }
}
