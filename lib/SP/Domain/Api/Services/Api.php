<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use Exception;
use SP\Core\Application;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\DataModel\ProfileData;
use SP\Domain\Api\Ports\ApiRequestService;
use SP\Domain\Api\Ports\ApiService;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Auth\Ports\AuthTokenService;
use SP\Domain\Auth\Services\AuthToken;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Api\Controllers\Help\HelpInterface;
use SP\Util\Filter;

use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class Api
 */
final class Api extends Service implements ApiService
{
    private TrackRequest    $trackRequest;
    private ?AuthTokenModel $authToken = null;
    private ?string         $helpClass = null;
    private ?ApiStatuses $status = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        Application                                  $application,
        private readonly TrackService      $trackService,
        private readonly ApiRequestService $apiRequest,
        private readonly AuthTokenService  $authTokenService,
        private readonly UserServiceInterface        $userService,
        private readonly UserProfileServiceInterface $userProfileService
    ) {
        parent::__construct($application);

        $this->trackRequest = $trackService->buildTrackRequest(__CLASS__);
    }

    /**
     * Sets up API
     *
     * @throws ServiceException
     * @throws SPException
     * @throws Exception
     */
    public function setup(int $actionId): void
    {
        $this->status = ApiStatuses::INITIALIZING;

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
            $this->authToken = $this->authTokenService
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

        if ($this->authToken->getActionId() !== $actionId) {
            $this->accessDenied();
        }

        $this->setupUser();

        if (AuthToken::isSecuredAction($actionId)) {
            $this->requireMasterPass();
        }

        $this->status = ApiStatuses::INITIALIZED;
    }

    /**
     * Añadir un seguimiento
     *
     * @throws ServiceException
     */
    private function addTracking(): void
    {
        try {
            $this->trackService->add($this->trackRequest);
        } catch (Exception $e) {
            processException($e);

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
     * @param string $param
     * @param bool $required Si es requerido
     * @param mixed|null $default Valor por defecto
     *
     * @return mixed
     * @throws ServiceException
     */
    public function getParam(string $param, bool $required = false, mixed $default = null): mixed
    {
        if ($required && !$this->apiRequest->exists($param)) {
            throw new ServiceException(
                __u('Wrong parameters'),
                SPException::ERROR,
                join(PHP_EOL, $this->getHelp($this->apiRequest->getMethod())),
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
    private function getHelp(string $action): array
    {
        if ($this->helpClass !== null) {
            return call_user_func([$this->helpClass, 'getHelpFor'], $action);
        }

        return [];
    }

    /**
     * @throws ServiceException
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
        $userLoginResponse = new UserDataDto(
            $this->userService->getById($this->authToken->getUserId())
        );
        $userLoginResponse->getIsDisabled() && $this->accessDenied();

        $this->context->setUserData($userLoginResponse);
        $this->context->setUserProfile(
            $this->userProfileService
                ->getById($userLoginResponse->getUserProfileId())
                ->hydrate(ProfileData::class)
        );
    }

    /**
     * @throws ContextException
     * @throws ServiceException
     */
    public function requireMasterPass(): void
    {
        $this->context->setTrasientKey(ContextInterface::MASTER_PASSWORD_KEY, $this->getMasterPassFromVault());
    }

    /**
     * Devolver la clave maestra
     *
     * @throws ServiceException
     */
    private function getMasterPassFromVault(): string
    {
        $this->requireInitialized();

        try {
            $tokenPass = $this->getParam('tokenPass', true);

            Hash::checkHashKey($tokenPass, $this->authToken->getHash()) || $this->accessDenied();

            /** @var VaultInterface $vault */
            $vault = unserialize($this->authToken->getVault(), ['allowed_classes' => [Vault::class, Crypt::class]]);

            $key = sha1($tokenPass . $this->getParam('authToken'));

            if ($vault && ($pass = $vault->getData($key))) {
                return $pass;
            }

            throw new ServiceException(
                __u('Internal error'),
                SPException::ERROR,
                __u('Invalid data'),
                JsonRpcResponse::INTERNAL_ERROR
            );
        } catch (CryptException $e) {
            throw new ServiceException(
                __u('Internal error'),
                SPException::ERROR,
                $e->getMessage(),
                JsonRpcResponse::INTERNAL_ERROR
            );
        }
    }

    /**
     * @throws ServiceException
     */
    private function requireInitialized(): void
    {
        if ($this->status === null) {
            throw new ServiceException(
                __u('API not initialized'),
                SPException::ERROR,
                __u('Please run setup method before'),
                JsonRpcResponse::INTERNAL_ERROR
            );
        }
    }

    /**
     * @throws ServiceException
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
     * @throws ServiceException
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
            return $value;
        }

        return $default;
    }

    /**
     * @return string
     * @throws CryptException
     * @throws ServiceException
     */
    public function getMasterPass(): string
    {
        $this->requireInitialized();

        return $this->getMasterKeyFromContext();
    }

    public function getRequestId(): int
    {
        return $this->apiRequest->getId();
    }

    /**
     * @throws InvalidClassException
     */
    public function setHelpClass(string $helpClass): void
    {
        if (class_exists($helpClass) && is_subclass_of($helpClass, HelpInterface::class)) {
            $this->helpClass = $helpClass;

            return;
        }

        throw new InvalidClassException('Invalid class for helper');
    }
}
