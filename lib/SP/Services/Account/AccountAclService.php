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

namespace SP\Services\Account;

use SP\Core\Acl\Acl;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\Dto\AccountAclDto;
use SP\DataModel\ProfileData;
use SP\Services\Service;
use SP\Services\User\UserLoginResponse;
use SP\Services\UserGroup\UserToUserGroupService;
use SP\Storage\File\FileCache;
use SP\Storage\File\FileCacheInterface;
use SP\Storage\File\FileException;
use SP\Util\FileUtil;

/**
 * Class AccountAclService
 *
 * @package SP\Services\Account
 */
final class AccountAclService extends Service
{
    /**
     * ACL's file base path
     */
    const ACL_PATH = CACHE_PATH . DIRECTORY_SEPARATOR . 'accountAcl' . DIRECTORY_SEPARATOR;
    /**
     * @var bool
     */
    public static $useCache = true;
    /**
     * @var AccountAclDto
     */
    private $accountAclDto;
    /**
     * @var AccountAcl
     */
    private $accountAcl;
    /**
     * @var Acl
     */
    private $acl;
    /**
     * @var UserLoginResponse
     */
    private $userData;

    /**
     * @param $userId
     *
     * @return bool
     */
    public static function clearAcl($userId)
    {
        logger(sprintf('Clearing ACL for user ID: %d', $userId));

        try {
            if (FileUtil::rmdir_recursive(self::ACL_PATH . $userId) === false) {
                logger(sprintf('Unable to delete %s directory', self::ACL_PATH . $userId));

                return false;
            }

            return true;
        } catch (FileNotFoundException $e) {
            processException($e);
        }

        return false;
    }

    /**
     * Obtener la ACL de una cuenta
     *
     * @param int           $actionId
     * @param AccountAclDto $accountAclDto
     * @param bool          $isHistory
     *
     * @return AccountAcl
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAcl($actionId, AccountAclDto $accountAclDto = null, $isHistory = false)
    {
        $this->accountAcl = new AccountAcl($actionId, $isHistory);
        $this->accountAcl->setShowPermission(self::getShowPermission($this->context->getUserData(), $this->context->getUserProfile()));

        if ($accountAclDto !== null) {
            $this->accountAclDto = $accountAclDto;

            $accountAcl = $this->getAclFromCache($accountAclDto->getAccountId(), $actionId);

            if (self::$useCache && null !== $accountAcl) {
                $this->accountAcl->setModified(($accountAclDto->getDateEdit() > $accountAcl->getTime()
                    || $this->userData->getLastUpdate() > $accountAcl->getTime()));

                if (!$this->accountAcl->isModified()) {
                    logger('Account ACL HIT');

                    return $accountAcl;
                }
            }

            logger('Account ACL MISS');

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
     *
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
     *
     * @return AccountAcl
     */
    public function getAclFromCache($accountId, $actionId)
    {
        try {
            $acl = FileCache::factory($this->getCacheFileForAcl($accountId, $actionId))
                ->load();

            if ($acl instanceof AccountAcl) {
                return $acl;
            }
        } catch (FileException $e) {
            logger($e->getMessage());
        }

        return null;
    }

    /**
     * @param int $accountId
     * @param int $actionId
     *
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
     * @throws ConstraintException
     * @throws QueryException
     */
    private function updateAcl()
    {
        if ($this->checkComponents()) {
            $this->makeAcl();
        }

        if (self::$useCache) {
            $this->saveAclInCache($this->accountAcl);
        }

        return $this->accountAcl;
    }

    /**
     * @return bool
     */
    private function checkComponents()
    {
        return null !== $this->accountAclDto;
    }

    /**
     * Crear la ACL de una cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function makeAcl()
    {
        $this->compileAccountAccess();
        $this->accountAcl->setCompiledAccountAccess(true);

        $this->compileShowAccess();
        $this->accountAcl->setCompiledShowAccess(true);

        $this->accountAcl->setTime(time());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    private function compileAccountAccess()
    {
        $this->accountAcl->setResultView(false);
        $this->accountAcl->setResultEdit(false);

        // Check out if user is admin or owner/maingroup
        if ($this->userData->getIsAdminApp()
            || $this->userData->getIsAdminAcc()
            || $this->userData->getId() === $this->accountAclDto->getUserId()
            || $this->userData->getUserGroupId() === $this->accountAclDto->getUserGroupId()
        ) {
            $this->accountAcl->setResultView(true);
            $this->accountAcl->setResultEdit(true);

            return;
        }

        // Check out if user is listed in secondary users of the account
        $userInUsers = $this->getUserInSecondaryUsers($this->userData->getId());
        $this->accountAcl->setUserInUsers(count($userInUsers) > 0);

        if ($this->accountAcl->isUserInUsers()) {
            $this->accountAcl->setResultView(true);
            $this->accountAcl->setResultEdit((int)$userInUsers[0]->isEdit === 1);

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
            $this->accountAcl->setUserInGroups(true);
            $this->accountAcl->setResultView(true);
            $this->accountAcl->setResultEdit(true);

            return;
        }

        // Check out if user groups match with account's secondary groups
        $userGroupsInSecondaryUserGroups = $this->getUserGroupsInSecondaryGroups($userGroups, $this->userData->getUserGroupId());

        $this->accountAcl->setUserInGroups(count($userGroupsInSecondaryUserGroups) > 0);

        if ($this->accountAcl->isUserInGroups()) {
            $this->accountAcl->setResultView(true);
            $this->accountAcl->setResultEdit((int)$userGroupsInSecondaryUserGroups[0]->isEdit === 1);
        }
    }

    /**
     * Checks if the user is listed in the account users
     *
     * @param $userId
     *
     * @return array
     */
    private function getUserInSecondaryUsers($userId)
    {
        return array_values(array_filter($this->accountAclDto->getUsersId(), function ($value) use ($userId) {
            return (int)$value->id === $userId;
        }));
    }

    /**
     * Comprobar si los grupos del usuario está vinculado desde el grupo principal de la cuenta
     *
     * @param array $userGroups
     *
     * @return bool
     */
    private function getUserGroupsInMainGroup(array $userGroups)
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
     *
     * @return bool|array
     */
    private function getUserGroupsInSecondaryGroups(array $userGroups, $userGroupId)
    {
        $isAccountFullGroupAccess = $this->config->getConfigData()->isAccountFullGroupAccess();

        // Comprobar si el grupo del usuario está vinculado desde los grupos secundarios de la cuenta
        return array_values(array_filter($this->accountAclDto->getUserGroupsId(),
            function ($value) use ($userGroupId, $isAccountFullGroupAccess, $userGroups) {
                return (int)$value->id === $userGroupId
                    // o... permitir los grupos que no sean el principal del usuario?
                    || ($isAccountFullGroupAccess
                        // Comprobar si el usuario está vinculado desde los grupos secundarios de la cuenta
                        && in_array((int)$value->id, $userGroups));
            }));
    }

    /**
     * compileShowAccess
     */
    private function compileShowAccess()
    {
        // Mostrar historial
        $this->accountAcl->setShowHistory($this->acl->checkUserAccess(Acl::ACCOUNT_HISTORY_VIEW));

        // Mostrar lista archivos
        $this->accountAcl->setShowFiles($this->acl->checkUserAccess(Acl::ACCOUNT_FILE));

        // Mostrar acción de ver clave
        $this->accountAcl->setShowViewPass($this->acl->checkUserAccess(Acl::ACCOUNT_VIEW_PASS));

        // Mostrar acción de editar
        $this->accountAcl->setShowEdit($this->acl->checkUserAccess(Acl::ACCOUNT_EDIT));

        // Mostrar acción de editar clave
        $this->accountAcl->setShowEditPass($this->acl->checkUserAccess(Acl::ACCOUNT_EDIT_PASS));

        // Mostrar acción de eliminar
        $this->accountAcl->setShowDelete($this->acl->checkUserAccess(Acl::ACCOUNT_DELETE));

        // Mostrar acción de restaurar
        $this->accountAcl->setShowRestore($this->acl->checkUserAccess(Acl::ACCOUNT_EDIT));

        // Mostrar acción de enlace público
        $this->accountAcl->setShowLink($this->acl->checkUserAccess(Acl::PUBLICLINK_CREATE));

        // Mostrar acción de ver cuenta
        $this->accountAcl->setShowView($this->acl->checkUserAccess(Acl::ACCOUNT_VIEW));

        // Mostrar acción de copiar cuenta
        $this->accountAcl->setShowCopy($this->acl->checkUserAccess(Acl::ACCOUNT_COPY));
    }

    /**
     * Saves the ACL
     *
     * @param AccountAcl $accountAcl
     *
     * @return null|FileCacheInterface
     */
    public function saveAclInCache(AccountAcl $accountAcl)
    {
        try {
            return FileCache::factory($this->getCacheFileForAcl($accountAcl->getAccountId(), $accountAcl->getActionId()))
                ->save($accountAcl);
        } catch (FileException $e) {
            return null;
        }
    }

    protected function initialize()
    {
        $this->acl = $this->dic->get(Acl::class);
        $this->userData = $this->context->getUserData();
    }
}