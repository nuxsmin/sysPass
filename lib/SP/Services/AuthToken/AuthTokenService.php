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

    const SECURED_ACTIONS = [
        ActionsInterface::ACCOUNT_VIEW_PASS,
        ActionsInterface::ACCOUNT_EDIT_PASS,
        ActionsInterface::ACCOUNT_CREATE
    ];

    /**
     * @var AuthTokenRepository
     */
    protected $authTokenRepository;

    /**
     * Devuelver un array de acciones posibles para los tokens
     *
     * @return array
     */
    public static function getTokenActions()
    {
        $actions = [
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

        return $actions;
    }

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->authTokenRepository->search($itemSearchData);
    }

    /**
     * @param $id
     *
     * @return AuthTokenData
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById($id)
    {
        return $this->authTokenRepository->getById($id)->getData();
    }

    /**
     * @param $id
     *
     * @return AuthTokenService
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete($id)
    {
        if ($this->authTokenRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return bool
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        if (($count = $this->authTokenRepository->deleteByIdBatch($ids)) !== count($ids)) {
            throw new ServiceException(__u('Error while removing the tokens'), ServiceException::WARNING);
        }

        return $count;
    }

    /**
     * @param $itemData
     *
     * @return mixed
     * @throws SPException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData)
    {
        return $this->authTokenRepository->create($this->injectSecureData($itemData));
    }

    /**
     * Injects secure data for token
     *
     * @param AuthTokenData $authTokenData
     * @param string        $token
     *
     * @return AuthTokenData
     * @throws ServiceException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    private function injectSecureData(AuthTokenData $authTokenData, $token = null)
    {
        if ($token === null) {
            $token = $this->authTokenRepository->getTokenByUserId($authTokenData->getUserId()) ?: $this->generateToken();
        }

        if (self::isSecuredAction($authTokenData->getActionId())) {
            $authTokenData->setVault($this->getSecureData($token, $authTokenData->getHash()));
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
     * @return string
     * @throws EnvironmentIsBrokenException
     */
    private function generateToken()
    {
        return PasswordUtil::generateRandomBytes(32);
    }

    /**
     * @param int $action
     *
     * @return bool
     */
    public static function isSecuredAction(int $action)
    {
        return in_array($action, self::SECURED_ACTIONS, true);
    }

    /**
     * Generar la llave segura del token
     *
     * @param string $token
     * @param string $key
     *
     * @return Vault
     * @throws ServiceException
     * @throws CryptoException
     */
    private function getSecureData($token, $key)
    {
        return (new Vault())->saveData($this->getMasterKeyFromContext(), $key . $token);
    }

    /**
     * @param AuthTokenData $itemData
     *
     * @throws Exception
     */
    public function refreshAndUpdate(AuthTokenData $itemData)
    {
        $this->transactionAware(function () use ($itemData) {
            $token = $this->generateToken();
            $vault = serialize($this->getSecureData($token, $itemData->getHash()));

            $this->authTokenRepository->refreshTokenByUserId($itemData->getUserId(), $token);
            $this->authTokenRepository->refreshVaultByUserId($itemData->getUserId(), $vault, Hash::hashKey($itemData->getHash()));

            $this->update($itemData, $token);
        });
    }

    /**
     * @param AuthTokenData $itemData
     * @param string        $token
     *
     * @throws SPException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(AuthTokenData $itemData, $token = null)
    {
        if ($this->authTokenRepository->update($this->injectSecureData($itemData, $token)) === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }
    }

    /**
     * @param AuthTokenData $itemData
     *
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateRaw(AuthTokenData $itemData)
    {
        if ($this->authTokenRepository->update($itemData) === 0) {
            throw new NoSuchItemException(__u('Token not found'));
        }
    }

    /**
     * Devolver los datos de un token
     *
     * @param $actionId int El id de la accion
     * @param $token    string El token de seguridad
     *
     * @return false|AuthTokenData
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function getTokenByToken($actionId, $token)
    {
        $result = $this->authTokenRepository->getTokenByToken($actionId, $token);

        if ($result->getNumRows() === 0) {
            throw new ServiceException(__u('Internal error'));
        }

        return $result->getData();
    }

    /**
     * @return AuthTokenData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAllBasic()
    {
        return $this->authTokenRepository->getAll()->getDataAsArray();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->authTokenRepository = $this->dic->get(AuthTokenRepository::class);
    }
}