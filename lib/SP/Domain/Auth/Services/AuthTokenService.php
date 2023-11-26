<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Auth\Services;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\DataModel\AuthTokenData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Auth\Ports\AuthTokenServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Common\Services\ServiceItemTrait;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Auth\Repositories\AuthTokenRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Util\PasswordUtil;

/**
 * Class AuthTokenService
 *
 * @package SP\Domain\Common\Services\AuthToken
 */
final class AuthTokenService extends Service implements AuthTokenServiceInterface
{
    use ServiceItemTrait;

    private const SECURED_ACTIONS = [
        AclActionsInterface::ACCOUNT_VIEW_PASS,
        AclActionsInterface::ACCOUNT_EDIT_PASS,
        AclActionsInterface::ACCOUNT_CREATE,
    ];

    private const CAN_USE_SECURE_TOKEN_ACTIONS = [
        AclActionsInterface::ACCOUNT_VIEW,
        AclActionsInterface::CATEGORY_VIEW,
        AclActionsInterface::CLIENT_VIEW,
    ];

    private AuthTokenRepository $authTokenRepository;

    public function __construct(Application $application, AuthTokenRepository $authTokenRepository)
    {
        parent::__construct($application);

        $this->authTokenRepository = $authTokenRepository;
    }


    /**
     * Devuelver un array de acciones posibles para los tokens
     *
     * @return array
     */
    public static function getTokenActions(): array
    {
        return [
            AclActionsInterface::ACCOUNT_SEARCH    => Acl::getActionInfo(AclActionsInterface::ACCOUNT_SEARCH),
            AclActionsInterface::ACCOUNT_VIEW      => Acl::getActionInfo(AclActionsInterface::ACCOUNT_VIEW),
            AclActionsInterface::ACCOUNT_VIEW_PASS => Acl::getActionInfo(AclActionsInterface::ACCOUNT_VIEW_PASS),
            AclActionsInterface::ACCOUNT_EDIT_PASS => Acl::getActionInfo(AclActionsInterface::ACCOUNT_EDIT_PASS),
            AclActionsInterface::ACCOUNT_DELETE    => Acl::getActionInfo(AclActionsInterface::ACCOUNT_DELETE),
            AclActionsInterface::ACCOUNT_CREATE    => Acl::getActionInfo(AclActionsInterface::ACCOUNT_CREATE),
            AclActionsInterface::ACCOUNT_EDIT      => Acl::getActionInfo(AclActionsInterface::ACCOUNT_EDIT),
            AclActionsInterface::CATEGORY_SEARCH   => Acl::getActionInfo(AclActionsInterface::CATEGORY_SEARCH),
            AclActionsInterface::CATEGORY_VIEW     => Acl::getActionInfo(AclActionsInterface::CATEGORY_VIEW),
            AclActionsInterface::CATEGORY_CREATE   => Acl::getActionInfo(AclActionsInterface::CATEGORY_CREATE),
            AclActionsInterface::CATEGORY_EDIT     => Acl::getActionInfo(AclActionsInterface::CATEGORY_EDIT),
            AclActionsInterface::CATEGORY_DELETE   => Acl::getActionInfo(AclActionsInterface::CATEGORY_DELETE),
            AclActionsInterface::CLIENT_SEARCH     => Acl::getActionInfo(AclActionsInterface::CLIENT_SEARCH),
            AclActionsInterface::CLIENT_VIEW       => Acl::getActionInfo(AclActionsInterface::CLIENT_VIEW),
            AclActionsInterface::CLIENT_CREATE     => Acl::getActionInfo(AclActionsInterface::CLIENT_CREATE),
            AclActionsInterface::CLIENT_EDIT       => Acl::getActionInfo(AclActionsInterface::CLIENT_EDIT),
            AclActionsInterface::CLIENT_DELETE     => Acl::getActionInfo(AclActionsInterface::CLIENT_DELETE),
            AclActionsInterface::TAG_SEARCH        => Acl::getActionInfo(AclActionsInterface::TAG_SEARCH),
            AclActionsInterface::TAG_VIEW          => Acl::getActionInfo(AclActionsInterface::TAG_VIEW),
            AclActionsInterface::TAG_CREATE        => Acl::getActionInfo(AclActionsInterface::TAG_CREATE),
            AclActionsInterface::TAG_EDIT          => Acl::getActionInfo(AclActionsInterface::TAG_EDIT),
            AclActionsInterface::TAG_DELETE        => Acl::getActionInfo(AclActionsInterface::TAG_DELETE),
            AclActionsInterface::GROUP_VIEW        => Acl::getActionInfo(AclActionsInterface::GROUP_VIEW),
            AclActionsInterface::GROUP_CREATE      => Acl::getActionInfo(AclActionsInterface::GROUP_CREATE),
            AclActionsInterface::GROUP_EDIT        => Acl::getActionInfo(AclActionsInterface::GROUP_EDIT),
            AclActionsInterface::GROUP_DELETE      => Acl::getActionInfo(AclActionsInterface::GROUP_DELETE),
            AclActionsInterface::GROUP_SEARCH      => Acl::getActionInfo(AclActionsInterface::GROUP_SEARCH),
            AclActionsInterface::CONFIG_BACKUP_RUN => Acl::getActionInfo(AclActionsInterface::CONFIG_BACKUP_RUN),
            AclActionsInterface::CONFIG_EXPORT_RUN => Acl::getActionInfo(AclActionsInterface::CONFIG_EXPORT_RUN),
        ];
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->authTokenRepository->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): AuthTokenData
    {
        return $this->authTokenRepository->getById($id)->getData();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): AuthTokenService
    {
        if ($this->authTokenRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $count = $this->authTokenRepository->deleteByIdBatch($ids);

        if ($count !== count($ids)) {
            throw new ServiceException(
                __u('Error while removing the tokens'),
                SPException::WARNING
            );
        }

        return $count;
    }

    /**
     * @throws SPException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(AuthTokenData $itemData): int
    {
        return $this->authTokenRepository->create($this->injectSecureData($itemData));
    }

    /**
     * Injects secure data for token
     *
     * @throws ServiceException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    private function injectSecureData(AuthTokenData $authTokenData, ?string $token = null): AuthTokenData
    {
        if ($token === null) {
            $token = $this->authTokenRepository
                ->getTokenByUserId($authTokenData->getUserId()) ?: $this->generateToken();
        }

        if (self::isSecuredAction($authTokenData->getActionId())
            || self::canUseSecureTokenAction($authTokenData->getActionId())
        ) {
            $authTokenData->setVault(
                $this->getSecureData($token, $authTokenData->getHash())
            );
            $authTokenData->setHash(Hash::hashKey($authTokenData->getHash()));
        } else {
            $authTokenData->setHash(null);
        }

        $authTokenData->setToken($token);
        $authTokenData->setCreatedBy($this->context->getUserData()->getId());

        return $authTokenData;
    }

    /**
     * Generar un token de acceso
     *
     * @throws EnvironmentIsBrokenException
     */
    private function generateToken(): string
    {
        return PasswordUtil::generateRandomBytes(32);
    }

    public static function isSecuredAction(int $action): bool
    {
        return in_array($action, self::SECURED_ACTIONS, true);
    }

    public static function canUseSecureTokenAction(int $action): bool
    {
        return in_array($action, self::CAN_USE_SECURE_TOKEN_ACTIONS, true);
    }

    /**
     * Generar la llave segura del token
     *
     * @throws ServiceException
     * @throws CryptoException
     */
    private function getSecureData(string $token, string $key): VaultInterface
    {
        return (new Vault())->saveData(
            $this->getMasterKeyFromContext(),
            $key.$token
        );
    }

    /**
     * @throws Exception
     */
    public function refreshAndUpdate(AuthTokenData $itemData): void
    {
        $this->transactionAware(
            function () use ($itemData) {
                $token = $this->generateToken();
                $vault = serialize(
                    $this->getSecureData($token, $itemData->getHash())
                );

                $this->authTokenRepository->refreshTokenByUserId(
                    $itemData->getUserId(),
                    $token
                );
                $this->authTokenRepository->refreshVaultByUserId(
                    $itemData->getUserId(),
                    $vault,
                    Hash::hashKey($itemData->getHash())
                );

                $this->update($itemData, $token);
            }
        );
    }

    /**
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function update(AuthTokenData $itemData, ?string $token = null): void
    {
        if ($this->authTokenRepository->update($this->injectSecureData($itemData, $token)) === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }
    }

    /**
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateRaw(AuthTokenData $itemData): void
    {
        if ($this->authTokenRepository->update($itemData) === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }
    }

    /**
     * Devolver los datos de un token
     *
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function getTokenByToken(int $actionId, string $token)
    {
        $result = $this->authTokenRepository->getTokenByToken($actionId, $token);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }

        return $result->getData();
    }

    /**
     * @return AuthTokenData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic(): array
    {
        return $this->authTokenRepository->getAll()->getDataAsArray();
    }
}
