<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\AuthToken;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AuthTokenData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\AuthToken\AuthTokenRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;
use SP\Storage\Database\QueryResult;
use SP\Util\PasswordUtil;

/**
 * Class AuthTokenService
 *
 * @package SP\Services\AuthToken
 */
final class AuthTokenService extends Service
{
    use ServiceItemTrait;

    private const SECURED_ACTIONS = [
        ActionsInterface::ACCOUNT_VIEW_PASS,
        ActionsInterface::ACCOUNT_EDIT_PASS,
        ActionsInterface::ACCOUNT_CREATE
    ];

    private const CAN_USE_SECURE_TOKEN_ACTIONS = [
        ActionsInterface::ACCOUNT_VIEW,
        ActionsInterface::CATEGORY_VIEW,
        ActionsInterface::CLIENT_VIEW,
    ];

    protected ?AuthTokenRepository $authTokenRepository = null;

    /**
     * Devuelver un array de acciones posibles para los tokens
     *
     * @return array
     */
    public static function getTokenActions(): array
    {
        return [
            ActionsInterface::ACCOUNT_SEARCH => Acl::getActionInfo(ActionsInterface::ACCOUNT_SEARCH),
            ActionsInterface::ACCOUNT_VIEW => Acl::getActionInfo(ActionsInterface::ACCOUNT_VIEW),
            ActionsInterface::ACCOUNT_VIEW_PASS => Acl::getActionInfo(ActionsInterface::ACCOUNT_VIEW_PASS),
            ActionsInterface::ACCOUNT_EDIT_PASS => Acl::getActionInfo(ActionsInterface::ACCOUNT_EDIT_PASS),
            ActionsInterface::ACCOUNT_DELETE => Acl::getActionInfo(ActionsInterface::ACCOUNT_DELETE),
            ActionsInterface::ACCOUNT_CREATE => Acl::getActionInfo(ActionsInterface::ACCOUNT_CREATE),
            ActionsInterface::ACCOUNT_EDIT => Acl::getActionInfo(ActionsInterface::ACCOUNT_EDIT),
            ActionsInterface::CATEGORY_SEARCH => Acl::getActionInfo(ActionsInterface::CATEGORY_SEARCH),
            ActionsInterface::CATEGORY_VIEW => Acl::getActionInfo(ActionsInterface::CATEGORY_VIEW),
            ActionsInterface::CATEGORY_CREATE => Acl::getActionInfo(ActionsInterface::CATEGORY_CREATE),
            ActionsInterface::CATEGORY_EDIT => Acl::getActionInfo(ActionsInterface::CATEGORY_EDIT),
            ActionsInterface::CATEGORY_DELETE => Acl::getActionInfo(ActionsInterface::CATEGORY_DELETE),
            ActionsInterface::CLIENT_SEARCH => Acl::getActionInfo(ActionsInterface::CLIENT_SEARCH),
            ActionsInterface::CLIENT_VIEW => Acl::getActionInfo(ActionsInterface::CLIENT_VIEW),
            ActionsInterface::CLIENT_CREATE => Acl::getActionInfo(ActionsInterface::CLIENT_CREATE),
            ActionsInterface::CLIENT_EDIT => Acl::getActionInfo(ActionsInterface::CLIENT_EDIT),
            ActionsInterface::CLIENT_DELETE => Acl::getActionInfo(ActionsInterface::CLIENT_DELETE),
            ActionsInterface::TAG_SEARCH => Acl::getActionInfo(ActionsInterface::TAG_SEARCH),
            ActionsInterface::TAG_VIEW => Acl::getActionInfo(ActionsInterface::TAG_VIEW),
            ActionsInterface::TAG_CREATE => Acl::getActionInfo(ActionsInterface::TAG_CREATE),
            ActionsInterface::TAG_EDIT => Acl::getActionInfo(ActionsInterface::TAG_EDIT),
            ActionsInterface::TAG_DELETE => Acl::getActionInfo(ActionsInterface::TAG_DELETE),
            ActionsInterface::GROUP_VIEW => Acl::getActionInfo(ActionsInterface::GROUP_VIEW),
            ActionsInterface::GROUP_CREATE => Acl::getActionInfo(ActionsInterface::GROUP_CREATE),
            ActionsInterface::GROUP_EDIT => Acl::getActionInfo(ActionsInterface::GROUP_EDIT),
            ActionsInterface::GROUP_DELETE => Acl::getActionInfo(ActionsInterface::GROUP_DELETE),
            ActionsInterface::GROUP_SEARCH => Acl::getActionInfo(ActionsInterface::GROUP_SEARCH),
            ActionsInterface::CONFIG_BACKUP_RUN => Acl::getActionInfo(ActionsInterface::CONFIG_BACKUP_RUN),
            ActionsInterface::CONFIG_EXPORT_RUN => Acl::getActionInfo(ActionsInterface::CONFIG_EXPORT_RUN)
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById(int $id): AuthTokenData
    {
        return $this->authTokenRepository->getById($id)->getData();
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
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
    public function create($itemData): int
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
    private function injectSecureData(
        AuthTokenData $authTokenData,
        ?string       $token = null
    ): AuthTokenData
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
    private function getSecureData(string $token, string $key): Vault
    {
        return (new Vault())->saveData(
            $this->getMasterKeyFromContext(),
            $key . $token
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
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\DuplicatedItemException
     * @throws \SP\Repositories\NoSuchItemException
     * @throws \SP\Services\ServiceException
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize(): void
    {
        $this->authTokenRepository = $this->dic->get(AuthTokenRepository::class);
    }
}