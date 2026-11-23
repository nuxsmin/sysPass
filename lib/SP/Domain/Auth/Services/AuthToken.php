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
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Auth\Ports\AuthTokenRepository;
use SP\Domain\Auth\Ports\AuthTokenService;
use SP\Domain\Common\Providers\Password;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Crypt\Ports\HashService;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\DuplicatedItemException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Database\Dtos\QueryResult;
use Throwable;

use function SP\__u;

/**
 * Class AuthToken
 *
 * @template T of AuthTokenModel
 */
final class AuthToken extends Service implements AuthTokenService
{
    private ?string $lastGeneratedToken = null;

    /**
     * @param AuthTokenRepository<AuthToken> $authTokenRepository
     * @param CryptInterface $crypt
     * @param CustomFieldDataService<\SP\Domain\CustomField\Models\CustomFieldData> $customFieldDataService
     */
    public function __construct(
        ConfigFileService $config,
        EventDispatcherInterface $eventDispatcher,
        Context $context,
        private readonly AuthTokenRepository $authTokenRepository,
        private readonly CryptInterface      $crypt,
        private readonly MasterKeyStore      $masterKeyStore,
        private readonly HashService         $hashService,
        private readonly CustomFieldDataService $customFieldDataService
    ) {
        parent::__construct($config, $eventDispatcher, $context);
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
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws UnauthorizedActionException
     */
    public function getById(int $id): AuthTokenModel
    {
        $result = $this->authTokenRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }

        /** @var AuthTokenModel $authToken */
        $authToken = $result->getData(AuthTokenModel::class);
        $this->assertCanManage($authToken);

        return $authToken;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws UnauthorizedActionException
     * @throws Exception
     */
    public function delete(int $id): void
    {
        $this->authTokenRepository->transactionAware(
            function () use ($id): void {
                $this->getById($id);

                if ($this->authTokenRepository->delete($id)->getAffectedNumRows() === 0) {
                    throw new NoSuchItemException(__u('Token not found'));
                }

                $this->customFieldDataService->deleteByItem($id, AclActionsInterface::AUTHTOKEN);
            },
            $this
        );
    }

    /**
     * Deletes the given token IDs with per-token atomic outcomes.
     *
     * @param int[] $ids
     * @return array{deleted: int[], failed: array<int, string>}
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids): array
    {
        $uniqueIds = array_values(array_unique(array_map('intval', $ids)));

        if ($uniqueIds === []) {
            throw new ServiceException(__u('Error while removing the tokens'), SPException::WARNING);
        }

        $deleted = [];
        $failed = [];

        foreach ($uniqueIds as $id) {
            if ($id <= 0) {
                $failed[$id] = __u('Token not found');
                continue;
            }

            try {
                $this->delete($id);
                $deleted[] = $id;
            } catch (UnauthorizedActionException) {
                $failed[$id] = __u('You don\'t have permission to do this operation');
            } catch (NoSuchItemException) {
                $failed[$id] = __u('Token not found');
            } catch (Throwable $e) {
                $this->eventDispatcher->notify('exception', new Event($e));
                $failed[$id] = __u('Error while removing the tokens');
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
        ];
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
        $this->lastGeneratedToken = null;
        $this->assertCanManage($authToken);

        $expireDate = $this->resolveExpireDate($authToken, null, false);
        $token = $this->generateToken();
        $secureAuthToken = $this->injectSecureData($authToken, $token, null, $expireDate);
        $id = $this->authTokenRepository->create($secureAuthToken)->getLastId();
        $this->lastGeneratedToken = $token;

        return $id;
    }

    public function getLastGeneratedToken(): ?string
    {
        return $this->lastGeneratedToken;
    }

    /**
     * @throws UnauthorizedActionException
     */
    private function assertCanManage(AuthTokenModel $authToken): void
    {
        $userData = $this->context->getUserData();

        if (!$userData->isAdminApp && $authToken->getUserId() !== $userData->id) {
            throw new UnauthorizedActionException();
        }
    }

    /**
     * Injects secure data for token
     *
     * @throws CryptException
     * @throws ServiceException
     * @throws ValidationException
     */
    private function injectSecureData(
        AuthTokenModel $authToken,
        string $token,
        ?AuthTokenModel $existing = null,
        ?int $expireDate = null
    ): AuthTokenModel {
        if (AuthTokenModel::isKeyDependentAction((int)$authToken->getActionId())) {
            if (!empty($authToken->getHash())) {
                $properties = [
                    'vault' => $this->getSecureData($token, $authToken->getHash())->getSerialized(),
                    'hash' => $this->hashService->hashKey($authToken->getHash())
                ];
            } elseif ($existing !== null
                && $existing->getToken() === $token
                && !empty($existing->getVault())
                && !empty($existing->getHash())
            ) {
                $properties = [
                    'vault' => $existing->getVault(),
                    'hash' => $existing->getHash()
                ];
            } else {
                throw new ValidationException(__u('Password cannot be blank'));
            }
        } else {
            $properties = [
                'vault' => null,
                'hash' => null
            ];
        }

        $properties['token'] = $token;
        $properties['createdBy'] = $this->context->getUserData()->id;
        $properties['expireDate'] = $expireDate;

        return $authToken->mutate($properties);
    }

    /**
     * Resolves and validates token expiration timestamp according to system policy and caller role.
     *
     * @throws UnauthorizedActionException
     * @throws ValidationException
     */
    private function resolveExpireDate(
        AuthTokenModel  $authToken,
        ?AuthTokenModel $existing,
        bool            $actionChanged
    ): ?int {
        $now = time();
        $configData = $this->config->getConfigData();
        $defaultTime = $configData->getAuthTokenDefaultTime();
        $maxTime = $configData->getAuthTokenMaxTime();

        if ($maxTime <= 0) {
            throw new ValidationException(__u('Invalid maximum token lifetime configuration'));
        }

        $noExpiry = $authToken->getNoExpiry();
        $requestedExpireDate = $authToken->getExpireDate();
        $isAdminApp = $this->context->getUserData()->isAdminApp;

        if ($noExpiry === true) {
            if (!$isAdminApp) {
                throw new UnauthorizedActionException();
            }

            if ($requestedExpireDate !== null) {
                throw new ValidationException(__u('Invalid expiration date'));
            }

            return null;
        }

        if ($requestedExpireDate !== null) {
            if ($existing !== null
                && !$actionChanged
                && $requestedExpireDate === $existing->getExpireDate()
            ) {
                return $existing->getExpireDate();
            }

            if ($requestedExpireDate <= $now) {
                throw new ValidationException(__u('Invalid expiration date'));
            }

            if ($requestedExpireDate > $now + $maxTime) {
                throw new ValidationException(__u('Expiration date exceeds the maximum allowed lifetime'));
            }

            return $requestedExpireDate;
        }

        if ($existing !== null && $noExpiry === null) {
            $existingExpireDate = $existing->getExpireDate();

            if (!$actionChanged) {
                return $existingExpireDate;
            }

            if ($existingExpireDate !== null) {
                if ($existingExpireDate <= $now) {
                    throw new ValidationException(__u('Invalid expiration date'));
                }

                if ($existingExpireDate > $now + $maxTime) {
                    throw new ValidationException(__u('Expiration date exceeds the maximum allowed lifetime'));
                }

                return $existingExpireDate;
            }

            if ($isAdminApp) {
                return null;
            }
        }

        if ($defaultTime <= 0 || $defaultTime > $maxTime) {
            throw new ValidationException(__u('Default token lifetime exceeds the maximum allowed lifetime'));
        }

        return $now + $defaultTime;
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
                        $this->masterKeyStore->get(),
                        sha1($key . $token)
                    );
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
        $this->lastGeneratedToken = null;

        $this->authTokenRepository->transactionAware(
            function () use ($authToken): void {
                $existing = $this->getById((int)$authToken->getId());
                $userData = $this->context->getUserData();

                if (!$userData->isAdminApp && $authToken->getUserId() !== $userData->id) {
                    throw new UnauthorizedActionException();
                }

                $actionChanged = $existing->getActionId() !== $authToken->getActionId();
                $expireDate = $this->resolveExpireDate($authToken, $existing, $actionChanged);
                $token = $this->generateToken();
                $secureData = $this->injectSecureData($authToken, $token, $existing, $expireDate);

                $this->authTokenRepository->update($secureData);
                $this->lastGeneratedToken = $token;
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
     * @throws UnauthorizedActionException
     */
    public function update(AuthTokenModel $authToken): void
    {
        $this->lastGeneratedToken = null;

        $existing = $this->getById((int)$authToken->getId());
        $userData = $this->context->getUserData();

        if (!$userData->isAdminApp && $authToken->getUserId() !== $userData->id) {
            throw new UnauthorizedActionException();
        }

        $actionChanged = $existing->getActionId() !== $authToken->getActionId();
        $expireDate = $this->resolveExpireDate($authToken, $existing, $actionChanged);
        $token = $actionChanged ? $this->generateToken() : (string)$existing->getToken();

        $secureAuthToken = $this->injectSecureData($authToken, $token, $existing, $expireDate);

        $this->authTokenRepository->update($secureAuthToken);

        $this->lastGeneratedToken = $actionChanged ? $token : null;
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

        /** @var AuthTokenModel $authToken */
        $authToken = $result->getData(AuthTokenModel::class);
        $expireDate = $authToken->getExpireDate();

        if ($expireDate !== null && ($expireDate <= 0 || $expireDate <= time())) {
            throw new NoSuchItemException(__u('Token not found'));
        }

        return $authToken;
    }

    /**
     * @return array<T>
     */
    public function getAll(): array
    {
        return $this->authTokenRepository->getAll()->getDataAsArray(AuthTokenModel::class);
    }
}
