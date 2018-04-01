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

use SP\Account\AccountAcl;
use SP\Core\Acl\Acl;
use SP\DataModel\Dto\AccountAclDto;
use SP\DataModel\ProfileData;
use SP\Services\Service;
use SP\Services\User\UserLoginResponse;
use SP\Services\UserGroup\UserToUserGroupService;
use SP\Storage\FileCache;
use SP\Storage\FileException;
use SP\Util\FileUtil;

/**
 * Class AccountAclService
 *
 * @package SP\Services\Account
 */
class AccountAclService extends Service
{
    /**
     * ACL's file base path
     */
    const ACL_PATH = CACHE_PATH . DIRECTORY_SEPARATOR . 'accountAcl' . DIRECTORY_SEPARATOR;
    /**
     * @var AccountAclDto
     */
    protected $accountAclDto;
    /**
     * @var AccountAcl
     */
    protected $accountAcl;
    /**
     * @var Acl
     */
    protected $acl;
    /**
     * @var FileCache
     */
    protected $fileCache;
    /**
     * @var UserLoginResponse
     */
    protected $userData;

    /**
     * @param $userId
     */
    public static function clearAcl($userId)
    {
        if (FileUtil::rmdir_recursive(self::ACL_PATH . $userId) === false) {
            debugLog(sprintf('Unable to delete %s directory', self::ACL_PATH . $userId));
        }

        debugLog(__FUNCTION__);
    }

    /**
     * Obtener la ACL de una cuenta
     *
     * @param int           $actionId
     * @param AccountAclDto $accountAclDto
     * @param bool          $isHistory
     * @return AccountAcl
     */
    public function getAcl($actionId, AccountAclDto $accountAclDto = null, $isHistory = false)
    {
        $this->accountAcl = new AccountAcl($actionId, $isHistory);
        $this->accountAcl->showPermission = self::getShowPermission($this->context->getUserData(), $this->context->getUserProfile());

        if ($accountAclDto !== null) {
            $this->accountAclDto = $accountAclDto;

            $accountAcl = $this->getAclFromCache($accountAclDto->getAccountId(), $actionId);

            if (null !== $accountAcl
                && !($this->accountAcl->modified = ($accountAclDto->getDateEdit() > $accountAcl->getTime()
                    || $this->userData->getLastUpdate() > $accountAcl->getTime()))
            ) {
                debugLog('Account ACL HIT');

                return $accountAcl;
            }

            debugLog('Account ACL MISS');

            $this->accountAcl->setAccountId($accountAclDto->getAccountId());

            return $this->updateAcl();
        }

        return $this->accountAcl;
    }

    /**
     * Sets grants which don't need the account's data
     *
     * @param UserLoginResponse $userData
     * @param ProfileData       $profileData
     * @return bool
     */
    public static function getShowPermission(UserLoginResponse $userData, ProfileData $profileData)
    {
        return $userData->getIsAdminApp()
            || $userData->getIsAdminAcc()
            || $profileData->isAccPermission();
    }

    /**
     * Resturns an stored ACL
     *
     * @param int $accountId
     * @param int $actionId
     * @return AccountAcl
     */
    public function getAclFromCache($accountId, $actionId)
    {
        try {
            return $this->fileCache->load($this->getCacheFileForAcl($accountId, $actionId));
        } catch (FileException $e) {
            return null;
        }
    }

    /**
     * @param int $accountId
     * @param int $actionId
     * @return string
     */
    public function getCacheFileForAcl($accountId, $actionId)
    {
        $userId = $this->context->getUserData()->getId();
        return self::ACL_PATH . $userId . DIRECTORY_SEPARATOR . $accountId . DIRECTORY_SEPARATOR . md5($userId . $accountId . $actionId) . '.cache';
    }

    /**
     * Actualizar la ACL
     *
     * @return AccountAcl
     */
    public function updateAcl()
    {
        if ($this->checkComponents()) {
            $this->makeAcl();
        }

        $this->saveAclInCache($this->accountAcl);

        return $this->accountAcl;
    }

    /**
     * @return bool
     */
    protected function checkComponents()
    {
        return null !== $this->accountAclDto;
    }

    /**
     * Crear la ACL de una cuenta
     */
    protected function makeAcl()
    {
        $this->compileAccountAccess();
        $this->accountAcl->setCompiledAccountAccess(true);

        $this->compileShowAccess();
        $this->accountAcl->setCompiledShowAccess(true);

        $this->accountAcl->setTime(time());
    }

    /**
     * compileAccountAccess
     */
    protected function compileAccountAccess()
    {
        $this->accountAcl->resultView = $this->accountAcl->resultEdit = false;

        // Check out if user is admin or owner/maingroup
        if ($this->userData->getIsAdminApp()
            || $this->userData->getIsAdminAcc()
            || $this->userData->getId() === $this->accountAclDto->getUserId()
            || $this->userData->getUserGroupId() === $this->accountAclDto->getUserGroupId()
        ) {
            $this->accountAcl->resultView = true;
            $this->accountAcl->resultEdit = true;

            return;
        }

        // Check out if user is listed in secondary users of the account
        $userInUsers = $this->getUserInSecondaryUsers($this->userData->getId());
        $this->accountAcl->userInUsers = count($userInUsers) > 0;

        if ($this->accountAcl->userInUsers) {
            $this->accountAcl->resultView = true;
            $this->accountAcl->resultEdit = (int)$userInUsers[0]->isEdit === 1;

            return;
        }

        // Analyze user's groups
        $userToUserGroupService = $this->dic->get(UserToUserGroupService::class);

        // Groups in whinch the user is listed in
        $userGroups = array_map(function ($value) {
            return (int)$value->userGroupId;
        }, $userToUserGroupService->getGroupsForUser($this->userData->getId()));

        // Check out if user groups match with account's main group
        if ($this->getUserGroupsInMainGroup($userGroups)) {
            $this->accountAcl->userInGroups = true;
            $this->accountAcl->resultView = true;
            $this->accountAcl->resultEdit = true;

            return;
        }

        // Check out if user groups match with account's secondary groups
        $userGroupsInSecondaryUserGroups = $this->getUserGroupsInSecondaryGroups($userGroups, $this->userData->getUserGroupId());

        $this->accountAcl->userInGroups = count($userGroupsInSecondaryUserGroups) > 0;
        $this->accountAcl->resultView = true;
        $this->accountAcl->resultEdit = $this->accountAcl->userInGroups && (int)$userGroupsInSecondaryUserGroups[0]->isEdit === 1;
    }

    /**
     * Checks if the user is listed in the account users
     *
     * @param $userId
     * @return array
     */
    protected function getUserInSecondaryUsers($userId)
    {
        return array_filter($this->accountAclDto->getUsersId(), function ($value) use ($userId) {
            return (int)$value->id === $userId;
        });
    }

    /**
     * Comprobar si los grupos del usuario está vinculado desde el grupo principal de la cuenta
     *
     * @param array $userGroups
     * @return bool
     */
    protected function getUserGroupsInMainGroup(array $userGroups)
    {
        // Comprobar si el usuario está vinculado desde el grupo principal de la cuenta
        return in_array($this->accountAclDto->getUserGroupId(), $userGroups);
    }

    /**
     * Comprobar si el usuario o el grupo del usuario se encuentran los grupos asociados a la
     * cuenta.
     *
     * @param array $userGroups
     * @param int   $userGroupId
     * @return bool|array
     */
    protected function getUserGroupsInSecondaryGroups(array $userGroups, $userGroupId)
    {
        $isAccountFullGroupAccess = $this->config->getConfigData()->isAccountFullGroupAccess();

        // Comprobar si el grupo del usuario está vinculado desde los grupos secundarios de la cuenta
        return array_filter($this->accountAclDto->getUserGroupsId(),
            function ($value) use ($userGroupId, $isAccountFullGroupAccess, $userGroups) {
                return (int)$value->id === $userGroupId
                    // o... permitir los grupos que no sean el principal del usuario?
                    || ($isAccountFullGroupAccess
                        // Comprobar si el usuario está vinculado desde los grupos secundarios de la cuenta
                        && in_array((int)$value->id, $userGroups));
            });
    }

    /**
     * compileShowAccess
     */
    protected function compileShowAccess()
    {
        // Mostrar historial
        $this->accountAcl->showHistory = $this->acl->checkUserAccess(Acl::ACCOUNT_VIEW_HISTORY);

        // Mostrar lista archivos
        $this->accountAcl->showFiles = $this->acl->checkUserAccess(Acl::ACCOUNT_FILE);

        // Mostrar acción de ver clave
        $this->accountAcl->showViewPass = $this->accountAcl->checkAccountAccess(Acl::ACCOUNT_VIEW_PASS)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_VIEW_PASS);

        // Mostrar acción de editar
        $this->accountAcl->showEdit = $this->accountAcl->checkAccountAccess(Acl::ACCOUNT_EDIT)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_EDIT)
            && !$this->accountAcl->isHistory();

        // Mostrar acción de editar clave
        $this->accountAcl->showEditPass = $this->accountAcl->checkAccountAccess(Acl::ACCOUNT_EDIT_PASS)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_EDIT_PASS)
            && !$this->accountAcl->isHistory();

        // Mostrar acción de eliminar
        $this->accountAcl->showDelete = $this->accountAcl->checkAccountAccess(Acl::ACCOUNT_DELETE)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_DELETE);

        // Mostrar acción de restaurar
        $this->accountAcl->showRestore = $this->accountAcl->checkAccountAccess(Acl::ACCOUNT_EDIT)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_EDIT);

        // Mostrar acción de enlace público
        $this->accountAcl->showLink = $this->acl->checkUserAccess(Acl::PUBLICLINK_CREATE);

        // Mostrar acción de ver cuenta
        $this->accountAcl->showView = $this->accountAcl->checkAccountAccess(Acl::ACCOUNT_VIEW)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_VIEW);

        // Mostrar acción de copiar cuenta
        $this->accountAcl->showCopy = $this->accountAcl->checkAccountAccess(Acl::ACCOUNT_COPY)
            && $this->acl->checkUserAccess(Acl::ACCOUNT_COPY);
    }

    /**
     * Saves the ACL
     *
     * @param AccountAcl $accountAcl
     * @return null|\SP\Storage\FileStorageInterface
     */
    public function saveAclInCache(AccountAcl $accountAcl)
    {
        try {
            return $this->fileCache->save($this->getCacheFileForAcl($accountAcl->getAccountId(), $accountAcl->getActionId()), $accountAcl);
        } catch (FileException $e) {
            return null;
        }
    }

    public function initialize()
    {
        $this->acl = $this->dic->get(Acl::class);
        $this->fileCache = $this->dic->get(FileCache::class);
        $this->userData = $this->context->getUserData();
    }
}