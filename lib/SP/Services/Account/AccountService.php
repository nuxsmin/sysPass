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

namespace SP\Services\Account;

use Defuse\Crypto\Exception\CryptoException;
use SP\Account\AccountRequest;
use SP\Account\AccountSearchFilter;
use SP\Account\AccountUtil;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountData;
use SP\DataModel\AccountPassData;
use SP\DataModel\Dto\AccountDetailsResponse;
use SP\DataModel\Dto\AccountSearchResponse;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Account\AccountRepository;
use SP\Repositories\Account\AccountToTagRepository;
use SP\Repositories\Account\AccountToUserGroupRepository;
use SP\Repositories\Account\AccountToUserRepository;
use SP\Services\Config\ConfigService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\ServiceItemTrait;

/**
 * Class AccountService
 *
 * @package SP\Services\Account
 */
class AccountService extends Service implements AccountServiceInterface
{
    use ServiceItemTrait;

    /**
     * @var AccountRepository
     */
    protected $accountRepository;
    /**
     * @var AccountToUserGroupRepository
     */
    protected $accountToUserGroupRepository;
    /**
     * @var AccountToUserRepository
     */
    protected $accountToUserRepository;
    /**
     * @var AccountToTagRepository
     */
    protected $accountToTagRepository;

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function initialize()
    {
        $this->accountRepository = $this->dic->get(AccountRepository::class);
        $this->accountToUserRepository = $this->dic->get(AccountToUserRepository::class);
        $this->accountToUserGroupRepository = $this->dic->get(AccountToUserGroupRepository::class);
        $this->accountToTagRepository = $this->dic->get(AccountToTagRepository::class);
    }

    /**
     * @param int $id
     *
     * @return AccountDetailsResponse
     * @throws QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function getById($id)
    {
        return new AccountDetailsResponse($id, $this->accountRepository->getById($id));
    }

    /**
     * @param AccountDetailsResponse $accountDetailsResponse
     * @return AccountService
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function withUsersById(AccountDetailsResponse $accountDetailsResponse)
    {
        $accountDetailsResponse->setUsers($this->accountToUserRepository->getUsersByAccountId($accountDetailsResponse->getId()));

        return $this;
    }

    /**
     * @param AccountDetailsResponse $accountDetailsResponse
     * @return AccountService
     */
    public function withUserGroupsById(AccountDetailsResponse $accountDetailsResponse)
    {
        $accountDetailsResponse->setUserGroups($this->accountToUserGroupRepository->getUserGroupsByAccountId($accountDetailsResponse->getId()));

        return $this;
    }

    /**
     * @param AccountDetailsResponse $accountDetailsResponse
     * @return AccountService
     */
    public function withTagsById(AccountDetailsResponse $accountDetailsResponse)
    {
        $accountDetailsResponse->setTags($this->accountToTagRepository->getTagsByAccountId($accountDetailsResponse->getId()));

        return $this;
    }

    /**
     * @param $id
     * @return bool
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function incrementViewCounter($id)
    {
        return $this->accountRepository->incrementViewCounter($id);
    }

    /**
     * @param $id
     * @return bool
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function incrementDecryptCounter($id)
    {
        return $this->accountRepository->incrementDecryptCounter($id);
    }

    /**
     * @param $id
     * @return \SP\DataModel\AccountPassData
     */
    public function getPasswordForId($id)
    {
        return $this->accountRepository->getPasswordForId($id);
    }

    /**
     * @param AccountRequest $accountRequest
     * @return int
     * @throws QueryException
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function create(AccountRequest $accountRequest)
    {
        $accountRequest->changePermissions = AccountAclService::getShowPermission($this->context->getUserData(), $this->context->getUserProfile());
        $accountRequest->userGroupId = $accountRequest->userGroupId ?: $this->context->getUserData()->getUserGroupId();

        if (empty($accountRequest->key)) {
            $pass = $this->getPasswordEncrypted($accountRequest->pass);

            $accountRequest->pass = $pass['pass'];
            $accountRequest->key = $pass['key'];
        }

        $accountRequest->id = $this->accountRepository->create($accountRequest);

        $this->addItems($accountRequest);

        return $accountRequest->id;
    }

    /**
     * Devolver los datos de la clave encriptados
     *
     * @param string $pass
     * @param string $masterPass Clave maestra a utilizar
     * @return array
     * @throws ServiceException
     */
    public function getPasswordEncrypted($pass, $masterPass = null)
    {
        try {
            $masterPass = $masterPass ?: CryptSession::getSessionKey($this->context);

            $out['key'] = Crypt::makeSecuredKey($masterPass);
            $out['pass'] = Crypt::encrypt($pass, $out['key'], $masterPass);

            if (strlen($pass) > 1000 || strlen($out['key']) > 1000) {
                throw new ServiceException(__u('Error interno'), SPException::ERROR);
            }

            return $out;
        } catch (CryptoException $e) {
            throw new ServiceException(__u('Error interno'), SPException::ERROR);
        }
    }

    /**
     * Adds external items to the account
     *
     * @param AccountRequest $accountRequest
     */
    protected function addItems(AccountRequest $accountRequest)
    {
        try {
            if ($accountRequest->changePermissions) {
                if (is_array($accountRequest->userGroupsView) && !empty($accountRequest->userGroupsView)) {
                    $this->accountToUserGroupRepository->add($accountRequest);
                }

                if (is_array($accountRequest->userGroupsEdit) && !empty($accountRequest->userGroupsEdit)) {
                    $this->accountToUserGroupRepository->addEdit($accountRequest);
                }

                if (is_array($accountRequest->usersView) && !empty($accountRequest->usersView)) {
                    $this->accountToUserRepository->add($accountRequest);
                }

                if (is_array($accountRequest->usersEdit) && !empty($accountRequest->usersEdit)) {
                    $this->accountToUserRepository->add($accountRequest);
                }
            }

            if (is_array($accountRequest->tags) && !empty($accountRequest->tags)) {
                $this->accountToTagRepository->add($accountRequest);
            }
        } catch (SPException $e) {
            debugLog($e->getMessage());
        }
    }

    /**
     * Updates external items for the account
     *
     * @param AccountRequest $accountRequest
     * @throws QueryException
     * @throws SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    public function update(AccountRequest $accountRequest)
    {
        $accountRequest->changePermissions = AccountAclService::getShowPermission($this->context->getUserData(), $this->context->getUserProfile());

        // Cambiar el grupo principal si el usuario es Admin
        $accountRequest->changeUserGroup = ($accountRequest->userGroupId !== 0
            && ($this->context->getUserData()->getIsAdminApp() || $this->context->getUserData()->getIsAdminAcc()));

        $this->addHistory($accountRequest->id);

        $this->accountRepository->update($accountRequest);

        $this->updateItems($accountRequest);
    }

    /**
     * @param int  $accountId
     * @param bool $isDelete
     * @return bool
     * @throws QueryException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    protected function addHistory($accountId, $isDelete = false)
    {
        $accountHistoryRepository = $this->dic->get(AccountHistoryService::class);
        $configService = $this->dic->get(ConfigService::class);

        return $accountHistoryRepository->create([
            'id' => $accountId,
            'isDelete' => (int)$isDelete,
            'isModify' => (int)!$isDelete,
            'masterPassHash' => $configService->getByParam('masterPwd')
        ]);
    }

    /**
     * Updates external items for the account
     *
     * @param AccountRequest $accountRequest
     */
    protected function updateItems(AccountRequest $accountRequest)
    {
        try {

            if ($accountRequest->changePermissions) {
                if ($accountRequest->updateUserGroupPermissions) {
                    if (!empty($accountRequest->userGroupsView)) {
                        $this->accountToUserGroupRepository->update($accountRequest);
                    } else {
                        $this->accountToUserGroupRepository->deleteByAccountId($accountRequest->id);
                    }

                    if (!empty($accountRequest->userGroupsEdit)) {
                        $this->accountToUserGroupRepository->updateEdit($accountRequest);
                    } else {
                        $this->accountToUserGroupRepository->deleteEditByAccountId($accountRequest->id);
                    }
                }

                if ($accountRequest->updateUserPermissions) {
                    if (!empty($accountRequest->usersView)) {
                        $this->accountToUserRepository->update($accountRequest);
                    } else {
                        $this->accountToUserRepository->deleteByAccountId($accountRequest->id);
                    }

                    if (!empty($accountRequest->usersEdit)) {
                        $this->accountToUserRepository->updateEdit($accountRequest);
                    } else {
                        $this->accountToUserRepository->deleteEditByAccountId($accountRequest->id);
                    }
                }
            }

            if ($accountRequest->updateTags) {
                if (!empty($accountRequest->tags)) {
                    $this->accountToTagRepository->update($accountRequest);
                } else {
                    $this->accountToTagRepository->deleteByAccountId($accountRequest->id);
                }
            }
        } catch (SPException $e) {
            debugLog($e->getMessage());
        }
    }

    /**
     * @param AccountRequest $accountRequest
     * @param bool           $addHistory
     * @throws SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    public function editPassword(AccountRequest $accountRequest, $addHistory = true)
    {
        if ($addHistory) {
            $this->addHistory($accountRequest->id);
        }

        $pass = $this->getPasswordEncrypted($accountRequest->pass);

        $accountRequest->pass = $pass['pass'];
        $accountRequest->key = $pass['key'];

        $this->accountRepository->editPassword($accountRequest);
    }

    /**
     * @param AccountPasswordRequest $accountRequest
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function updatePasswordMasterPass(AccountPasswordRequest $accountRequest)
    {
        $this->accountRepository->updatePassword($accountRequest);
    }


    /**
     * @param $historyId
     * @param $accountId
     * @throws QueryException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    public function editRestore($historyId, $accountId)
    {
        $this->addHistory($accountId);

        $this->accountRepository->editRestore($historyId, $this->context->getUserData()->getId());
    }

    /**
     * @param $id
     * @return AccountService
     * @throws SPException
     * @throws ServiceException
     */
    public function delete($id)
    {
        if ($this->accountRepository->delete($id) === 0) {
            throw new ServiceException(__u('Cuenta no encontrada'), ServiceException::INFO);
        }

        return $this;
    }

    /**
     * @param array $ids
     * @return AccountService
     * @throws SPException
     * @throws ServiceException
     */
    public function deleteByIdBatch(array $ids)
    {
        if ($this->accountRepository->deleteByIdBatch($ids) === 0) {
            throw new ServiceException(__u('Error al eliminar las cuentas'), ServiceException::WARNING);
        }

        return $this;
    }

    /**
     * @param $accountId
     * @return array
     */
    public function getForUser($accountId = null)
    {
        $queryFilter = AccountUtil::getAccountFilterUser($this->context);

        if (null !== $accountId) {
            $queryFilter->addFilter('Account.id <> ? AND (Account.parentId = 0 OR Account.parentId IS NULL)', [$accountId]);
        }

        return $this->accountRepository->getForUser($queryFilter);
    }

    /**
     * @param $accountId
     * @return array
     */
    public function getLinked($accountId)
    {
        $queryFilter = AccountUtil::getAccountFilterUser($this->context)
            ->addFilter('Account.parentId = ?', [$accountId]);

        return $this->accountRepository->getLinked($queryFilter);
    }

    /**
     * @param $id
     * @return AccountPassData
     */
    public function getPasswordHistoryForId($id)
    {
        $queryFilter = AccountUtil::getAccountHistoryFilterUser($this->context)
            ->addFilter('AH.id = ?', [$id]);

        return $this->accountRepository->getPasswordHistoryForId($queryFilter);
    }

    /**
     * @return AccountData[]
     */
    public function getAllBasic()
    {
        return $this->accountRepository->getAll();
    }

    /**
     * @param ItemSearchData $itemSearchData
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->accountRepository->search($itemSearchData);
    }

    /**
     * Devolver el número total de cuentas
     *
     * @return \stdClass
     */
    public function getTotalNumAccounts()
    {
        return $this->accountRepository->getTotalNumAccounts();
    }

    /**
     * Obtener los datos de una cuenta.
     *
     * @param $id
     *
     * @return \SP\DataModel\AccountExtData
     * @throws QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    public function getDataForLink($id)
    {
        return $this->accountRepository->getDataForLink($id);
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return array Con los datos de la clave
     */
    public function getAccountsPassData()
    {
        return $this->accountRepository->getAccountsPassData();
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @param AccountSearchFilter $accountSearchFilter
     * @return AccountSearchResponse
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter)
    {
        return $this->accountRepository->getByFilter($accountSearchFilter);
    }
}