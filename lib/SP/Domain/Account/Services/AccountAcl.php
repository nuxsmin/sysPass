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

namespace SP\Domain\Account\Services;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\ProfileData;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Ports\UserToUserGroupService;
use SP\Infrastructure\File\FileException;

use function SP\processException;

/**
 * Class AccountAcl
 */
final class AccountAcl extends Service implements AccountAclService
{
    /**
     * ACL's file base path
     */
    public const ACL_PATH = CACHE_PATH . DIRECTORY_SEPARATOR . 'accountAcl' . DIRECTORY_SEPARATOR;

    private ?AccountAclDto     $accountAclDto = null;
    private ?AccountPermission $accountAcl    = null;
    private UserDataDto        $userData;

    public function __construct(
        Application                             $application,
        private readonly AclInterface           $acl,
        private readonly UserToUserGroupService $userToUserGroupService,
        private readonly ?FileCacheService      $fileCache = null
    ) {
        parent::__construct($application);

        $this->userData = $this->context->getUserData();
    }

    /**
     * Obtener la ACL de una cuenta
     *
     * @param int $actionId
     * @param AccountAclDto $accountAclDto
     * @param bool $isHistory
     *
     * @return AccountPermission
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAcl(int $actionId, AccountAclDto $accountAclDto, bool $isHistory = false): AccountPermission
    {
        $this->accountAcl = new AccountPermission($actionId, $isHistory);
        $this->accountAcl->setShowPermission(
            self::getShowPermission($this->context->getUserData(), $this->context->getUserProfile())
        );

        $this->accountAclDto = $accountAclDto;

        if (null !== $this->fileCache) {
            $accountAcl = $this->getAclFromCache($accountAclDto->getAccountId(), $actionId);

            if (null !== $accountAcl) {
                $isModified = $accountAclDto->getDateEdit() > $accountAcl->getTime()
                              || $this->userData->getLastUpdate() > $accountAcl->getTime();

                if (!$isModified) {
                    $this->eventDispatcher->notify(
                        'get.acl',
                        new Event($this, EventMessage::factory()->addDescription('Account ACL HIT'))
                    );

                    return $accountAcl;
                }

                $this->accountAcl->setModified(true);
            }
        }

        $this->eventDispatcher->notify(
            'get.acl',
            new Event($this, EventMessage::factory()->addDescription('Account ACL MISS'))
        );

        $this->accountAcl->setAccountId($accountAclDto->getAccountId());

        return $this->buildAcl();
    }

    /**
     * Sets grants which don't need the account's data
     *
     * @param UserDataDto $userData
     * @param ProfileData $profileData
     *
     * @return bool
     */
    public static function getShowPermission(UserDataDto $userData, ProfileData $profileData): bool
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
     * @return AccountPermission|null
     */
    public function getAclFromCache(int $accountId, int $actionId): ?AccountPermission
    {
        try {
            $acl = $this->fileCache->load($this->getCacheFileForAcl($accountId, $actionId));

            if ($acl instanceof AccountPermission) {
                return $acl;
            }
        } catch (FileException $e) {
            processException($e);
        }

        return null;
    }

    /**
     * @param int $accountId
     * @param int $actionId
     *
     * @return string
     */
    private function getCacheFileForAcl(int $accountId, int $actionId): string
    {
        $userId = $this->context->getUserData()->getId();

        return self::ACL_PATH
               . $userId
               . DIRECTORY_SEPARATOR
               . $accountId
               . DIRECTORY_SEPARATOR
               . md5($userId . $accountId . $actionId)
               . '.cache';
    }

    /**
     * Crear la ACL de una cuenta
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function buildAcl(): AccountPermission
    {
        $this->compileAccountAccess();
        $this->accountAcl->setCompiledAccountAccess(true);

        $this->compileShowAccess();
        $this->accountAcl->setCompiledShowAccess(true);

        $this->accountAcl->setTime(time());

        $this->saveAclInCache($this->accountAcl);

        return $this->accountAcl;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    private function compileAccountAccess(): void
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
            $this->accountAcl->setResultEdit((int)$userInUsers[0]['isEdit'] === 1);

            return;
        }

        // Analyze user's groups
        // Groups in which the user is listed in
        $userGroups = array_map(
            static fn($value) => (int)$value->userGroupId,
            $this->userToUserGroupService->getGroupsForUser($this->userData->getId())
        );

        // Check out if user groups match with account's main group
        if ($this->getUserGroupsInMainGroup($userGroups)) {
            $this->accountAcl->setUserInGroups(true);
            $this->accountAcl->setResultView(true);
            $this->accountAcl->setResultEdit(true);

            return;
        }

        // Check out if user groups match with account's secondary groups
        $userGroupsInSecondaryUserGroups =
            $this->getUserGroupsInSecondaryGroups(
                $userGroups,
                $this->userData->getUserGroupId()
            );

        $this->accountAcl->setUserInGroups(count($userGroupsInSecondaryUserGroups) > 0);

        if ($this->accountAcl->isUserInGroups()) {
            $this->accountAcl->setResultView(true);
            $this->accountAcl->setResultEdit((int)$userGroupsInSecondaryUserGroups[0]['isEdit'] === 1);
        }
    }

    /**
     * Checks if the user is listed in the account users
     *
     * @param int $userId
     *
     * @return array
     */
    private function getUserInSecondaryUsers(int $userId): array
    {
        return array_values(
            array_filter(
                $this->accountAclDto->getUsersId(),
                static function ($value) use ($userId) {
                    return (int)$value->getId() === $userId;
                }
            )
        );
    }

    /**
     * Comprobar si los grupos del usuario está vinculado desde el grupo principal de la cuenta
     *
     * @param array $userGroups
     *
     * @return bool
     */
    private function getUserGroupsInMainGroup(array $userGroups): bool
    {
        // Comprobar si el usuario está vinculado desde el grupo principal de la cuenta
        return in_array($this->accountAclDto->getUserGroupId(), $userGroups, true);
    }

    /**
     * Comprobar si el usuario o el grupo del usuario se encuentran los grupos asociados a la
     * cuenta.
     *
     * @param array $userGroups
     * @param int $userGroupId
     *
     * @return array
     */
    private function getUserGroupsInSecondaryGroups(array $userGroups, int $userGroupId): array
    {
        $isAccountFullGroupAccess = $this->config->getConfigData()->isAccountFullGroupAccess();

        // Comprobar si el grupo del usuario está vinculado desde los grupos secundarios de la cuenta
        return array_values(
            array_filter(
                $this->accountAclDto->getUserGroupsId(),
                static function ($value) use ($userGroupId, $isAccountFullGroupAccess, $userGroups) {
                    return (int)$value->getId() === $userGroupId
                           // o... permitir los grupos que no sean el principal del usuario?
                           || ($isAccountFullGroupAccess
                               // Comprobar si el usuario está vinculado desde los grupos secundarios de la cuenta
                               && in_array((int)$value->getId(), $userGroups, true));
                }
            )
        );
    }

    /**
     * compileShowAccess
     */
    private function compileShowAccess(): void
    {
        // Mostrar historial
        $this->accountAcl->setShowHistory($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_HISTORY_VIEW));

        // Mostrar lista archivos
        $this->accountAcl->setShowFiles($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_FILE));

        // Mostrar acción de ver clave
        $this->accountAcl->setShowViewPass($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_VIEW_PASS));

        // Mostrar acción de editar
        $this->accountAcl->setShowEdit($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_EDIT));

        // Mostrar acción de editar clave
        $this->accountAcl->setShowEditPass($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_EDIT_PASS));

        // Mostrar acción de eliminar
        $this->accountAcl->setShowDelete($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_DELETE));

        // Mostrar acción de restaurar
        $this->accountAcl->setShowRestore($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_EDIT));

        // Mostrar acción de enlace público
        $this->accountAcl->setShowLink($this->acl->checkUserAccess(AclActionsInterface::PUBLICLINK_CREATE));

        // Mostrar acción de ver cuenta
        $this->accountAcl->setShowView($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_VIEW));

        // Mostrar acción de copiar cuenta
        $this->accountAcl->setShowCopy($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_COPY));
    }

    /**
     * Saves the ACL
     *
     * @param AccountPermission $accountAcl
     *
     * @return void
     */
    private function saveAclInCache(AccountPermission $accountAcl): void
    {
        if (null === $this->fileCache) {
            return;
        }

        try {
            $this->fileCache->save(
                $accountAcl,
                $this->getCacheFileForAcl($accountAcl->getAccountId(), $accountAcl->getActionId())
            );
        } catch (FileException $e) {
            processException($e);
        }
    }
}
