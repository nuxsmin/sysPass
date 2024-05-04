<?php
declare(strict_types=1);
/**
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

namespace SP\Domain\Auth\Services;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Auth\Ports\AuthTokenRepository;
use SP\Domain\Auth\Ports\AuthTokenService;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Common\Providers\Password;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class AuthToken
 *
 * @template T of AuthTokenModel
 */
final class AuthToken extends Service implements AuthTokenService
{
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

    /**
     * @param Application $application
     * @param AuthTokenRepository<AuthToken> $authTokenRepository
     * @param CryptInterface $crypt
     */
    public function __construct(
        Application                          $application,
        private readonly AuthTokenRepository $authTokenRepository,
        private readonly CryptInterface      $crypt
    ) {
        parent::__construct($application);
    }

    /**
     * @param ItemSearchDto $itemSearchData
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->authTokenRepository->search($itemSearchData);
    }

    /**
     * @param int $id
     * @return AuthTokenModel
     */
    public function getById(int $id): AuthTokenModel
    {
        return $this->authTokenRepository->getById($id)->getData(AuthTokenModel::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): void
    {
        if ($this->authTokenRepository->delete($id)->getAffectedNumRows() === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }
    }

    /**
     * Deletes all the items for given ids
     *
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): void
    {
        if ($this->authTokenRepository->deleteByIdBatch($ids)->getAffectedNumRows() === 0) {
            throw new ServiceException(__u('Error while removing the tokens'), SPException::WARNING);
        }
    }

    /**
     * @throws SPException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(AuthTokenModel $authToken): int
    {
        $secureAuthToken = $this->injectSecureData($authToken, $this->getOrBuildToken($authToken));

        return $this->authTokenRepository->create($secureAuthToken)->getLastId();
    }

    /**
     * Injects secure data for token
     *
     * @throws CryptException
     * @throws ServiceException
     */
    private function injectSecureData(AuthTokenModel $authToken, string $token): AuthTokenModel
    {
        if (self::isSecuredAction($authToken->getActionId())
            || self::canUseSecureTokenAction($authToken->getActionId())
        ) {
            $properties = [
                'vault' => $this->getSecureData($token, $authToken->getHash())->getSerialized(),
                'hash' => Hash::hashKey($authToken->getHash())
            ];
        } else {
            $properties = [
                'hash' => null
            ];
        }

        $properties['token'] = $token;
        $properties['createdBy'] = $this->context->getUserData()->getId();

        return $authToken->mutate($properties);
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
     * @throws CryptException
     */
    private function getSecureData(string $token, string $key): VaultInterface
    {
        return Vault::factory($this->crypt)
                    ->saveData(
                        $this->getMasterKeyFromContext(),
                        $key . $token
                    );
    }

    /**
     * @param AuthTokenModel $authToken
     * @return string|null
     * @throws EnvironmentIsBrokenException
     * @throws SPException
     */
    private function getOrBuildToken(AuthTokenModel $authToken): ?string
    {
        $currentToken = $this->authTokenRepository->getTokenByUserId($authToken->getUserId());

        return match ($currentToken->getNumRows()) {
            1 => $currentToken->getData(AuthTokenModel::class)->getToken(),
            0 => $this->generateToken()
        };
    }

    /**
     * Generar un token de acceso
     *
     * @throws EnvironmentIsBrokenException
     */
    private function generateToken(): string
    {
        return Password::generateRandomBytes(32);
    }

    /**
     * @throws Exception
     */
    public function refreshAndUpdate(AuthTokenModel $authToken): void
    {
        $this->authTokenRepository->transactionAware(
            function () use ($authToken) {
                $token = $this->generateToken();
                $vault = Serde::serialize($this->getSecureData($token, $authToken->getHash()));

                $this->authTokenRepository->refreshTokenByUserId(
                    $authToken->getUserId(),
                    $token
                );
                $this->authTokenRepository->refreshVaultByUserId(
                    $authToken->getUserId(),
                    $vault,
                    Hash::hashKey($authToken->getHash())
                );

                $secureData = $this->injectSecureData($authToken, $token);

                $this->authTokenRepository->update($secureData);
            },
            $this
        );
    }

    /**
     * @throws ConstraintException
     * @throws CryptException
     * @throws DuplicatedItemException
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    public function update(AuthTokenModel $authToken): void
    {
        $secureAuthToken = $this->injectSecureData($authToken, $this->getOrBuildToken($authToken));

        $this->authTokenRepository->update($secureAuthToken);
    }

    /**
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateRaw(AuthTokenModel $authToken): void
    {
        $this->authTokenRepository->update($authToken);
    }

    /**
     * Devolver los datos de un token
     *
     * @param int $actionId
     * @param string $token
     * @return AuthTokenModel
     * @throws NoSuchItemException
     */
    public function getTokenByToken(int $actionId, string $token): AuthTokenModel
    {
        $result = $this->authTokenRepository->getTokenByToken($actionId, $token);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }

        return $result->getData(AuthTokenModel::class);
    }

    /**
     * @return array<T>
     */
    public function getAll(): array
    {
        return $this->authTokenRepository->getAll()->getDataAsArray(AuthTokenModel::class);
    }
}
